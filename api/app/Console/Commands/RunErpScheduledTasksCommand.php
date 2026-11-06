<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyScheduledTask;
use App\Models\CompanyScheduledTaskLog;
use App\Services\Erp\ErpScheduledTaskService;
use Illuminate\Console\Command;

class RunErpScheduledTasksCommand extends Command
{
    protected $signature = 'erp:run-scheduled-tasks {--company= : Limit to one company_id}';

    protected $description = 'Run due ERP pull scheduled tasks for companies with ERP enabled';

    public function handle(ErpScheduledTaskService $service): int
    {
        $companyFilter = (int) $this->option('company');

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
            ->orderBy('id')
            ->limit(50);

        $tasks = $query->get();
        $this->info('Due tasks: '.$tasks->count());

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
