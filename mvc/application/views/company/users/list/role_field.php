<?php
/**
 * @var object $listItem item from the list for a given record
 * @var array|null $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

$roleLabels = [
    'manager' => trim((string) ($pageTexts['role_label_manager'] ?? '')),
    'customer' => trim((string) ($pageTexts['role_label_customer'] ?? '')),
    'seller' => trim((string) ($pageTexts['role_label_seller'] ?? '')),
    'warehouse' => trim((string) ($pageTexts['role_label_warehouse'] ?? '')),
];

$roleBadgeClasses = [
    'manager' => 'fv-user-role-badge fv-user-role-badge--manager',
    'customer' => 'fv-user-role-badge fv-user-role-badge--customer',
    'seller' => 'fv-user-role-badge fv-user-role-badge--seller',
    'warehouse' => 'fv-user-role-badge fv-user-role-badge--warehouse',
];

$companyPivots = $listItem->company_users ?? [];
$types = [];

foreach ($companyPivots as $companyUser) {
    $type = strtolower(trim((string) (is_object($companyUser) ? ($companyUser->type ?? '') : '')));
    if ($type !== '' && ! in_array($type, $types, true)) {
        $types[] = $type;
    }
}

if ($types === []) {
    echo '<span class="text-muted">—</span>';
    return;
}
?>
<span class="fv-user-role-badges">
<?php foreach ($types as $type):
    $label = $roleLabels[$type] ?? '';
    if ($label === '') {
        $label = function_exists('mb_strtoupper') ? mb_strtoupper($type, 'UTF-8') : strtoupper($type);
    }
    $badgeClass = $roleBadgeClasses[$type] ?? 'fv-user-role-badge fv-user-role-badge--default';
    ?>
    <span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
    </span>
<?php endforeach; ?>
</span>
