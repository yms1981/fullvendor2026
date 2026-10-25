<?php
/**
 * @var object $listItem item from the list for a given record
 */
$tipoD = strtoupper((string) ($listItem->tipo_d ?? 'D'));
if ($tipoD === 'C') {
    echo '';
    return;
}
$name = $listItem->seller->user->name ?? $listItem->seller_info ?? '';
echo $name !== '' ? mb_strtoupper($name, 'UTF-8') : '—';
?>
