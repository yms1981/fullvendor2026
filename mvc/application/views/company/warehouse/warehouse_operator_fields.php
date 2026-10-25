<?php
/**
 * Usuario operador del depósito (login warehouse).
 *
 * @var array $pageTexts
 * @var object|null $operatorUser
 */
$pt = $pageTexts ?? [];
$t = static function (string $key, string $fallback = '') use ($pt): string {
    return htmlspecialchars((string) ($pt[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
};
$operatorUser = $operatorUser ?? null;
$hasOperator = is_object($operatorUser);
$operatorEmail = $hasOperator ? (string) ($operatorUser->email ?? '') : '';
$operatorPhone = $hasOperator ? (string) ($operatorUser->phone ?? $operatorUser->phone_number ?? '') : '';
?>
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-uppercase text-muted mb-2">
            <?= $t('section_operator_user', $t('usuarios_asociados', '')) ?>
            <span class="text-lowercase font-weight-normal">(<?= $t('label_optional', '') ?>)</span>
        </h6>
        <p class="text-muted small mb-3"><?= $t('section_operator_user_help', '') ?></p>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="operator_email"><?= $t('email', '') ?></label>
            <input type="email" class="form-control" name="operator_email" id="operator_email"
                   autocomplete="off"
                   value="<?= htmlspecialchars($operatorEmail, ENT_QUOTES, 'UTF-8') ?>"
                <?= $hasOperator ? 'readonly' : '' ?>>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <div class="passwodeye">
                <label for="operator_password"><?= $t('contraseña', $t('password', '')) ?></label>
                <input type="password" class="form-control" name="operator_password" id="operator_password"
                       autocomplete="new-password">
                <span toggle="#operator_password" class="fa fa-fw fa-eye field_icon toggle-password" style="top: 30px;"></span>
            </div>
            <?php if ($hasOperator) : ?>
                <small class="form-text text-muted"><?= $t('operator_password_hint', '') ?></small>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="operator_phone"><?= $t('phone_number', '') ?></label>
            <input type="text" class="form-control" name="operator_phone" id="operator_phone"
                   autocomplete="off"
                   value="<?= htmlspecialchars($operatorPhone, ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>
</div>
<?php if ($hasOperator && ! empty($operatorUser->id)) : ?>
    <input type="hidden" name="operator_user_id" value="<?= (int) $operatorUser->id ?>">
<?php endif; ?>
