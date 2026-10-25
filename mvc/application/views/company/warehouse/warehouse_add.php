<?php
/**
 * @var $pageTexts array the translated string conglomerated for this view
 * @var array|object[] $countries listado de países (API country)
 * @var int|null $companyDefaultCountryId
 * @var object|null $warehouseDraft
 * @var array|object[] $warehouseRoleUsers
 */
$companyDefaultCountryId = isset($companyDefaultCountryId) ? (int) $companyDefaultCountryId : null;
if (($companyDefaultCountryId === null || $companyDefaultCountryId === 0) && !empty($company) && !empty($countries)) {
    $cn = trim((string) ($company->country ?? ''));
    if ($cn !== '' && ctype_digit($cn)) {
        $companyDefaultCountryId = (int) $cn;
    }
}
$countryPlaceholder = $pageTexts['placeholder_select_country'] ?? $pageTexts['label_select'] ?? '';
$warehouseFormCountryFlagsUrl = rtrim((string) base_url(), '/') . '/images/flags/';
?>
<script>
window.WAREHOUSE_ADDRESS_FORM = <?= json_encode([
    'countryFlagsBaseUrl' => $warehouseFormCountryFlagsUrl,
    'selPlaceholder' => (string) ($pageTexts['label_select'] ?? ''),
    'phCountry' => (string) ($pageTexts['placeholder_select_country'] ?? ''),
    'companyDefaultCountryId' => $companyDefaultCountryId ? (int) $companyDefaultCountryId : '',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"> <?= $pageTexts['add_new_deposit'] ?></h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="form_add"
                          class="forms-sample"
                          method="post"
                          action="<?= company_url('warehouses/save')?>"
                          data-fv-warehouse-form="1"
                          data-msg-name-required="<?= htmlspecialchars((string) ($pageTexts['name_required'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                          data-msg-country-required="<?= htmlspecialchars((string) ($pageTexts['country_required'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label><?= htmlspecialchars((string) ($pageTexts['name'] ?? '')) ?> <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="name">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="form-group">
                                            <label><?= htmlspecialchars((string) ($pageTexts['street'] ?? '')) ?></label>
                                            <input type="text" class="form-control" name="street">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label><?= htmlspecialchars((string) ($pageTexts['street_number'] ?? '')) ?>:</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text px-2" id="warehouse_street_number_prefix" style="min-width: 5.5rem;"></span>
                                                </div>
                                                <input type="tel" class="form-control" name="street_number" id="warehouse_street_number" autocomplete="off" inputmode="numeric">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars((string) ($pageTexts['city'] ?? '')) ?>:</label>
                                    <input type="text" class="form-control validate" id="city_name">
                                    <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                                    <input type="hidden" id="city_id" name="city_id">
                                    <div id="city_suggestions" class="suggestions-box"></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars((string) ($pageTexts['state'] ?? '')) ?>:</label>
                                    <input type="text" class="form-control validate" id="state_name">
                                    <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                                    <input type="hidden" id="state_id" name="state_id">
                                    <div id="state_suggestions" class="suggestions-box"></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars((string) ($pageTexts['country'] ?? '')) ?>: <span class="color-red">*</span></label>
                                    <select class="form-control location-select" id="country_id" name="country_id">
                                        <option value=""><?= htmlspecialchars($countryPlaceholder) ?></option>
                                        <?php foreach ($countries ?? [] as $country): ?>
                                            <?php
                                            $cid = (int) ($country->id ?? $country->country_id ?? 0);
                                            $selected = ($companyDefaultCountryId && $cid === $companyDefaultCountryId) ? ' selected' : '';
                                            $sn = strtolower((string) ($country->sortname ?? ''));
                                            $countryLabel = isset($country->name)
                                                ? (function_exists('mb_strtoupper') ? mb_strtoupper((string) $country->name, 'UTF-8') : strtoupper((string) $country->name))
                                                : '';
                                            ?>
                                            <option value="<?= $cid ?>" data-sortname="<?= htmlspecialchars($sn) ?>" data-phonecode="<?= (int) ($country->phonecode ?? 0) ?>" data-phonemask="<?= htmlspecialchars((string) ($country->phonemask ?? ''), ENT_QUOTES, 'UTF-8') ?>"<?= $selected ?>><?= htmlspecialchars($countryLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
            <?php $this->load->view('company/assigned_table', [
                'entity' => $warehouseDraft ?? (object) ['id' => 0, 'users' => []],
                'entityType' => 'warehouse',
                'pageTexts' => $pageTexts ?? [],
                'warehouseRoleUsers' => $warehouseRoleUsers ?? [],
                'assignUsersPending' => true,
            ]); ?>
        </div>
    </div>
</div>
<?php $this->load->view('components/crud/form_action_fab', [
    'formId' => 'form_add',
    'saveLabel' => (string) ($pageTexts['save'] ?? ''),
    'cancelLabel' => (string) ($pageTexts['back'] ?? ''),
    'cancelUrl' => company_url('warehouses'),
]); ?>
