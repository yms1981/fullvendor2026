<?php
/**
 * @var $customer Object Customer
 * @var $termsOfSales array terms of sales
 * @var $customerGroups array customer groups
 * @var $sellers array sellers
 * @var $pageTexts array the translated string conglomerated for this view
 */

// Inicializar con objetos vacíos para evitar errores
$commercial = (object)['id' => null, 'street' => '', 'street_number' => '', 'country' => (object)['id' => null, 'name' => ''], 'state' => (object)['id' => null, 'name' => ''], 'city' => (object)['id' => null, 'name' => ''], 'zipcode' => '', 'zone' => '', 'state_id' => null, 'city_id' => null];
$dispatch   = (object)['id' => null, 'street' => '', 'street_number' => '', 'country' => (object)['id' => null, 'name' => ''], 'state' => (object)['id' => null, 'name' => ''], 'city' => (object)['id' => null, 'name' => ''], 'zipcode' => '', 'zone' => '', 'shipping_notes' => '', 'state_id' => null, 'city_id' => null];

if (!empty($customer->addresses)) {
    foreach ($customer->addresses as $address) {
        if (isset($address->pivot->type)) {
            switch ($address->pivot->type) {
                case 'commercial':
                    $commercial = $address;
                    break;
                case 'dispatch':
                    $dispatch = $address;
                    break;
            }
        }
    }
}

// Fallback: clientes importados / sin filas en address_customer usan columnas denormalizadas.
$commercialStreetVal = '';
if (!empty($commercial->street)) {
    $commercialStreetVal = trim((string) (($commercial->street ?? '') . ' ' . ($commercial->street_number ?? '')));
} elseif (!empty($customer->commercial_address)) {
    $commercialStreetVal = trim((string) $customer->commercial_address);
}

$dispatchStreetVal = '';
if (!empty($dispatch->street)) {
    $dispatchStreetVal = trim((string) (($dispatch->street ?? '') . ' ' . ($dispatch->street_number ?? '')));
} elseif (!empty($customer->dispatch_address)) {
    $dispatchStreetVal = trim((string) $customer->dispatch_address);
}

$commercialStateVal = '';
$commercialCityVal = '';
if (!empty($commercial) && is_object($commercial)) {
    if (!empty($commercial->state_text)) {
        $commercialStateVal = (string) $commercial->state_text;
    } elseif (!empty($commercial->state) && is_object($commercial->state) && !empty($commercial->state->name)) {
        $commercialStateVal = $commercial->state->name;
    } elseif (isset($commercial->state) && is_string($commercial->state) && trim($commercial->state) !== '') {
        $commercialStateVal = $commercial->state;
    } elseif (!empty($commercial->state_label)) {
        $commercialStateVal = (string) $commercial->state_label;
    }
    if (!empty($commercial->city_text)) {
        $commercialCityVal = (string) $commercial->city_text;
    } elseif (!empty($commercial->city) && is_object($commercial->city) && !empty($commercial->city->name)) {
        $commercialCityVal = $commercial->city->name;
    } elseif (isset($commercial->city) && is_string($commercial->city) && trim($commercial->city) !== '') {
        $commercialCityVal = $commercial->city;
    } elseif (!empty($commercial->city_label)) {
        $commercialCityVal = (string) $commercial->city_label;
    }
}
if ($commercialStateVal === '' && !empty($customer->commercial_state)) {
    $commercialStateVal = (string) $customer->commercial_state;
}
if ($commercialCityVal === '' && !empty($customer->commercial_city)) {
    $commercialCityVal = (string) $customer->commercial_city;
}

$dispatchStateVal = '';
$dispatchCityVal = '';
if (!empty($dispatch) && is_object($dispatch)) {
    if (!empty($dispatch->state_text)) {
        $dispatchStateVal = (string) $dispatch->state_text;
    } elseif (!empty($dispatch->state) && is_object($dispatch->state) && !empty($dispatch->state->name)) {
        $dispatchStateVal = $dispatch->state->name;
    } elseif (isset($dispatch->state) && is_string($dispatch->state) && trim($dispatch->state) !== '') {
        $dispatchStateVal = $dispatch->state;
    } elseif (!empty($dispatch->state_label)) {
        $dispatchStateVal = (string) $dispatch->state_label;
    }
    if (!empty($dispatch->city_text)) {
        $dispatchCityVal = (string) $dispatch->city_text;
    } elseif (!empty($dispatch->city) && is_object($dispatch->city) && !empty($dispatch->city->name)) {
        $dispatchCityVal = $dispatch->city->name;
    } elseif (isset($dispatch->city) && is_string($dispatch->city) && trim($dispatch->city) !== '') {
        $dispatchCityVal = $dispatch->city;
    } elseif (!empty($dispatch->city_label)) {
        $dispatchCityVal = (string) $dispatch->city_label;
    }
}
if ($dispatchStateVal === '' && !empty($customer->dispatch_state)) {
    $dispatchStateVal = (string) $customer->dispatch_state;
}
if ($dispatchCityVal === '' && !empty($customer->dispatch_city)) {
    $dispatchCityVal = (string) $customer->dispatch_city;
}

$commercialZipVal = !empty($commercial->zipcode)
    ? (string) $commercial->zipcode
    : (string) ($customer->commercial_zip_code ?? '');
$commercialZoneVal = !empty($commercial->zone)
    ? (string) $commercial->zone
    : (string) ($customer->commercial_zone ?? '');
$dispatchZipVal = !empty($dispatch->zipcode)
    ? (string) $dispatch->zipcode
    : (string) ($customer->dispatch_zip_code ?? '');
$dispatchZoneVal = !empty($dispatch->zone)
    ? (string) $dispatch->zone
    : (string) ($customer->dispatch_zone ?? '');
$dispatchNotesVal = !empty($dispatch->notes)
    ? (string) $dispatch->notes
    : (string) ($customer->dispatch_shipping_notes ?? '');

// País: pivot o match por nombre denormalizado
$hasCommercialCountryId = ($commercial && !empty($commercial->country) && !empty($commercial->country->id));
$commercialCountryNameFallback = trim((string) ($customer->commercial_country ?? ''));
$hasDispatchCountryId = ($dispatch && !empty($dispatch->country) && !empty($dispatch->country->id));
$dispatchCountryNameFallback = trim((string) ($customer->dispatch_country ?? ''));

$fnUpperVal = function ($s) {
    $s = (string) $s;
    return $s === '' ? '' : (function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s));
};

$companyDefaultCountryId = !empty($companyDefaultCountryId) ? (int) $companyDefaultCountryId : null;

// PNG de países (igual que country_flag_field / flag_avatar). Si en el servidor es /images/flag/ (sin s), cámbialo aquí.
$customerFormCountryFlagsUrl = rtrim((string) base_url(), '/') . '/images/flags/';
?>
<style>
    .fv-location-ac { position: relative; }
    .fv-location-suggestions {
        position: absolute;
        z-index: 1050;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        max-height: 220px;
        overflow-y: auto;
        display: none;
        box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.1);
    }
    .fv-location-suggestion {
        padding: 0.4rem 0.75rem;
        cursor: pointer;
        font-size: 0.9rem;
    }
    .fv-location-suggestion:hover {
        background-color: #f1f3f5;
    }
    /* Selects nativos: flecha y fondo para distinguirlos de inputs de texto */
    #form_edit select.fv-form-select:not([multiple]),
    #form_edit select.form-control:not([multiple]):not(.select2-hidden-accessible) {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-color: #f8fafc;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%236b7280' d='M1.41 0L6 4.58 10.59 0 12 1.41l-6 6-6-6z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.85rem center;
        background-size: 12px 8px;
        padding-right: 2.35rem;
        cursor: pointer;
        border-color: #cbd5e1;
    }
    #form_edit .select2-container--default .select2-selection--single {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        min-height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
    }
    #form_edit .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 6px;
    }
    #form_edit .select2-container--default .select2-selection--multiple {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        min-height: 42px;
        border-radius: 10px;
    }
</style>
<form id="form_edit" class="forms-sample"
      action="<?= company_url('customers/update/'.$customer->customer_id) ?>"
      method="post"
      data-customer-edit="1"
      novalidate>

    <input type="hidden" name="customer_id" value="<?= $customer->customer_id ?>">
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">

                <!-- Encabezado -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h3 class="page-title"><?= htmlspecialchars(fv_page_heading_text($pageTexts['titulo_editar'] ?? $pageTexts['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">

                            <!-- Datos Cliente -->
                            <div class="col-12 mb-2">
                                <div class="row align-items-center">
                                    <div class="col-12 col-md-6">
                                        <label><strong><?= strtoupper($pageTexts['dato_cliente'] ?? '') ?></strong></label>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-center justify-content-end flex-wrap">
                                            <div class="mr-2 mr-md-3 mb-1 mb-md-0">
                                                <label class="mb-0 mr-1" style="font-size: 0.85rem; font-weight: 500;">ID:</label>
                                                <input type="text" class="form-control form-control-sm d-inline-block" style="width: 70px; display: inline-block;" value="<?= $customer->customer_id ?>" disabled>
                                            </div>
                                            <div class="mb-1 mb-md-0">
                                                <label class="mb-0 mr-1" style="font-size: 0.85rem; font-weight: 500;"><?= htmlspecialchars($pageTexts['label_id_erp'] ?? 'ID ERP') ?></label>
                                                <input type="text" class="form-control form-control-sm d-inline-block" style="width: 72px; display: inline-block;" name="cust_id_kor" id="cust_id_kor"
                                                       value="<?= htmlspecialchars((string) ($customer->cust_id_kor ?? '')) ?>"
                                                       title="<?= htmlspecialchars($pageTexts['label_id_erp'] ?? '') ?>">
                                                <input type="number" class="form-control form-control-sm d-inline-block ml-1" style="width: 76px; display: inline-block;" name="id_kor" id="id_kor" min="0" step="1"
                                                       value="<?= (int) ($customer->id_kor ?? 0) ?>"
                                                       title="<?= htmlspecialchars($pageTexts['label_id_erp'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tax ID -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['label_contact_name'] ?? '' ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="name"
                                           value="<?= $customer->name ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['label_business_name'] ?? '' ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="business_name"
                                           value="<?= $customer->business_name ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="row">
                                    <div class="col-7">
                                        <div class="form-group">
                                            <label><?= $pageTexts['label_tax_id'] ?? '' ?>:</label>
                                            <input type="text" class="form-control" name="tax_id"
                                                   value="<?= $customer->tax_id ?>">
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label>% <?= $pageTexts['descuento'] ?>:</label>
                                            <input type="number" class="form-control" name="discount" min="0" max="100"
                                                   value="<?= $customer->discount ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php
                            $fvEmailOthersPreview = '';
                            $fvEmailsRaw = $customer->emails ?? [];
                            if (is_object($fvEmailsRaw)) {
                                $fvEmailsRaw = (array) $fvEmailsRaw;
                            }
                            if (is_array($fvEmailsRaw) && $fvEmailsRaw !== []) {
                                $fvOthers = [];
                                foreach ($fvEmailsRaw as $fvEmailRow) {
                                    $fvEmailRow = is_object($fvEmailRow) ? $fvEmailRow : (object) $fvEmailRow;
                                    if (! empty($fvEmailRow->is_primary)) {
                                        continue;
                                    }
                                    $fvOtherEmail = trim((string) ($fvEmailRow->email ?? ''));
                                    if ($fvOtherEmail !== '') {
                                        $fvOthers[] = $fvOtherEmail;
                                    }
                                }
                                $fvEmailOthersPreview = implode(', ', $fvOthers);
                            }
                            $fvEmailOthersTrunc = $fvEmailOthersPreview;
                            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                                if (mb_strlen($fvEmailOthersTrunc) > 48) {
                                    $fvEmailOthersTrunc = rtrim(mb_substr($fvEmailOthersTrunc, 0, 47), " ,") . '…';
                                }
                            } elseif (strlen($fvEmailOthersTrunc) > 48) {
                                $fvEmailOthersTrunc = rtrim(substr($fvEmailOthersTrunc, 0, 47), " ,") . '…';
                            }
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="fv-email-label-row d-flex flex-wrap align-items-baseline">
                                        <span><?= htmlspecialchars((string) ($pageTexts['label_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></span>
                                        <a href="javascript:void(0)"
                                           id="fv_email_others_preview"
                                           class="fv-email-others-preview ml-2"
                                           style="<?= $fvEmailOthersPreview === '' ? 'display:none;' : '' ?>"
                                           data-full="<?= htmlspecialchars($fvEmailOthersPreview, ENT_QUOTES, 'UTF-8') ?>"
                                           title="<?= htmlspecialchars($fvEmailOthersPreview !== '' ? $fvEmailOthersPreview : (string) ($pageTexts['emails_list_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($fvEmailOthersTrunc, ENT_QUOTES, 'UTF-8') ?></a>
                                    </label>
                                    <div class="input-group">
                                        <input type="hidden" name="email" id="email_hidden"
                                               value="<?= htmlspecialchars(trim((string) ($customer->email ?? ''))) ?>">
                                        <input type="email" class="form-control" id="email_field" autocomplete="email"
                                               value="<?= htmlspecialchars(trim((string) ($customer->email ?? ''))) ?>" disabled>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btn_change_email" title="<?= htmlspecialchars($pageTexts['emails_add'] ?? ($pageTexts['modal_change_email'] ?? '')) ?>">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php
                            $portalPasswordValue = '';
                            $hasPortalUser = false;
                            $portalUsersRaw = $customer->users ?? [];
                            if (is_object($portalUsersRaw)) {
                                $portalUsersRaw = (array) $portalUsersRaw;
                            }
                            $customerEmailLower = function_exists('mb_strtolower')
                                ? mb_strtolower(trim((string) ($customer->email ?? '')), 'UTF-8')
                                : strtolower(trim((string) ($customer->email ?? '')));
                            if (is_array($portalUsersRaw) && $portalUsersRaw !== [] && $customerEmailLower !== '') {
                                foreach ($portalUsersRaw as $portalUserRow) {
                                    $portalUserRow = is_object($portalUserRow) ? $portalUserRow : (object) $portalUserRow;
                                    $portalUserEmail = function_exists('mb_strtolower')
                                        ? mb_strtolower(trim((string) ($portalUserRow->email ?? '')), 'UTF-8')
                                        : strtolower(trim((string) ($portalUserRow->email ?? '')));
                                    if ($portalUserEmail === $customerEmailLower) {
                                        $hasPortalUser = true;
                                        $portalPasswordValue = trim((string) ($portalUserRow->password_plain ?? ''));
                                        break;
                                    }
                                }
                            }
                            $tPortalPwd = static function (string $key, string $fallback = '') use ($pageTexts): string {
                                $value = $pageTexts[$key] ?? '';
                                if (! is_string($value) || trim($value) === '') {
                                    $value = $fallback;
                                }

                                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                            };
                            $canManagePortalPassword = $customerEmailLower !== '' && (int) ($customer->customer_id ?? 0) > 0;
                            ?>
                            <!-- Password del usuario portal (mismo email en users) — solo lectura -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label for="customer_portal_password"><?= $tPortalPwd('password', 'Password') ?>:</label>
                                    <div class="input-group fv-portal-password-group">
                                        <input type="password"
                                               class="form-control bg-light"
                                               id="customer_portal_password"
                                               value="<?= htmlspecialchars($portalPasswordValue, ENT_QUOTES, 'UTF-8') ?>"
                                               readonly
                                               autocomplete="off"
                                               placeholder="<?= $tPortalPwd('password', 'Password') ?>"
                                            <?= $canManagePortalPassword ? '' : 'disabled' ?>>
                                        <div class="input-group-append">
                                            <button type="button"
                                                    class="btn btn-outline-secondary"
                                                    id="fv_portal_password_toggle"
                                                    title="<?= $tPortalPwd('portal_password_show', 'Show') ?>"
                                                    aria-label="<?= $tPortalPwd('portal_password_show', 'Show') ?>"
                                                <?= ($canManagePortalPassword && $portalPasswordValue !== '') ? '' : 'disabled' ?>>
                                                <i class="fa fa-eye" aria-hidden="true"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-primary"
                                                    id="fv_portal_password_change_btn"
                                                    title="<?= $tPortalPwd('portal_password_change', 'Change password') ?>"
                                                    aria-label="<?= $tPortalPwd('portal_password_change', 'Change password') ?>"
                                                <?= $canManagePortalPassword ? '' : 'disabled' ?>>
                                                <i class="fa-solid fa-key" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php if ($canManagePortalPassword && ! $hasPortalUser) : ?>
                                        <small class="form-text text-muted"><?= $tPortalPwd('portal_password_no_user', '') ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Teléfono (prefijo según país comercial o país de la compañía) -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['phone'] ?>:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2" id="phone_country_prefix" style="min-width: 5.5rem;"></span>
                                        </div>
                                        <input type="tel" class="form-control telInput" name="phone" id="customer_phone" autocomplete="off"
                                            value="<?= htmlspecialchars($customer->phone ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Celular -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['cell_phone'] ?>:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2" id="cell_country_prefix" style="min-width: 5.5rem;"></span>
                                        </div>
                                        <input type="text" class="form-control" name="cell_phone" id="customer_cell_phone"
                                               value="<?= htmlspecialchars($customer->cell_phone ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- IMPORTANTE: mantener multiple="multiple" y name="sellers[]" para permitir varios vendedores. No agregar clase js-example-basic-multiple (el footer la reinicializa). -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['seller_asigned'] ?>:</label><br>
                                    <select class="form-control w-100 customer-edit-sellers-multi fv-form-select" name="sellers[]" id="seller_asigned" multiple="multiple">
                                        <?php
                                        $sellerIds = isset($customer->sellers) && is_array($customer->sellers) ? array_column($customer->sellers, 'id') : [];
                                        foreach ($sellers as $seller) :
                                            if (empty($seller->user_id)) continue;
                                            if (in_array($seller->id, $sellerIds)) { $str_flag = 'selected'; } else { $str_flag = ''; }
                                        ?>
                                            <?php $sellerLabel = isset($seller->user->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($seller->user->name, 'UTF-8') : strtoupper($seller->user->name)) : ''; ?>
                                            <option value="<?= (int)$seller->id ?>" <?php echo $str_flag; ?>><?= htmlspecialchars($sellerLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Asignar Catálogo -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['assign_catalog_seller'] ?>:</label>
                                    <select class="form-control fv-form-select" name="seller_assign_catalog" id="seller_assign_catalog">
                                        <option selected disabled><?= $pageTexts['seleccionar'] ?></option>
                                        <?php foreach ($sellers as $seller) : ?>
                                            <?php if ($seller->user_id): ?>
                                                <?php $assignSellerLabel = isset($seller->user->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($seller->user->name, 'UTF-8') : strtoupper($seller->user->name)) : ''; ?>
                                                <option value="<?= $seller->id ?>" <?= $customer->seller_assign_catalog == $seller->id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($assignSellerLabel) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['termino_pago'] ?>:</label>
                                    <select class="form-control notranslate fv-form-select" name="term_id" id="customer_term_id">
                                        <option value="" disabled<?= empty($customer->term_id) ? ' selected' : '' ?>><?= $pageTexts['label_select'] ?? '' ?></option>
                                        <?php foreach ($termsOfSales as $tsale) : ?>
                                            <?php $termLabel = isset($tsale->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($tsale->name, 'UTF-8') : strtoupper($tsale->name)) : ''; ?>
                                            <option value="<?= $tsale->term_id ?>"
                                                <?= $customer->term_id == $tsale->term_id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($termLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['group'] ?>: <span class="color-red">*</span></label>
                                    <select class="form-control notranslate fv-form-select" name="group_id" id="customer_group_id">
                                        <option value="" disabled<?= empty($customer->group_id) ? ' selected' : '' ?>><?= $pageTexts['label_select'] ?? '' ?></option>
                                        <?php foreach ($customerGroups as $group) : ?>
                                            <?php $groupLabel = isset($group->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($group->name, 'UTF-8') : strtoupper($group->name)) : ''; ?>
                                            <option value="<?= $group->group_id ?>"
                                                <?= $customer->group_id == $group->group_id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($groupLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Enviar catálogo (debajo de Assign catalog order to) -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars((string) ($pageTexts['catalogue_emails'] ?? $pageTexts['enviar_catalogo'] ?? $pageTexts['field_catalog_emails'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</label>
                                    <select class="form-control notranslate fv-form-select" name="catalog_emails" id="customer_catalog_emails">
                                        <option value="1" <?= $customer->catalog_emails ? 'selected' : '' ?>><?= htmlspecialchars((string) ($pageTexts['si'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="0" <?= !$customer->catalog_emails ? 'selected' : '' ?>><?= htmlspecialchars((string) ($pageTexts['label_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                    </select>
                                </div>
                            </div>

                            <!-- Dato Comercial -->
                            <div class="col-12 mt-3 mb-2">
                                <label><strong><?= strtoupper($pageTexts['dato_comercial']) ?></strong></label>
                            </div>

                            <input type="hidden" name="commercial_address_id" value="<?= $commercial ? $commercial->id : '' ?>">

                            <!-- Dirección de entrega comercial (completa, solo lectura; se arma desde los campos) -->
                            <div class="col-12 mb-2">
                                <div class="form-group mb-0">
                                    <label><?= $pageTexts['commercial_delivery_address'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper bg-light" id="commercial_delivery_address" name="commercial_delivery_address"
                                           value="<?= htmlspecialchars($fnUpperVal($customer->commercial_delivery_address ?? ($commercial ? trim(($commercial->street ?? '') . ' ' . ($commercial->street_number ?? '')) : ''))) ?>"
                                           placeholder="<?= isset($pageTexts['placeholder_address_auto']) ? htmlspecialchars($pageTexts['placeholder_address_auto']) : '' ?>"
                                           readonly
                                           autocomplete="off">
                                </div>
                            </div>

                            <!-- Orden: Address, Country, State, City, ZipCode, Zone -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_address'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper" id="commercial_address" name="commercial_address"
                                           value="<?= htmlspecialchars($fnUpperVal($commercialStreetVal)) ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_country'] ?>:</label>
                                    <select class="form-control" id="commercial_country_id" name="commercial_country" data-flag-field="sortname">
                                        <option value=""><?= $pageTexts['placeholder_select_country'] ?? $pageTexts['label_select'] ?? '' ?></option>
                                        <?php if (!empty($countries)): ?>
                                            <?php foreach ($countries as $country): ?>
                                                <?php
                                                $flagClass = strtolower($country->sortname ?? '');
                                                $countryLabel = isset($country->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($country->name, 'UTF-8') : strtoupper($country->name)) : '';
                                                $selected = ($hasCommercialCountryId && (int) $commercial->country->id === (int) $country->id) ? 'selected' : '';
                                                if (!$selected && !$hasCommercialCountryId && $commercialCountryNameFallback !== '') {
                                                    $cname = isset($country->name) ? trim((string) $country->name) : '';
                                                    if ($cname !== '' && strcasecmp($cname, $commercialCountryNameFallback) === 0) {
                                                        $selected = 'selected';
                                                    }
                                                }
                                                if (!$selected && !$hasCommercialCountryId && $commercialCountryNameFallback === '' && $companyDefaultCountryId && (int) $country->id === $companyDefaultCountryId) {
                                                    $selected = 'selected';
                                                }
                                                ?>
                                                <option value="<?= (int) $country->id ?>" data-sortname="<?= htmlspecialchars($flagClass) ?>" data-phonecode="<?= (int) ($country->phonecode ?? 0) ?>" data-phonemask="<?= htmlspecialchars((string) ($country->phonemask ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>>
                                                    <?= htmlspecialchars($countryLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_state'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="commercial_state_id" name="commercial_state_id" autocomplete="off">
                                        <option value=""></option>
                                        <?php if (!empty($commercial->state_id) && $commercialStateVal !== ''): ?>
                                            <option value="<?= (int) $commercial->state_id ?>" selected><?= htmlspecialchars($fnUpperVal($commercialStateVal)) ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" id="commercial_state" name="commercial_state" value="<?= htmlspecialchars($fnUpperVal($commercialStateVal)) ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_city'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="commercial_city_id" name="commercial_city_id" autocomplete="off">
                                        <option value=""></option>
                                        <?php if (!empty($commercial->city_id) && $commercialCityVal !== ''): ?>
                                            <option value="<?= (int) $commercial->city_id ?>" selected><?= htmlspecialchars($fnUpperVal($commercialCityVal)) ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" id="commercial_city" name="commercial_city" value="<?= htmlspecialchars($fnUpperVal($commercialCityVal)) ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_zip_code'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="commercial_zip_code" name="commercial_zip_code" autocomplete="off">
                                        <option value=""></option>
                                        <?php if ($commercialZipVal !== ''): ?>
                                            <option value="<?= htmlspecialchars($fnUpperVal($commercialZipVal)) ?>" selected><?= htmlspecialchars($fnUpperVal($commercialZipVal)) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_zone'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper" name="commercial_zone" id="commercial_zone"
                                           value="<?= htmlspecialchars($fnUpperVal($commercialZoneVal)) ?>">
                                </div>
                            </div>

                            <div class="col-12 mt-2 mb-2">
                                <input id="asignar_datos" type="checkbox" value="1">
                                <label for="asignar_datos" class="mb-0">
                                    <u><?= htmlspecialchars((string) ($pageTexts['asignar_datos'] ?? ''), ENT_QUOTES, 'UTF-8') ?></u>
                                </label>
                            </div>

                            <!-- Dato Entrega -->
                            <div class="col-12 mt-3 mb-2">
                                <label><strong><?= strtoupper($pageTexts['dato_entrega']) ?></strong></label>
                            </div>

                            <input type="hidden" name="dispatch_address_id" value="<?= $dispatch ? $dispatch->id : '' ?>">

                            <!-- Delivery completo (solo lectura; misma lógica que comercial) -->
                            <div class="col-12 mb-2">
                                <div class="form-group mb-0">
                                    <label><?= htmlspecialchars((string) ($pageTexts['delivery_address_full'] ?? $pageTexts['dispatch_delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</label>
                                    <input type="text" class="form-control cust-address-upper bg-light" name="dispatch_delivery_address" id="dispatch_delivery_address"
                                           value="<?= htmlspecialchars($fnUpperVal($customer->dispatch_delivery_address ?? '')) ?>"
                                           placeholder="<?= isset($pageTexts['placeholder_address_auto']) ? htmlspecialchars($pageTexts['placeholder_address_auto']) : '' ?>"
                                           readonly
                                           autocomplete="off">
                                </div>
                            </div>

                            <!-- Orden: Address, Country, State, City, ZipCode, Zone (igual que comercial) -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars((string) ($pageTexts['dispatch_address'] ?? $pageTexts['commercial_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</label>
                                    <input type="text" class="form-control cust-address-upper" name="dispatch_address" id="dispatch_address"
                                           value="<?= htmlspecialchars($fnUpperVal($dispatchStreetVal)) ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_country'] ?>:</label>
                                    <select class="form-control" id="dispatch_country_id" name="dispatch_country" data-flag-field="sortname">
                                        <option value=""><?= $pageTexts['placeholder_select_country'] ?? $pageTexts['label_select'] ?? '' ?></option>
                                        <?php if (!empty($countries)): ?>
                                            <?php foreach ($countries as $country): ?>
                                                <?php
                                                $flagClass = strtolower($country->sortname ?? '');
                                                $countryLabel = isset($country->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($country->name, 'UTF-8') : strtoupper($country->name)) : '';
                                                $selected = ($hasDispatchCountryId && (int) $dispatch->country->id === (int) $country->id) ? 'selected' : '';
                                                if (!$selected && !$hasDispatchCountryId && $dispatchCountryNameFallback !== '') {
                                                    $cname = isset($country->name) ? trim((string) $country->name) : '';
                                                    if ($cname !== '' && strcasecmp($cname, $dispatchCountryNameFallback) === 0) {
                                                        $selected = 'selected';
                                                    }
                                                }
                                                ?>
                                                <option value="<?= (int) $country->id ?>" data-sortname="<?= htmlspecialchars($flagClass) ?>" data-phonecode="<?= (int) ($country->phonecode ?? 0) ?>" data-phonemask="<?= htmlspecialchars((string) ($country->phonemask ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>>
                                                    <?= htmlspecialchars($countryLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_state'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="dispatch_state_id" name="dispatch_state_id" autocomplete="off">
                                        <option value=""></option>
                                        <?php if (!empty($dispatch->state_id) && $dispatchStateVal !== ''): ?>
                                            <option value="<?= (int) $dispatch->state_id ?>" selected><?= htmlspecialchars($fnUpperVal($dispatchStateVal)) ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" id="dispatch_state" name="dispatch_state" value="<?= htmlspecialchars($fnUpperVal($dispatchStateVal)) ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_city'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="dispatch_city_id" name="dispatch_city_id" autocomplete="off">
                                        <option value=""></option>
                                        <?php if (!empty($dispatch->city_id) && $dispatchCityVal !== ''): ?>
                                            <option value="<?= (int) $dispatch->city_id ?>" selected><?= htmlspecialchars($fnUpperVal($dispatchCityVal)) ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" id="dispatch_city" name="dispatch_city" value="<?= htmlspecialchars($fnUpperVal($dispatchCityVal)) ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_zipcode'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="dispatch_zip_code" name="dispatch_zip_code" autocomplete="off">
                                        <option value=""></option>
                                        <?php if ($dispatchZipVal !== ''): ?>
                                            <option value="<?= htmlspecialchars($fnUpperVal($dispatchZipVal)) ?>" selected><?= htmlspecialchars($fnUpperVal($dispatchZipVal)) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_zone'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper" name="dispatch_zone" id="dispatch_zone"
                                           value="<?= htmlspecialchars($fnUpperVal($dispatchZoneVal)) ?>">
                                </div>
                            </div>

                            <!-- Notas -->
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label><?= $pageTexts['notes'] ?>:</label>
                                    <textarea class="form-control" rows="5" name="dispatch_shipping_notes"><?= htmlspecialchars((string) $dispatchNotesVal) ?></textarea>
                                </div>
                            </div>

                            <div class="col-12">
                                <hr class="fv-portal-users-sep my-2">
                                <?php $this->load->view('company/assigned_table', [
                                    'entity' => $customer,
                                    'entityType' => 'customer',
                                    'pageTexts' => $pageTexts ?? [],
                                    'countries' => $countries ?? [],
                                    'companyPhoneCountryMeta' => $companyPhoneCountryMeta ?? [],
                                    'companyDefaultCountryId' => $companyDefaultCountryId ?? null,
                                ]); ?>
                            </div>

                            <div class="col-12">
                                <?php $this->load->view('company/customer/customer_last_activity', [
                                    'pageTexts' => $pageTexts ?? [],
                                    'customerLastCatalogs' => $customerLastCatalogs ?? [],
                                    'customerLastOrders' => $customerLastOrders ?? [],
                                    'customerLastOrdersCurrency' => $customerLastOrdersCurrency ?? '',
                                    'company' => $company ?? null,
                                    'apiToken' => $apiToken ?? '',
                                    'apiBaseUrl' => $apiBaseUrl ?? '/api',
                                ]); ?>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</form>
<?php $this->load->view('components/crud/form_action_fab', [
    'formId' => 'form_edit',
    'saveLabel' => (string) ($pageTexts['guardar'] ?? ''),
    'cancelLabel' => (string) ($pageTexts['cancelar'] ?? ''),
    'cancelUrl' => company_url('customers'),
]); ?>
<?php $this->load->view('company/customer/customer_emails_table', [
    'customer' => $customer,
    'pageTexts' => $pageTexts,
    'apiToken' => $apiToken ?? '',
    'apiBaseUrl' => $apiBaseUrl ?? '/api',
]); ?>

<?php
$fvPortalPwdLangId = function_exists('fv_resolve_company_language_id') ? (int) fv_resolve_company_language_id() : 2;
$fvPortalPwdLocaleMap = [1 => 'en', 2 => 'es', 3 => 'pt', 4 => 'it', 5 => 'ru', 6 => 'de', 7 => 'zh'];
$fvPortalPwdLocale = $fvPortalPwdLocaleMap[$fvPortalPwdLangId] ?? 'en';
?>
<div class="modal fade" id="fv_portal_password_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars((string) ($pageTexts['portal_password_change'] ?? $pageTexts['password'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['cerrar'] ?? 'Close'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="fv_portal_password_new"><?= htmlspecialchars((string) ($pageTexts['portal_password_new'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="fv_portal_password_new" autocomplete="new-password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary js-fv-portal-pwd-eye" data-target="#fv_portal_password_new" tabindex="-1">
                                <i class="fa fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label for="fv_portal_password_confirm"><?= htmlspecialchars((string) ($pageTexts['portal_password_confirm'] ?? $pageTexts['confirm_password'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="fv_portal_password_confirm" autocomplete="new-password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary js-fv-portal-pwd-eye" data-target="#fv_portal_password_confirm" tabindex="-1">
                                <i class="fa fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-danger d-none" id="fv_portal_password_error"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars((string) ($pageTexts['cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="btn btn-primary" id="fv_portal_password_save_btn"><?= htmlspecialchars((string) ($pageTexts['guardar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
<script>
<?php
$fvPortalDefaultPhonecode = (int) ($companyPhoneCountryMeta['phonecode'] ?? 0);
if ($fvPortalDefaultPhonecode < 1 && !empty($companyDefaultCountryId) && !empty($countries)) {
    foreach ($countries as $fvPcCountry) {
        $fvPcCountry = is_object($fvPcCountry) ? $fvPcCountry : (object) $fvPcCountry;
        if ((int) ($fvPcCountry->id ?? 0) === (int) $companyDefaultCountryId) {
            $fvPortalDefaultPhonecode = (int) ($fvPcCountry->phonecode ?? 0);
            break;
        }
    }
}
?>
window.FV_PORTAL_PASSWORD = <?= json_encode([
    'customerId' => (int) ($customer->customer_id ?? 0),
    'apiToken' => (string) ($apiToken ?? ''),
    'apiBaseUrl' => rtrim((string) ($apiBaseUrl ?? '/api'), '/'),
    'appLocale' => $fvPortalPwdLocale,
    'defaultPhonecode' => $fvPortalDefaultPhonecode > 0 ? (string) $fvPortalDefaultPhonecode : '',
    'i18n' => [
        'show' => (string) ($pageTexts['portal_password_show'] ?? ''),
        'hide' => (string) ($pageTexts['portal_password_hide'] ?? ''),
        'change' => (string) ($pageTexts['portal_password_change'] ?? ''),
        'mismatch' => (string) ($pageTexts['error_password_not_match'] ?? ''),
        'required' => (string) ($pageTexts['portal_password_required'] ?? ''),
        'updated' => (string) ($pageTexts['portal_password_updated'] ?? ''),
        'no_user' => (string) ($pageTexts['portal_password_no_user'] ?? ''),
        'error' => (string) ($pageTexts['error_occurred'] ?? ''),
        'cancel' => (string) ($pageTexts['cancelar'] ?? ''),
        'portal_credentials_send' => (string) ($pageTexts['portal_credentials_send'] ?? ''),
        'portal_credentials_send_sms' => (string) ($pageTexts['portal_credentials_send_sms'] ?? ''),
        'portal_credentials_send_whatsapp' => (string) ($pageTexts['portal_credentials_send_whatsapp'] ?? ''),
        'portal_credentials_confirm' => (string) ($pageTexts['portal_credentials_confirm'] ?? ''),
        'portal_credentials_confirm_sms' => (string) ($pageTexts['portal_credentials_confirm_sms'] ?? ''),
        'portal_credentials_confirm_whatsapp' => (string) ($pageTexts['portal_credentials_confirm_whatsapp'] ?? ''),
        'portal_credentials_sent' => (string) ($pageTexts['portal_credentials_sent'] ?? ''),
        'portal_credentials_sent_sms' => (string) ($pageTexts['portal_credentials_sent_sms'] ?? ''),
        'portal_credentials_sent_whatsapp' => (string) ($pageTexts['portal_credentials_sent_whatsapp'] ?? ''),
        'portal_credentials_send_failed' => (string) ($pageTexts['portal_credentials_send_failed'] ?? ''),
        'portal_credentials_sending' => (string) ($pageTexts['portal_credentials_sending'] ?? ''),
        'portal_credentials_sending_sms' => (string) ($pageTexts['portal_credentials_sending_sms'] ?? ''),
        'portal_credentials_sending_whatsapp' => (string) ($pageTexts['portal_credentials_sending_whatsapp'] ?? ''),
        'portal_credentials_no_phone' => (string) ($pageTexts['portal_credentials_no_phone'] ?? ''),
        'portal_credentials_no_phonecode' => (string) ($pageTexts['portal_credentials_no_phonecode'] ?? ''),
        'btn_accept' => (string) ($pageTexts['btn_accept'] ?? $pageTexts['aceptar'] ?? ''),
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<script src="<?= dated_file_url('companyasset/company/customer/portal-password.js') ?>"></script>
<script src="<?= dated_file_url('companyasset/company/customer/send-portal-credentials.js') ?>"></script>

<?php
$userModalPageTexts = is_array($userModalPageTexts ?? null) ? $userModalPageTexts : [];
$userModalPageTexts = array_merge(is_array($pageTexts ?? null) ? $pageTexts : [], $userModalPageTexts);
$this->load->view('company/users/_modal_add_user', [
    'pageTexts' => $userModalPageTexts,
    'currentType' => 'customer',
    'company' => $company ?? null,
    'sellerProfiles' => $userModalSellerProfiles ?? [],
    'userModalCustomers' => $userModalCustomers ?? [],
    'userModalWarehouses' => [],
    'companyPhoneCountryMeta' => $companyPhoneCountryMeta ?? [],
    'companyUserCountryFlagsUrl' => $companyUserCountryFlagsUrl ?? (rtrim((string) base_url(), '/') . '/images/flags/'),
    'userTypes' => ['customer'],
    'userModalCustomerLabels' => $userModalCustomerLabels ?? [],
    'userModalListReloadUrl' => $userModalListReloadUrl ?? company_url('customers/edit/' . (int) ($customer->customer_id ?? 0)),
    'planLimitBlock' => $planLimitBlock ?? ['can_create' => true],
    'preselectedCustomerId' => (int) ($customer->customer_id ?? 0),
]);
$this->load->view('company/users/_modal_edit_user', [
    'pageTexts' => $userModalPageTexts,
]);
?>

<script>
$(document).ready(function() {
    var selPlaceholder = '<?= isset($pageTexts['label_select']) ? addslashes($pageTexts['label_select']) : (isset($pageTexts['placeholder_select_country']) ? addslashes($pageTexts['placeholder_select_country']) : '') ?>';
    var phCountry = '<?= isset($pageTexts['placeholder_select_country']) ? addslashes($pageTexts['placeholder_select_country']) : '' ?>';
    var companyDefaultCountryId = '<?= !empty($companyDefaultCountryId) ? (int) $companyDefaultCountryId : '' ?>';
    var countryFlagsBaseUrl = <?= json_encode($customerFormCountryFlagsUrl) ?>;
    if (typeof window.console !== 'undefined' && typeof window.console.log === 'function') {
        window.console.log('[company/customers/edit] countryFlagsBaseUrl', countryFlagsBaseUrl);
    }

    function countryFlagHtml(sortname) {
        var sn = String(sortname || '').toLowerCase().replace(/[^a-z-]/g, '');
        if (sn.length < 2) {
            return '';
        }
        var src = countryFlagsBaseUrl + sn + '.png';
        return '<img src="' + src.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="" style="display:inline-block;width:20px;height:15px;object-fit:cover;vertical-align:middle;border:1px solid #ddd;border-radius:2px;margin-right:8px" onerror="this.style.display=\'none\'" />';
    }

    function logCountryListWithFlagImages() {
        try {
            var c = window.console;
            if (typeof c === 'undefined') {
                return;
            }
            var seen = {};
            var rows = [];
            ['#commercial_country_id', '#dispatch_country_id'].forEach(function(selector) {
                var $select = $(selector);
                if (!$select.length) return;
                $select.find('option').each(function() {
                    var value = String($(this).attr('value') || '').trim();
                    if (!value || seen[value]) return;
                    seen[value] = true;
                    var sortname = String($(this).data('sortname') || '').toLowerCase().replace(/[^a-z-]/g, '');
                    var image = sortname.length >= 2 ? (countryFlagsBaseUrl + sortname + '.png') : '';
                    rows.push({
                        country_id: value,
                        country_name: $.trim($(this).text() || ''),
                        sortname: sortname,
                        flag_image_url: image
                    });
                });
            });
            var label = '[company/customers/edit] Lista de paises con URL de bandera';
            if (typeof c.log === 'function') {
                c.log(label + ' (n=' + rows.length + ', base=' + String(countryFlagsBaseUrl || '') + ')', rows);
            }
            if (typeof c.groupCollapsed === 'function' && typeof c.groupEnd === 'function') {
                c.groupCollapsed(label + ' (tabla)');
                if (typeof c.table === 'function') {
                    c.table(rows);
                }
                c.groupEnd();
            } else if (typeof c.table === 'function') {
                c.table(rows);
            }
        } catch (err) {
            if (typeof window.console !== 'undefined' && typeof window.console.warn === 'function') {
                window.console.warn('[company/customers/edit] logCountryListWithFlagImages', err);
            }
        }
    }
    logCountryListWithFlagImages();

    function updatePhonePrefix() {
        var cid = $('#commercial_country_id').val();
        if (!cid && companyDefaultCountryId) {
            cid = String(companyDefaultCountryId);
        }
        var $opt = cid ? $('#commercial_country_id option[value="' + cid + '"]') : $();
        if (!$opt.length) {
            $('#phone_country_prefix, #cell_country_prefix').html('<span class="text-muted small">—</span>');
            return;
        }
        var sortname = String($opt.data('sortname') || '').toLowerCase();
        var pc = parseInt($opt.data('phonecode'), 10);
        if (isNaN(pc)) {
            pc = 0;
        }
        var flag = countryFlagHtml(sortname);
        var html = flag + '<span class="ml-1 font-weight-bold">+' + pc + '</span>';
        $('#phone_country_prefix, #cell_country_prefix').html(html);
    }

    function nationalPhoneDigits(raw, phonecode, maskDigitCount) {
        var digits = String(raw || '').replace(/\D/g, '');
        if (!digits) {
            return '';
        }
        var pc = String(phonecode || '').replace(/\D/g, '');
        var need = parseInt(maskDigitCount, 10) || 0;
        if (pc && digits.indexOf(pc) === 0 && digits.length > pc.length) {
            var rest = digits.slice(pc.length);
            if (!need || rest.length === need || rest.length <= need) {
                return rest;
            }
            return rest.slice(0, need);
        }
        if (need > 0 && digits.length > need) {
            return digits.slice(-need);
        }
        return digits;
    }

    function updatePhoneInputMasks() {
        if (typeof Inputmask === 'undefined') {
            return false;
        }
        var cid = $('#commercial_country_id').val();
        if (!cid && companyDefaultCountryId) {
            cid = String(companyDefaultCountryId);
        }
        var $opt = cid ? $('#commercial_country_id option[value="' + cid + '"]') : $();
        var maskPh = '';
        var phoneCode = 0;
        if ($opt.length) {
            maskPh = ($opt.attr('data-phonemask') || '').trim();
            phoneCode = parseInt($opt.attr('data-phonecode'), 10);
            if (isNaN(phoneCode)) {
                phoneCode = 0;
            }
            if (!maskPh) {
                if (phoneCode === 1) {
                    maskPh = '(###) ###-####';
                }
            }
        }
        var phoneEl = document.getElementById('customer_phone');
        var cellEl = document.getElementById('customer_cell_phone');
        if (!phoneEl || !cellEl) {
            return true;
        }

        var maskDigitCount = (maskPh.match(/#/g) || []).length;
        var rawPhone = nationalPhoneDigits(phoneEl.value, phoneCode, maskDigitCount);
        var rawCell = nationalPhoneDigits(cellEl.value, phoneCode, maskDigitCount);

        try { Inputmask.remove(phoneEl); } catch (e) {}
        try { Inputmask.remove(cellEl); } catch (e) {}

        if (!maskPh) {
            phoneEl.value = rawPhone;
            cellEl.value = rawCell;
            return true;
        }

        var imMask = maskPh.replace(/#/g, '9');
        var opts = {
            mask: imMask,
            placeholder: '_',
            showMaskOnHover: false,
            autoUnmask: true,
            clearIncomplete: false,
            jitMasking: true
        };
        new Inputmask(opts).mask(phoneEl);
        new Inputmask(opts).mask(cellEl);

        if (phoneEl.inputmask && typeof phoneEl.inputmask.setValue === 'function') {
            phoneEl.inputmask.setValue(rawPhone);
        } else {
            $(phoneEl).val(rawPhone).trigger('input');
        }
        if (cellEl.inputmask && typeof cellEl.inputmask.setValue === 'function') {
            cellEl.inputmask.setValue(rawCell);
        } else {
            $(cellEl).val(rawCell).trigger('input');
        }
        return true;
    }

    function tryInitPhoneInputMasks() {
        return updatePhoneInputMasks();
    }

    function forceAddressUpper(el) {
        var v = el.value;
        var nv = v.toUpperCase();
        if (v !== nv) {
            var pos = el.selectionStart;
            el.value = nv;
            if (el.setSelectionRange) {
                try { el.setSelectionRange(pos, pos); } catch (e) {}
            }
        }
    }

    $(document).on('input', '.cust-address-upper', function() {
        forceAddressUpper(this);
    });

    function getCountryText(countryEl) {
        if (!countryEl.length) return '';
        var v = countryEl.val();
        if (!v) return '';
        var opt = countryEl.find('option[value="' + v + '"]');
        if (opt.length) return $.trim(opt.text());
        if (typeof countryEl.select2 === 'function') {
            var sel = countryEl.select2('data');
            if (sel && sel[0]) {
                var t = sel[0].text;
                if (t && typeof t === 'string') return $.trim(t);
                if (t && t.indexOf) return $.trim($('<div>').html(t).text());
            }
        }
        return '';
    }

    var usStateAbbrByName = {
        'ALABAMA': 'AL', 'ALASKA': 'AK', 'ARIZONA': 'AZ', 'ARKANSAS': 'AR',
        'CALIFORNIA': 'CA', 'COLORADO': 'CO', 'CONNECTICUT': 'CT', 'DELAWARE': 'DE',
        'FLORIDA': 'FL', 'GEORGIA': 'GA', 'HAWAII': 'HI', 'IDAHO': 'ID',
        'ILLINOIS': 'IL', 'INDIANA': 'IN', 'IOWA': 'IA', 'KANSAS': 'KS',
        'KENTUCKY': 'KY', 'LOUISIANA': 'LA', 'MAINE': 'ME', 'MARYLAND': 'MD',
        'MASSACHUSETTS': 'MA', 'MICHIGAN': 'MI', 'MINNESOTA': 'MN', 'MISSISSIPPI': 'MS',
        'MISSOURI': 'MO', 'MONTANA': 'MT', 'NEBRASKA': 'NE', 'NEVADA': 'NV',
        'NEW HAMPSHIRE': 'NH', 'NEW JERSEY': 'NJ', 'NEW MEXICO': 'NM', 'NEW YORK': 'NY',
        'NORTH CAROLINA': 'NC', 'NORTH DAKOTA': 'ND', 'OHIO': 'OH', 'OKLAHOMA': 'OK',
        'OREGON': 'OR', 'PENNSYLVANIA': 'PA', 'RHODE ISLAND': 'RI', 'SOUTH CAROLINA': 'SC',
        'SOUTH DAKOTA': 'SD', 'TENNESSEE': 'TN', 'TEXAS': 'TX', 'UTAH': 'UT',
        'VERMONT': 'VT', 'VIRGINIA': 'VA', 'WASHINGTON': 'WA', 'WEST VIRGINIA': 'WV',
        'WISCONSIN': 'WI', 'WYOMING': 'WY', 'DISTRICT OF COLUMBIA': 'DC'
    };

    /**
     * Para concatenar: solo sigla si existe; si no, solo el nombre (nunca "MI - MICHIGAN").
     */
    function normalizeStateForConcat(raw) {
        var s = $.trim(raw || '').toUpperCase();
        if (!s) {
            return '';
        }
        // "MI - MICHIGAN" / "MI — MICHIGAN" → MI
        var m = s.match(/^([A-Z0-9]{1,5})\s*[-–—]\s+(.+)$/);
        if (m) {
            return m[1];
        }
        if (/^[A-Z]{2}$/.test(s)) {
            return s;
        }
        if (usStateAbbrByName[s]) {
            return usStateAbbrByName[s];
        }
        return s;
    }

    function getStateForDelivery(prefix) {
        var $sel = $('#' + prefix + '_state_id');
        var $opt = $sel.find('option:selected');
        var code = $.trim($opt.attr('data-code') || '').toUpperCase();
        if (code) {
            return normalizeStateForConcat(code);
        }
        var full = $.trim($opt.attr('data-full-name') || '').toUpperCase();
        var fromHidden = $.trim($('#' + prefix + '_state').val() || '');
        var fromText = $.trim($opt.text() || '');
        return normalizeStateForConcat(code || fromHidden || full || fromText);
    }

    function buildDeliveryAddress(prefix) {
        // Orden fijo: Address, City, State(solo sigla o solo nombre), ZipCode, Country
        var address = (prefix === 'commercial') ? $.trim($('#commercial_address').val() || '') : $.trim($('#dispatch_address').val() || '');
        var city = $.trim($('#' + prefix + '_city').val() || '');
        var state = getStateForDelivery(prefix);
        var country = getCountryText($('#' + prefix + '_country_id'));
        if (country === phCountry) country = '';
        var zip = (prefix === 'commercial') ? $.trim($('#commercial_zip_code').val() || '') : $.trim($('#dispatch_zip_code').val() || '');

        var parts = [];
        if (address) parts.push(address.toUpperCase());
        if (city) parts.push(city.toUpperCase());
        if (state) parts.push(state.toUpperCase());
        if (zip) parts.push(zip.toUpperCase());
        if (country) parts.push(country.toUpperCase());
        return parts.join(', ');
    }

    function updateCommercialDeliveryAddress() {
        var $el = $('#commercial_delivery_address');
        if (!$el.length) {
            return;
        }
        $el.val(buildDeliveryAddress('commercial'));
    }
    window.updateCommercialDeliveryAddress = updateCommercialDeliveryAddress;

    function updateDispatchDeliveryAddress() {
        var $el = $('#dispatch_delivery_address');
        if (!$el.length) {
            return;
        }
        $el.val(buildDeliveryAddress('dispatch'));
    }
    window.updateDispatchDeliveryAddress = updateDispatchDeliveryAddress;

    function unlockEditableAddressFields() {
        // Los campos largos (delivery full) permanecen readonly; el resto editables.
        $('#commercial_address, #commercial_zone')
            .prop('readonly', false)
            .removeAttr('readonly')
            .removeClass('bg-light');
        $('#dispatch_address, #dispatch_zone')
            .prop('readonly', false)
            .prop('disabled', false)
            .removeAttr('readonly')
            .removeAttr('disabled')
            .removeClass('bg-light');
        $('#commercial_state_id, #commercial_city_id, #commercial_zip_code, #dispatch_state_id, #dispatch_city_id, #dispatch_zip_code')
            .prop('disabled', false)
            .removeAttr('disabled');
        $('#commercial_delivery_address, #dispatch_delivery_address')
            .prop('readonly', true)
            .attr('readonly', 'readonly')
            .addClass('bg-light');
    }
    window.unlockEditableAddressFields = unlockEditableAddressFields;

    function applyLocationCascade(prefix) {
        // Cascada real: locationCascadeSelects.js (país → estado → ciudad → zip)
        unlockEditableAddressFields();
    }

    function initCountrySelect(selector) {
        var $el = $(selector);
        if (!$el.length || typeof $.fn.select2 === 'undefined') {
            return false;
        }
        try { $el.select2('destroy'); } catch (e) {}
        $el.select2({
            placeholder: phCountry || selPlaceholder,
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownParent: $(document.body),
            matcher: function(params, data) {
                if ($.trim(params.term || '') === '') {
                    return data;
                }
                if (typeof data.text === 'undefined' || data.children) {
                    return null;
                }
                var term = params.term.toLowerCase();
                var raw = (typeof data.text === 'string') ? data.text : '';
                var txt = $('<div/>').html(raw).text().toLowerCase();
                var el = data.element;
                var sortname = el ? String($(el).data('sortname') || '').toLowerCase() : '';
                if (txt.indexOf(term) > -1 || sortname.indexOf(term) > -1) {
                    return data;
                }
                return null;
            },
            templateResult: function(country) {
                if (!country.id) {
                    return country.text;
                }
                var $opt = $(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $('<span/>').text(($.trim($opt.text()) || '').toUpperCase()).html();
                var fh = countryFlagHtml(sortname);
                if (fh) {
                    return $('<span class="d-inline-flex align-items-center"/>').html(fh + labelUp);
                }
                return $('<span/>').html(labelUp);
            },
            templateSelection: function(country) {
                if (!country.id) {
                    return country.text;
                }
                var $opt = $(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $('<span/>').text(($.trim($opt.text()) || '').toUpperCase()).html();
                var fh = countryFlagHtml(sortname);
                if (fh) {
                    return $('<span class="d-inline-flex align-items-center"/>').html(fh + labelUp);
                }
                return $('<span/>').html(labelUp);
            },
            escapeMarkup: function(m) { return m; }
        });
        return true;
    }

    function ensureCountryDefaultSelected(selector) {
        if (!companyDefaultCountryId) {
            return;
        }
        var $sel = $(selector);
        if (!$sel.length) {
            return;
        }
        var current = String($sel.val() || '');
        if (current !== '' && current !== '0') {
            return;
        }
        var $opt = $sel.find('option[value="' + String(companyDefaultCountryId) + '"]');
        if (!$opt.length) {
            return;
        }
        $sel.val(String(companyDefaultCountryId));
        if (typeof $.fn.select2 !== 'undefined') {
            $sel.trigger('change.select2');
        }
        $sel.trigger('change');
    }

    function tryInitCountrySelects() {
        if (typeof $.fn.select2 === 'undefined') {
            return false;
        }
        initCountrySelect('#commercial_country_id');
        initCountrySelect('#dispatch_country_id');
        ensureCountryDefaultSelected('#commercial_country_id');
        return true;
    }

    function runPhoneMasksAfterCountrySelects() {
        updatePhonePrefix();
        if (!tryInitPhoneInputMasks()) {
            var masAttempts = 0, masMax = 60;
            var imv = setInterval(function() {
                if (tryInitPhoneInputMasks() || ++masAttempts >= masMax) {
                    clearInterval(imv);
                }
            }, 100);
        }
    }

    if (!tryInitCountrySelects()) {
        var ctryAttempts = 0;
        var ctryIv = setInterval(function() {
            if (tryInitCountrySelects() || ++ctryAttempts >= 60) {
                clearInterval(ctryIv);
                runPhoneMasksAfterCountrySelects();
            }
        }, 100);
    } else {
        runPhoneMasksAfterCountrySelects();
    }

    $(document).on('select2:open', '#commercial_country_id, #dispatch_country_id', function() {
        setTimeout(function() {
            var $f = $('.select2-container--open .select2-search__field');
            if ($f.length) {
                $f[0].focus();
            }
        }, 0);
    });

    $('#commercial_country_id').on('change', function() {
        updatePhonePrefix();
        updatePhoneInputMasks();
        updateCommercialDeliveryAddress();
    });

    $('#commercial_address, #commercial_zone').on('input change', function() {
        updateCommercialDeliveryAddress();
    });
    $('#dispatch_address, #dispatch_zone').on('input change', function() {
        updateDispatchDeliveryAddress();
    });

    setTimeout(function() {
        updatePhonePrefix();
        tryInitPhoneInputMasks();
        updateCommercialDeliveryAddress();
        updateDispatchDeliveryAddress();
        unlockEditableAddressFields();
    }, 0);

    function unlockDispatchAddressFields() {
        unlockEditableAddressFields();
    }

    $('#asignar_datos').on('change', function() {
        unlockEditableAddressFields();
        if (!$(this).is(':checked')) {
            return;
        }
        if (typeof window.fvCopyCommercialAddressToDispatch === 'function') {
            window.fvCopyCommercialAddressToDispatch();
            return;
        }
        $('input[name="dispatch_address"]').val($('input[name="commercial_address"]').val());
        $('input[name="dispatch_zone"]').val($('input[name="commercial_zone"]').val());
        $('#dispatch_state').val($('#commercial_state').val() || '');
        $('#dispatch_city').val($('#commercial_city').val() || '');
        $('#dispatch_state_id').html($('#commercial_state_id').html()).val($('#commercial_state_id').val());
        $('#dispatch_city_id').html($('#commercial_city_id').html()).val($('#commercial_city_id').val());
        $('#dispatch_zip_code').html($('#commercial_zip_code').html()).val($('#commercial_zip_code').val());
        var cId = $('#commercial_country_id').val();
        $('#dispatch_country_id').val(cId || '');
        if (typeof $.fn.select2 !== 'undefined') {
            $('#dispatch_country_id').trigger('change.select2');
            try { $('#dispatch_state_id').trigger('change.select2'); } catch (e1) {}
            try { $('#dispatch_city_id').trigger('change.select2'); } catch (e2) {}
            try { $('#dispatch_zip_code').trigger('change.select2'); } catch (e3) {}
        }
        updateDispatchDeliveryAddress();
    });

    $('#form_edit').on('submit', function() {
        $('.cust-address-upper').each(function() { forceAddressUpper(this); });
    });
    
    // El alta/cambio de emails se gestiona en la grilla (customer-emails.js).
    var apiToken = '<?= isset($apiToken) ? $apiToken : "" ?>';
    var apiBaseUrl = '<?= isset($apiBaseUrl) ? $apiBaseUrl : "/api" ?>';

    // Seller Assigned: multiselect. Select2 se carga en footer (CoreLoader async); esperar a que esté disponible.
    function initSellerMultiselect() {
        var $el = $('#form_edit #seller_asigned');
        if (!$el.length || typeof $el.select2 !== 'function') return false;
        try { $el.select2('destroy'); } catch (e) {}
        $el.select2({
            placeholder: '<?= isset($pageTexts['seleccionar_uno_mas']) ? addslashes($pageTexts['seleccionar_uno_mas']) : (isset($pageTexts['seleccionar']) ? addslashes($pageTexts['seleccionar']) : '') ?>',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            templateResult: function(opt) {
                if (!opt.id) return opt.text;
                return $('<span/>').text(($.trim($(opt.element).text()) || '').toUpperCase());
            },
            templateSelection: function(opt) {
                if (!opt.id) return opt.text;
                return $('<span/>').text(($.trim($(opt.element).text()) || '').toUpperCase());
            }
        });
        return true;
    }

    function initCustomerSingleSelects() {
        if (typeof $.fn.select2 !== 'function') {
            return false;
        }
        var selPlaceholder = '<?= isset($pageTexts['label_select']) ? addslashes($pageTexts['label_select']) : (isset($pageTexts['seleccionar']) ? addslashes($pageTexts['seleccionar']) : '') ?>';
        var ids = ['#customer_catalog_emails', '#seller_assign_catalog', '#customer_term_id', '#customer_group_id'];
        ids.forEach(function(sel) {
            var $el = $('#form_edit ' + sel);
            if (!$el.length) {
                return;
            }
            try { $el.select2('destroy'); } catch (e) {}
            $el.select2({
                width: '100%',
                minimumResultsForSearch: Infinity,
                placeholder: selPlaceholder,
                allowClear: false
            });
        });
        return true;
    }

    function initCustomerSelectWidgets() {
        var okMulti = initSellerMultiselect();
        var okSingle = initCustomerSingleSelects();
        return okMulti && okSingle;
    }

    if (!initCustomerSelectWidgets()) {
        var attempts = 0, maxAttempts = 50;
        var iv = setInterval(function() {
            if (initCustomerSelectWidgets() || ++attempts >= maxAttempts) clearInterval(iv);
        }, 100);
    }
});
</script>
