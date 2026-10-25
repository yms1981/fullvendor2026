<?php
/**
 * @var object $listItem item from the list for a given record
 */
$address = isset($listItem->address) ? (object) $listItem->address : null;
if ($address === null) {
    echo '';
    return;
}
$street = trim((string) ($address->street ?? ''));
$number = trim((string) ($address->street_number ?? ''));
$cityName = '';
if (isset($address->city) && (is_object($address->city) || is_array($address->city))) {
    $city = (object) $address->city;
    $cityName = trim((string) ($city->name ?? ''));
}
if ($cityName === '' && isset($address->city_text)) {
    $cityName = trim((string) $address->city_text);
}
$line = trim($street . ' ' . $number);
if ($cityName !== '') {
    $line = $line !== '' ? ($line . ' - ' . $cityName) : $cityName;
}
echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
