<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$db = db();

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    verify_csrf();
    $content = trim($_POST['content'] ?? '');
    $image = null;
    if (!empty($_FILES['photo']['tmp_name'])) {
        $image = upload_file($_FILES['photo'], 'posts');
    }
    if (!$content && !$image) {
        $_SESSION['error'] = 'Please write something or add a photo.';
    } else {
        $privacy = $_POST['privacy'] ?? 'public';
        create_post($user['id'], $content, $image, $privacy);
    }
    redirect('feed.php');
}

$posts = get_feed_posts($user['id']);
$friends = get_friends($user['id']);
$allUsers = get_users();
$notifCount = unread_notifications_count($user['id']);
$friendRequests = get_received_requests($user['id']);
$friendsCount = friends_count($user['id']);
$onlineUsers = [];
$stmt = $db->prepare('SELECT * FROM users WHERE id != ? AND is_online = 1 ORDER BY first_name, last_name LIMIT 8');
$stmt->execute([$user['id']]);
$onlineUsers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FaceConnect | News Feed</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="top-header">
    <div class="bar">
        <a href="feed.php" class="logo">FaceConnect</a>
        <div class="search">
            <input type="text" placeholder="Search FaceConnect">
        </div>
        <div class="actions">
            <a href="friend_requests.php" title="Friends">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </a>
            <a href="messages.php" title="Messenger">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </a>
            <a href="notifications.php" title="Notifications" class="position-rel">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <?php if ($notifCount): ?><span class="conv-unread" id="notif-badge"></span><?php endif; ?>
            </a>
            <div class="dropdown">
                <img src="<?= h(UPLOAD_URL . $user['avatar']) ?>" class="avatar-sm" alt="">
            </div>
        </div>
    </div>
</div>

<div class="app-layout">
    <div class="sidebar-left">
        <div class="section">
            <div class="shortcut active" onclick="location.href='feed.php'">
                <img src="<?= h(UPLOAD_URL . $user['avatar']) ?>" alt="">
                <div>
                    <span><?= h($user['first_name'] . ' ' . $user['last_name']) ?></span>
                    <small>Your profile</small>
                </div>
            </div>
            <div class="shortcut" onclick="location.href='friends.php'">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Friends</span>
            </div>
            <div class="shortcut" onclick="location.href='messages.php'">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2" ry="2"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="17.5" x2="17.51" y2="17.5"/></svg>
                <span>Messages</span>
            </div>
            <div class="shortcut" onclick="location.href='profile.php?id=<?= $user['id'] ?>'">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
                <span>Profile</span>
            </div>
            <div class="shortcut" onclick="location.href='settings.php'">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1M3 10v.6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-.6M15 4.8a1.65 1.65 0 0 0 1-1.51L15.06 3.35a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-.6a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1M15 4.8"/></svg>
                <span>Settings</span>
            </div>
            <a href="logout.php" class="shortcut" style="color: var(--danger);">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Log out</span>
            </a>
        </div>

        <div class="section">
            <div class="section-title">Friends online</div>
            <div style="padding: 0 16px 12px;">
                <?php foreach ($onlineUsers as $ou): ?>
                <div class="shortcut" style="justify-content: flex-start;" title="<?= h($ou['first_name'] . ' ' . $ou['last_name']) ?>">
                    <img src="<?= h(UPLOAD_URL . $ou['avatar']) ?>" alt="" style="width:32px;height:32px;">
                    <div style="flex:1; min-width:0;">
                        <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= h($ou['first_name']) ?></span>
                        <span style="display:inline-flex; align-items:center; gap:4px; color:var(--success); font-size:11px;"><span style="width:6px;height:6px;background:var(--success);border-radius:50%;display:block;"></span> Active</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="section">
            <div class="stories">
                <div class="story" onclick="location.href='profile.php?id=<?= $user['id'] ?>'">
                    <div class="story-avatar me" style="background-image:url('<?= h(UPLOAD_URL . $user['avatar']) ?>')"></div>
                    <span>Your story</span>
                </div>
                <?php foreach ($friends as $f): ?>
                <div class="story" onclick="location.href='profile.php?id=<?= (int)$f['friend_id'] ?>'">
                    <div class="story-avatar" style="background-image:url('<?= h(UPLOAD_URL . $f['avatar']) ?>')"></div>
                    <span><?= h($f['first_name']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section card">
            <div class="create-post">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="composer">
                        <img src="<?= h(UPLOAD_URL . $user['avatar']) ?>" class="avatar" alt="">
                        <textarea name="content" placeholder="What's on your mind, <?= h($user['first_name']) ?>?" required></textarea>
                    </div>
                    <div class="tools">
                        <div class="tool">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 0 4.82 0l.06.06a3 3 0 0 0 0 4.82l.06.06"/></svg>
                            <span>Photo/video</span>
                            <input type="file" name="photo" accept="image/*" class="hidden" onchange="this.form.requestSubmit()">
                        </div>
                        <div class="tool">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/><path d="M12 21v-8a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v8"/></svg>
                            <span>Tag people</span>
                        </div>
                        <div class="tool">
                            <span>Privacy: <b><?= h($_POST['privacy'] ?? 'Public') ?></b></span>
                        </div>
                        <button type="submit" name="create_post" class="btn btn-primary btn-post">Post</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?= h($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= h($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php foreach ($posts as $p): ?>
        <div class="post" id="post-<?= (int)$p['id'] ?>">
            <div class="post-header">
                <a href="profile.php?id=<?= (int)$p['user_id'] ?>">
                    <img src="<?= h(UPLOAD_URL . $p['avatar']) ?>" class="post-avatar" alt="">
                </a>
                <div class="post-meta">
                    <div class="post-name"><a href="profile.php?id=<?= (int)$p['user_id'] ?>"><?= h($p['first_name'] . ' ' . $p['last_name']) ?></a></div>
                    <div class="post-time"><?= time_ago($p['created_at']) ?></div>
                    <div class="post-actions">
                        <?php if ($p['user_id'] == $user['id']): ?>
                        <a onclick="return confirm('Delete this post?')">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($p['content']): ?>
            <div class="post-content"><?= h($p['content']) ?></div>
            <?php endif; ?>
            <?php if ($p['image']): ?>
            <img src="<?= h(UPLOAD_URL . $p['image']) ?>" class="post-image" alt="">
            <?php endif; ?>
            <div class="post-footer">
                <div class="action" onclick="toggleLike(<?= (int)$p['id'] ?>, this)" id="like-btn-<?= (int)$p['id'] ?>" <?= $p['user_liked'] ? 'class="action active"' : '' ?>>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 3c1.1 0 2 .9 2 2s0 2 -1 1 1 1 1 2L9 19l-5 -5 -4 -4 1 -1"/></svg>
                    <span class="like-count"><?= $p['like_count'] ?? 0 ?></span>
                    <span>Like</span>
                </div>
                <div class="action" onclick="document.getElementById('comment-input-<?= (int)$p['id'] ?>').focus()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span><?= $p['comment_count'] ?? 0 ?> Comment</span>
                </div>
                <div class="action">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M7 10l7 7"/></svg>
                    <span>Share</span>
                </div>
            </div>
            <div class="post-comments" id="comments-<?= (int)$p['id'] ?>">
                <?php foreach (get_comments((int)$p['id']) as $c): ?>
                <div class="comment">
                    <img src="<?= h(UPLOAD_URL . $c['avatar']) ?>" class="comment-avatar" alt="">
                    <div class="comment-body">
                        <div class="comment-header"><strong><?= h($c['first_name'] . ' ' . $c['last_name']) ?></strong><span class="time"><?= time_ago($c['created_at']) ?></span></div>
                        <div class="comment-content"><?= h($c['content']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <form class="comment-form" onsubmit="addComment(<?= (int)$p['id'] ?>, this); return false;">
                    <textarea name="content" class="auto-grow" placeholder="Write a comment…" required></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">Comment</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!$posts): ?>
        <div class="empty-state"><h3>No posts yet</h3><p>Create a post or add friends to see updates.</p></div>
        <?php endif; ?>
    </div>

    <div class="sidebar-right">
        <div class="section">
            <div class="section-title">Suggestions</div>
            <?php $suggested = array_slice(array_filter($allUsers, function($u) use ($user) { return $u['id'] != $user['id']; }), 0, 6); ?>
            <?php foreach ($suggested as $s): ?>
            <div class="shortcut" onclick="location.href='profile.php?id=<?= (int)$s['id'] ?>'">
                <img src="<?= h(UPLOAD_URL . $s['avatar']) ?>" alt="">
                <div style="flex:1; min-width:0;">
                    <span style="display:block; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= h($s['first_name'] . ' ' . $s['last_name']) ?></span>
                    <small>Add friend</small>
                </div>
                <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); sendFriendRequest(<?= (int)$s['id'] ?>, this)" style="height:28px;padding:0 8px;font-size:12px;">Add</button>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <div class="section-title">Pages</div>
            <div style="padding: 0 16px 12px; color: var(--gray-text); font-size: 13px;">No pages to show yet.</div>
        </div>
    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
