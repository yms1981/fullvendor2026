<?php
/**
 * @var object|null $company
 * @var array $userTypes
 * @var array|object|null $sellerProfiles
 * @var array $pageTexts
 * @var array $companyPhoneCountryMeta
 * @var string $companyUserCountryFlagsUrl
 * @var string $addFlowType manager|seller|warehouse
 */
$pt = $pageTexts ?? [];
$addFlowType = isset($addFlowType) ? (string) $addFlowType : 'manager';
if (!in_array($addFlowType, ['manager', 'seller', 'warehouse', 'customer'], true)) {
    $addFlowType = 'manager';
}
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
$rolePlaceholder = $t('select_role', $pt['label_select'] ?? '');
$searchPh = $t('role_search_placeholder', $pt['search'] ?? '');
$phoneRowColClass = 'col-lg-3 col-md-6';
$planLimitBlock = isset($planLimitBlock) && is_array($planLimitBlock) ? $planLimitBlock : ['can_create' => true];
$planLimitBlocked = empty($planLimitBlock['can_create']);
$formAction = $formAction ?? company_url('users/save');
$usersListRedirectUrl = $usersListUrl ?? company_url('users?type=' . rawurlencode($addFlowType));
$pageTitleKey = $pageTitleKey ?? 'add_user';
?>
<script>
    window.hasDefaultSeller = <?= json_encode(isset($company->default_seller_id) ? $company->default_seller_id : null) ?>;
    window.companyUserFormLang = <?= json_encode([
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
        'user_save_confirm_title' => $pt['user_save_confirm_title'] ?? '',
        'user_save_confirm_accept' => $pt['user_save_confirm_accept'] ?? '',
        'user_save_confirm_cancel' => $pt['user_save_confirm_cancel'] ?? '',
        'user_password_masked' => $pt['user_password_masked'] ?? '',
        'user_validation_name_required' => $pt['user_validation_name_required'] ?? '',
        'user_validation_email_required' => $pt['user_validation_email_required'] ?? '',
        'user_validation_password_required' => $pt['user_validation_password_required'] ?? '',
        'user_validation_related_required' => $pt['user_validation_related_required'] ?? '',
        'user_validation_warehouse_required' => $pt['user_validation_warehouse_required'] ?? ($pt['user_modal_select_warehouse_placeholder'] ?? ''),
        'user_validation_profile_required' => $pt['user_validation_profile_required'] ?? '',
        'user_validation_role_required' => $pt['user_validation_role_required'] ?? ($pt['select_role'] ?? ''),
        'select_role' => $pt['select_role'] ?? '',
        'user_modal_select_warehouse' => $pt['user_modal_select_warehouse'] ?? '',
        'user_modal_select_warehouse_placeholder' => $pt['user_modal_select_warehouse_placeholder'] ?? '',
        'user_modal_search_customer' => $pt['user_modal_search_customer'] ?? ($pt['user_modal_select_customer'] ?? ''),
        'role_search_placeholder' => $pt['role_search_placeholder'] ?? ($pt['search'] ?? ''),
        'tab_manager' => $pt['tab_manager'] ?? '',
        'user_confirm_label_role' => $pt['user_confirm_label_role'] ?? '',
        'user_confirm_label_profile' => $pt['user_confirm_label_profile'] ?? '',
        'user_confirm_label_related' => $pt['user_confirm_label_related'] ?? '',
        'user_confirm_label_seller' => $pt['user_confirm_label_seller'] ?? '',
        'user_confirm_yes' => $pt['user_confirm_yes'] ?? '',
        'user_confirm_no' => $pt['user_confirm_no'] ?? '',
        'user_existing_registered' => $pt['user_existing_registered'] ?? '',
        'user_existing_admin' => $pt['user_existing_admin'] ?? '',
        'name' => $pt['name'] ?? '',
        'email' => $pt['email'] ?? '',
        'password' => $pt['password'] ?? '',
        'telefono' => $pt['telefono'] ?? '',
        'cell_number' => $pt['cell_number'] ?? '',
        'fax' => $pt['fax'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.companyUsersListUrl = <?= json_encode($usersListRedirectUrl, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"><?= $t($pageTitleKey, $t('add_user', '')) ?></h3>
            </div>
            <?php $this->load->view('company/plan_limit_notice', ['planLimitBlock' => $planLimitBlock]); ?>
            <div class="card">
                <div class="card-body">
                    <form class="forms-sample" id="form_add"
                          action="<?= htmlspecialchars((string) $formAction, ENT_QUOTES, 'UTF-8') ?>" method="post"
                          enctype="multipart/form-data"
                          data-user-create="1"
                          data-plan-limit-blocked="<?= $planLimitBlocked ? '1' : '0' ?>"
                          data-plan-limit-message="<?= htmlspecialchars((string) ($planLimitBlock['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <!-- Datos de acceso -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name"><?= $t('name', '') ?>: </label>
                                    <input type="text" class="form-control" name="name" id="name" autocomplete="off" value="">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="email"><?= $t('email', '') ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="email" id="email" autocomplete="off" value="">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="passwodeye">
                                        <label for="password"><?= $t('password', '') ?>:</label>
                                        <input type="password" class="form-control" name="password" id="password" autocomplete="off">
                                        <span toggle="#password-field" class="fa fa-fw fa-eye field_icon toggle-password" style="top: 30px;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php $sectionPersonalPage = trim((string) ($pt['user_section_personal_data'] ?? '')); ?>
                        <?php if ($sectionPersonalPage !== ''): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <h6 class="text-uppercase text-muted mb-2"><?= htmlspecialchars($sectionPersonalPage, ENT_QUOTES, 'UTF-8') ?></h6>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="first_name"><?= $t('first_name', '') ?>:</label>
                                    <input type="text" class="form-control" name="first_name" id="first_name" autocomplete="off" value="" placeholder="<?= $t('first_name_placeholder', $pt['first_name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="last_name"><?= $t('last_name', '') ?>:</label>
                                    <input type="text" class="form-control" name="last_name" id="last_name" autocomplete="off" value="" placeholder="<?= $t('last_name_placeholder', $pt['last_name'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <?php $sectionAddressPage = trim((string) ($pt['user_section_address_data'] ?? '')); ?>
                        <?php if ($sectionAddressPage !== ''): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <h6 class="text-uppercase text-muted mb-2"><?= htmlspecialchars($sectionAddressPage, ENT_QUOTES, 'UTF-8') ?></h6>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address"><?= $t('address', '') ?>:</label>
                                    <input type="text" class="form-control" name="address" id="address" autocomplete="off" value="" placeholder="<?= $t('address_placeholder', $pt['address'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="city"><?= $t('city', '') ?>:</label>
                                    <input type="text" class="form-control" name="city" id="city" autocomplete="off" value="" placeholder="<?= $t('city_placeholder', $pt['city'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="state"><?= $t('state', '') ?>:</label>
                                    <input type="text" class="form-control" name="state" id="state" autocomplete="off" value="" placeholder="<?= $t('state_placeholder', $pt['state'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="zip_code"><?= $t('zip_code', '') ?>:</label>
                                    <input type="text" class="form-control" name="zip_code" id="zip_code" autocomplete="off" value="" placeholder="<?= $t('zip_code_placeholder', $pt['zip_code'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Cliente / almacén según rol -->
                        <?php
                        $warehousesList = isset($warehouses) && is_array($warehouses) ? $warehouses : [];
                        $warehouseLabel = $t('user_modal_select_warehouse', $pt['role_label_warehouse'] ?? '');
                        $warehousePh = $t('user_modal_select_warehouse_placeholder', $searchPh);
                        ?>
                        <div class="row">
                            <div class="col-md-12" id="role-search-container" style="display: none; position: relative;">
                                <div class="form-group d-none" id="form-warehouse-select-wrap">
                                    <label for="form-warehouse-select"><?= $warehouseLabel ?>: <span class="color-red">*</span></label>
                                    <select class="form-control" id="form-warehouse-select" autocomplete="off">
                                        <option value=""><?= $warehousePh ?></option>
                                        <?php foreach ($warehousesList as $whItem) :
                                            $whItem = is_object($whItem) ? $whItem : (object) $whItem;
                                            $whId = (int) ($whItem->id ?? 0);
                                            if ($whId < 1) {
                                                continue;
                                            }
                                            $whName = trim((string) ($whItem->name ?? ''));
                                            ?>
                                            <option value="<?= (int) $whId ?>"><?= htmlspecialchars($whName !== '' ? $whName : (string) $whId, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group d-none" id="form-customer-search-wrap" style="position: relative;">
                                    <label id="role-search-label" for="role-search-input"></label>
                                    <input type="text" class="form-control" id="role-search-input" placeholder="<?= $searchPh ?>" autocomplete="off">
                                    <div id="role-search-results"
                                         style="position: absolute; z-index: 1000; width: 100%; background: #fff; overflow-y: auto; border-radius: 0.25rem;"></div>
                                </div>
                                <input type="hidden" name="related_id" id="related_id" value="">
                            </div>
                        </div>

                        <!-- Rol + teléfonos -->
                        <div class="row align-items-end">
                            <div class="<?= htmlspecialchars($phoneRowColClass, ENT_QUOTES, 'UTF-8') ?> mb-3" id="role-select-wrap">
                                <div class="form-group">
                                    <label for="role"><?= $t('role', '') ?>:</label>
                                    <select id="role" name="role" class="form-control" required>
                                        <?php
                                        $roleOptions = isset($userTypes) && is_array($userTypes)
                                            ? $userTypes
                                            : ['manager', 'seller', 'warehouse'];
                                        $roleOptions = array_values(array_filter($roleOptions, static function ($value) {
                                            return in_array((string) $value, ['manager', 'seller', 'warehouse', 'customer'], true);
                                        }));
                                        // En el alta por formulario no se crea cliente aquí (va por pestaña Clientes).
                                        $roleOptions = array_values(array_filter($roleOptions, static function ($value) {
                                            return (string) $value !== 'customer';
                                        }));
                                        $roleLabelKeys = [
                                            'manager' => 'role_label_manager',
                                            'seller' => 'role_label_seller',
                                            'warehouse' => 'role_label_warehouse',
                                            'customer' => 'role_label_customer',
                                        ];
                                        $presetRole = in_array($addFlowType, $roleOptions, true);
                                        ?>
                                        <option value="" disabled <?= $presetRole ? '' : 'selected' ?>><?= $rolePlaceholder ?></option>
                                        <?php foreach ($roleOptions as $value) :
                                            $v = (string) $value;
                                            $labelKey = $roleLabelKeys[$v] ?? '';
                                            $label = $labelKey !== '' ? trim((string) ($pt[$labelKey] ?? '')) : '';
                                            if ($label === '') {
                                                $label = function_exists('mb_strtoupper') ? mb_strtoupper($v, 'UTF-8') : strtoupper($v);
                                            }
                                            $sel = ($v === $addFlowType) ? 'selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="companyuser-phone-col <?= htmlspecialchars($phoneRowColClass, ENT_QUOTES, 'UTF-8') ?> mb-3">
                                <div class="form-group">
                                    <label for="phone"><?= $t('telefono', '') ?>:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                        </div>
                                        <input type="text" class="form-control companyuser-tel-input" name="phone" id="phone" autocomplete="off" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="companyuser-phone-col <?= htmlspecialchars($phoneRowColClass, ENT_QUOTES, 'UTF-8') ?> mb-3">
                                <div class="form-group">
                                    <label for="cellphone"><?= $t('cell_number', '') ?>:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                        </div>
                                        <input type="text" class="form-control companyuser-tel-input" name="cellphone" id="cellphone" autocomplete="off" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="companyuser-phone-col <?= htmlspecialchars($phoneRowColClass, ENT_QUOTES, 'UTF-8') ?> mb-3">
                                <div class="form-group">
                                    <label for="fax"><?= $t('fax', '') ?>:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                        </div>
                                        <input type="text" class="form-control companyuser-tel-input" name="fax" id="fax" autocomplete="off" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="seller-fields" style="display:none;">
                            <div class="row">
                                <?php if (!empty($pt['dato_vendedor'])) : ?>
                                <div class="col-12 mb-2">
                                    <label><strong><?= htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper((string) $pt['dato_vendedor'], 'UTF-8') : strtoupper((string) $pt['dato_vendedor']), ENT_QUOTES, 'UTF-8') ?></strong></label>
                                </div>
                            <?php endif; ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label for="mobile"><?= $t('phone_number', $pt['telefono'] ?? '') ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="mobile" id="mobile">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label for="cellNumber"><?= $t('cell_number', '') ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="cellNumber" id="cellNumber">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label for="seller_fax"><?= $t('fax', '') ?></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                            </div>
                                            <input type="text" class="form-control companyuser-tel-input" name="seller_fax" id="seller_fax">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group d-flex align-items-center justify-content-between flex-wrap">
                                        <span class="mb-0 mr-2"><?= $t('email_on_salesman_order', '') ?></span>
                                        <div class="custom-control custom-switch mb-0">
                                            <input type="checkbox" class="custom-control-input" id="email_on_salesman_order_switch" data-seller-email-order-flag="1" aria-label="<?= $t('email_on_salesman_order', '') ?>">
                                            <label class="custom-control-label" for="email_on_salesman_order_switch"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group" id="seller-select">
                                        <label for="profile_seller"><?= $t('profile', '') ?></label>
                                        <select name="profile_id" id="profile_seller" class="form-control notranslate" disabled>
                                            <?php if (!empty($sellerProfiles)) : ?>
                                                <option disabled selected value=""><?= $t('select_profile', '') ?></option>
                                                <?php foreach ($sellerProfiles as $profile) : ?>
                                                    <option value="<?= (int) ($profile->profile_id ?? 0) ?>" <?= !empty($profile->default) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars((string) ($profile->profile_name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <option value=""><?= $t('no_profile_found', '') ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row align-items-center">
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.35rem 1.25rem;">
                                            <div class="custom-control custom-checkbox mb-0">
                                                <input type="checkbox" class="custom-control-input" name="all_customers" id="all_customers" value="1">
                                                <label class="custom-control-label" for="all_customers"><?= $t('all_customer', '') ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox mb-0">
                                                <input type="checkbox" class="custom-control-input" name="checkBoxDefault" id="seller-default" value="1">
                                                <label class="custom-control-label" for="seller-default"><?= $t('default', '') ?></label>
                                            </div>
                                            <div class="custom-control custom-checkbox mb-0">
                                                <input type="checkbox" class="custom-control-input" name="proforma" id="seller-proforma" value="1">
                                                <label class="custom-control-label" for="seller-proforma"><?= $t('proforma', '') ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_add',
                        'saveLabel' => (string) ($t('save', '')),
                        'cancelLabel' => (string) ($t('back', '')),
                        'cancelUrl' => company_url('users'),
                        'saveDisabled' => !empty($planLimitBlocked),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('company/users/_companyuser_phone_script', [
    'companyPhoneCountryMeta' => $companyPhoneCountryMeta ?? [],
    'companyUserCountryFlagsUrl' => $companyUserCountryFlagsUrl ?? '',
]); ?>
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
