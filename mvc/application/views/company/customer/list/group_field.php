<?php
/**
 * @var object $listItem item from the list for a given record
 * @var array $groupList The list of available customer groups
 * @var array $pageTexts
 * @var string $apiToken The API token for authentication
 * @var string $apiBaseUrl The API base URL
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

$upper = static function ($value): string {
    $s = trim((string) $value);
    if ($s === '') {
        return '';
    }

    return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
};

$customerId = (int) ($listItem->customer_id ?? 0);
$currentGroupId = $listItem->group_id ?? null;
if ($currentGroupId === null || $currentGroupId === '' || (int) $currentGroupId === 0) {
    $relatedGroupId = null;
    if (is_object($listItem->customer_group ?? null)) {
        $relatedGroupId = $listItem->customer_group->group_id ?? null;
    } elseif (is_array($listItem->customer_group ?? null)) {
        $relatedGroupId = $listItem->customer_group['group_id'] ?? null;
    }
    if ($relatedGroupId !== null && (int) $relatedGroupId > 0) {
        $currentGroupId = (int) $relatedGroupId;
    } else {
        $currentGroupId = null;
    }
} else {
    $currentGroupId = (int) $currentGroupId;
}

$emptyLabel = $upper($pageTexts['option_vacio'] ?? '');
$groups = [];
if (isset($groupList) && (is_array($groupList) || $groupList instanceof Traversable)) {
    foreach ($groupList as $group) {
        $groups[] = is_object($group) ? $group : (object) (array) $group;
    }
}

static $fvCustomerGroupSelectBooted = false;
if (! $fvCustomerGroupSelectBooted) {
    $fvCustomerGroupSelectBooted = true;
    $bootConfig = [
        'apiToken' => (string) ($apiToken ?? ''),
        'apiBaseUrl' => (string) ($apiBaseUrl ?? '/api'),
        'updating' => (string) ($pageTexts['group_updating'] ?? $pageTexts['spinner_actualizar'] ?? ''),
        'updated' => (string) ($pageTexts['group_updated'] ?? ''),
        'error' => (string) ($pageTexts['group_update_error'] ?? ''),
    ];
    ?>
<script>
window.fvCustomerGroupSelect = <?= json_encode($bootConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<style>
.customer-group-select-container { position: relative; }
.customer-group-select-container .select-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
}
.customer-group-select-container .customer-group-select {
    border: 1px solid #ced4da;
    border-radius: 4px;
    transition: all 0.2s ease;
    padding-right: 30px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    text-transform: uppercase;
}
.customer-group-select-container .customer-group-select:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.customer-group-select-container .customer-group-select:disabled {
    background-color: #e9ecef;
    opacity: 0.6;
    cursor: not-allowed;
}
.customer-group-select-container .select-arrow {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #6c757d;
    font-size: 1rem;
    z-index: 1;
}
</style>
    <?php
}
?>
<div class="customer-group-select-container">
    <div class="select-wrapper">
        <select class="form-control form-control-sm customer-group-select"
                data-customer-id="<?= $customerId ?>"
                data-original-group-id="<?= htmlspecialchars((string) ($currentGroupId ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                style="min-width: 150px; font-size: 0.875rem;">
            <option value="" <?= $currentGroupId === null ? 'selected' : '' ?>><?= htmlspecialchars($emptyLabel, ENT_QUOTES, 'UTF-8') ?></option>
            <?php foreach ($groups as $group): ?>
                <?php
                $gid = isset($group->group_id) ? (int) $group->group_id : 0;
                if ($gid < 1) {
                    continue;
                }
                $gname = $upper($group->name ?? '');
                ?>
                <option value="<?= $gid ?>" <?= $currentGroupId === $gid ? 'selected' : '' ?>>
                    <?= htmlspecialchars($gname, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <i class="mdi mdi-chevron-down select-arrow" aria-hidden="true"></i>
    </div>
    <div class="group-update-indicator mt-1" style="min-height: 18px; font-size: 0.7rem;"></div>
</div>
