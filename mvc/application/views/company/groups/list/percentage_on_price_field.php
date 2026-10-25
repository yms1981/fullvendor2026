<?php
/**
 * @var object $listItem item from the list for a given record
 * @var mixed $value the value of the percentage_on_price field
 * @var array|null $pageTexts
 */

$percentageValue = $value ?? $listItem->percentage_on_price ?? null;
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

if ($percentageValue === null || $percentageValue === '' || $percentageValue === 'nothing') {
    echo htmlspecialchars((string) ($pageTexts['ninguno'] ?? ''), ENT_QUOTES, 'UTF-8');
} elseif ($percentageValue === 'Increase on Price List %') {
    echo htmlspecialchars((string) ($pageTexts['percentage_increase_option'] ?? $percentageValue), ENT_QUOTES, 'UTF-8');
} elseif ($percentageValue === 'Decrease on Price List %') {
    echo htmlspecialchars((string) ($pageTexts['percentage_decrease_option'] ?? $percentageValue), ENT_QUOTES, 'UTF-8');
} else {
    echo htmlspecialchars((string) $percentageValue, ENT_QUOTES, 'UTF-8');
}