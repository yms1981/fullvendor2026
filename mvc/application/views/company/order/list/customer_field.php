<?php
/**
 * Celda cliente — grilla moderna.
 *
 * @var object $listItem
 * @var array $countryDataMap
 */
if (!function_exists('applyPhoneMask')) {
    function applyPhoneMask($phoneNumber, $mask) {
        if (empty($phoneNumber) || empty($mask)) {
            return $phoneNumber;
        }
        $digits = preg_replace('/\D/', '', $phoneNumber);
        if (empty($digits)) {
            return $phoneNumber;
        }
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

$c = $listItem->customer ?? null;
if (!$c) {
    echo '—';
    return;
}
$orderIdForView = (int) ($listItem->order_id ?? 0);
$orderViewUrl = $orderIdForView > 0 ? company_url('orders/view/' . $orderIdForView) : '';
$countryName = trim($c->commercial_country ?? $c->dispatch_country ?? $c->country->name ?? '');
$countryData = (isset($countryDataMap) && is_array($countryDataMap) && $countryName !== '')
    ? ($countryDataMap[$countryName] ?? null)
    : null;
?>
<div class="fv-grid-customer">
    <?php $businessName = trim($c->business_name ?? ''); if ($businessName !== '') : ?>
        <div class="fv-grid-customer__line">
            <i class="mdi mdi-domain" aria-hidden="true"></i>
            <?php if ($orderViewUrl !== '') : ?>
                <a href="<?= htmlspecialchars($orderViewUrl, ENT_QUOTES, 'UTF-8') ?>" class="fv-grid-customer__name">
                    <?= htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php else : ?>
                <span class="fv-grid-customer__name"><?= htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php $contactName = trim($c->name ?? ''); if ($contactName !== '') : ?>
        <div class="fv-grid-customer__line">
            <i class="mdi mdi-account-outline" aria-hidden="true"></i>
            <span><?= htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($c->email ?? $c->customer_email ?? null)) : ?>
        <div class="fv-grid-customer__line fv-grid-customer__muted">
            <i class="mdi mdi-email-outline" aria-hidden="true"></i>
            <span><?= htmlspecialchars($c->email ?? $c->customer_email ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>
    <?php
    $phone = trim($c->phone ?? $c->customer_phone ?? '');
    if ($phone !== '') :
        $formattedPhone = $phone;
        if ($countryData && !empty($countryData['phonecode'])) {
            if (!empty($countryData['phonemask'])) {
                $formattedPhone = applyPhoneMask($phone, $countryData['phonemask']);
            }
            $formattedPhone = '+' . $countryData['phonecode'] . ' ' . $formattedPhone;
        } else {
            $formattedPhone = applyPhoneMask($phone, '(###) ### ####');
        }
    ?>
        <div class="fv-grid-customer__line fv-grid-customer__muted">
            <i class="mdi mdi-phone-outline" aria-hidden="true"></i>
            <span><?= htmlspecialchars($formattedPhone, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>
    <?php
    $fnAddrUpper = static function ($s) {
        $s = trim((string) $s);
        if ($s === '') {
            return '';
        }
        return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
    };
    $addrSegments = [];
    foreach ([
        $c->commercial_address ?? '',
        $c->commercial_state ?? '',
        $c->commercial_city ?? '',
        $c->commercial_zone ?? '',
        $c->commercial_zip_code ?? '',
        $c->commercial_country ?? '',
    ] as $seg) {
        $t = trim((string) $seg);
        if ($t !== '') {
            $addrSegments[] = $fnAddrUpper($t);
        }
    }
    if (count($addrSegments) === 0) {
        foreach ([
            $c->dispatch_address ?? '',
            $c->dispatch_state ?? '',
            $c->dispatch_city ?? '',
            $c->dispatch_zone ?? '',
            $c->dispatch_zip_code ?? '',
            $c->dispatch_country ?? '',
        ] as $seg) {
            $t = trim((string) $seg);
            if ($t !== '') {
                $addrSegments[] = $fnAddrUpper($t);
            }
        }
    }
    $addr = implode(', ', $addrSegments);
    if ($addr === '') {
        $legacy = trim((string) ($c->customer_address ?? $c->dispatch_delivery_address ?? ''));
        if ($legacy !== '') {
            $addr = $fnAddrUpper($legacy);
        }
    }
    if ($addr !== '') :
    ?>
        <div class="fv-grid-customer__line fv-grid-customer__muted">
            <i class="mdi mdi-map-marker-outline" aria-hidden="true"></i>
            <span><?= htmlspecialchars($addr, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>
</div>
