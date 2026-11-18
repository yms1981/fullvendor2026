<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyScheduledTask;
use App\Models\CompanyScheduledTaskLog;
use App\Services\Erp\ErpScheduledTaskService;
use Illuminate\Console\Command;

class RunErpScheduledTasksCommand extends Command
{
    protected $signature = 'erp:run-scheduled-tasks
        {--company= : Limit to one company_id}
        {--limit=1 : Max due tasks to run in this tick (default 1 to avoid HTTP cron timeouts starving others)}';

    protected $description = 'Run due ERP pull scheduled tasks for companies with ERP enabled';

    public function handle(ErpScheduledTaskService $service): int
    {
        $companyFilter = (int) $this->option('company');
        $limit = max(1, min(20, (int) $this->option('limit')));

        // Reparar: activas sin next_run_at, o nunca ejecutadas aún con next_run_at futuro
        // (bug antiguo: al activar se programaba now()+frecuencia y la 1ª corrida no arrancaba).
        $repair = CompanyScheduledTask::query()
            ->where('enabled', true)
            ->where('frequency', '!=', CompanyScheduledTask::FREQUENCY_MANUAL)
            ->when($companyFilter > 0, fn ($q) => $q->where('company_id', $companyFilter))
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('last_run_at')
                            ->where('next_run_at', '>', now());
                    });
            });
        $repaired = (clone $repair)->update(['next_run_at' => now()]);
        if ($repaired > 0) {
            $this->info("Repaired next_run_at for {$repaired} task(s).");
        }

        $query = CompanyScheduledTask::query()
            ->where('enabled', true)
            ->where('frequency', '!=', CompanyScheduledTask::FREQUENCY_MANUAL)
            ->where(function ($q) {
                $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
            })
            ->whereHas('company', function ($q) use ($companyFilter) {
                $q->where('erp_enabled', 1);
                if ($companyFilter > 0) {
                    $q->where('company_id', $companyFilter);
                }
            })
            // Fairness: nunca corridas primero, luego las más atrasadas.
            ->orderByRaw('CASE WHEN last_run_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('next_run_at')
            ->orderBy('id')
            ->limit($limit);

        $tasks = $query->get();
        $this->info('Due tasks this tick: '.$tasks->count().' (limit='.$limit.')');

        foreach ($tasks as $task) {
            $result = $service->runTask($task, CompanyScheduledTaskLog::TRIGGER_CRON);
            $this->line(sprintf(
                '[%s] company=%s type=%s status=%s %s',
                now()->toDateTimeString(),
                $task->company_id,
                $task->task_type,
                $result['status'],
                $result['message']
            ));
        }

        // Seed tasks for ERP-enabled companies that have none yet.
        $companies = Company::query()
            ->where('erp_enabled', 1)
            ->when($companyFilter > 0, fn ($q) => $q->where('company_id', $companyFilter))
            ->get();
        foreach ($companies as $company) {
            $service->ensureTasksForCompany($company);
        }

        return self::SUCCESS;
    }
}
