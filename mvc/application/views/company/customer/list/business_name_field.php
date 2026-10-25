<?php
/**
 * @var object $listItem the customer item from the list
 * @var mixed $value the value of business_name field
 */
// Obtener business_name
$businessName = trim($listItem->business_name ?? '');

// Obtener name
$name = trim($listItem->name ?? '');

// Dirección comercial: orden fijo Address, State, City, ZipCode, Country — todo en mayúsculas
$fnUpper = static function ($s) {
    $s = trim((string) $s);
    if ($s === '') {
        return '';
    }
    return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
};

$commercialAddress = trim($listItem->commercial_address ?? '');
$state = trim($listItem->commercial_state ?? '');
$city = trim($listItem->commercial_city ?? '');
$zipCode = trim($listItem->commercial_zip_code ?? '');
$country = trim($listItem->commercial_country ?? '');

$commercialAddressSegments = [];
foreach ([$commercialAddress, $state, $city, $zipCode, $country] as $segment) {
    $t = trim((string) $segment);
    if ($t !== '') {
        $commercialAddressSegments[] = $fnUpper($t);
    }
}
$commercialAddressFull = implode(', ', $commercialAddressSegments);

// Obtener el customer_id para el enlace de edición
$customerId = $listItem->customer_id ?? null;
$editUrl = $customerId ? company_url('customers/edit/' . $customerId) : null;
$linkStyle = 'color: inherit; text-decoration: underline;';

// Preparar los valores con iconos y formato
$formattedParts = [];

// Business name con icono de compañía y en negrita, como enlace a la edición
// Usar la misma estructura que el botón editar para consistencia
if (!empty($businessName)) {
    if ($editUrl) {
        $escapedUrl = htmlspecialchars($editUrl, ENT_QUOTES);
        // Usar data-edit-url igual que el botón editar para que funcione con el mismo manejador
        $formattedParts[] = '<i class="mdi mdi-office-building"></i> <strong><a href="javascript:void(0)" class="customer-name-link" data-edit-url="' . $escapedUrl . '" style="' . $linkStyle . ' cursor: pointer;">' . htmlspecialchars($businessName) . '</a></strong>';
    } else {
        $formattedParts[] = '<i class="mdi mdi-office-building"></i> <strong>' . htmlspecialchars($businessName) . '</strong>';
    }
}

// Name con icono de contacto, como enlace a la edición
// Usar la misma estructura que el botón editar para consistencia
if (!empty($name)) {
    if ($editUrl) {
        $escapedUrl = htmlspecialchars($editUrl, ENT_QUOTES);
        // Usar data-edit-url igual que el botón editar para que funcione con el mismo manejador
        $formattedParts[] = '<i class="mdi mdi-account"></i> <a href="javascript:void(0)" class="customer-name-link" data-edit-url="' . $escapedUrl . '" style="' . $linkStyle . ' cursor: pointer;">' . htmlspecialchars($name) . '</a>';
    } else {
        $formattedParts[] = '<i class="mdi mdi-account"></i> ' . htmlspecialchars($name);
    }
}

// Dirección comercial con icono de dirección, como enlace a la edición
if (!empty($commercialAddressFull)) {
    if ($editUrl) {
        $escapedUrl = addslashes($editUrl);
        $formattedParts[] = '<i class="mdi mdi-map-marker"></i> <a href="javascript:void(0)" onclick="openurl(\'' . $escapedUrl . '\'); return false;" style="' . $linkStyle . '">' . htmlspecialchars($commercialAddressFull) . '</a>';
    } else {
        $formattedParts[] = '<i class="mdi mdi-map-marker"></i> ' . htmlspecialchars($commercialAddressFull);
    }
}
?>
<?= !empty($formattedParts) ? implode('<br>', $formattedParts) : '' ?>
