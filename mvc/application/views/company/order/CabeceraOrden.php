<?php
/**
 * @var $data Object Order info
 * @var $lang_json Array The lang translations for every translatable string
 * @var $info_company Object Company info
 * @var $info_customer Object Customer info
 * @var $seller Object Seller
 * @var $wuser Object Warehouse user
 * @var array $countryDataMap map country name => [phonecode, phonemask] para formatear teléfonos
 */

if (!function_exists('applyPhoneMask')) {
    function applyPhoneMask($phoneNumber, $mask) {
        if (empty($phoneNumber) || empty($mask)) return $phoneNumber;
        $digits = preg_replace('/\D/', '', $phoneNumber);
        if (empty($digits)) return $phoneNumber;
        $result = '';
        $digitIndex = 0;
        for ($i = 0; $i < strlen($mask) && $digitIndex < strlen($digits); $i++) {
            if ($mask[$i] === '#') {
                $result .= $digits[$digitIndex++];
            } else {
                $result .= $mask[$i];
            }
        }
        if ($digitIndex < strlen($digits)) {
            $result .= substr($digits, $digitIndex);
        }
        return $result;
    }
}

$lang_order = isset($lang_json['cmp_orders']['lang']) ? $lang_json['cmp_orders']['lang'] : [];
$format_date = (isset($info_company->format_date->format_php)) ? $info_company->format_date->format_php : fv_display_date_format();
$countryDataMap = isset($countryDataMap) && is_array($countryDataMap) ? $countryDataMap : [];

// País para formato de teléfono: orden address o cliente
$address = $data->address ?? null;
$countryName = '';
if ($address && isset($address->country) && is_object($address->country)) {
    $countryName = trim($address->country->name ?? '');
}
if ($countryName === '' && $info_customer) {
    $countryName = trim($info_customer->commercial_country ?? $info_customer->dispatch_country ?? '');
}
if ($countryName === '' && $info_customer && isset($info_customer->country) && is_object($info_customer->country)) {
    $countryName = trim($info_customer->country->name ?? '');
}
$countryData = ($countryName !== '' && isset($countryDataMap[$countryName])) ? $countryDataMap[$countryName] : null;

// Dirección COMERCIAL del cliente (no dispatch): relación commercialAddresses, luego campos planos commercial_*, fallback orden
$addressConcatenada = '';
$addrOrder = $data->address ?? null;

if ($info_customer) {
    $d = $info_customer;
    // 1) Relación commercialAddresses: dirección comercial completa con street, city, state, country
    $commercialAddrs = $d->commercialAddresses ?? $d->commercial_addresses ?? null;
    if (!is_array($commercialAddrs) && is_object($commercialAddrs) && method_exists($commercialAddrs, 'toArray')) {
        $commercialAddrs = $commercialAddrs->toArray();
    }
    if (!empty($commercialAddrs) && is_array($commercialAddrs)) {
        $first = is_object($commercialAddrs[0]) ? $commercialAddrs[0] : (object) $commercialAddrs[0];
        $street = trim(($first->street ?? '') . ' ' . ($first->street_number ?? ''));
        $cityName = null;
        $stateName = null;
        $countryName = null;
        if (isset($first->city) && (is_object($first->city) || is_array($first->city))) {
            $cityName = is_object($first->city) ? ($first->city->name ?? null) : (is_array($first->city) ? ($first->city['name'] ?? null) : null);
        }
        if (isset($first->state) && (is_object($first->state) || is_array($first->state))) {
            $stateName = is_object($first->state) ? ($first->state->name ?? null) : (is_array($first->state) ? ($first->state['name'] ?? null) : null);
        }
        if (isset($first->country) && (is_object($first->country) || is_array($first->country))) {
            $countryName = is_object($first->country) ? ($first->country->name ?? null) : (is_array($first->country) ? ($first->country['name'] ?? null) : null);
        }
        $parts = array_filter([
            $street !== '' ? $street : null,
            $first->zone ?? null,
            $first->zipcode ?? null,
            $cityName,
            $stateName,
            $countryName,
        ]);
        $addressConcatenada = trim(implode(', ', $parts));
    }
    // 2) Campos planos commercial: todos los componentes en orden
    if ($addressConcatenada === '') {
        $parts = array_filter([
            trim((string)($d->commercial_address ?? $d->commercialAddress ?? '')),
            trim((string)($d->commercial_zone ?? $d->commercialZone ?? '')),
            trim((string)($d->commercial_zip_code ?? $d->commercialZipCode ?? '')),
            trim((string)($d->commercial_city ?? $d->commercialCity ?? '')),
            trim((string)($d->commercial_state ?? $d->commercialState ?? '')),
            trim((string)($d->commercial_country ?? $d->commercialCountry ?? '')),
        ]);
        $addressConcatenada = trim(implode(', ', $parts));
    }
}

// 3) Fallback: dirección de la orden (envío)
if ($addressConcatenada === '' && $addrOrder && is_object($addrOrder)) {
    $parts = array_filter([
        trim(($addrOrder->street ?? '') . ' ' . ($addrOrder->street_number ?? '')),
        $addrOrder->zone ?? null,
        $addrOrder->zipcode ?? null,
        isset($addrOrder->city->name) ? $addrOrder->city->name : null,
        isset($addrOrder->state->name) ? $addrOrder->state->name : null,
        isset($addrOrder->country->name) ? $addrOrder->country->name : null,
    ]);
    $addressConcatenada = trim(implode(', ', $parts));
}
?>
<div class="col-lg-6 mb-3">
    <?php if ($info_customer): ?>
        <?php $v = trim((string)($info_customer->business_name ?? '')); if ($v !== ''): ?>
        <p class="mb-1">
            <i class="mdi mdi-domain text-muted me-2" title="<?= htmlspecialchars($lang_order['customer'] ?? 'Cliente') ?>"></i>
            <span><?= htmlspecialchars(strtoupper($v)) ?></span>
        </p>
        <?php endif; ?>
        <?php $v = trim((string)($info_customer->name ?? '')); if ($v !== ''): ?>
        <p class="mb-1">
            <i class="mdi mdi-account-outline text-muted me-2" title="<?= htmlspecialchars($lang_order['contact_name'] ?? 'Contacto') ?>"></i>
            <span><?= htmlspecialchars($v) ?></span>
        </p>
        <?php endif; ?>
        <?php $v = trim((string)($info_customer->email ?? '')); if ($v !== ''): ?>
        <p class="mb-1">
            <i class="mdi mdi-email-outline text-muted me-2" title="Email"></i>
            <span><?= htmlspecialchars($v) ?></span>
        </p>
        <?php endif; ?>
        <?php
        $phone = trim($info_customer->phone ?? $info_customer->customer_phone ?? '');
        $cellPhone = trim($info_customer->cell_phone ?? $info_customer->customer_cell_phone ?? '');
        $formattedPhone = $phone;
        $formattedCell = $cellPhone;
        if ($countryData && !empty($countryData['phonecode'])) {
            if ($phone !== '' && !empty($countryData['phonemask'])) {
                $formattedPhone = applyPhoneMask($phone, $countryData['phonemask']);
            }
            if ($phone !== '') $formattedPhone = '+' . $countryData['phonecode'] . ' ' . $formattedPhone;
            if ($cellPhone !== '' && !empty($countryData['phonemask'])) {
                $formattedCell = applyPhoneMask($cellPhone, $countryData['phonemask']);
            }
            if ($cellPhone !== '') $formattedCell = '+' . $countryData['phonecode'] . ' ' . $formattedCell;
        } else {
            if ($phone !== '') $formattedPhone = applyPhoneMask($phone, '(###) ### ####');
            if ($cellPhone !== '') $formattedCell = applyPhoneMask($cellPhone, '(###) ### ####');
        }
        if ($phone !== ''): ?>
        <p class="mb-1">
            <i class="mdi mdi-phone-outline text-muted me-2" title="<?= htmlspecialchars($lang_order['phone_order'] ?? 'Teléfono') ?>"></i>
            <span><?= htmlspecialchars($formattedPhone) ?></span>
        </p>
        <?php endif; ?>
        <?php if ($cellPhone !== ''): ?>
        <p class="mb-1">
            <i class="mdi mdi-cellphone text-muted me-2" title="<?= htmlspecialchars($lang_order['cell_phone'] ?? 'Celular') ?>"></i>
            <span><?= htmlspecialchars($formattedCell) ?></span>
        </p>
        <?php endif; ?>
        <?php if ($addressConcatenada !== ''): ?>
        <p class="mb-1">
            <i class="mdi mdi-map-marker-outline text-muted me-2" title="<?= htmlspecialchars($lang_order['address_catalog'] ?? 'Dirección') ?>"></i>
            <span><?= htmlspecialchars($addressConcatenada) ?></span>
        </p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!isset($mostrarNombreVendedor) || $mostrarNombreVendedor): ?>
        <p class="mb-1">
            <strong><?= $lang_order['seller_order'] ?? 'Vendedor' ?>:</strong>
            <?php $sellerName = ($seller && isset($seller->user)) ? trim((string)($seller->user->name ?? '')) : ''; ?>
            <?php if ($sellerName !== ''): ?>
                <span class="badge badge-secondary font-weight-normal text-uppercase"><?= htmlspecialchars($sellerName) ?></span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if ($data->warehouse_id && $wuser): ?>
        <?php $wName = trim((string)($wuser->name ?? '')); if ($wName !== ''): ?>
        <p class="mb-1"><strong><?= htmlspecialchars((string) ($lang_order['warehouse_user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($wName) ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php $src = trim((string)($data->source ?? '')); if ($src !== ''): ?>
    <p class="mb-1"><strong>Origin: </strong><span class="notranslate"><?= htmlspecialchars($src) ?></span></p>
    <?php endif; ?>
</div>
<div class="col-lg-6  mb-3 text-right">
    <p class="mb-1">
        <strong><?= $lang_order['fecha_creacion_order'] ?? 'Order Date' ?>:</strong>
        <?= !empty($data->created) ? date($format_date, strtotime($data->created)) : '—' ?>
    </p>
    <p class="mb-1"><strong><?= $lang_order['order_orden_number'] ?>:
        </strong><?=@$data->order_number?></p>
    <p class="mb-1">

        <?php
        $so = $data->status_order ?? $data->statusOrder ?? null;
        if ($so):
            $statusLangKeys = [
                'New' => 'new', 'Read' => 'read', 'Pending' => 'pending', 'Dispatched' => 'dispatched',
                'Delivered' => 'delivered', 'Return Request' => 'return_request', 'Returned' => 'returned',
                'Canceled' => 'canceled', 'Approved' => 'approved', 'Warehouse Assigned' => 'warehouse_assigned',
                'Partially Completed' => 'partialy_completed', 'Warehouse Completed' => 'warehouse_completed',
                'Completed' => 'completed', 'Rejected' => 'rechazado', 'In Proccess' => 'pending',
                'Credit Note' => 'rechazado', 'Returned by Warehouse' => 'returned', 'Shopping' => 'new',
            ];
            $nameEn = (string) ($so->name_status_english ?? '');
            $statusKey = $statusLangKeys[$nameEn] ?? strtolower(str_replace(' ', '_', $nameEn));
            $statusLabel = trim((string) ($lang_order[$statusKey] ?? ''));
            if ($statusLabel === '') {
                $statusLabel = $nameEn !== '' ? $nameEn : (string) ($so->name_status_spanish ?? '');
            }
        ?>
        <span class="roundblock notranslate" style="background: <?= $so->color ?? '#ccc' ?>;"></span>
        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </p>

    <?php if($data->proforma): ?>

        <p class="mb-1 form-group pl-3 pt-2 text-right">
            <!-- <input readonly type="checkbox" name="proforma" id="seller-proforma "
                                <?= ($data->proforma != 0) ? 'checked="true"' : '' ?>
                                value="<?= ($data->proforma == 0 || $data->proforma==null) ? '1' : $data->proforma ?>"
                                value="1"> -->
            <i class='bx bxs-check-square text-primary pt-0'></i>
            <label style="vertical-align: middle;" class="pt-0"><?= $lang_order['proforma'] ?> </label>

        </p>
    <?php endif; ?>

</div>
