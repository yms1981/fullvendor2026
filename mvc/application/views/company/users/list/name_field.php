<?php
/**
 * Nombre del usuario en el listado company/users.
 *
 * @var mixed  $listItem objeto o array fila del listado
 * @var mixed  $value     valor del campo `name` desde el CRUD
 */
$display = '-';

$get = static function ($row, string $key): string {
    if (is_object($row) && isset($row->{$key})) {
        return trim((string) $row->{$key});
    }
    if (is_array($row) && array_key_exists($key, $row)) {
        return trim((string) $row[$key]);
    }

    return '';
};

if (isset($listItem) && (is_object($listItem) || is_array($listItem))) {
    $name = '';
    if (isset($value)) {
        if (is_string($value) || is_numeric($value)) {
            $name = trim((string) $value);
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            $name = trim((string) $value);
        }
    }
    if ($name === '') {
        $name = $get($listItem, 'name');
    }
    if ($name === '') {
        $name = $get($listItem, 'short_name');
    }
    if ($name === '') {
        $email = $get($listItem, 'email');
        if ($email !== '') {
            $name = trim((string) (explode('@', $email, 2)[0] ?? ''));
        }
    }
    if ($name !== '') {
        $display = $name;
    }
}
?>
<span class="user-list-name-cell"><?= htmlspecialchars($display, ENT_QUOTES, 'UTF-8') ?></span>
