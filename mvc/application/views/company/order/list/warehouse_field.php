<?php
/**
 * Columna Warehouse del listado de órdenes: nombre del almacenero (users).
 *
 * @var object $listItem item from the list for a given record
 */
$name = '';

if (! empty($listItem->warehouse_user_display)) {
    $name = trim((string) $listItem->warehouse_user_display);
}
if ($name === '' && ! empty($listItem->warehouse_user_name)) {
    $name = trim((string) $listItem->warehouse_user_name);
}
if ($name === '') {
    $wu = $listItem->warehouse_user ?? null;
    if (is_object($wu)) {
        if (is_object($wu->user ?? null)) {
            $name = trim((string) ($wu->user->name ?? ''));
            if ($name === '') {
                $name = trim((string) ($wu->user->email ?? ''));
            }
        }
        if ($name === '') {
            $name = trim((string) ($wu->name ?? ''));
        }
        if ($name === '' && ! empty($wu->first_name)) {
            $name = trim(trim((string) $wu->first_name) . ' ' . trim((string) ($wu->last_name ?? '')));
        }
    }
}
if ($name === '') {
    $warehouse = $listItem->warehouse ?? null;
    if (is_object($warehouse)) {
        $name = trim((string) ($warehouse->name ?? ''));
    } elseif (is_array($warehouse)) {
        $name = trim((string) ($warehouse['name'] ?? ''));
    }
}
if ($name !== '') {
    $name = mb_strtoupper($name, 'UTF-8');
}
?>
<?= htmlspecialchars($name) ?>
