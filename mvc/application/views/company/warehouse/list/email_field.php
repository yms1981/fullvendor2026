<?php
/**
 * @var object $listItem
 */
$users = $listItem->users ?? null;
$email = '';
if (is_array($users) && isset($users[0])) {
    $email = (string) ($users[0]->email ?? '');
} elseif (is_object($users) && isset($users->email)) {
    $email = (string) $users->email;
} elseif (is_object($users) && isset($users[0])) {
    $email = (string) ($users[0]->email ?? '');
}
echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
