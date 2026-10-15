<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$view_id = (int)($_GET['user'] ?? $user['id']);
$profile = get_user($view_id);
if (!$profile) {
    $_SESSION['error'] = 'User not found.';
    redirect('feed.php');
}
$friends = get_friends($view_id);
$friendsCount = friends_count($view_id);
$allUsers = get_users();
$notSent = [];
foreach ($allUsers as $u) {
    if ($u['id'] == $user['id']) continue;
    $rel = friendship_status($user['id'], $u['id']);
    if (!$rel) $notSent[] = $u;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Friends | FaceConnect</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="top-header">
    <div class="bar">
        <a href="feed.php" class="logo">FaceConnect</a>
        <div class="search"><input type="text" placeholder="Search FaceConnect"></div>
        <div class="actions">
            <a href="friend_requests.php"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></a>
            <a href="messages.php"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></a>
            <a href="notifications.php"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></a>
            <img src="<?= h(UPLOAD_URL . $user['avatar']) ?>" class="avatar-sm" alt="">
        </div>
    </div>
</div>

<div class="container">
    <a href="profile.php?id=<?= $view_id ?>" style="padding:12px 0; color:var(--gray-text);">&larr; Back to profile</a>
    <h2 style="font-size:20px;font-weight:600;margin-bottom:12px;">Friends</h2>
    <div class="friends-grid">
        <?php foreach ($friends as $f): ?>
        <div class="friend-card">
            <a href="profile.php?id=<?= (int)$f['friend_id'] ?>">
                <img src="<?= h(UPLOAD_URL . $f['avatar']) ?>" alt="">
                <span><?= h($f['first_name'] . ' ' . $f['last_name']) ?></span>
            </a>
        </div>
        <?php endforeach; ?>
        <?php if (!$friends): ?>
        <div style="grid-column:1/-1; text-align:center; padding:24px; color:var(--gray-text);">No friends yet.</div>
        <?php endif; ?>
    </div>

    <h2 style="font-size:20px;font-weight:600;margin:24px 0 12px;">Add friends</h2>
    <div class="friends-grid">
        <?php foreach (array_slice($notSent, 0, 8) as $u): ?>
        <div class="friend-card">
            <a href="profile.php?id=<?= (int)$u['id'] ?>">
                <img src="<?= h(UPLOAD_URL . $u['avatar']) ?>" alt="">
                <span><?= h($u['first_name'] . ' ' . $u['last_name']) ?></span>
            </a>
            <button class="btn btn-sm btn-primary" style="width:100%;font-size:12px;" onclick="sendFriendRequest(<?= (int)$u['id'] ?>, this)">Add</button>
        </div>
        <?php endforeach; ?>
        <?php if (!$notSent): ?>
        <div style="grid-column:1/-1; text-align:center; padding:24px; color:var(--gray-text);">No suggestions.</div>
        <?php endif; ?>
    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
