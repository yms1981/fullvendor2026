<?php
/**
 * @var array $planLimitBlock ['can_create' => bool, 'message' => string]
 */
$planLimitBlock = isset($planLimitBlock) && is_array($planLimitBlock) ? $planLimitBlock : ['can_create' => true, 'message' => ''];
if (! empty($planLimitBlock['can_create'])) {
    return;
}
$msg = trim((string) ($planLimitBlock['message'] ?? ''));
if ($msg === '') {
    return;
}
?>
<div class="alert alert-warning mb-3" role="alert">
    <i class="mdi mdi-alert-outline mr-1" aria-hidden="true"></i>
    <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
</div>
