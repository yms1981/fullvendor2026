<?php
/**
 * @var $termsOfSales array terms of sales
 * @var $customerGroups array customer groups
 * @var $sellers array sellers
 * @var $pageTexts array the translated string conglomerated for this view
 */
$fnUpperVal = function ($s) {
    $s = (string) $s;
    return $s === '' ? '' : (function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s));
};
$companyDefaultCountryId = !empty($companyDefaultCountryId) ? (int) $companyDefaultCountryId : null;
// PNG de países (listas usan /images/flags/). Si tu servidor usa /images/flag/, ajusta aquí.
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
    #form_add select.fv-form-select:not([multiple]),
    #form_add select.form-control:not([multiple]):not(.select2-hidden-accessible) {
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
    #form_add .select2-container--default .select2-selection--single {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        min-height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
    }
    #form_add .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 6px;
    }
    #form_add .select2-container--default .select2-selection--multiple {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        min-height: 42px;
        border-radius: 10px;
    }
</style>
<?php
$planLimitBlock = isset($planLimitBlock) && is_array($planLimitBlock) ? $planLimitBlock : ['can_create' => true];
$planLimitBlocked = empty($planLimitBlock['can_create']);
?>
<form id="form_add" class="forms-sample"
      action="<?= company_url('customers/save') ?>"
      method="post"
      novalidate
      data-plan-limit-blocked="<?= $planLimitBlocked ? '1' : '0' ?>"
      data-plan-limit-message="<?= htmlspecialchars((string) ($planLimitBlock['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      data-msg-email-invalid="<?= htmlspecialchars((string) ($pageTexts['customer_add_validation_email_invalid'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      data-msg-email-required="<?= htmlspecialchars((string) ($pageTexts['customer_add_validation_email_required'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">

                <!-- Encabezado -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h3 class="page-title"><?= $pageTexts['titulo'] ?></h3>
                    </div>
                </div>

                <?php $this->load->view('company/plan_limit_notice', ['planLimitBlock' => $planLimitBlock]); ?>

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
                                        <div class="d-flex align-items-center justify-content-md-end flex-wrap mt-2 mt-md-0">
                                            <span class="mr-2 mb-1 mb-md-0" style="font-size: 0.85rem; font-weight: 500;"><?= htmlspecialchars($pageTexts['label_id_erp'] ?? 'ID ERP') ?></span>
                                            <input type="text" class="form-control form-control-sm d-inline-block mb-1 mb-md-0" style="width: 72px;" name="cust_id_kor" id="cust_id_kor"
                                                   value="<?= htmlspecialchars((string) set_value('cust_id_kor')) ?>">
                                            <input type="number" class="form-control form-control-sm d-inline-block ml-md-1 mb-1 mb-md-0" style="width: 76px;" name="id_kor" id="id_kor" min="0" step="1"
                                                   value="<?= htmlspecialchars((string) (set_value('id_kor') !== '' ? set_value('id_kor') : '0')) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tax ID -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['label_contact_name'] ?? '' ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="name"
                                           value="<?= htmlspecialchars((string) set_value('name')) ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['label_business_name'] ?? '' ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="business_name"
                                           value="<?= htmlspecialchars((string) set_value('business_name')) ?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="row">
                                    <div class="col-7">
                                        <div class="form-group">
                                            <label><?= $pageTexts['label_tax_id'] ?? '' ?>:</label>
                                            <input type="text" class="form-control" name="tax_id"
                                                   value="<?= set_value('tax_id'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label>% <?= $pageTexts['descuento'] ?>:</label>
                                            <input type="number" class="form-control" name="discount" min="0" max="100"
                                                   value="<?= set_value('discount'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['label_email'] ?? '' ?>: <span class="color-red">*</span></label>
                                    <input type="email"
                                           class="form-control"
                                           name="email"
                                           required
                                           autocomplete="email"
                                           inputmode="email"
                                           maxlength="255"
                                           pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                                           title="<?= htmlspecialchars((string) ($pageTexts['customer_add_validation_email_invalid'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           value="<?= htmlspecialchars((string) set_value('email')) ?>">
                                </div>
                            </div>

                            <!-- Teléfono -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['phone'] ?>:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text px-2" id="phone_country_prefix" style="min-width: 5.5rem;"></span>
                                        </div>
                                        <input type="tel" class="form-control telInput" name="phone" id="customer_phone" autocomplete="off"
                                               value="<?= htmlspecialchars(set_value('phone')) ?>">
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
                                               value="<?= htmlspecialchars(set_value('cell_phone')) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Enviar catálogo -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars((string) ($pageTexts['catalogue_emails'] ?? $pageTexts['enviar_catalogo'] ?? $pageTexts['field_catalog_emails'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</label>
                                    <select class="form-control notranslate fv-form-select" name="catalog_emails" id="customer_catalog_emails">
                                        <option value="1"><?= htmlspecialchars((string) ($pageTexts['si'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="0"><?= htmlspecialchars((string) ($pageTexts['label_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                    </select>
                                </div>
                            </div>

                            <!-- IMPORTANTE: mantener multiple="multiple" y name="sellers[]" para permitir varios vendedores. No agregar clase js-example-basic-multiple (el footer la reinicializa). -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['seller_asigned'] ?>:</label><br>
                                    <select class="form-control w-100 customer-add-sellers-multi fv-form-select" name="sellers[]" id="seller_asigned" multiple="multiple">
                                        <?php
                                        $sellerIds = [];
                                        foreach ($sellers as $seller) :
                                            if (empty($seller->user_id)) continue;
                                            if (!empty($seller->isDefault) || in_array($seller->id, $sellerIds)) { $str_flag = 'selected'; } else { $str_flag = ''; }
                                        ?>
                                            <?php $sellerLabelAdd = isset($seller->user->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($seller->user->name, 'UTF-8') : strtoupper($seller->user->name)) : ''; ?>
                                            <option value="<?= (int)$seller->id ?>" <?php echo $str_flag; ?>><?= htmlspecialchars($sellerLabelAdd) ?></option>
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
                                                <option value="<?= $seller->id ?>">
                                                    <?= $seller->user->name ?>
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
                                        <option selected disabled value=""><?= $pageTexts['label_select'] ?? '' ?></option>
                                        <?php foreach ($termsOfSales as $tsale) : ?>
                                            <?php $termLabelAdd = isset($tsale->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($tsale->name, 'UTF-8') : strtoupper($tsale->name)) : ''; ?>
                                            <option value="<?= $tsale->term_id ?>"
                                                <?= $tsale->isDefault ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($termLabelAdd) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['group'] ?>: <span class="color-red">*</span></label>
                                    <select class="form-control notranslate fv-form-select" name="group_id" id="customer_group_id">
                                        <option selected disabled value=""><?= $pageTexts['label_select'] ?? '' ?></option>
                                        <?php foreach ($customerGroups as $group) : ?>
                                            <?php $groupLabelAdd = isset($group->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($group->name, 'UTF-8') : strtoupper($group->name)) : ''; ?>
                                            <option value="<?= $group->group_id ?>"
                                                <?= $group->isDefault ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($groupLabelAdd) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Dato Comercial -->
                            <div class="col-12 mt-3 mb-2">
                                <label><strong><?= strtoupper($pageTexts['dato_comercial'] ?? '') ?></strong></label>
                            </div>

                            <!-- Dirección Comercial -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_address'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper" id="commercial_address" name="commercial_address"
                                           value="<?= htmlspecialchars($fnUpperVal(set_value('commercial_address'))) ?>">
                                </div>
                            </div>

                            <!-- Dirección Comercial de Entrega (completa, solo lectura) -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_delivery_address'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper bg-light" id="commercial_delivery_address" name="commercial_delivery_address"
                                           value="<?= htmlspecialchars($fnUpperVal(set_value('commercial_delivery_address'))) ?>"
                                           placeholder="<?= isset($pageTexts['placeholder_address_auto']) ? htmlspecialchars($pageTexts['placeholder_address_auto']) : '' ?>"
                                           readonly
                                           autocomplete="off">
                                </div>
                            </div>

                            <!-- País Comercial -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_country'] ?>:</label>
                                    <select class="form-control location-select" id="commercial_country_id" name="commercial_country">
                                        <option value=""><?= $pageTexts['label_select'] ?? $pageTexts['placeholder_select_country'] ?? '' ?></option>
                                        <?php if (!empty($countries)): ?>
                                            <?php foreach ($countries as $country): ?>
                                                <?php
                                                $sn = strtolower($country->sortname ?? '');
                                                $countryLabelAdd = isset($country->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($country->name, 'UTF-8') : strtoupper($country->name)) : '';
                                                $selected = ($companyDefaultCountryId && (int) ($country->id ?? $country->country_id ?? 0) === $companyDefaultCountryId) ? 'selected' : '';
                                                ?>
                                                <option value="<?= (int)($country->id ?? $country->country_id ?? 0) ?>" data-sortname="<?= htmlspecialchars($sn) ?>" data-phonecode="<?= (int)($country->phonecode ?? 0) ?>" data-phonemask="<?= htmlspecialchars((string) ($country->phonemask ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>><?= htmlspecialchars($countryLabelAdd) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Estado Comercial -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_state'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="commercial_state_id" name="commercial_state_id" autocomplete="off">
                                        <option value=""></option>
                                    </select>
                                    <input type="hidden" id="commercial_state" name="commercial_state" value="<?= htmlspecialchars($fnUpperVal(set_value('commercial_state'))) ?>">
                                </div>
                            </div>

                            <!-- Código Postal y Ciudad (ZIP antes que ciudad) -->
                            <div class="col-md-6 col-lg-4">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label><?= $pageTexts['commercial_zip_code'] ?>:</label>
                                            <select class="form-control fv-geo-select" id="commercial_zip_code" name="commercial_zip_code" autocomplete="off">
                                                <option value=""></option>
                                                <?php if (set_value('commercial_zip_code')): ?>
                                                    <option value="<?= htmlspecialchars($fnUpperVal(set_value('commercial_zip_code'))) ?>" selected><?= htmlspecialchars($fnUpperVal(set_value('commercial_zip_code'))) ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-7">
                                        <div class="form-group">
                                            <label><?= $pageTexts['commercial_city'] ?>:</label>
                                            <select class="form-control fv-geo-select" id="commercial_city_id" name="commercial_city_id" autocomplete="off">
                                                <option value=""></option>
                                            </select>
                                            <input type="hidden" id="commercial_city" name="commercial_city" value="<?= htmlspecialchars($fnUpperVal(set_value('commercial_city'))) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Zona Comercial -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['commercial_zone'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper" name="commercial_zone" id="commercial_zone"
                                           value="<?= htmlspecialchars($fnUpperVal(set_value('commercial_zone'))) ?>">
                                </div>
                            </div>

                            <div class="col-12 mt-2 mb-2">
                                <input id="asignar_datos" type="checkbox" value="1">
                                <label for="asignar_datos" class="mb-0">
                                    <u><?= $pageTexts['asignar_datos'] ?>:</u>
                                </label>
                            </div>

                            <!-- Dato Entrega -->
                            <div class="col-12 mt-3 mb-2">
                                <label><strong><?= strtoupper($pageTexts['dato_entrega'] ?? '') ?></strong></label>
                            </div>

                            <!-- Dirección de entrega completa (primera línea, solo lectura) -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label><?= $pageTexts['delivery_address_full'] ?? '' ?>:</label>
                                    <input type="text" class="form-control cust-address-upper bg-light" name="dispatch_delivery_address" id="dispatch_delivery_address"
                                           value="<?= htmlspecialchars($fnUpperVal(set_value('dispatch_delivery_address'))) ?>"
                                           placeholder="<?= isset($pageTexts['placeholder_address_auto']) ? htmlspecialchars($pageTexts['placeholder_address_auto']) : '' ?>"
                                           readonly
                                           autocomplete="off">
                                </div>
                            </div>

                            <!-- Dirección Entrega (calle) -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_delivery_address'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper" name="dispatch_address" id="dispatch_address"
                                           value="<?= htmlspecialchars($fnUpperVal(set_value('dispatch_address'))) ?>">
                                </div>
                            </div>

                            <!-- País Entrega -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_country'] ?>:</label>
                                    <select class="form-control location-select" id="dispatch_country_id" name="dispatch_country">
                                        <option value=""><?= $pageTexts['label_select'] ?? $pageTexts['placeholder_select_country'] ?? '' ?></option>
                                        <?php if (!empty($countries)): ?>
                                            <?php foreach ($countries as $country): ?>
                                                <?php
                                                $sn = strtolower($country->sortname ?? '');
                                                $countryLabelAdd = isset($country->name) ? (function_exists('mb_strtoupper') ? mb_strtoupper($country->name, 'UTF-8') : strtoupper($country->name)) : '';
                                                ?>
                                                <option value="<?= (int)($country->id ?? $country->country_id ?? 0) ?>" data-sortname="<?= htmlspecialchars($sn) ?>" data-phonecode="<?= (int)($country->phonecode ?? 0) ?>" data-phonemask="<?= htmlspecialchars((string) ($country->phonemask ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($countryLabelAdd) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Estado Entrega -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_state'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="dispatch_state_id" name="dispatch_state_id" autocomplete="off">
                                        <option value=""></option>
                                    </select>
                                    <input type="hidden" id="dispatch_state" name="dispatch_state" value="<?= htmlspecialchars($fnUpperVal(set_value('dispatch_state'))) ?>">
                                </div>
                            </div>

                            <!-- Código Postal Entrega (antes que ciudad) -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_zipcode'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="dispatch_zip_code" name="dispatch_zip_code" autocomplete="off">
                                        <option value=""></option>
                                        <?php if (set_value('dispatch_zip_code')): ?>
                                            <option value="<?= htmlspecialchars($fnUpperVal(set_value('dispatch_zip_code'))) ?>" selected><?= htmlspecialchars($fnUpperVal(set_value('dispatch_zip_code'))) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Ciudad Entrega -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_city'] ?>:</label>
                                    <select class="form-control fv-geo-select" id="dispatch_city_id" name="dispatch_city_id" autocomplete="off">
                                        <option value=""></option>
                                    </select>
                                    <input type="hidden" id="dispatch_city" name="dispatch_city" value="<?= htmlspecialchars($fnUpperVal(set_value('dispatch_city'))) ?>">
                                </div>
                            </div>

                            <!-- Zona Entrega -->
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['dispatch_zone'] ?>:</label>
                                    <input type="text" class="form-control cust-address-upper" name="dispatch_zone" id="dispatch_zone"
                                           value="<?= htmlspecialchars($fnUpperVal(set_value('dispatch_zone'))) ?>">
                                </div>
                            </div>

                            <!-- Notas -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label><?= $pageTexts['notes'] ?>:</label>
                                    <textarea class="form-control" rows="5" name="dispatch_shipping_notes"><?= htmlspecialchars((string) set_value('dispatch_shipping_notes')) ?></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</form>
<?php $this->load->view('components/crud/form_action_fab', [
    'formId' => 'form_add',
    'saveLabel' => (string) ($pageTexts['guardar'] ?? ''),
    'cancelLabel' => (string) ($pageTexts['cancelar'] ?? ''),
    'cancelUrl' => company_url('customers'),
    'saveDisabled' => $planLimitBlocked,
]); ?>

<script>
$(document).ready(function() {
    var selPlaceholder = '<?= isset($pageTexts['label_select']) ? addslashes($pageTexts['label_select']) : (isset($pageTexts['placeholder_select_country']) ? addslashes($pageTexts['placeholder_select_country']) : '') ?>';

    // Seller Assigned: multiselect. Select2 se carga en footer (CoreLoader async); esperar a que esté disponible.
    function initSellerMultiselect() {
        var $el = $('#form_add #seller_asigned');
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
        var ids = ['#customer_catalog_emails', '#seller_assign_catalog', '#customer_term_id', '#customer_group_id'];
        ids.forEach(function(sel) {
            var $el = $('#form_add ' + sel);
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
        return initSellerMultiselect() && initCustomerSingleSelects();
    }

    if (!initCustomerSelectWidgets()) {
        var attempts = 0, maxAttempts = 50;
        var iv = setInterval(function() {
            if (initCustomerSelectWidgets() || ++attempts >= maxAttempts) clearInterval(iv);
        }, 100);
    }

    var phCountry = '<?= isset($pageTexts['placeholder_select_country']) ? addslashes($pageTexts['placeholder_select_country']) : '' ?>';
    var companyDefaultCountryId = '<?= !empty($companyDefaultCountryId) ? (int) $companyDefaultCountryId : '' ?>';
    var countryFlagsBaseUrl = <?= json_encode($customerFormCountryFlagsUrl) ?>;
    if (typeof window.console !== 'undefined' && typeof window.console.log === 'function') {
        window.console.log('[company/customers/add] countryFlagsBaseUrl', countryFlagsBaseUrl);
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
            var label = '[company/customers/add] Lista de paises con URL de bandera';
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
                window.console.warn('[company/customers/add] logCountryListWithFlagImages', err);
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

    function buildDeliveryAddress(prefix) {
        // Orden fijo: Address, State, City, ZipCode, Country
        var address = (prefix === 'commercial') ? $.trim($('#commercial_address').val() || '') : $.trim($('#dispatch_address').val() || '');
        var state = $.trim($('#' + prefix + '_state').val() || '');
        var city = $.trim($('#' + prefix + '_city').val() || '');
        var country = getCountryText($('#' + prefix + '_country_id'));
        if (country === phCountry) country = '';
        var zip = (prefix === 'commercial') ? $.trim($('#commercial_zip_code').val() || '') : $.trim($('#dispatch_zip_code').val() || '');

        var parts = [];
        if (address) parts.push(address.toUpperCase());
        if (state) parts.push(state.toUpperCase());
        if (city) parts.push(city.toUpperCase());
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

    $('#form_add').on('submit', function() {
        $('.cust-address-upper').each(function() { forceAddressUpper(this); });
    });

    function unlockDispatchAddressFields() {
        unlockEditableAddressFields();
    }

    $('#asignar_datos').on('change', function() {
        unlockDispatchAddressFields();
        if (!$(this).is(':checked')) {
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
});
</script>
