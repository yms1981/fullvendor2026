<?php
/**
 * @var object $listItem
 */
$users = $listItem->users ?? null;
$phone = '';
if (is_array($users) && isset($users[0])) {
    $u = $users[0];
    $phone = trim((string) ($u->phone ?? $u->phone_number ?? ''));
} elseif (is_object($users)) {
    $phone = trim((string) ($users->phone ?? $users->phone_number ?? ''));
}
echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
