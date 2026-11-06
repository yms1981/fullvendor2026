<?php

namespace App\Console\Commands;

use App\Services\Erp\ErpProductImageQueueService;
use Illuminate\Console\Command;

class ProcessErpProductImageQueueCommand extends Command
{
    protected $signature = 'erp:process-image-queue
                            {--company= : Limit to one company_id}
                            {--limit=40 : Max images to download in this run (one by one)}';

    protected $description = 'Drain ERP product image download queue one file at a time';

    public function handle(ErpProductImageQueueService $queue): int
    {
        $companyId = $this->option('company');
        $companyId = $companyId !== null && $companyId !== '' ? (int) $companyId : null;
        $limit = max(1, (int) $this->option('limit'));

        $stats = $queue->processPending($limit, $companyId);

        $this->info(sprintf(
            'processed=%d downloaded=%d failed=%d skipped=%d remaining_pending=%d',
            $stats['processed'],
            $stats['downloaded'],
            $stats['failed'],
            $stats['skipped'],
            $stats['remaining_pending']
        ));

        return self::SUCCESS;
    }
}
