<?php
/**
 * @var object $listItem the customer item from the list
 * @var mixed $value the value of business_name field
 */
// Obtener business_name
$businessName = trim($listItem->business_name ?? '');

// Obtener name
$name = trim($listItem->name ?? '');

// Dirección comercial: Address, City, State(sigla|nombre), ZipCode, Country
$commercialAddressFull = function_exists('fv_format_customer_commercial_address')
    ? fv_format_customer_commercial_address($listItem)
    : '';

// Obtener el customer_id para el enlace de edición
$customerId = $listItem->customer_id ?? null;
$editUrl = $customerId ? company_url('customers/edit/' . $customerId) : null;
$linkStyle = 'color: inherit; text-decoration: underline;';

// Preparar los valores con iconos y formato
$formattedParts = [];

// Business name con icono de compañía y en negrita, como enlace a la edición
if (!empty($businessName)) {
    if ($editUrl) {
        $escapedUrl = htmlspecialchars($editUrl, ENT_QUOTES);
        $formattedParts[] = '<i class="mdi mdi-office-building"></i> <strong><a href="javascript:void(0)" class="customer-name-link" data-edit-url="' . $escapedUrl . '" style="' . $linkStyle . ' cursor: pointer;">' . htmlspecialchars($businessName) . '</a></strong>';
    } else {
        $formattedParts[] = '<i class="mdi mdi-office-building"></i> <strong>' . htmlspecialchars($businessName) . '</strong>';
    }
}

// Name con icono de contacto, como enlace a la edición
if (!empty($name)) {
    if ($editUrl) {
        $escapedUrl = htmlspecialchars($editUrl, ENT_QUOTES);
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
<?php
ob_start();
$this->load->view('company/customer/list/email_field', [
    'listItem' => $listItem,
    'value' => $listItem->email ?? '',
    'countryDataMap' => $countryDataMap ?? [],
]);
$emailBlock = trim((string) ob_get_clean());
if ($emailBlock !== '') {
    echo (!empty($formattedParts) ? '<br>' : '') . $emailBlock;
}
?>
