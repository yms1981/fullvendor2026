<?php
/**
 * @var object $listItem item from the list for a given record
 */
$sellers = $listItem->sellers ?? [];

$formatSellerName = static function ($name) {
    if ($name === null || $name === '') {
        return '---';
    }
    $name = (string) $name;

    return function_exists('mb_strtoupper') ? mb_strtoupper($name, 'UTF-8') : strtoupper($name);
};

if (count($sellers) > 1) {
    $parts = array_map(static function ($seller) use ($formatSellerName) {
        return $formatSellerName($seller->user->name ?? null);
    }, $sellers);
    echo htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
} elseif (count($sellers) === 1) {
    echo htmlspecialchars($formatSellerName($sellers[0]->user->name ?? null), ENT_QUOTES, 'UTF-8');
}
?>
