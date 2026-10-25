<?php
/**
 * @var object $editingUser
 * @var object|null $sellerRecord
 * @var bool $isSellerUser
 * @var object|null $company
 * @var array $pageTexts
 * @var array $companyPhoneCountryMeta
 * @var string $companyUserCountryFlagsUrl
 */
$pt = $pageTexts ?? [];
if (trim((string) ($pt['telefono'] ?? '')) === '') {
    foreach (['phone_number', 'col_phone', 'phones'] as $_k) {
        $_v = trim((string) ($pt[$_k] ?? ''));
        if ($_v !== '') {
            $pt['telefono'] = $pt[$_k];
            break;
        }
    }
}
if (trim((string) ($pt['role'] ?? '')) === '') {
    foreach (['rol_name', 'roles_usuario'] as $_k) {
        $_v = trim((string) ($pt[$_k] ?? ''));
        if ($_v !== '') {
            $pt['role'] = $pt[$_k];
            break;
        }
    }
}
$t = function (string $key, string $fallback = '') use ($pt): string {
    return htmlspecialchars((string) ($pt[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
};
$eu = $editingUser;
$seller = $sellerRecord ?? ($eu->seller ?? null);
$editRole = isset($editRole) ? strtolower(trim((string) $editRole)) : '';
if ($editRole === '') {
    $editRole = !empty($isSellerUser) ? 'seller' : 'manager';
}
$isSellerUser = ($editRole === 'seller') || !empty($isSellerUser) || (is_object($seller) && !empty($seller->id));
$editProfileId = isset($editProfileId) ? (int) $editProfileId : 0;
$editRelatedId = isset($editRelatedId) ? (int) $editRelatedId : 0;
$sellerId = is_object($seller) ? (int) ($seller->id ?? 0) : 0;
$companyDefaultSellerId = isset($company->default_seller_id) ? (int) $company->default_seller_id : 0;
$isDefaultSeller = $sellerId > 0 && (
    !empty($seller->isDefault)
    || ($companyDefaultSellerId > 0 && $companyDefaultSellerId === $sellerId)
);
$hasOtherDefaultSeller = $companyDefaultSellerId > 0 && $companyDefaultSellerId !== $sellerId
    ? $companyDefaultSellerId
    : null;
$phoneRowColClass = 'col-lg-3 col-md-6';
$rolePlaceholder = $t('select_role', $pt['label_select'] ?? '');
$searchPh = $t('role_search_placeholder', $pt['search'] ?? '');
$sellerPhone = is_object($seller) ? (string) ($seller->phone_number ?? '') : '';
$sellerCell = is_object($seller) ? (string) ($seller->cell_number ?? '') : '';
$sellerFax = is_object($seller) ? (string) ($seller->fax ?? '') : '';
if ($sellerPhone === '') {
    $sellerPhone = (string) ($eu->phone ?? '');
}
if ($sellerCell === '') {
    $sellerCell = (string) ($eu->cellphone ?? '');
}
if ($sellerFax === '') {
    $sellerFax = (string) ($eu->fax ?? '');
}
$emailOnOrder = is_object($seller) && !empty($seller->email_on_salesman_order);
$formAction = $formAction ?? company_url('users/update/' . (int) ($eu->id ?? 0));
$pageTitleKey = $pageTitleKey ?? 'edit_user';
$embeddedInModal = ! empty($embeddedInModal);
?>
<script>
    window.hasDefaultSeller = <?= json_encode($hasOtherDefaultSeller) ?>;
    window.companyUserFormLang = Object.assign({}, window.companyUserFormLang || {}, <?= json_encode([
        'user_saved' => $pt['user_saved'] ?? '',
        'user_updated' => $pt['user_updated'] ?? '',
        'user_error_save' => $pt['user_error_save'] ?? '',
        'user_error_validation' => $pt['user_error_validation'] ?? '',
        'user_error_upload_url' => $pt['user_error_upload_url'] ?? '',
        'user_error_upload' => $pt['user_error_upload'] ?? '',
        'user_error_generic' => $pt['user_error_generic'] ?? '',
        'user_default_seller_title' => $pt['user_default_seller_title'] ?? '',
        'user_default_seller_text' => $pt['user_default_seller_text'] ?? '',
        'user_default_seller_cancel' => $pt['user_default_seller_cancel'] ?? '',
        'user_default_seller_confirm' => $pt['user_default_seller_confirm'] ?? '',
        'btn_ok' => $pt['ok'] ?? 'OK',
        'name' => $pt['name'] ?? '',
        'email' => $pt['email'] ?? '',
        'password' => $pt['password'] ?? '',
        'telefono' => $pt['telefono'] ?? '',
        'cell_number' => $pt['cell_number'] ?? '',
        'fax' => $pt['fax'] ?? '',
        'user_existing_registered' => $pt['user_existing_registered'] ?? '',
        'user_existing_admin' => $pt['user_existing_admin'] ?? '',
        'user_modal_select_warehouse' => $pt['user_modal_select_warehouse'] ?? '',
        'user_modal_select_warehouse_placeholder' => $pt['user_modal_select_warehouse_placeholder'] ?? '',
        'select_role' => $pt['select_role'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
</script>
<?php if (! $embeddedInModal) : ?>
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"><?= $t($pageTitleKey, $t('edit_user', '')) ?></h3>
            </div>
            <div class="card">
                <div class="card-body">
<?php else : ?>
<div class="fv-user-edit-modal-form">
<?php endif; ?>
<?php
$miOpen = static function (string $icon) use ($embeddedInModal): void {
    if (! $embeddedInModal) {
        return;
    }
    echo '<div class="modal-input-icon"><i class="mdi ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
};
$miClose = static function () use ($embeddedInModal): void {
    if ($embeddedInModal) {
        echo '</div>';
    }
};
?>
                    <form class="forms-sample" id="form_edit"
                          action="<?= htmlspecialchars((string) $formAction, ENT_QUOTES, 'UTF-8') ?>" method="post"
                          enctype="multipart/form-data"
                          data-upload-image-url="<?= htmlspecialchars(company_url('users/upload_image'), ENT_QUOTES, 'UTF-8') ?>"
                          data-embedded-modal="<?= $embeddedInModal ? '1' : '0' ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="name"><?= $t('name', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                    <?php $miOpen('mdi-account-outline'); ?>
                                    <input type="text" class="form-control" name="name" id="name" autocomplete="off"
                                           value="<?= htmlspecialchars((string) ($eu->name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php $miClose(); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="email"><?= $t('email', '') ?>: <span class="color-red">*</span></label>
                                    <?php $miOpen('mdi-email-outline'); ?>
                                    <input type="text" class="form-control" name="email" id="email" autocomplete="off" required
                                           value="<?= htmlspecialchars((string) ($eu->email ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php $miClose(); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <?php if ($embeddedInModal) : ?>
                                        <label for="password"><?= $t('password', '') ?></label>
                                        <div class="modal-input-icon">
                                            <i class="mdi mdi-lock-outline" aria-hidden="true"></i>
                                            <div class="input-group">
                                                <input type="password" class="form-control" name="password" id="password" autocomplete="off"
                                                       value="<?= htmlspecialchars((string) ($eu->password_plain ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary px-2 js-modal-toggle-password" tabindex="-1" aria-label="<?= $t('password', '') ?>">
                                                        <span class="fa fa-eye" aria-hidden="true"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <div class="passwodeye">
                                            <label for="password"><?= $t('password', '') ?>:</label>
                                            <input type="password" class="form-control" name="password" id="password" autocomplete="off"
                                                   value="<?= htmlspecialchars((string) ($eu->password_plain ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <span toggle="#password-field" class="fa fa-fw fa-eye field_icon toggle-password" style="top: 30px;"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php $sectionPersonalEdit = trim((string) ($pt['user_section_personal_data'] ?? '')); ?>
                        <?php if ($sectionPersonalEdit !== ''): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <?php if ($embeddedInModal) : ?>
                                        <div class="modal-section-divider" aria-hidden="true"></div>
                                    <?php endif; ?>
                                    <h6 class="text-uppercase text-muted mb-2"><?= htmlspecialchars($sectionPersonalEdit, ENT_QUOTES, 'UTF-8') ?></h6>
                                </div>
                            </div>
                        <?php elseif ($embeddedInModal) : ?>
                            <div class="modal-section-divider" aria-hidden="true"></div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="first_name"><?= $t('first_name', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                    <?php $miOpen('mdi-account-circle-outline'); ?>
                                    <input type="text" class="form-control" name="first_name" id="first_name" autocomplete="off"
                                           value="<?= htmlspecialchars((string) ($eu->first_name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="<?= $t('first_name_placeholder', $pt['first_name'] ?? '') ?>">
                                    <?php $miClose(); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="last_name"><?= $t('last_name', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                    <?php $miOpen('mdi-account-circle-outline'); ?>
                                    <input type="text" class="form-control" name="last_name" id="last_name" autocomplete="off"
                                           value="<?= htmlspecialchars((string) ($eu->last_name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="<?= $t('last_name_placeholder', $pt['last_name'] ?? '') ?>">
                                    <?php $miClose(); ?>
                                </div>
                            </div>
                        </div>

                        <?php $sectionAddressEdit = trim((string) ($pt['user_section_address_data'] ?? '')); ?>
                        <?php if ($sectionAddressEdit !== ''): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <?php if ($embeddedInModal) : ?>
                                        <div class="modal-section-divider" aria-hidden="true"></div>
                                    <?php endif; ?>
                                    <h6 class="text-uppercase text-muted mb-2"><?= htmlspecialchars($sectionAddressEdit, ENT_QUOTES, 'UTF-8') ?></h6>
                                </div>
                            </div>
                        <?php elseif ($embeddedInModal) : ?>
                            <div class="modal-section-divider" aria-hidden="true"></div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="address"><?= $t('address', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                    <?php $miOpen('mdi-map-marker-outline'); ?>
                                    <input type="text" class="form-control" name="address" id="address" autocomplete="off"
                                           value="<?= htmlspecialchars((string) ($eu->address ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="<?= $t('address_placeholder', $pt['address'] ?? '') ?>">
                                    <?php $miClose(); ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="city"><?= $t('city', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                    <?php $miOpen('mdi-city-variant-outline'); ?>
                                    <input type="text" class="form-control" name="city" id="city" autocomplete="off"
                                           value="<?= htmlspecialchars((string) ($eu->city ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="<?= $t('city_placeholder', $pt['city'] ?? '') ?>">
                                    <?php $miClose(); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="state"><?= $t('state', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                    <?php $miOpen('mdi-map-outline'); ?>
                                    <input type="text" class="form-control" name="state" id="state" autocomplete="off"
                                           value="<?= htmlspecialchars((string) ($eu->state ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="<?= $t('state_placeholder', $pt['state'] ?? '') ?>">
                                    <?php $miClose(); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="zip_code"><?= $t('zip_code', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                    <?php $miOpen('mdi-mailbox-outline'); ?>
                                    <input type="text" class="form-control" name="zip_code" id="zip_code" autocomplete="off"
                                           value="<?= htmlspecialchars((string) ($eu->zip_code ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="<?= $t('zip_code_placeholder', $pt['zip_code'] ?? '') ?>">
                                    <?php $miClose(); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Cliente / almacén según rol (solo lectura en edición) -->
                        <?php
                        $warehousesList = isset($warehouses) && is_array($warehouses) ? $warehouses : [];
                        $warehouseLabel = $t('user_modal_select_warehouse', $pt['role_label_warehouse'] ?? '');
                        $warehousePh = $t('user_modal_select_warehouse_placeholder', $searchPh);
                        $showWarehouse = ($editRole === 'warehouse');
                        ?>
                        <div class="row">
                            <div class="col-md-12" id="role-search-container" style="<?= $showWarehouse ? '' : 'display: none;' ?> position: relative;">
                                <div class="form-group<?= $showWarehouse ? '' : ' d-none' ?><?= $embeddedInModal ? ' mb-2' : '' ?>" id="form-warehouse-select-wrap">
                                    <label for="form-warehouse-select"><?= $warehouseLabel ?>: <span class="color-red">*</span></label>
                                    <?php $miOpen('mdi-warehouse'); ?>
                                    <select class="form-control" id="form-warehouse-select" name="related_id_display" disabled>
                                        <option value=""><?= $warehousePh ?></option>
                                        <?php foreach ($warehousesList as $whItem) :
                                            $whItem = is_object($whItem) ? $whItem : (object) $whItem;
                                            $whId = (int) ($whItem->id ?? 0);
                                            if ($whId < 1) {
                                                continue;
                                            }
                                            $whName = trim((string) ($whItem->name ?? ''));
                                            $whSel = ($editRelatedId > 0 && $editRelatedId === $whId) ? ' selected' : '';
                                            ?>
                                            <option value="<?= (int) $whId ?>"<?= $whSel ?>><?= htmlspecialchars($whName !== '' ? $whName : (string) $whId, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php $miClose(); ?>
                                    <?php if ($editRelatedId > 0) : ?>
                                        <input type="hidden" name="related_id" id="related_id" value="<?= (int) $editRelatedId ?>">
                                    <?php else : ?>
                                        <input type="hidden" name="related_id" id="related_id" value="">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Rol (desactivado) + teléfonos -->
                        <?php if ($embeddedInModal) : ?>
                            <div class="modal-section-divider" aria-hidden="true"></div>
                        <?php endif; ?>
                        <div class="row align-items-end<?= $embeddedInModal ? ' modal-user-phones-row' : '' ?>">
                            <div class="<?= htmlspecialchars($phoneRowColClass, ENT_QUOTES, 'UTF-8') ?> mb-3" id="role-select-wrap">
                                <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                    <label for="role"><?= $t('role', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                    <?php $miOpen('mdi-badge-account-outline'); ?>
                                    <select id="role" class="form-control" disabled aria-disabled="true">
                                        <?php
                                        $roleOptions = isset($userTypes) && is_array($userTypes)
                                            ? $userTypes
                                            : ['manager', 'seller', 'warehouse'];
                                        $roleOptions = array_values(array_filter($roleOptions, static function ($value) {
                                            return in_array((string) $value, ['manager', 'seller', 'warehouse', 'customer'], true);
                                        }));
                                        if ($editRole === 'customer' && ! in_array('customer', $roleOptions, true)) {
                                            $roleOptions[] = 'customer';
                                        }
                                        $roleLabelKeys = [
                                            'manager' => 'role_label_manager',
                                            'seller' => 'role_label_seller',
                                            'warehouse' => 'role_label_warehouse',
                                            'customer' => 'role_label_customer',
                                        ];
                                        ?>
                                        <option value="" disabled><?= $rolePlaceholder ?></option>
                                        <?php foreach ($roleOptions as $value) :
                                            $v = (string) $value;
                                            $labelKey = $roleLabelKeys[$v] ?? '';
                                            $label = $labelKey !== '' ? trim((string) ($pt[$labelKey] ?? '')) : '';
                                            if ($label === '') {
                                                $label = function_exists('mb_strtoupper') ? mb_strtoupper($v, 'UTF-8') : strtoupper($v);
                                            }
                                            $sel = ($v === $editRole) ? ' selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?>"<?= $sel ?>>
                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php $miClose(); ?>
                                    <input type="hidden" name="role" value="<?= htmlspecialchars($editRole, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                            <?php if (! $isSellerUser) : ?>
                                <div class="companyuser-phone-col <?= htmlspecialchars($phoneRowColClass, ENT_QUOTES, 'UTF-8') ?> mb-3">
                                    <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                        <label for="phone"><?= $t('telefono', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="phone" id="phone" autocomplete="off"
                                                   value="<?= htmlspecialchars((string) ($eu->phone ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="companyuser-phone-col <?= htmlspecialchars($phoneRowColClass, ENT_QUOTES, 'UTF-8') ?> mb-3">
                                    <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                        <label for="cellphone"><?= $t('cell_number', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="cellphone" id="cellphone" autocomplete="off"
                                                   value="<?= htmlspecialchars((string) ($eu->cellphone ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="companyuser-phone-col <?= htmlspecialchars($phoneRowColClass, ENT_QUOTES, 'UTF-8') ?> mb-3">
                                    <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                        <label for="fax"><?= $t('fax', '') ?><?= $embeddedInModal ? '' : ':' ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="fax" id="fax" autocomplete="off"
                                                   value="<?= htmlspecialchars((string) ($eu->fax ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="seller-fields" style="<?= $isSellerUser ? '' : 'display:none;' ?>">
                            <div class="row">
                                <?php if (!empty($pt['dato_vendedor'])) : ?>
                                    <div class="col-12 mb-2">
                                        <label><strong><?= htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper((string) $pt['dato_vendedor'], 'UTF-8') : strtoupper((string) $pt['dato_vendedor']), ENT_QUOTES, 'UTF-8') ?></strong></label>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                        <label for="mobile"><?= $t('phone_number', $pt['telefono'] ?? '') ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="mobile" id="mobile"
                                                   value="<?= htmlspecialchars($sellerPhone, ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                        <label for="cellNumber"><?= $t('cell_number', '') ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="cellNumber" id="cellNumber"
                                                   value="<?= htmlspecialchars($sellerCell, ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>">
                                        <label for="seller_fax"><?= $t('fax', '') ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="seller_fax" id="seller_fax"
                                                   value="<?= htmlspecialchars($sellerFax, ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group d-flex align-items-center justify-content-between flex-wrap<?= $embeddedInModal ? ' seller-email-switch-row' : '' ?>">
                                        <span class="mb-0 mr-2<?= $embeddedInModal ? ' seller-email-switch-label' : '' ?>"><?= $t('email_on_salesman_order', '') ?></span>
                                        <div class="custom-control custom-switch mb-0">
                                            <input type="checkbox" class="custom-control-input" id="email_on_salesman_order_switch"
                                                   data-seller-email-order-flag="1"
                                                   aria-label="<?= $t('email_on_salesman_order', '') ?>"
                                                   <?= $emailOnOrder ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="email_on_salesman_order_switch"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group<?= $embeddedInModal ? ' mb-2' : '' ?>" id="seller-select">
                                        <label for="profile_seller"><?= $t('profile', '') ?></label>
                                        <?php $miOpen('mdi-card-account-details-outline'); ?>
                                        <select name="profile_id" id="profile_seller" class="form-control notranslate">
                                            <?php if (!empty($sellerProfiles)) : ?>
                                                <option disabled value=""<?= $editProfileId < 1 ? ' selected' : '' ?>><?= $t('select_profile', '') ?></option>
                                                <?php foreach ($sellerProfiles as $profile) :
                                                    $pid = (int) ($profile->profile_id ?? 0);
                                                    $pSel = ($editProfileId > 0 && $editProfileId === $pid) ? ' selected' : '';
                                                    if ($pSel === '' && $editProfileId < 1 && !empty($profile->default)) {
                                                        $pSel = ' selected';
                                                    }
                                                    ?>
                                                    <option value="<?= $pid ?>"<?= $pSel ?>>
                                                        <?= htmlspecialchars((string) ($profile->profile_name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <option value=""><?= $t('no_profile_found', '') ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <?php $miClose(); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row align-items-center">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <div class="d-flex flex-wrap align-items-center<?= $embeddedInModal ? ' seller-modal-checks' : '' ?>" style="gap: 0.35rem 1.25rem;">
                                            <div class="custom-control custom-checkbox mb-0">
                                                <input type="checkbox" class="custom-control-input" name="all_customers" id="all_customers"
                                                       value="1"<?= (is_object($seller) && !empty($seller->all_customers)) ? ' checked' : '' ?>>
                                                <label class="custom-control-label" for="all_customers"><?= $t('all_customer', '') ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox mb-0">
                                                <input type="checkbox" class="custom-control-input" name="checkBoxDefault" id="seller-default"
                                                       value="1"<?= $isDefaultSeller ? ' checked' : '' ?><?= $isDefaultSeller ? ' disabled' : '' ?>>
                                                <label class="custom-control-label" for="seller-default"><?= $t('default', '') ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox mb-0">
                                                <input type="checkbox" class="custom-control-input" name="proforma" id="seller-proforma"
                                                       value="1"<?= (is_object($seller) && !empty($seller->proforma)) ? ' checked' : '' ?>>
                                                <label class="custom-control-label" for="seller-proforma"><?= $t('proforma', '') ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-group">
                                    <label><?= $t('image', '') ?>: <span class="color-red">*</span></label>
                                    <div class="fileinput fileinput-new text-center" data-provides="fileinput">
                                        <div class="fileinput-new thumbnail" style="width: 250px; height:150px;">
                                            <?php
                                            $_img = trim((string) ($eu->image ?? ''));
                                            $_isUuid = $_img !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $_img);
                                            if (function_exists('fv_user_avatar_image_url')) {
                                                $_preview = fv_user_avatar_image_url($eu);
                                            } elseif (function_exists('company_logo_is_api_image') && company_logo_is_api_image($_img)) {
                                                $_preview = company_logo_url($_img);
                                            } elseif ($_isUuid && function_exists('fv_api_image_url')) {
                                                $_preview = fv_api_image_url($_img);
                                            } elseif ($_img !== '') {
                                                $_preview = company_logo_url($_img);
                                            } else {
                                                $_preview = '';
                                            }
                                            ?>
                                            <?php if ($_preview !== '') : ?>
                                                <img src="<?= htmlspecialchars($_preview, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                            <?php else : ?>
                                                <img src="<?= base_url('uploads/user_anonimo.jpeg') ?>" alt="">
                                            <?php endif; ?>
                                        </div>
                                        <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 250px; max-height: 150px;"></div>
                                        <div>
                                            <span class="btn btn-default btn-file">
                                                <span class="fileinput-new"><i class="fa-solid fa-upload"></i></span>
                                                <span class="fileinput-exists"><i class="fa-solid fa-pen-to-square"></i></span>
                                                <input type="file" name="images" accept=".jpg, .jpeg, .png">
                                                <input type="hidden" name="image" value="<?= htmlspecialchars((string) ($eu->image ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            </span>
                                            <a href="javascript:void(0)" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">
                                                <i class="fa-solid fa-ban"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                    <?php if ($embeddedInModal) : ?>
                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <?= $t('cancel', $t('boton_cancelar', $t('back', ''))) ?>
                        </button>
                        <button type="submit" class="btn btn-success" form="form_edit">
                            <?= $t('update', $t('guardar', $t('save', ''))) ?>
                        </button>
                    </div>
                    </div><!-- .fv-user-edit-modal-form -->
                    <?php else : ?>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_edit',
                        'saveLabel' => (string) ($t('update', '')),
                        'cancelLabel' => (string) ($t('back', '')),
                        'cancelUrl' => company_url('users'),
                    ]); ?>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="card-body">
                <div class="page-header">
                    <h3 class="page-title"><?= $t('user_profiles', '') ?></h3>
                </div>
                <div class="table-responsive">
                    <table class="table w-100">
                        <thead>
                        <tr>
                            <th class="fuente_16px"><?= $t('role', '') ?></th>
                            <th class="fuente_16px"><?= $t('profile', '') ?></th>
                            <th class="text-center fuente_16px"><?= $t('colmn_action', '') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($eu->company_users ?? $eu->companyUsers ?? []) as $companyUser) :
                            $typeStr = (string) ($companyUser->type ?? '');
                            $roleUp = $typeStr !== '' ? (function_exists('mb_strtoupper') ? mb_strtoupper($typeStr, 'UTF-8') : strtoupper($typeStr)) : '';
                            $profName = $companyUser->profileable->profile_name ?? $companyUser->profileable->name ?? null;
                            ?>
                            <tr>
                                <td class="fuente_16px"><?= htmlspecialchars($roleUp, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="fuente_16px"><?= $profName !== null && $profName !== '' ? htmlspecialchars((string) $profName, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                <td class="text-center text-muted fuente_16px">—</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
                    <?php endif; ?>

<?php $this->load->view('company/users/_companyuser_phone_script', [
    'companyPhoneCountryMeta' => $companyPhoneCountryMeta ?? [],
    'companyUserCountryFlagsUrl' => $companyUserCountryFlagsUrl ?? '',
]); ?>
<?php if (! $embeddedInModal) : ?>
<?php
$fvUserJson = $this->session->flashdata('user_form_validation_json');
if (is_string($fvUserJson) && $fvUserJson !== '') :
    $btnOk = htmlspecialchars((string) ($pt['ok'] ?? 'OK'), ENT_QUOTES, 'UTF-8');
    ?>
<script>
(function () {
    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function showFvError(payload) {
        var lines = [];
        if (payload.errors && typeof payload.errors === 'object') {
            Object.keys(payload.errors).forEach(function (k) {
                var v = payload.errors[k];
                if (Array.isArray(v)) {
                    v.forEach(function (x) { lines.push(x); });
                } else if (v != null && v !== '') {
                    lines.push(v);
                }
            });
        }
        var html = lines.length
            ? '<ul style="text-align:left;margin:0;padding-left:1.2em;">' + lines.map(function (x) {
                return '<li>' + esc(x) + '</li>';
            }).join('') + '</ul>'
            : '';
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({
                icon: 'error',
                title: payload.message || '',
                html: html || undefined,
                confirmButtonText: '<?= $btnOk ?>'
            });
        } else if (typeof swal !== 'undefined') {
            swal({ title: payload.message || '', text: lines.join('\n'), icon: 'error' });
        } else {
            alert((payload.message || '') + (lines.length ? '\n' + lines.join('\n') : ''));
        }
    }
    try {
        var payload = JSON.parse(<?= json_encode($fvUserJson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
        if (payload) {
            showFvError(payload);
        }
    } catch (e) {}
})();
</script>
<?php endif; ?>
<?php
$fvUserSuccess = $this->session->flashdata('user_form_success_message');
if (is_string($fvUserSuccess) && trim($fvUserSuccess) !== '') :
    $btnOkSucc = htmlspecialchars((string) ($pt['ok'] ?? 'OK'), ENT_QUOTES, 'UTF-8');
    ?>
<script>
(function () {
    var msg = <?= json_encode($fvUserSuccess, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var btnOk = <?= json_encode($btnOkSucc, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    function fire() {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({ icon: 'success', title: msg, confirmButtonText: btnOk });
        } else if (typeof swal === 'function') {
            swal({ title: msg, icon: 'success' });
        } else {
            window.alert(msg);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fire);
    } else {
        fire();
    }
})();
</script>
<?php endif; ?>
<?php endif; ?>
