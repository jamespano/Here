<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$notifications = get_notifications($user['id']);
mark_notifications_read($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications | FaceConnect</title>
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
    <a href="feed.php" style="padding:12px 0; color:var(--gray-text);">&larr; Back to feed</a>
    <h2 style="font-size:20px;font-weight:600;margin-bottom:16px;">Notifications</h2>
    <div class="card">
        <?php if (!$notifications): ?>
        <div class="card-body"><div class="empty-state"><h3>No notifications</h3><p>You'll see updates here when friends interact with you.</p></div></div>
        <?php else: ?>
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);color:var(--gray-text);font-size:13px;">All notifications marked as read.</div>
        <?php foreach ($notifications as $n):
            $actor = get_user((int)$n['actor_id']);
        ?>
        <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>" onclick="location.href='<?= $n['post_id'] ? 'post.php?id='.$n['post_id'] : ($n['conversation_id'] ? 'message.php?id='.$n['conversation_id'] : 'feed.php') ?>'">
            <img src="<?= h(UPLOAD_URL . $actor['avatar']) ?>" alt="">
            <div class="notif-text">
                <div>
                    <strong><?= h($actor['first_name'] . ' ' . $actor['last_name']) ?></strong>
                    <?php switch ($n['type']): case 'like': ?> liked your post.<?php break; case 'comment': ?> commented on your post.<?php break; case 'friend_accept': ?> accepted your friend request.<?php break; case 'message': ?> sent you a message.<?php break; default: ?> interacted with you.<?php break; endswitch; ?>
                </div>
                <span class="notif-time"><?= time_ago($n['created_at']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
