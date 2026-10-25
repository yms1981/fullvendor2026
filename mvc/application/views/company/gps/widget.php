<?php
/**
 * Widget GPS sellers: listado + mapa de ruta del día.
 * Textos: api/lang -> cmp-gps
 */
$CI = get_instance();

$gpsLangId = $CI->session->userdata('language_id');
$gpsLang = [];
if (isset($CI->langmodel) && is_object($CI->langmodel) && method_exists($CI->langmodel, 'getMdl')) {
    $gpsLang = $CI->langmodel->getMdl('cmp-gps', $gpsLangId);
}
$gpsLang = is_array($gpsLang) ? $gpsLang : [];

$h = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$gpsToken = (string) ($CI->session->userdata('token') ?? '');
$gpsCompanyId = (int) ($CI->session->userdata('compId') ?? 0);
$gpsUserId = (int) ($CI->session->userdata('user_logueado') ?? $CI->session->userdata('id') ?? 0);
$gpsApiBase = rtrim(base_url('api'), '/');

$gpsCfg = [
    'apiBaseUrl' => $gpsApiBase,
    'imageBaseUrl' => rtrim(company_url('image'), '/'),
    'apiToken' => $gpsToken,
    'companyId' => $gpsCompanyId,
    'userId' => $gpsUserId,
    'lang' => $gpsLang,
];
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="stylesheet" href="<?= base_url('companyasset/css/fv-company-gps.css?v=' . (defined('APP_VERSION') ? APP_VERSION : '1')) ?>">
<div id="fvCompanyGps" aria-live="polite">
    <button type="button" id="fvGpsBtn" title="<?= $h($gpsLang['open_label'] ?? '') ?>" aria-label="<?= $h($gpsLang['open_label'] ?? '') ?>">
        <i class="mdi mdi-map-marker"></i>
    </button>

    <div id="fvGpsBackdrop" class="fv-gps-backdrop" hidden></div>

    <aside id="fvGpsPanel" class="fv-gps-panel" hidden aria-label="<?= $h($gpsLang['widget_title'] ?? '') ?>">
        <div class="fv-gps-header">
            <div class="fv-gps-header-main">
                <button type="button" id="fvGpsBack" class="fv-gps-iconbtn" hidden title="<?= $h($gpsLang['back'] ?? '') ?>">
                    <i class="mdi mdi-arrow-left"></i>
                </button>
                <div class="fv-gps-header-titles">
                    <div id="fvGpsTitle" class="fv-gps-title"><?= $h($gpsLang['widget_title'] ?? '') ?></div>
                    <div id="fvGpsSubtitle" class="fv-gps-subtitle"><?= $h($gpsLang['sellers_subtitle'] ?? '') ?></div>
                </div>
            </div>
            <div class="fv-gps-header-actions">
                <button type="button" id="fvGpsClose" class="fv-gps-iconbtn" title="<?= $h($gpsLang['close_label'] ?? '') ?>">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
        </div>

        <div id="fvGpsListView" class="fv-gps-body">
            <div id="fvGpsSearchWrap" class="fv-gps-search">
                <input type="search" id="fvGpsSearch" placeholder="<?= $h($gpsLang['search_placeholder'] ?? '') ?>" autocomplete="off">
            </div>
            <div class="fv-gps-section-label"><?= $h($gpsLang['sellers'] ?? '') ?></div>
            <div id="fvGpsSellers" class="fv-gps-list"></div>
            <div id="fvGpsEmpty" class="fv-gps-empty" hidden><?= $h($gpsLang['no_sellers'] ?? '') ?></div>
            <div id="fvGpsError" class="fv-gps-error" hidden></div>
        </div>

        <div id="fvGpsMapView" class="fv-gps-map-view" hidden>
            <div class="fv-gps-datebar">
                <label for="fvGpsDate" class="fv-gps-date-label"><?= $h($gpsLang['date_label'] ?? '') ?></label>
                <input type="date" id="fvGpsDate" class="fv-gps-date fv-skip-company-datepicker">
                <button type="button" id="fvGpsReload" class="fv-gps-iconbtn" title="<?= $h($gpsLang['reload'] ?? '') ?>">
                    <i class="mdi mdi-refresh"></i>
                </button>
            </div>
            <div id="fvGpsMapMeta" class="fv-gps-map-meta"></div>
            <div class="fv-gps-map-wrap">
                <div id="fvGpsMap" class="fv-gps-map" role="img" aria-label="<?= $h($gpsLang['map_label'] ?? '') ?>"></div>
                <div id="fvGpsMapEmpty" class="fv-gps-map-empty" hidden><?= $h($gpsLang['no_points'] ?? '') ?></div>
            </div>
        </div>
    </aside>
</div>
<script>
window.FV_COMPANY_GPS = <?= json_encode($gpsCfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="<?= base_url('companyasset/company/fv-company-gps.js?v=' . (defined('APP_VERSION') ? APP_VERSION : '1')) ?>"></script>
