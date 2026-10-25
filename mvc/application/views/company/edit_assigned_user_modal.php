<?php
/**
 * Modal para editar credenciales de un usuario asociado (p. ej. operador de depósito).
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
                        <label for="edit_assigned_user_email"><?= $t('email', '') ?></label>
                        <input type="email" class="form-control" id="edit_assigned_user_email" name="email" readonly>
                    </div>

                    <div class="form-group">
                        <div class="passwodeye">
                            <label for="edit_assigned_user_password"><?= $t('contraseña', $t('password', '')) ?></label>
                            <input type="password" class="form-control" id="edit_assigned_user_password" name="password" autocomplete="new-password">
                            <span toggle="#edit_assigned_user_password" class="fa fa-fw fa-eye field_icon toggle-password" style="top: 30px;"></span>
                        </div>
                        <small class="form-text text-muted"><?= $t('operator_password_hint', '') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="edit_assigned_user_phone"><?= $t('phone_number', '') ?></label>
                        <input type="text" class="form-control" id="edit_assigned_user_phone" name="phone" autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $t('cancelar', '') ?></button>
                    <button type="submit" class="btn btn-success"><?= $t('update', $t('save', '')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
