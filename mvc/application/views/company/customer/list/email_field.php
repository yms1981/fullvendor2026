<?php
/**
 * @var object $listItem the customer item from the list
 * @var mixed $value the value of email field
 * @var array $countryDataMap map of country names to country data (sortname, phonecode, phonemask)
 */

/**
 * Función para aplicar máscara a un número de teléfono
 * Usa function_exists para evitar redeclaración
 */
if (!function_exists('applyPhoneMask')) {
    function applyPhoneMask($phoneNumber, $mask, $phonecode = null) {
        if (empty($phoneNumber) || empty($mask)) {
            return $phoneNumber;
        }
        
        // Remover caracteres no numéricos del número
        $digits = preg_replace('/\D/', '', (string) $phoneNumber);
        
        // Si no hay dígitos, retornar el número original
        if ($digits === null || $digits === '') {
            return $phoneNumber;
        }

        $maskDigitCount = substr_count($mask, '#');
        $pc = preg_replace('/\D/', '', (string) ($phonecode ?? ''));
        if ($pc !== '' && str_starts_with($digits, $pc) && strlen($digits) > strlen($pc)) {
            $digits = substr($digits, strlen($pc));
        }
        if ($maskDigitCount > 0 && strlen($digits) > $maskDigitCount) {
            $digits = substr($digits, -$maskDigitCount);
        }
        
        // Aplicar la máscara: reemplazar # con dígitos
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
        
        return $result;
    }
}

// Obtener el customer_id para el enlace de edición
$customerId = $listItem->customer_id ?? null;

// Preparar los valores con iconos
$formattedParts = [];

// Email con icono de email, como enlace a la edición
$email = trim($listItem->email ?? '');
if (!empty($email)) {
    if ($customerId) {
        $editUrl = company_url('customers/edit/' . $customerId);
        $escapedUrl = htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8');
        $formattedParts[] = '<i class="mdi mdi-email"></i> <a href="javascript:void(0)" class="customer-name-link" data-edit-url="' . $escapedUrl . '" style="color: inherit; text-decoration: underline; cursor: pointer;">' . htmlspecialchars($email) . '</a>';
    } else {
        $formattedParts[] = '<i class="mdi mdi-email"></i> ' . htmlspecialchars($email);
    }
}

// Obtener el país del cliente
$countryName = trim($listItem->commercial_country ?? '');
$countryData = null;
if (!empty($countryName) && isset($countryDataMap) && is_array($countryDataMap)) {
    $countryData = $countryDataMap[$countryName] ?? null;
}

// Obtener la URL de edición
$editUrl = $customerId ? company_url('customers/edit/' . $customerId) : null;
$linkStyle = 'color: inherit; text-decoration: underline;';

// Phone con icono de teléfono, como enlace a la edición
$phone = trim($listItem->phone ?? '');
if (!empty($phone)) {
    $formattedPhone = $phone;
    
    // Si hay país asociado, aplicar phonecode y máscara
    if ($countryData && !empty($countryData['phonecode'])) {
        $phonecode = $countryData['phonecode'];
        $phonemask = $countryData['phonemask'] ?? '';
        
        // Aplicar máscara si existe
        if (!empty($phonemask)) {
            $formattedPhone = applyPhoneMask($phone, $phonemask, $phonecode);
        }
        
        $formattedPhone = '+' . $phonecode . ' ' . $formattedPhone;
    } else {
        // Si no hay país, aplicar máscara por defecto (###) ### ####
        $formattedPhone = applyPhoneMask($phone, '(###) ### ####');
    }
    
    if ($editUrl) {
        $escapedUrl = addslashes($editUrl);
        $formattedParts[] = '<i class="mdi mdi-phone"></i> <a href="javascript:void(0)" onclick="openurl(\'' . $escapedUrl . '\'); return false;" style="' . $linkStyle . '">' . htmlspecialchars($formattedPhone) . '</a>';
    } else {
        $formattedParts[] = '<i class="mdi mdi-phone"></i> ' . htmlspecialchars($formattedPhone);
    }
}

// Cell phone con icono de teléfono móvil, como enlace a la edición
$cellPhone = trim($listItem->cell_phone ?? '');
if (!empty($cellPhone)) {
    $formattedCellPhone = $cellPhone;
    
    // Si hay país asociado, aplicar phonecode y máscara
    if ($countryData && !empty($countryData['phonecode'])) {
        $phonecode = $countryData['phonecode'];
        $phonemask = $countryData['phonemask'] ?? '';
        
        // Aplicar máscara si existe
        if (!empty($phonemask)) {
            $formattedCellPhone = applyPhoneMask($cellPhone, $phonemask, $phonecode);
        }
        
        $formattedCellPhone = '+' . $phonecode . ' ' . $formattedCellPhone;
    } else {
        // Si no hay país, aplicar máscara por defecto (###) ### ####
        $formattedCellPhone = applyPhoneMask($cellPhone, '(###) ### ####');
    }
    
    if ($editUrl) {
        $escapedUrl = addslashes($editUrl);
        $formattedParts[] = '<i class="mdi mdi-cellphone"></i> <a href="javascript:void(0)" onclick="openurl(\'' . $escapedUrl . '\'); return false;" style="' . $linkStyle . '">' . htmlspecialchars($formattedCellPhone) . '</a>';
    } else {
        $formattedParts[] = '<i class="mdi mdi-cellphone"></i> ' . htmlspecialchars($formattedCellPhone);
    }
}
?>
<?= !empty($formattedParts) ? implode('<br>', $formattedParts) : '' ?>

