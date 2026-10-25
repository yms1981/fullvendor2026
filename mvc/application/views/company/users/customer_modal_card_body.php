<?php
/**
 * Vista previa de cliente (modal alta usuario): alineada al listado (iconos MDI, dirección concatenada, teléfonos con bandera + prefijo + máscara).
 *
 * @var string $business_name
 * @var string $contact_name
 * @var string $address_full
 * @var string $phone_raw
 * @var string $cell_raw
 * @var array $countryData
 * @var string $flagsBaseUrl
 * @var array $cardLabels
 */
if (! function_exists('fv_apply_phone_mask_modal')) {
    function fv_apply_phone_mask_modal(string $phoneNumber, string $mask): string
    {
        if ($phoneNumber === '' || $mask === '') {
            return $phoneNumber;
        }
        $digits = preg_replace('/\D/', '', $phoneNumber);
        if ($digits === '') {
            return $phoneNumber;
        }
        $result = '';
        $digitIndex = 0;
        $maskLength = strlen($mask);
        for ($i = 0; $i < $maskLength && $digitIndex < strlen($digits); $i++) {
            if ($mask[$i] === '#') {
                $result .= $digits[$digitIndex];
                $digitIndex++;
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

$h = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$business_name = trim((string) ($business_name ?? ''));
$contact_name = trim((string) ($contact_name ?? ''));
$address_full = trim((string) ($address_full ?? ''));
$phone_raw = trim((string) ($phone_raw ?? ''));
$cell_raw = trim((string) ($cell_raw ?? ''));
$countryData = isset($countryData) && is_array($countryData) ? $countryData : [];
$flagsBaseUrl = rtrim((string) ($flagsBaseUrl ?? ''), '/') . '/';
$cardLabels = isset($cardLabels) && is_array($cardLabels) ? $cardLabels : [];

$lbl = static function (string $key, string $fb) use ($cardLabels, $h): string {
    $v = trim((string) ($cardLabels[$key] ?? ''));

    return $h($v !== '' ? $v : $fb);
};

$sortname = strtolower(trim((string) ($countryData['sortname'] ?? '')));
$phonecode = $countryData['phonecode'] ?? '';
$phonemask = trim((string) ($countryData['phonemask'] ?? ''));
$countryAlt = (string) ($countryData['name'] ?? '');

$formatTel = static function (string $raw) use ($phonecode, $phonemask): string {
    if ($raw === '') {
        return '';
    }
    if ($phonemask !== '') {
        $formatted = fv_apply_phone_mask_modal($raw, $phonemask);
    } else {
        $formatted = fv_apply_phone_mask_modal($raw, '(###) ### ####');
    }
    if ($phonecode !== '' && $phonecode !== null && (string) $phonecode !== '') {
        return '+' . (string) $phonecode . ' ' . $formatted;
    }

    return $formatted;
};

$flagImg = static function (string $sort, string $flagsBase, string $alt) use ($h): string {
    if ($sort === '') {
        return '';
    }
    $url = $flagsBase . $sort . '.png';

    return '<img src="' . $h($url) . '" class="user-modal-customer-card__flag" width="22" height="16" alt="' . $h($alt) . '" title="' . $h($alt) . '" onerror="this.style.display=\'none\'" />';
};

$row = static function (string $iconClass, string $labelHtml, string $bodyHtml): string {
    return '<li class="list-group-item user-modal-customer-card__row border-0 px-2 py-2">'
        . '<div class="d-flex align-items-start">'
        . '<span class="user-modal-customer-card__icon text-danger mr-2 mt-1"><i class="mdi ' . $iconClass . '" aria-hidden="true"></i></span>'
        . '<div class="flex-grow-1 min-w-0">'
        . $labelHtml
        . $bodyHtml
        . '</div></div></li>';
};

$mutedLbl = static function (string $text): string {
    if (trim($text) === '') {
        return '';
    }

    return '<div class="text-uppercase text-muted small mb-1" style="font-size:0.68rem;letter-spacing:.02em;">' . $text . '</div>';
};

$items = [];

if ($business_name !== '') {
    $items[] = $row(
        'mdi-office-building',
        $mutedLbl($lbl('business', 'Business name')),
        '<strong class="d-block text-dark">' . $h($business_name) . '</strong>'
    );
}

if ($contact_name !== '') {
    $items[] = $row(
        'mdi-account',
        $mutedLbl($lbl('contact', 'Contact name')),
        '<span class="d-block text-dark">' . $h($contact_name) . '</span>'
    );
}

if ($address_full !== '') {
    $items[] = $row(
        'mdi-map-marker',
        $mutedLbl($lbl('address', 'Address')),
        '<span class="d-block text-dark text-wrap">' . $h($address_full) . '</span>'
    );
}

if ($phone_raw !== '') {
    $txt = $formatTel($phone_raw);
    $phoneBody = '<div class="d-flex align-items-center flex-wrap">'
        . $flagImg($sortname, $flagsBaseUrl, $countryAlt)
        . '<span class="ml-2 font-weight-medium text-dark">' . $h($txt) . '</span></div>';
    $items[] = $row('mdi-phone', $mutedLbl($lbl('phone', 'Phone')), $phoneBody);
}

if ($cell_raw !== '') {
    $txt = $formatTel($cell_raw);
    $cellBody = '<div class="d-flex align-items-center flex-wrap">'
        . $flagImg($sortname, $flagsBaseUrl, $countryAlt)
        . '<span class="ml-2 font-weight-medium text-dark">' . $h($txt) . '</span></div>';
    $items[] = $row('mdi-cellphone', $mutedLbl($lbl('cell', 'Cell phone')), $cellBody);
}

if ($items === []) {
    echo '<p class="text-muted mb-0 small"></p>';

    return;
}

echo '<div class="user-modal-customer-card border rounded bg-white shadow-sm overflow-hidden">';
echo '<ul class="list-group list-group-flush mb-0">';
echo implode('', $items);
echo '</ul></div>';
