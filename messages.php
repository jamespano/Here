<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$conversations = get_conversations($user['id']);
$allUsers = get_users();
$notMessaged = [];
foreach ($allUsers as $u) {
    if ($u['id'] == $user['id']) continue;
    $notMessaged[] = $u;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages | FaceConnect</title>
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

<div class="app-layout">
    <div class="sidebar-left">
        <div class="section">
            <div class="shortcut active" style="justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>Messenger</span>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="section card">
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <h3 style="font-size:16px;font-weight:600;color:var(--dark);">Chats</h3>
            </div>
            <div class="messages-list">
                <?php foreach ($conversations as $c): 
                    $other = get_user((int)$c['other_user_id']);
                ?>
                <a href="message.php?id=<?= (int)$c['id'] ?>" class="conv-item <?= (isset($_GET['id']) && (int)$_GET['id'] == $c['id']) ? 'active' : '' ?>">
                    <img src="<?= h(UPLOAD_URL . $other['avatar']) ?>" alt="">
                    <div class="conv-info">
                        <div class="conv-name"><?= h($other['first_name'] . ' ' . $other['last_name']) ?></div>
                        <div class="conv-preview"><?= h($c['last_message'] ?? 'No messages yet') ?></div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                        <div class="conv-time"><?= $c['last_message_at'] ? time_ago($c['last_message_at']) : '' ?></div>
                        <?php if ($c['unread_count']): ?><div class="conv-unread"></div><?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php if (!$conversations): ?>
                <div class="empty-state" style="padding:40px 16px;"><h3>No conversations yet</h3><p>Start a new message from friends.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="sidebar-right">
        <div class="section">
            <div class="section-title">New message</div>
            <div style="padding:0 16px 12px;">
                <input type="text" placeholder="Search people" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:6px;outline:none;">
                <div style="margin-top:12px;">
                    <?php foreach (array_slice($notMessaged, 0, 6) as $u): ?>
                    <a href="message.php?user=<?= (int)$u['id'] ?>" class="shortcut" style="justify-content:space-between;">
                        <img src="<?= h(UPLOAD_URL . $u['avatar']) ?>" alt="" style="width:40px;height:40px;">
                        <span style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($u['first_name'] . ' ' . $u['last_name']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
