<?php
/**
 * @var Object $entity
 * @var string $entityType
 * @var array $pageTexts
 * @var array|object[] $warehouseRoleUsers
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    return htmlspecialchars((string) ($pageTexts[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
};
$isWarehousePicker = ($entityType ?? '') === 'warehouse';
$warehouseRoleUsers = is_array($warehouseRoleUsers ?? null) ? $warehouseRoleUsers : [];
?>
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addUserForm" method="POST" action="<?= company_url($entityType.'s/assignUser') ?>"
                <?php if ($isWarehousePicker) : ?>
                    data-msg-select-user-required="<?= $t('select_user_required', '') ?>"
                    <?php if (!empty($assignUsersPending)) : ?>
                    data-assign-pending="1"
                    <?php endif; ?>
                <?php endif; ?>>
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">
                        <?= $isWarehousePicker
                            ? $t('modal_select_warehouse_user', $t('buscar_agregar_usuarios', ''))
                            : $t('buscar_agregar_usuarios', 'Search and add users') ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= $t('cerrar', 'Close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if ($isWarehousePicker) : ?>
                        <div id="warehouseUserPicker" class="fv-warehouse-user-picker">
                            <?php if (count($warehouseRoleUsers) === 0) : ?>
                                <p class="text-muted mb-0"><?= $t('no_warehouse_users_available', '') ?></p>
                            <?php else : ?>
                                <div class="form-group mb-3">
                                    <label for="warehouse_user_picker_filter"><?= $t('warehouse_user_picker_filter', '') ?></label>
                                    <input type="search"
                                           class="form-control"
                                           id="warehouse_user_picker_filter"
                                           autocomplete="off"
                                           placeholder="<?= $t('warehouse_user_picker_filter', '') ?>">
                                </div>
                                <div class="table-responsive fv-warehouse-user-picker__list">
                                    <table class="table table-hover table-sm mb-0 fv-warehouse-user-picker__table">
                                        <thead>
                                        <tr>
                                            <th class="fv-warehouse-user-picker__col-select"></th>
                                            <th><?= $t('nombre', 'Name') ?></th>
                                            <th><?= $t('email', 'Email') ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($warehouseRoleUsers as $pickerUser) : ?>
                                            <?php
                                            $pickerUserId = (int) ($pickerUser->id ?? 0);
                                            $pickerName = trim((string) ($pickerUser->name ?? ''));
                                            $pickerEmail = trim((string) ($pickerUser->email ?? ''));
                                            $pickerSearch = strtolower($pickerName . ' ' . $pickerEmail);
                                            ?>
                                            <tr class="fv-warehouse-user-picker__row"
                                                data-user-id="<?= $pickerUserId ?>"
                                                data-search="<?= htmlspecialchars($pickerSearch, ENT_QUOTES, 'UTF-8') ?>"
                                                tabindex="0"
                                                role="button">
                                                <td class="fv-warehouse-user-picker__col-select text-center">
                                                    <input type="radio"
                                                           name="warehouse_user_picker_choice"
                                                           value="<?= $pickerUserId ?>"
                                                           aria-label="<?= htmlspecialchars($pickerName !== '' ? $pickerName : $pickerEmail, ENT_QUOTES, 'UTF-8') ?>">
                                                </td>
                                                <td><?= htmlspecialchars($pickerName, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($pickerEmail, ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-muted small mb-0 mt-2 d-none" id="warehouse_user_picker_empty_filter">
                                    <?= $t('warehouse_user_picker_no_results', '') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="form-group">
                            <label for="searchUser"><?= $t('buscar_por_nombre', 'Search by name') ?></label>
                            <input type="text" class="form-control validate" id="user_name" placeholder="<?= $t('enter_user_name', 'Enter user name...') ?>">
                            <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                            <div id="user_suggestions" class="suggestions-box"></div>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="<?= $entityType ?>_id" value="<?= $entity->{$entityType.'_id'} ?? $entity->id ?>">
                    <input type="hidden" id="user_id" name="user_id" value="">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $t('cancelar', 'Cancel') ?></button>
                    <button type="submit"
                            class="btn btn-success"
                        <?= ($isWarehousePicker && count($warehouseRoleUsers) === 0) ? ' disabled' : '' ?>>
                        <?= $t('aceptar', 'Accept') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
