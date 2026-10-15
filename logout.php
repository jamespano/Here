<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $u = current_user();
    if ($u) db()->prepare('UPDATE users SET is_online = 0, last_seen = datetime(\'now\') WHERE id = ?')->execute([$u['id']]);
}
session_destroy();
redirect('login.php');
