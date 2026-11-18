<?php
/**
 * Modal para editar credenciales de un usuario asociado (portal / depósito).
 *
 * @var array $pageTexts
 */
$pt = $pageTexts ?? [];
$t = static function (string $key, string $fallback = '') use ($pt): string {
    return htmlspecialchars((string) ($pt[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
};
?>
<div class="modal fade" id="editAssignedUserModal" tabindex="-1" aria-labelledby="editAssignedUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editAssignedUserForm" method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAssignedUserModalLabel"><?= $t('modal_edit_assigned_user', $t('editar', '')) ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= $t('cerrar', '') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_assigned_user_id" name="user_id" value="">

                    <div class="form-group">
                        <label for="edit_assigned_user_name"><?= $t('nombre', $t('name', '')) ?></label>
                        <input type="text" class="form-control" id="edit_assigned_user_name" name="name" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="edit_assigned_user_email"><?= $t('email', '') ?></label>
                        <input type="email" class="form-control" id="edit_assigned_user_email" name="email" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="edit_assigned_user_password_current"><?= $t('password', '') ?></label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control bg-light"
                                   id="edit_assigned_user_password_current"
                                   readonly
                                   autocomplete="off">
                            <div class="input-group-append">
                                <button type="button"
                                        class="btn btn-outline-secondary js-edit-assigned-pwd-eye"
                                        data-target="#edit_assigned_user_password_current"
                                        tabindex="-1"
                                        title="<?= $t('portal_password_show', '') ?>"
                                        aria-label="<?= $t('portal_password_show', '') ?>">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_assigned_user_password"><?= $t('portal_password_new', $t('password', '')) ?></label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control"
                                   id="edit_assigned_user_password"
                                   name="password"
                                   autocomplete="new-password"
                                   placeholder="<?= $t('portal_password_new', '') ?>">
                            <div class="input-group-append">
                                <button type="button"
                                        class="btn btn-outline-secondary js-edit-assigned-pwd-eye"
                                        data-target="#edit_assigned_user_password"
                                        tabindex="-1"
                                        title="<?= $t('portal_password_show', '') ?>"
                                        aria-label="<?= $t('portal_password_show', '') ?>">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted"><?= $t('operator_password_hint', '') ?></small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="edit_assigned_user_phone"><?= $t('phone_number', $t('phone', '')) ?></label>
                        <input type="text" class="form-control" id="edit_assigned_user_phone" name="phone" autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $t('cancelar', '') ?></button>
                    <button type="submit" class="btn btn-success"><?= $t('update', $t('guardar', $t('save', ''))) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
