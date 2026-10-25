<?php
/**
 * @var object $listItem the country item from the list
 * @var mixed $value the value of country_flag field (not used)
 */

$sortname = strtolower(trim($listItem->sortname ?? ''));
$countryName = trim($listItem->name ?? '');

// Construir la URL de la bandera
$flagUrl = '/images/flags/' . $sortname . '.png';

// Crear avatar circular con la bandera
?>
<div class="country-flag-avatar" style="display: inline-block; width: 40px; height: 40px; border-radius: 50%; overflow: hidden; border: 2px solid #ddd; background: #f5f5f5; margin-right: 50px; vertical-align: middle;">
    <img src="<?= htmlspecialchars($flagUrl) ?>" 
         alt="<?= htmlspecialchars($countryName) ?>" 
         title="<?= htmlspecialchars($countryName) ?>"
         style="width: 100%; height: 100%; object-fit: cover;"
         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'%3E%3Crect width=\'40\' height=\'40\' fill=\'%23f5f5f5\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' font-size=\'12\' fill=\'%23999\'%3E<?= htmlspecialchars(strtoupper($sortname)) ?>%3C/text%3E%3C/svg%3E';">
</div>

