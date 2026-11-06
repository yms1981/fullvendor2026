<?php
/** @var array $pageTexts */
/** @var array $tasks */
/** @var bool $erpEnabled */
/** @var string $erpUrl */
/** @var array $frequencies */
/** @var array $logs */
/** @var array $imageQueue */
/** @var string $updateUrlBase */
/** @var string $runUrlBase */
/** @var string $logsUrl */
/** @var string $imageQueueUrl */
/** @var string $imageQueueProcessUrl */
$logs = is_array($logs ?? null) ? $logs : [];
$logsUrl = (string) ($logsUrl ?? '');
$imageQueueUrl = (string) ($imageQueueUrl ?? '');
$imageQueueProcessUrl = (string) ($imageQueueProcessUrl ?? '');
$imageQueue = is_array($imageQueue ?? null) ? $imageQueue : [];
$iqTotal = (int) ($imageQueue['total'] ?? 0);
$iqPending = (int) ($imageQueue['pending'] ?? 0);
$iqProcessing = (int) ($imageQueue['processing'] ?? 0);
$iqDownloaded = (int) ($imageQueue['downloaded'] ?? 0);
$iqFailed = (int) ($imageQueue['failed'] ?? 0);
$iqSkipped = (int) ($imageQueue['skipped'] ?? 0);
$iqPercent = (float) ($imageQueue['percent'] ?? 0);
$iqActive = !empty($imageQueue['active']);
$iqCronOk = !empty($imageQueue['cron_ok']);
$iqCronLast = (string) ($imageQueue['cron_last_tick_at'] ?? '');
$iqRecent = is_array($imageQueue['recent'] ?? null) ? $imageQueue['recent'] : [];
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    return htmlspecialchars((string) ($pageTexts[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
};
$queueStatusLabel = static function (string $status) use ($pageTexts): string {
    return match ($status) {
        'pending' => (string) ($pageTexts['queue_status_pending'] ?? $status),
        'processing' => (string) ($pageTexts['queue_status_processing'] ?? $status),
        'downloaded' => (string) ($pageTexts['queue_status_downloaded'] ?? $status),
        'failed' => (string) ($pageTexts['queue_status_failed'] ?? $status),
        'skipped' => (string) ($pageTexts['queue_status_skipped'] ?? $status),
        default => $status,
    };
};
$freqLabels = [
    'manual' => $pageTexts['frequency_manual'] ?? 'Manual',
    'every_15_min' => $pageTexts['frequency_every_15_min'] ?? 'Every 15 min',
    'hourly' => $pageTexts['frequency_every_1_hour'] ?? ($pageTexts['frequency_hourly'] ?? 'Every 1 hour'),
    'every_1_hour' => $pageTexts['frequency_every_1_hour'] ?? 'Every 1 hour',
    'every_2_hours' => $pageTexts['frequency_every_2_hours'] ?? 'Every 2 hours',
    'every_3_hours' => $pageTexts['frequency_every_3_hours'] ?? 'Every 3 hours',
    'every_4_hours' => $pageTexts['frequency_every_4_hours'] ?? 'Every 4 hours',
    'every_5_hours' => $pageTexts['frequency_every_5_hours'] ?? 'Every 5 hours',
    'every_6_hours' => $pageTexts['frequency_every_6_hours'] ?? 'Every 6 hours',
    'daily' => $pageTexts['frequency_daily'] ?? 'Daily',
];
$typeLabels = [
    'sync_customers' => $pageTexts['task_sync_customers'] ?? 'sync_customers',
    'sync_products' => $pageTexts['task_sync_products'] ?? 'sync_products',
    'sync_categories' => $pageTexts['task_sync_categories'] ?? 'sync_categories',
    'sync_customer_balances' => $pageTexts['task_sync_customer_balances'] ?? 'sync_customer_balances',
    'sync_product_inventories' => $pageTexts['task_sync_product_inventories'] ?? 'sync_product_inventories',
    'sync_product_images' => $pageTexts['task_sync_product_images'] ?? 'sync_product_images',
];

$formatWhen = static function (string $raw): string {
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    try {
        $dt = new DateTimeImmutable($raw);
        return $dt->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        return $raw;
    }
};

$shortMessage = static function (string $message, int $max = 140): array {
    $message = trim($message);
    if ($message === '') {
        return ['short' => '', 'full' => ''];
    }
    // Prefer inner ERP JSON message when present
    if (preg_match('/ERP HTTP\s+\d+:\s*(\{.*\})\s*$/s', $message, $m)) {
        $decoded = json_decode($m[1], true);
        if (is_array($decoded) && is_string($decoded['message'] ?? null) && trim($decoded['message']) !== '') {
            $message = trim((string) $decoded['message']);
        }
    }
    if (mb_strlen($message) <= $max) {
        return ['short' => $message, 'full' => $message];
    }
    return ['short' => rtrim(mb_substr($message, 0, $max)) . '…', 'full' => $message];
};
?>
<div class="container-fluid page-body-wrapper pt-3 fv-scheduled-tasks-page">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="card fv-scheduled-card">
                <div class="card-header fv-scheduled-card__header">
                    <div class="fv-scheduled-card__title-wrap">
                        <h3 class="page-title mb-0"><?= $t('page_title') ?></h3>
                    </div>
                    <a href="#fv-scheduled-task-logs" class="fv-scheduled-action-btn fv-scheduled-action-btn--ghost">
                        <svg class="fv-scheduled-action-btn__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6a7 7 0 0 1 7-7 7 7 0 0 1 7 7 7 7 0 0 1-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.95 8.95 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"></path>
                        </svg>
                        <span class="fv-scheduled-action-btn__label"><?= $t('btn_view_logs') ?></span>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!$erpEnabled): ?>
                        <div class="alert alert-warning">
                            <?= $t('erp_disabled_notice') ?>
                            <a href="<?= company_url('profile') ?>#erp"><?= $t('go_to_profile_erp') ?></a>
                        </div>
                    <?php else: ?>
                        <p class="fv-scheduled-erp-banner mb-3">
                            <i class="mdi mdi-lan-connect" aria-hidden="true"></i>
                            <span><?= $t('erp_connected_as') ?>:</span>
                            <strong><?= htmlspecialchars((string) $erpUrl, ENT_QUOTES, 'UTF-8') ?></strong>
                        </p>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped fv-scheduled-table" id="fv-scheduled-tasks-table"
                               data-erp-enabled="<?= $erpEnabled ? '1' : '0' ?>"
                               data-update-base="<?= htmlspecialchars($updateUrlBase, ENT_QUOTES, 'UTF-8') ?>"
                               data-run-base="<?= htmlspecialchars($runUrlBase, ENT_QUOTES, 'UTF-8') ?>"
                               data-logs-url="<?= htmlspecialchars($logsUrl, ENT_QUOTES, 'UTF-8') ?>"
                               data-msg-saved="<?= $t('saved') ?>"
                               data-msg-error="<?= $t('error') ?>"
                               data-msg-running="<?= $t('running') ?>"
                               data-msg-erp-disabled="<?= $t('erp_disabled_notice') ?>">
                            <thead>
                            <tr>
                                <th><?= $t('col_task') ?></th>
                                <th><?= $t('col_enabled') ?></th>
                                <th><?= $t('col_frequency') ?></th>
                                <th><?= $t('col_last_run') ?></th>
                                <th><?= $t('col_last_status') ?></th>
                                <th><?= $t('col_actions') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tasks as $task):
                                $task = is_array($task) ? (object) $task : $task;
                                $type = (string) ($task->task_type ?? '');
                                $enabled = !empty($task->enabled);
                                $frequency = (string) ($task->frequency ?? 'manual');
                                if ($frequency === 'hourly') {
                                    $frequency = 'every_1_hour';
                                }
                                $lastRun = $formatWhen((string) ($task->last_run_at ?? ''));
                                $lastStatus = (string) ($task->last_status ?? '');
                                $lastMessage = (string) ($task->last_message ?? '');
                                $statusClass = $lastStatus === 'success' ? 'success' : ($lastStatus === 'error' ? 'danger' : 'secondary');
                                $statusLabel = $lastStatus === 'success'
                                    ? ($pageTexts['status_success'] ?? $lastStatus)
                                    : ($lastStatus === 'error'
                                        ? ($pageTexts['status_error'] ?? $lastStatus)
                                        : $lastStatus);
                                ?>
                                <tr data-task-type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                                    <td>
                                        <strong class="fv-scheduled-task-name"><?= htmlspecialchars((string) ($typeLabels[$type] ?? $type), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if ($lastMessage !== ''):
                                            $msgParts = $shortMessage($lastMessage, 100);
                                            ?>
                                            <div class="small text-muted fv-scheduled-task-msg" title="<?= htmlspecialchars($msgParts['full'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($msgParts['short'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox"
                                                   class="custom-control-input fv-task-enabled"
                                                   id="task-enabled-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
                                                   <?= $enabled ? 'checked' : '' ?>
                                                   <?= $erpEnabled ? '' : 'disabled' ?>>
                                            <label class="custom-control-label"
                                                   for="task-enabled-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm fv-task-frequency" <?= $erpEnabled ? '' : 'disabled' ?>>
                                            <?php foreach ($frequencies as $freq):
                                                $freq = (string) $freq;
                                                ?>
                                                <option value="<?= htmlspecialchars($freq, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= $frequency === $freq ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) ($freqLabels[$freq] ?? $freq), ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="fv-task-last-run text-nowrap"><?= htmlspecialchars($lastRun, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="fv-task-last-status">
                                        <?php if ($lastStatus !== ''): ?>
                                            <span class="badge badge-<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?> fv-scheduled-status">
                                                <?= htmlspecialchars((string) $statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fv-task-actions text-nowrap">
                                        <button type="button"
                                                class="fv-task-run-btn fv-task-run"
                                                title="<?= $t('btn_run_now') ?>"
                                                aria-label="<?= $t('btn_run_now') ?>"
                                                <?= $erpEnabled ? '' : 'disabled' ?>>
                                            <svg class="fv-task-run-btn__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.12"></circle>
                                                <path fill="currentColor" d="M10 8.2v7.6c0 .5.55.8.97.55l6.1-3.8a.65.65 0 0 0 0-1.1l-6.1-3.8A.65.65 0 0 0 10 8.2z"></path>
                                            </svg>
                                            <span class="fv-task-run-btn__label"><?= $t('btn_run_now') ?></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-3 fv-scheduled-card" id="fv-image-queue-progress"
                 data-queue-url="<?= htmlspecialchars($imageQueueUrl, ENT_QUOTES, 'UTF-8') ?>"
                 data-process-url="<?= htmlspecialchars($imageQueueProcessUrl, ENT_QUOTES, 'UTF-8') ?>"
                 data-active="<?= $iqActive ? '1' : '0' ?>"
                 data-cron-ok="<?= $iqCronOk ? '1' : '0' ?>">
                <div class="card-header fv-scheduled-card__header">
                    <div class="fv-scheduled-card__title-wrap">
                        <h4 class="fv-scheduled-logs-title mb-0"><?= $t('queue_title') ?></h4>
                        <p class="text-muted small mb-0 mt-1"><?= $t('queue_hint') ?></p>
                    </div>
                    <div class="fv-scheduled-card__actions d-flex flex-wrap gap-2">
                        <button type="button"
                                class="fv-scheduled-action-btn fv-scheduled-action-btn--primary"
                                id="fv-image-queue-process"
                                title="<?= $t('btn_process_queue') ?>"
                                aria-label="<?= $t('btn_process_queue') ?>">
                            <svg class="fv-scheduled-action-btn__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M8 5v14l11-7L8 5z"></path>
                            </svg>
                            <span class="fv-scheduled-action-btn__label"><?= $t('btn_process_queue') ?></span>
                        </button>
                        <button type="button"
                                class="fv-scheduled-action-btn"
                                id="fv-image-queue-refresh"
                                title="<?= $t('btn_refresh_queue') ?>"
                                aria-label="<?= $t('btn_refresh_queue') ?>">
                            <svg class="fv-scheduled-action-btn__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M17.65 6.35A7.95 7.95 0 0 0 12 4a8 8 0 1 0 7.75 10h-2.1A6 6 0 1 1 12 6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"></path>
                            </svg>
                            <span class="fv-scheduled-action-btn__label"><?= $t('btn_refresh_queue') ?></span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3 <?= $iqCronOk ? 'd-none' : '' ?>" id="fv-image-queue-cron-warn" role="status">
                        <?= $t('queue_cron_missing') ?>
                        <?php if ($iqCronLast !== ''): ?>
                            <span class="d-block small mt-1" id="fv-image-queue-cron-last">
                                <?= $t('queue_cron_last_tick') ?>:
                                <strong><?= htmlspecialchars($formatWhen($iqCronLast), ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                        <?php else: ?>
                            <span class="d-block small mt-1" id="fv-image-queue-cron-last"></span>
                        <?php endif; ?>
                    </div>
                    <div class="fv-image-queue-stats" id="fv-image-queue-stats">
                        <div class="fv-image-queue-stat">
                            <span class="fv-image-queue-stat__label"><?= $t('queue_total') ?></span>
                            <strong class="fv-iq-total"><?= (int) $iqTotal ?></strong>
                        </div>
                        <div class="fv-image-queue-stat">
                            <span class="fv-image-queue-stat__label"><?= $t('queue_pending') ?></span>
                            <strong class="fv-iq-pending"><?= (int) $iqPending ?></strong>
                        </div>
                        <div class="fv-image-queue-stat">
                            <span class="fv-image-queue-stat__label"><?= $t('queue_downloaded') ?></span>
                            <strong class="fv-iq-downloaded text-success"><?= (int) $iqDownloaded ?></strong>
                        </div>
                        <div class="fv-image-queue-stat">
                            <span class="fv-image-queue-stat__label"><?= $t('queue_failed') ?></span>
                            <strong class="fv-iq-failed text-danger"><?= (int) $iqFailed ?></strong>
                        </div>
                        <div class="fv-image-queue-stat">
                            <span class="fv-image-queue-stat__label"><?= $t('queue_skipped') ?></span>
                            <strong class="fv-iq-skipped"><?= (int) $iqSkipped ?></strong>
                        </div>
                    </div>
                    <div class="fv-image-queue-bar-wrap mt-3" aria-hidden="true">
                        <div class="fv-image-queue-bar">
                            <div class="fv-image-queue-bar__fill" id="fv-image-queue-bar-fill" style="width: <?= htmlspecialchars((string) $iqPercent, ENT_QUOTES, 'UTF-8') ?>%;"></div>
                        </div>
                        <div class="fv-image-queue-bar__meta">
                            <span id="fv-image-queue-percent"><?= htmlspecialchars(rtrim(rtrim(number_format($iqPercent, 1, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?>%</span>
                            <span class="fv-iq-active-label <?= $iqActive ? '' : 'd-none' ?>" id="fv-image-queue-active"><?= $t('queue_active') ?></span>
                        </div>
                    </div>
                    <div class="table-responsive fv-scheduled-logs-scroll mt-3">
                        <table class="table table-sm table-hover fv-scheduled-logs-table" id="fv-image-queue-table">
                            <thead>
                            <tr>
                                <th><?= $t('queue_col_sku') ?></th>
                                <th><?= $t('queue_col_status') ?></th>
                                <th><?= $t('queue_col_attempts') ?></th>
                                <th><?= $t('queue_col_when') ?></th>
                                <th><?= $t('queue_col_detail') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($iqRecent === []): ?>
                                <tr class="fv-iq-empty">
                                    <td colspan="5" class="text-muted"><?= $t('queue_empty') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($iqRecent as $row):
                                    $row = is_array($row) ? (object) $row : $row;
                                    $st = (string) ($row->status ?? '');
                                    $when = (string) ($row->finished_at ?? $row->updated_at ?? $row->queued_at ?? '');
                                    $detail = (string) ($row->last_error ?? '');
                                    if ($detail === '') {
                                        $detail = (string) ($row->image_url ?? '');
                                    }
                                    $badge = match ($st) {
                                        'downloaded' => 'success',
                                        'failed' => 'danger',
                                        'processing' => 'info',
                                        'skipped' => 'secondary',
                                        default => 'warning',
                                    };
                                    ?>
                                    <tr>
                                        <td class="text-nowrap"><?= htmlspecialchars((string) ($row->sku ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $badge ?> fv-scheduled-status">
                                                <?= htmlspecialchars($queueStatusLabel($st), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td><?= (int) ($row->attempts ?? 0) ?></td>
                                        <td class="text-nowrap"><?= htmlspecialchars($formatWhen($when), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="fv-scheduled-log-msg" title="<?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(mb_strlen($detail) > 120 ? rtrim(mb_substr($detail, 0, 120)).'…' : $detail, ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-3 fv-scheduled-card" id="fv-scheduled-task-logs">
                <div class="card-header fv-scheduled-card__header fv-scheduled-logs-header">
                    <div class="fv-scheduled-card__title-wrap">
                        <h4 class="fv-scheduled-logs-title mb-0"><?= $t('logs_title') ?></h4>
                    </div>
                    <div class="fv-scheduled-logs-toolbar">
                        <div class="fv-scheduled-logs-filters">
                            <label class="sr-only" for="fv-logs-filter-type"><?= $t('logs_filter_all_tasks') ?></label>
                            <select id="fv-logs-filter-type" class="form-control form-control-sm fv-scheduled-filter">
                                <option value=""><?= $t('logs_filter_all_tasks') ?></option>
                                <?php foreach ($typeLabels as $typeKey => $typeLabel): ?>
                                    <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label class="sr-only" for="fv-logs-filter-status"><?= $t('logs_filter_all_status') ?></label>
                            <select id="fv-logs-filter-status" class="form-control form-control-sm fv-scheduled-filter">
                                <option value=""><?= $t('logs_filter_all_status') ?></option>
                                <option value="success"><?= $t('status_success') ?></option>
                                <option value="error"><?= $t('status_error') ?></option>
                            </select>
                        </div>
                        <button type="button"
                                class="fv-scheduled-action-btn"
                                id="fv-logs-refresh"
                                title="<?= $t('btn_refresh_logs') ?>"
                                aria-label="<?= $t('btn_refresh_logs') ?>">
                            <svg class="fv-scheduled-action-btn__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M17.65 6.35A7.95 7.95 0 0 0 12 4a8 8 0 1 0 7.75 10h-2.1A6 6 0 1 1 12 6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"></path>
                            </svg>
                            <span class="fv-scheduled-action-btn__label"><?= $t('btn_refresh_logs') ?></span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p class="fv-scheduled-logs-hint text-muted small mb-3"><?= $t('logs_cron_hint') ?></p>
                    <div class="table-responsive fv-scheduled-logs-scroll">
                        <table class="table table-sm table-hover fv-scheduled-logs-table" id="fv-scheduled-logs-table">
                            <thead>
                            <tr>
                                <th><?= $t('logs_col_when') ?></th>
                                <th><?= $t('logs_col_task') ?></th>
                                <th><?= $t('logs_col_trigger') ?></th>
                                <th><?= $t('logs_col_status') ?></th>
                                <th><?= $t('logs_col_duration') ?></th>
                                <th><?= $t('logs_col_message') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($logs === []): ?>
                                <tr class="fv-logs-empty">
                                    <td colspan="6" class="text-muted"><?= $t('logs_empty') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log):
                                    $log = is_array($log) ? (object) $log : $log;
                                    $logType = (string) ($log->task_type ?? '');
                                    $trigger = (string) ($log->trigger ?? '');
                                    $status = (string) ($log->status ?? '');
                                    $when = $formatWhen((string) ($log->finished_at ?? $log->created_at ?? ''));
                                    $duration = isset($log->duration_ms) ? ((int) $log->duration_ms) . ' ms' : '';
                                    $triggerLabel = $trigger === 'cron'
                                        ? ($pageTexts['trigger_cron'] ?? 'cron')
                                        : ($pageTexts['trigger_manual'] ?? 'manual');
                                    $statusLabel = $status === 'success'
                                        ? ($pageTexts['status_success'] ?? $status)
                                        : ($pageTexts['status_error'] ?? $status);
                                    $msgParts = $shortMessage((string) ($log->message ?? ''), 160);
                                    ?>
                                    <tr class="<?= $status === 'error' ? 'fv-log-row-error' : 'fv-log-row-success' ?>">
                                        <td class="text-nowrap"><?= htmlspecialchars($when, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($typeLabels[$logType] ?? $logType), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="fv-scheduled-trigger fv-scheduled-trigger--<?= htmlspecialchars($trigger === 'cron' ? 'cron' : 'manual', ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars((string) $triggerLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $status === 'success' ? 'success' : 'danger' ?> fv-scheduled-status">
                                                <?= htmlspecialchars((string) $statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap"><?= htmlspecialchars($duration, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="fv-scheduled-log-msg" title="<?= htmlspecialchars($msgParts['full'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($msgParts['short'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
window.fvScheduledTaskTypeLabels = <?= json_encode($typeLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.fvScheduledTaskUiTexts = <?= json_encode([
    'trigger_manual' => (string) ($pageTexts['trigger_manual'] ?? 'manual'),
    'trigger_cron' => (string) ($pageTexts['trigger_cron'] ?? 'cron'),
    'status_success' => (string) ($pageTexts['status_success'] ?? 'success'),
    'status_error' => (string) ($pageTexts['status_error'] ?? 'error'),
    'logs_empty' => (string) ($pageTexts['logs_empty'] ?? ''),
    'queue_empty' => (string) ($pageTexts['queue_empty'] ?? ''),
    'queue_status_pending' => (string) ($pageTexts['queue_status_pending'] ?? 'pending'),
    'queue_status_processing' => (string) ($pageTexts['queue_status_processing'] ?? 'processing'),
    'queue_status_downloaded' => (string) ($pageTexts['queue_status_downloaded'] ?? 'downloaded'),
    'queue_status_failed' => (string) ($pageTexts['queue_status_failed'] ?? 'failed'),
    'queue_status_skipped' => (string) ($pageTexts['queue_status_skipped'] ?? 'skipped'),
    'queue_active' => (string) ($pageTexts['queue_active'] ?? ''),
    'queue_cron_last_tick' => (string) ($pageTexts['queue_cron_last_tick'] ?? ''),
    'queue_batch_done' => (string) ($pageTexts['queue_batch_done'] ?? ''),
    'queue_processing' => (string) ($pageTexts['queue_processing'] ?? ''),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
