<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Selector de moneda admin (tabla `currency`) — modal agrupado por continente/categoría.
 *
 * @var array<string, string> $cfLang
 * @var list<object> $currencies_pather
 * @var array<string, string> $adminCountrySortnameMap
 * @var int|string $adminSelectedCurrencyId
 */
$cfLang = isset($cfLang) && is_array($cfLang) ? $cfLang : [];
$currencies_pather = isset($currencies_pather) && is_array($currencies_pather) ? $currencies_pather : [];
$adminCountrySortnameMap = isset($adminCountrySortnameMap) && is_array($adminCountrySortnameMap) ? $adminCountrySortnameMap : [];
$adminSelectedCurrencyId = (int) ($adminSelectedCurrencyId ?? 0);
$flagsBaseUrl = rtrim((string) base_url(), '/') . '/images/flags/';

$t = static function (string $key, string $fallback = '') use ($cfLang): string {
    $v = $cfLang[$key] ?? '';
    return (is_string($v) && $v !== '') ? $v : $fallback;
};

$selectedLabel = $t('currency_picker_placeholder', 'Choose a currency');
foreach ($currencies_pather as $group) {
    $children = $group->currencies ?? [];
    if (! is_array($children)) {
        continue;
    }
    foreach ($children as $child) {
        $c = is_array($child) ? (object) $child : $child;
        if ((int) ($c->id ?? 0) === $adminSelectedCurrencyId) {
            $selectedLabel = trim(
                (string) ($c->Contry ?? '') . ' · '
                . (string) ($c->Name ?? '') . ' · '
                . (string) ($c->Code ?? '') . ' '
                . (string) ($c->Symbol ?? '')
            );
            break 2;
        }
    }
}
?>
<style>
.admin-currency-picker-trigger {
    cursor: pointer;
    background-color: #fff;
}
.admin-currency-picker-trigger:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
#adminCurrencyPickerModal .admin-currency-modal-dialog {
    max-width: 520px;
    margin: 1.5rem auto;
}
#adminCurrencyPickerModal .modal-body {
    max-height: min(55vh, 420px);
    overflow-y: auto;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}
#adminCurrencyPickerModal .modal-header,
#adminCurrencyPickerModal .modal-footer {
    padding: 0.65rem 1rem;
    flex-shrink: 0;
}
#adminCurrencyPickerModal .modal-content {
    max-height: min(90vh, 640px);
    display: flex;
    flex-direction: column;
}
#adminCurrencyPickerModal .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    border-top: 1px solid #e9ecef;
}
.admin-currency-modal-group-title {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6c757d;
    margin: 1rem 0 0.35rem;
}
.admin-currency-modal-group-title:first-child {
    margin-top: 0;
}
.admin-currency-modal-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 0.35rem;
    margin-bottom: 0.35rem;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}
.admin-currency-modal-row:hover,
.admin-currency-modal-row.is-active {
    background-color: #f0f7ff;
    border-color: #b8daff;
}
.admin-currency-modal-row .admin-currency-flag {
    flex: 0 0 24px;
    width: 24px;
    height: 18px;
    object-fit: cover;
    border: 1px solid #ddd;
    border-radius: 2px;
}
.admin-currency-modal-row .admin-currency-flag-placeholder {
    flex: 0 0 24px;
    width: 24px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 700;
    color: #6c757d;
    border: 1px dashed #ced4da;
    border-radius: 2px;
    background: #f8f9fa;
}
.admin-currency-modal-row .admin-currency-meta {
    flex: 1 1 auto;
    min-width: 0;
    line-height: 1.25;
}
.admin-currency-modal-row .admin-currency-meta strong {
    display: block;
    font-size: 0.92rem;
}
.admin-currency-modal-row .admin-currency-meta small {
    color: #6c757d;
}
.admin-currency-modal-row .admin-currency-code {
    flex: 0 0 auto;
    font-weight: 700;
    color: #343a40;
}
</style>

<div class="admin-currency-picker-wrap">
    <input type="hidden" name="currency" id="admin_company_currency_id" value="<?= (int) $adminSelectedCurrencyId ?>">
    <div class="input-group">
        <div class="form-control admin-currency-picker-trigger d-flex align-items-center"
            id="admin_company_currency_display"
            role="button"
            tabindex="0"
            data-toggle="modal"
            data-target="#adminCurrencyPickerModal"
            aria-haspopup="dialog"
            aria-controls="adminCurrencyPickerModal">
            <span id="admin_company_currency_display_text" class="text-truncate"><?= htmlspecialchars($selectedLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="input-group-append">
            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#adminCurrencyPickerModal">
                <?= htmlspecialchars($t('currency_picker_select', 'Select'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="adminCurrencyPickerModal" tabindex="-1" role="dialog" aria-labelledby="adminCurrencyPickerModalLabel" aria-hidden="true"
    data-flags-base="<?= htmlspecialchars($flagsBaseUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-country-map="<?= htmlspecialchars(json_encode($adminCountrySortnameMap, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-scrollable admin-currency-modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminCurrencyPickerModalLabel"><?= htmlspecialchars($t('currency_picker_title', 'Select currency'), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($t('currency_picker_cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="search" class="form-control mb-3" id="admin_currency_picker_search"
                    placeholder="<?= htmlspecialchars($t('currency_picker_search', 'Search currency…'), ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="off">
                <div id="admin_currency_picker_list">
                    <?php foreach ($currencies_pather as $group) :
                        $groupId = (string) ($group->GroupID ?? '');
                        $children = $group->currencies ?? [];
                        if (! is_array($children) || $children === []) {
                            continue;
                        }
                    ?>
                    <div class="admin-currency-modal-group" data-group="<?= htmlspecialchars($groupId, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="admin-currency-modal-group-title"><?= htmlspecialchars($groupId, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach ($children as $child) :
                            $c = is_array($child) ? (object) $child : $child;
                            $cid = (int) ($c->id ?? 0);
                            $country = (string) ($c->Contry ?? '');
                            $name = (string) ($c->Name ?? '');
                            $code = (string) ($c->Code ?? '');
                            $symbol = (string) ($c->Symbol ?? '');
                            $searchBlob = strtolower(trim($groupId . ' ' . $country . ' ' . $name . ' ' . $code . ' ' . $symbol));
                            $isActive = $cid === $adminSelectedCurrencyId;
                        ?>
                        <div class="admin-currency-modal-row<?= $isActive ? ' is-active' : '' ?>"
                            role="button"
                            tabindex="0"
                            data-currency-id="<?= $cid ?>"
                            data-country="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>"
                            data-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            data-code="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                            data-symbol="<?= htmlspecialchars($symbol, ENT_QUOTES, 'UTF-8') ?>"
                            data-group="<?= htmlspecialchars($groupId, ENT_QUOTES, 'UTF-8') ?>"
                            data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="admin-currency-flag-slot" data-country="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>" data-group="<?= htmlspecialchars($groupId, ENT_QUOTES, 'UTF-8') ?>" data-code="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"></span>
                            <div class="admin-currency-meta">
                                <strong><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                            <div class="admin-currency-code text-right">
                                <div><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="text-muted"><?= htmlspecialchars($symbol, ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted small mb-0 d-none" id="admin_currency_picker_empty"><?= htmlspecialchars($t('currency_picker_empty', 'No currencies match your search.'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars($t('currency_picker_cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="btn btn-primary" id="admin_currency_picker_confirm_btn"<?= $adminSelectedCurrencyId > 0 ? '' : ' disabled' ?>><?= htmlspecialchars($t('currency_picker_confirm', 'Select'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
