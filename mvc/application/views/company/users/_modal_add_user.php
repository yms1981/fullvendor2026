<?php
/**
 * Modal alta usuario (listado). Requiere send_form.js y user_list_modal.js.
 *
 * @var array $pageTexts
 * @var string $currentType
 * @var object|null $company
 * @var array|object|null $sellerProfiles
 * @var array $userModalCustomers
 * @var array $userModalWarehouses
 * @var array $companyPhoneCountryMeta
 * @var string $companyUserCountryFlagsUrl
 * @var array $userTypes
 * @var array $userModalCustomerLabels
 * @var string $userModalListReloadUrl
 * @var array $planLimitBlock
 */
$pt = $pageTexts ?? [];
$planLimitBlock = isset($planLimitBlock) && is_array($planLimitBlock) ? $planLimitBlock : ['can_create' => true];
$planLimitBlocked = empty($planLimitBlock['can_create']);
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
$t = static function (string $key, string $fallback = '') use ($pt): string {
    return htmlspecialchars((string) ($pt[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
};
$rolePlaceholder = $t('select_role', $pt['label_select'] ?? '');
$currentType = isset($currentType) ? (string) $currentType : 'manager';
if (! in_array($currentType, ['manager', 'customer', 'seller', 'warehouse'], true)) {
    $currentType = 'manager';
}
$userTypes = isset($userTypes) && is_array($userTypes) ? $userTypes : ['manager', 'seller', 'warehouse', 'customer'];
$sellerProfiles = $sellerProfiles ?? [];
$userModalCustomers = isset($userModalCustomers) && is_array($userModalCustomers) ? $userModalCustomers : [];
$userModalWarehouses = isset($userModalWarehouses) && is_array($userModalWarehouses) ? $userModalWarehouses : [];
$userModalCustomerLabels = isset($userModalCustomerLabels) && is_array($userModalCustomerLabels) ? $userModalCustomerLabels : [];
$customerFieldLabel = trim((string) ($pt['user_modal_field_customer'] ?? ''));
if ($customerFieldLabel === '') {
    $customerFieldLabel = trim((string) ($pt['user_modal_select_customer'] ?? ''));
}
if ($customerFieldLabel === '') {
    $customerFieldLabel = trim((string) ($pt['user_modal_customer_field_label'] ?? ''));
}
if ($customerFieldLabel === '') {
    $customerFieldLabel = trim((string) ($pt['tab_customer'] ?? ''));
}
if ($customerFieldLabel === '') {
    $customerFieldLabel = trim((string) ($pt['user_confirm_label_related'] ?? ''));
}
?>
<script>
    window.hasDefaultSeller = <?= json_encode(isset($company->default_seller_id) ? $company->default_seller_id : null) ?>;
    window.companyUsersListUrl = <?= json_encode(
        (isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : ''),
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
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
        'user_validation_profile_required' => $pt['user_validation_profile_required'] ?? '',
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
        'first_name' => $pt['first_name'] ?? '',
        'last_name' => $pt['last_name'] ?? '',
        'address' => $pt['address'] ?? '',
        'city' => $pt['city'] ?? '',
        'state' => $pt['state'] ?? '',
        'zip_code' => $pt['zip_code'] ?? '',
        'user_section_personal_data' => $pt['user_section_personal_data'] ?? '',
        'user_section_address_data' => $pt['user_section_address_data'] ?? '',
        'user_modal_title' => $pt['user_modal_title'] ?? '',
        'user_modal_select_customer' => $pt['user_modal_select_customer'] ?? '',
        'user_modal_field_customer' => $pt['user_modal_field_customer'] ?? '',
        'user_modal_select_customer_placeholder' => $pt['user_modal_select_customer_placeholder'] ?? '',
        'user_modal_select_warehouse' => $pt['user_modal_select_warehouse'] ?? '',
        'user_modal_select_warehouse_placeholder' => $pt['user_modal_select_warehouse_placeholder'] ?? '',
        'user_modal_customer_card_title' => $pt['user_modal_customer_card_title'] ?? '',
        'user_modal_customer_loading' => $pt['user_modal_customer_loading'] ?? '',
        'user_modal_customer_error' => $pt['user_modal_customer_error'] ?? '',
        'user_modal_search_warehouse' => $pt['user_modal_search_warehouse'] ?? '',
        'user_modal_search_no_results' => $pt['user_modal_search_no_results'] ?? '',
        'tab_manager' => $pt['tab_manager'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.userModalCustomerLabels = <?= json_encode($userModalCustomerLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.userListModalConfig = <?= json_encode([
        'defaultRole' => $currentType,
        'customerDetailBaseUrl' => rtrim(company_url('users/customer_detail'), '/') . '/',
        'searchWarehouseUrl' => company_url('warehouses/searchWarehouse'),
        'warehouses' => array_values(array_filter(array_map(static function ($w) {
            if (! is_object($w)) {
                return null;
            }
            $id = (int) ($w->id ?? 0);
            if ($id < 1) {
                return null;
            }

            return [
                'id' => $id,
                'name' => trim((string) ($w->name ?? '')),
            ];
        }, $userModalWarehouses))),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<style>
    /* Extras del modal de usuario (shell global: fv-company-modals.css) */
    .fv-user-form-modal .modal-dialog.modal-add-user-compact {
        max-width: 36rem;
        margin: 1.5rem auto;
    }
    @media (min-width: 576px) { .fv-user-form-modal .modal-dialog.modal-add-user-compact { max-width: 42rem; } }
    @media (min-width: 992px) { .fv-user-form-modal .modal-dialog.modal-add-user-compact { max-width: 46rem; } }

    .fv-user-form-modal .modal-user-phones-row { margin-left: -0.35rem; margin-right: -0.35rem; }
    .fv-user-form-modal .modal-user-phones-row > [class*="col-"] { padding-left: 0.35rem; padding-right: 0.35rem; }
    .fv-user-form-modal .companyuser-tel-prefix {
        min-width: 3.4rem !important;
        padding-left: 0.4rem !important;
        padding-right: 0.4rem !important;
        background-color: #dc2626 !important;
        border-color: #dc2626 !important;
        border-right: 0;
        color: #fff !important;
        font-weight: 600;
        font-size: 0.8rem;
        border-radius: 0.5rem 0 0 0.5rem;
    }
    .fv-user-form-modal .modal-user-phones-row .form-control,
    .fv-user-form-modal .input-group .companyuser-tel-input {
        font-size: 0.875rem;
        border-radius: 0 0.5rem 0.5rem 0;
    }
    .fv-user-form-modal .modal-user-phones-row label {
        font-size: 0.7rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fv-user-form-modal .js-modal-toggle-password {
        border-color: #fecaca;
        border-left: 0;
        background-color: #fff;
        color: #dc2626;
        border-radius: 0 0.5rem 0.5rem 0;
    }
    .fv-user-form-modal .js-modal-toggle-password:hover { background-color: #dc2626; color: #fff; border-color: #dc2626; }
    .fv-user-form-modal .input-group > .form-control:not(:last-child) { border-radius: 0.5rem 0 0 0.5rem; }
    .fv-user-form-modal .passwodeye { position: relative; }
    .fv-user-form-modal .passwodeye .toggle-password {
        position: absolute;
        right: 0.75rem;
        top: 2.15rem;
        cursor: pointer;
        z-index: 2;
        color: #dc2626;
    }

    .fv-user-form-modal .fv-user-edit-modal-form { background: transparent; }
    .fv-user-form-modal #modal_edit_user_body .modal-footer {
        margin: 0.85rem -1.5rem -1.25rem;
        padding: 0.85rem 1.25rem;
    }

    .fv-user-form-modal .modal-user-two-col { margin-left: -0.4rem; margin-right: -0.4rem; }
    .fv-user-form-modal .modal-user-two-col > [class*="col-"] { padding-left: 0.4rem; padding-right: 0.4rem; }
    .fv-user-form-modal .modal-seller-phones-row { margin-left: -0.35rem; margin-right: -0.35rem; }
    .fv-user-form-modal .seller-modal-checks { gap: 0.5rem 1.25rem; }
    .fv-user-form-modal .seller-modal-checks .custom-control-label {
        font-size: 0.85rem;
        cursor: pointer;
        color: #7f1d1d;
        text-transform: none;
        font-weight: 500;
        letter-spacing: 0;
    }

    .fv-user-form-modal .customer-modal-card-preview .mdi { font-size: 1.05rem; vertical-align: middle; color: #dc2626; }
    .fv-user-form-modal .user-modal-customer-card {
        border: 1px solid #fecaca !important;
        border-radius: 0.5rem;
        background: #fff;
    }
    .fv-user-form-modal .user-modal-customer-card .card-header {
        background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
        color: #fff;
        border-bottom: 1px solid #b91c1c;
    }
    .fv-user-form-modal .user-modal-customer-card__row + .user-modal-customer-card__row { border-top: 1px solid #fee2e2 !important; }
    .fv-user-form-modal .user-modal-customer-card__icon .mdi { font-size: 1.35rem; line-height: 1; color: #dc2626; }
    .fv-user-form-modal .user-modal-customer-card__flag {
        vertical-align: middle;
        border: 1px solid #fecaca;
        border-radius: 2px;
        object-fit: cover;
    }

    .fv-user-form-modal .seller-email-switch-row {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        gap: 0.5rem 0.75rem;
        padding: 0.6rem 0.9rem;
        background-color: #fff;
        border: 1px solid #fecaca;
        border-left: 3px solid #dc2626;
        border-radius: 0.5rem;
    }
    .fv-user-form-modal .seller-email-switch-row .seller-email-switch-label {
        flex: 1 1 auto;
        font-size: 0.85rem;
        font-weight: 600;
        color: #7f1d1d;
        text-transform: none;
        letter-spacing: 0;
    }
</style>

<div class="modal fade fv-user-form-modal" id="modalAddUser" tabindex="-1" role="dialog" aria-hidden="true"
     data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-add-user-compact modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="modal-header__avatar" aria-hidden="true"><i class="mdi mdi-account-plus-outline"></i></span>
                    <span><?= $t('user_modal_title', $pt['add_user'] ?? '') ?></span>
                </h5>
                <button type="button"
                        class="close fv-modal-close-x"
                        data-dismiss="modal"
                        aria-label="<?= $t('back', $t('close', $t('cerrar', ''))) ?>">
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php $this->load->view('company/plan_limit_notice', ['planLimitBlock' => $planLimitBlock]); ?>
                <div id="manager-profile" class="d-none" aria-hidden="true"></div>
                <form class="forms-sample" id="form_add"
                      action="<?= company_url('users/save') ?>" method="post"
                      enctype="multipart/form-data"
                      data-user-create="1"
                      data-plan-limit-blocked="<?= $planLimitBlocked ? '1' : '0' ?>"
                      data-plan-limit-message="<?= htmlspecialchars((string) ($planLimitBlock['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row align-items-end modal-user-two-col">
                        <div class="col-6">
                            <div class="form-group mb-2">
                                <label for="name"><?= $t('name', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-account-outline" aria-hidden="true"></i>
                                    <input type="text" class="form-control" name="name" id="name" autocomplete="off" value="">
                                </div>
                            </div>
                        </div>
                        <div class="col-6" id="role-select-wrap">
                            <div class="form-group mb-2">
                                <label for="role"><?= $t('role', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-badge-account-outline" aria-hidden="true"></i>
                                    <select id="role" name="role" class="form-control">
                                        <option value="" disabled><?= $rolePlaceholder ?></option>
                                        <?php foreach ($userTypes as $value) :
                                            $v = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                                            $label = function_exists('mb_strtoupper') ? mb_strtoupper($v, 'UTF-8') : strtoupper($v);
                                            $sel = ($value === $currentType) ? 'selected' : '';
                                            ?>
                                            <option value="<?= $v ?>" <?= $sel ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-end modal-user-two-col">
                        <div class="col-6">
                            <div class="form-group mb-2">
                                <label for="email"><?= $t('email', '') ?>: <span class="text-danger">*</span></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-email-outline" aria-hidden="true"></i>
                                    <input type="text" class="form-control" name="email" id="email" autocomplete="off" value="">
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-2">
                                <label for="password"><?= $t('password', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-lock-outline" aria-hidden="true"></i>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="password" autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary px-2 js-modal-toggle-password" tabindex="-1" aria-label="<?= $t('password', '') ?>">
                                                <span class="fa fa-eye" aria-hidden="true"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row d-none" id="modal-customer-select-wrap">
                        <div class="col-12">
                            <div class="form-group mb-2">
                                <label for="modal-customer-select"><?= htmlspecialchars($customerFieldLabel, ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-control" id="modal-customer-select" autocomplete="off">
                                    <option value=""><?= $t('user_modal_select_customer_placeholder', '') ?></option>
                                    <?php foreach ($userModalCustomers as $c) :
                                        if (! is_object($c)) {
                                            continue;
                                        }
                                        $cid = (int) ($c->customer_id ?? 0);
                                        if ($cid < 1) {
                                            continue;
                                        }
                                        $cname = trim((string) ($c->name ?? ''));
                                        $cbiz = trim((string) ($c->business_name ?? ''));
                                        $label = $cname !== '' ? $cname : ('#' . $cid);
                                        if ($cbiz !== '' && strcasecmp($cbiz, $cname) !== 0) {
                                            $label .= ' — ' . $cbiz;
                                        }
                                        ?>
                                        <option value="<?= $cid ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row d-none" id="modal-warehouse-select-wrap">
                        <div class="col-12">
                            <div class="form-group mb-2">
                                <label for="modal-warehouse-select"><?= $t('user_modal_select_warehouse', '') ?></label>
                                <select class="form-control" id="modal-warehouse-select" autocomplete="off">
                                    <option value=""><?= $t('user_modal_select_warehouse_placeholder', '') ?></option>
                                    <?php foreach ($userModalWarehouses as $w) :
                                        if (! is_object($w)) {
                                            continue;
                                        }
                                        $wid = (int) ($w->id ?? 0);
                                        if ($wid < 1) {
                                            continue;
                                        }
                                        $wname = trim((string) ($w->name ?? ''));
                                        $label = $wname !== '' ? $wname : ('#' . $wid);
                                        ?>
                                        <option value="<?= $wid ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="related_id" id="related_id" value="">

                    <div class="modal-section-divider" aria-hidden="true"></div>

                    <div class="row align-items-end modal-user-two-col">
                        <div class="col-6">
                            <div class="form-group mb-2">
                                <label for="first_name"><?= $t('first_name', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-account-circle-outline" aria-hidden="true"></i>
                                    <input type="text" class="form-control" name="first_name" id="first_name" autocomplete="off" value="" placeholder="<?= $t('first_name_placeholder', $pt['first_name'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-2">
                                <label for="last_name"><?= $t('last_name', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-account-circle-outline" aria-hidden="true"></i>
                                    <input type="text" class="form-control" name="last_name" id="last_name" autocomplete="off" value="" placeholder="<?= $t('last_name_placeholder', $pt['last_name'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section-divider" aria-hidden="true"></div>

                    <div class="row align-items-end">
                        <div class="col-12">
                            <div class="form-group mb-2">
                                <label for="address"><?= $t('address', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-map-marker-outline" aria-hidden="true"></i>
                                    <input type="text" class="form-control" name="address" id="address" autocomplete="off" value="" placeholder="<?= $t('address_placeholder', $pt['address'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-end modal-user-two-col">
                        <div class="col-4">
                            <div class="form-group mb-2">
                                <label for="city"><?= $t('city', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-city-variant-outline" aria-hidden="true"></i>
                                    <input type="text" class="form-control" name="city" id="city" autocomplete="off" value="" placeholder="<?= $t('city_placeholder', $pt['city'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group mb-2">
                                <label for="state"><?= $t('state', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-map-outline" aria-hidden="true"></i>
                                    <input type="text" class="form-control" name="state" id="state" autocomplete="off" value="" placeholder="<?= $t('state_placeholder', $pt['state'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group mb-2">
                                <label for="zip_code"><?= $t('zip_code', '') ?></label>
                                <div class="modal-input-icon">
                                    <i class="mdi mdi-mailbox-outline" aria-hidden="true"></i>
                                    <input type="text" class="form-control" name="zip_code" id="zip_code" autocomplete="off" value="" placeholder="<?= $t('zip_code_placeholder', $pt['zip_code'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section-divider" aria-hidden="true"></div>

                    <div class="row align-items-end modal-user-phones-row" id="modal-user-main-phones-row">
                        <div class="col-4 companyuser-phone-col">
                            <div class="form-group mb-2">
                                <label for="phone"><?= $t('telefono', '') ?></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                    </div>
                                    <input type="text" class="form-control companyuser-tel-input" name="phone" id="phone" autocomplete="off" value="">
                                </div>
                            </div>
                        </div>
                        <div class="col-4 companyuser-phone-col">
                            <div class="form-group mb-2">
                                <label for="cellphone"><?= $t('cell_number', '') ?></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                    </div>
                                    <input type="text" class="form-control companyuser-tel-input" name="cellphone" id="cellphone" autocomplete="off" value="">
                                </div>
                            </div>
                        </div>
                        <div class="col-4 companyuser-phone-col">
                            <div class="form-group mb-2">
                                <label for="fax"><?= $t('fax', '') ?></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                    </div>
                                    <input type="text" class="form-control companyuser-tel-input" name="fax" id="fax" autocomplete="off" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-none" id="modal-customer-card-wrap">
                        <div class="card border mb-3">
                            <div class="card-header py-2">
                                <strong><?= $t('user_modal_customer_card_title', '') ?></strong>
                            </div>
                            <div class="card-body py-2" id="modal-customer-card-body">
                                <p class="text-muted mb-0 small" id="modal-customer-card-placeholder"></p>
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
                        </div>
                        <div class="row align-items-end modal-user-phones-row modal-seller-phones-row">
                            <div class="col-4 companyuser-phone-col">
                                <div class="form-group mb-2">
                                    <label for="mobile"><?= $t('phone_number', $pt['telefono'] ?? '') ?></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                        </div>
                                        <input type="text" class="form-control companyuser-tel-input" name="mobile" id="mobile">
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 companyuser-phone-col">
                                <div class="form-group mb-2">
                                    <label for="cellNumber"><?= $t('cell_number', '') ?></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2 companyuser-tel-prefix" style="min-width: 5.5rem;"></span>
                                        </div>
                                        <input type="text" class="form-control companyuser-tel-input" name="cellNumber" id="cellNumber">
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 companyuser-phone-col">
                                <div class="form-group mb-2">
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
                            <div class="col-12">
                                <div class="form-group mb-2 seller-email-switch-row">
                                    <div class="custom-control custom-switch flex-shrink-0 mb-0">
                                        <input type="checkbox" class="custom-control-input" id="email_on_salesman_order_switch" data-seller-email-order-flag="1" aria-label="<?= $t('email_on_salesman_order', '') ?>">
                                        <label class="custom-control-label" for="email_on_salesman_order_switch"></label>
                                    </div>
                                    <span class="seller-email-switch-label"><?= $t('email_on_salesman_order', '') ?></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2" id="seller-select">
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
                        <div class="form-group mb-2">
                            <div class="d-flex flex-wrap align-items-center seller-modal-checks">
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $t('back', '') ?></button>
                <button type="submit" class="btn btn-success" form="form_add"<?= $planLimitBlocked ? ' disabled' : '' ?>><?= $t('save', '') ?></button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('company/users/_companyuser_phone_script', [
    'companyPhoneCountryMeta' => $companyPhoneCountryMeta ?? [],
    'companyUserCountryFlagsUrl' => $companyUserCountryFlagsUrl ?? '',
]); ?>
