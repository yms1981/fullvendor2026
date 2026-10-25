<?php
/**
 * @var object $listItem the customer item from the list
 * @var mixed $value the value of country_flag field (not used, but required by template system)
 * @var array $countryMap map of country names to sortname
 */

// Obtener el nombre del país
$countryName = trim($listItem->commercial_country ?? '');

// Buscar el sortname del país
$sortname = '';
if (!empty($countryName) && isset($countryMap) && is_array($countryMap)) {
    $sortname = $countryMap[$countryName] ?? '';
}

// Construir la bandera si tenemos sortname
$flagHtml = '';
if (!empty($sortname)) {
    $flagUrl = '/images/flags/' . strtolower($sortname) . '.png';
    $flagHtml = '<img src="' . htmlspecialchars($flagUrl) . '" 
                  style="width: 20px; height: 15px; vertical-align: middle; border: 1px solid #ddd; border-radius: 2px; object-fit: cover; margin: 0;" 
                  onerror="this.style.display=\'none\'" 
                  alt="' . htmlspecialchars($countryName) . '" 
                  title="' . htmlspecialchars($countryName) . '" />';
}

// Mostrar solo la bandera
echo $flagHtml;
?>

