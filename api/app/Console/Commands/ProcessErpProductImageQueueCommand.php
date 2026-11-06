<?php

namespace App\Console\Commands;

use App\Models\ErpProductImageQueue;
use App\Services\Erp\ErpProductImageQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ProcessErpProductImageQueueCommand extends Command
{
    protected $signature = 'erp:process-image-queue
                            {--company= : Limit to one company_id}
                            {--limit=12 : Max images to download in this run (one by one)}
                            {--chain=0 : Internal background chain depth (do not set manually)}
                            {--no-spawn : Do not spawn a background continue process}';

    protected $description = 'Drain ERP product image download queue one file at a time (auto-continues in background while pending)';

    private const MAX_CHAIN = 40;

    public function handle(ErpProductImageQueueService $queue): int
    {
        $companyId = $this->option('company');
        $companyId = $companyId !== null && $companyId !== '' ? (int) $companyId : null;
        $limit = max(1, min(40, (int) $this->option('limit')));
        $chain = max(0, (int) $this->option('chain'));
        $noSpawn = (bool) $this->option('no-spawn');

        $pendingBeforeQuery = ErpProductImageQueue::query()
            ->where('status', ErpProductImageQueue::STATUS_PENDING);
        if ($companyId !== null && $companyId > 0) {
            $pendingBeforeQuery->where('company_id', $companyId);
        }
        $pendingBefore = (int) $pendingBeforeQuery->count();

        if ($pendingBefore <= 0) {
            $this->info('pending_before=0 processed=0 remaining_pending=0');

            return self::SUCCESS;
        }

        // Evitar doble tick (schedule:run + call explícito) procesando las mismas filas.
        $lock = Cache::lock('erp:process-image-queue:drain', 120);
        if (! $lock->get()) {
            $this->info(sprintf(
                'skipped_lock=1 pending_before=%d processed=0 remaining_pending=%d',
                $pendingBefore,
                $pendingBefore
            ));
            // Si otro proceso tiene el lock pero hay cola, asegurar cadena background.
            if (! $noSpawn && $chain < self::MAX_CHAIN) {
                $this->spawnBackgroundContinue($companyId, $limit, $chain + 1);
            }

            return self::SUCCESS;
        }

        $remaining = $pendingBefore;

        try {
            if ($companyId !== null && $companyId > 0) {
                $pruned = $queue->pruneIrrelevantPending($companyId);
            } else {
                $companyIds = ErpProductImageQueue::query()
                    ->where('status', ErpProductImageQueue::STATUS_PENDING)
                    ->distinct()
                    ->pluck('company_id');
                $pruned = ['skipped_inactive' => 0, 'skipped_has_photo' => 0, 'skipped_extra' => 0];
                foreach ($companyIds as $cid) {
                    $p = $queue->pruneIrrelevantPending((int) $cid);
                    foreach ($pruned as $k => $_) {
                        $pruned[$k] += (int) ($p[$k] ?? 0);
                    }
                }
            }

            $stats = $queue->processPending($limit, $companyId);
            $remaining = (int) ($stats['remaining_pending'] ?? 0);

            $this->info(sprintf(
                'pending_before=%d pruned_inactive=%d pruned_has_photo=%d pruned_extra=%d processed=%d downloaded=%d failed=%d skipped=%d remaining_pending=%d chain=%d',
                $pendingBefore,
                $pruned['skipped_inactive'],
                $pruned['skipped_has_photo'],
                $pruned['skipped_extra'],
                $stats['processed'],
                $stats['downloaded'],
                $stats['failed'],
                $stats['skipped'],
                $remaining,
                $chain
            ));
        } finally {
            try {
                $lock->release();
            } catch (Throwable) {
            }
        }

        // Sin UI abierta: si queda cola, lanzar otro proceso en background (Linux/cron).
        if ($remaining > 0 && ! $noSpawn && $chain < self::MAX_CHAIN) {
            $this->spawnBackgroundContinue($companyId, $limit, $chain + 1);
        }

        return self::SUCCESS;
    }

    private function spawnBackgroundContinue(?int $companyId, int $limit, int $nextChain): void
    {
        // Una sola cadena activa a la vez (evita fork bomb si cron + HTTP coinciden).
        if (! Cache::add('erp:image-queue:bg-spawn-gate', 1, 20)) {
            return;
        }

        $php = (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '')
            ? PHP_BINARY
            : 'php';
        $artisan = base_path('artisan');
        $companyOpt = ($companyId !== null && $companyId > 0) ? ' --company='.(int) $companyId : '';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = 'start /B "" '.escapeshellarg($php).' '.escapeshellarg($artisan)
                .' erp:process-image-queue --limit='.(int) $limit
                .' --chain='.(int) $nextChain
                .$companyOpt;
            @pclose(@popen($cmd, 'r'));

            return;
        }

        if (! function_exists('exec')) {
            return;
        }

        $cmd = sprintf(
            'nohup %s %s erp:process-image-queue --limit=%d --chain=%d%s > /dev/null 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($artisan),
            (int) $limit,
            (int) $nextChain,
            $companyOpt
        );
        @exec($cmd);
    }
}
