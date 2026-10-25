<?php
/**
 * @var object $entity
 * @var string $entityType
 * @var array $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
if (function_exists('fv_lang_json_flat_section') && function_exists('fv_resolve_company_language_id')) {
    $langId = (int) fv_resolve_company_language_id();
    $moduleKey = (($entityType ?? '') === 'warehouse') ? 'cmp-warehouse' : 'cmp-cliente';
    $moduleLang = fv_lang_json_flat_section($langId, $moduleKey);
    if ($moduleLang !== []) {
        $pageTexts = array_merge($moduleLang, $pageTexts);
    }
}
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    $value = $pageTexts[$key] ?? '';
    if (! is_string($value) || trim($value) === '') {
        $value = $fallback;
    }

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$users = $entity->users ?? [];
if (! is_array($users)) {
    $users = is_object($users) ? (array) $users : [];
}

$entityId = (int) ($entity->id ?? $entity->{$entityType . '_id'} ?? 0);
$assignUsersPending = !empty($assignUsersPending);
$isWarehouseEntity = ($entityType ?? '') === 'warehouse';
$warehouseRoleUsersList = is_array($warehouseRoleUsers ?? null) ? $warehouseRoleUsers : [];
$canAssignUsers = $entityId > 0 && ! $assignUsersPending;
$canOpenAssignModal = $isWarehouseEntity
    ? ($canAssignUsers || ($assignUsersPending && count($warehouseRoleUsersList) > 0))
    : $canAssignUsers;
$canUnassign = $isWarehouseEntity && $entityId > 0;

$confirmUnassignMsg = trim((string) ($pageTexts['confirm_unassign_user'] ?? ''));
if ($confirmUnassignMsg === '' && function_exists('fv_lang_json_flat_section') && function_exists('fv_resolve_company_language_id')) {
    $whLang = fv_lang_json_flat_section((int) fv_resolve_company_language_id(), 'cmp-warehouse');
    $confirmUnassignMsg = trim((string) ($whLang['confirm_unassign_user'] ?? ''));
}
$confirmUnassignAttr = htmlspecialchars($confirmUnassignMsg, ENT_QUOTES, 'UTF-8');
?>
<script>
window.FV_ASSIGNED_USERS_I18N = <?= json_encode([
    'confirm_unassign_user' => $confirmUnassignMsg,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/fv-assigned-users.css') ?>" />
<div class="card fv-assigned-users-card mt-4"
     data-confirm-unassign="<?= $confirmUnassignAttr ?>"
     data-assign-pending="<?= $assignUsersPending ? '1' : '0' ?>"
     data-available-users="<?= (int) count($warehouseRoleUsersList) ?>"
     data-label-delete="<?= $t('eliminar', 'Delete') ?>">
    <div class="card-header fv-assigned-users-card__header d-flex justify-content-between align-items-center">
        <span class="fv-assigned-users-card__title"><?= $t('usuarios_asociados', 'Associated Users') ?></span>
        <button type="button"
                class="btn btn-primary btn-sm fv-assigned-users-card__add-btn"
                data-toggle="modal"
                data-target="#addUserModal"
                title="<?= $t('agregar_usuario', 'Add User') ?>"
                aria-label="<?= $t('agregar_usuario', 'Add User') ?>"
            <?= $canOpenAssignModal ? '' : ' disabled' ?>>
            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
        </button>
    </div>
    <?php if ($assignUsersPending) : ?>
        <div class="card-body border-bottom py-3">
            <p class="text-muted small mb-0"><?= $t('assign_users_pending_hint', '') ?></p>
        </div>
    <?php endif; ?>
    <div class="card-body p-0">
        <div class="table-responsive fv-assigned-users-table-wrap">
            <table class="table table-striped mb-0 fv-assigned-users-table">
                <thead>
                <tr>
                    <th><?= $t('nombre', 'Name') ?></th>
                    <th><?= $t('email', 'Email') ?></th>
                    <th class="fv-assigned-users-table__actions-col text-center"><?= $t('acciones', 'Actions') ?></th>
                </tr>
                </thead>
                <tbody id="fv-assigned-users-tbody">
                <?php if (count($users) <= 0) : ?>
                    <tr class="fv-assigned-users-empty-row">
                        <td colspan="3" class="text-center text-muted py-4"><?= $t('sin_usuarios', 'No associated users') ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($users as $user) : ?>
                        <tr class="fv-assigned-users-data-row">
                            <td><?= htmlspecialchars((string) ($user->name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($user->email ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="fv-assigned-users-table__actions-col text-center">
                                <div class="fv-assigned-users-table__actions fv-crud-row-actions">
                                    <button type="button"
                                            class="btn fv-crud-btn fv-crud-btn--row btn-warning btn-sm js-edit-assigned-user"
                                            title="<?= $t('editar', 'Edit') ?>"
                                            aria-label="<?= $t('editar', 'Edit') ?>"
                                            data-user-id="<?= (int) ($user->id ?? 0) ?>"
                                            data-email="<?= htmlspecialchars((string) ($user->email ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-phone="<?= htmlspecialchars((string) ($user->phone ?? $user->phone_number ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-update-url="<?= htmlspecialchars(company_url('users/update/' . (int) ($user->id ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </button>
                                    <?php if ($canUnassign) : ?>
                                    <button type="button"
                                            class="btn fv-crud-btn fv-crud-btn--row btn-danger btn-sm js-unassign-assigned-user"
                                            title="<?= $t('eliminar', 'Delete') ?>"
                                            aria-label="<?= $t('eliminar', 'Delete') ?>"
                                            data-user-id="<?= (int) ($user->id ?? 0) ?>"
                                            data-warehouse-id="<?= $entityId ?>"
                                            data-confirm-msg="<?= $confirmUnassignAttr ?>"
                                            data-unassign-url="<?= htmlspecialchars(company_url('warehouses/unassignUser'), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canOpenAssignModal) : ?>
<?php $this->load->view('company/assign_user', [
    'entity' => $entity,
    'entityType' => $entityType,
    'pageTexts' => $pageTexts,
    'warehouseRoleUsers' => $warehouseRoleUsersList,
    'assignUsersPending' => $assignUsersPending,
]); ?>
<?php if ($canAssignUsers) : ?>
<?php $this->load->view('company/edit_assigned_user_modal', ['pageTexts' => $pageTexts]); ?>
<?php endif; ?>
<?php endif; ?>
