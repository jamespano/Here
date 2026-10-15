<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$post_id = (int)($_GET['id'] ?? 0);
$post = get_post($post_id);
if (!$post) {
    $_SESSION['error'] = 'Post not found.';
    redirect('feed.php');
}

$poster = get_user($post['user_id']);
$isOwner = $post['user_id'] == $user['id'];
$liked = false;
$stmt = db()->prepare('SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?');
$stmt->execute([$post_id, $user['id']]);
if ($stmt->fetch()) $liked = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['create_post'])) {
        $content = trim($_POST['content'] ?? '');
        $image = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $image = upload_file($_FILES['photo'], 'posts');
        }
        if (!$content && !$image) {
            $_SESSION['error'] = 'Please write something or add a photo.';
        } else {
            create_post($user['id'], $content, $image);
        }
        redirect('post.php?id=' . $post_id);
    }
    if (isset($_POST['add_comment'])) {
        $content = trim($_POST['content'] ?? '');
        if ($content) {
            $comment = add_comment($post_id, $user['id'], $content);
            if ($post['user_id'] != $user['id']) {
                add_notification($post['user_id'], $user['id'], 'comment', $post_id, $comment['id']);
            }
        }
        redirect('post.php?id=' . $post_id);
    }
    if (isset($_POST['delete_post']) && $isOwner) {
        delete_post($post_id, $user['id']);
        redirect('feed.php');
    }
    if (isset($_POST['toggle_like'])) {
        toggle_like($post_id, $user['id']);
        if ($post['user_id'] != $user['id']) {
            $stmt = db()->prepare('SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?');
            $stmt->execute([$post_id, $user['id']]);
            if ($stmt->fetch()) add_notification($post['user_id'], $user['id'], 'like', $post_id);
        }
        redirect('post.php?id=' . $post_id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($post['first_name'] . ' ' . $post['last_name']) ?> | FaceConnect</title>
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

    <div class="post">
        <div class="post-header">
            <a href="profile.php?id=<?= (int)$poster['id'] ?>">
                <img src="<?= h(UPLOAD_URL . $poster['avatar']) ?>" class="post-avatar" alt="">
            </a>
            <div class="post-meta">
                <div class="post-name"><a href="profile.php?id=<?= (int)$poster['id'] ?>"><?= h($poster['first_name'] . ' ' . $poster['last_name']) ?></a></div>
                <div class="post-time"><?= time_ago($post['created_at']) ?></div>
                <div class="post-actions">
                    <?php if ($isOwner): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this post?')">
                        <?= csrf_field() ?>
                        <button type="submit" name="delete_post" class="btn btn-sm btn-outline">Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($post['content']): ?>
        <div class="post-content"><?= h($post['content']) ?></div>
        <?php endif; ?>
        <?php if ($post['image']): ?>
        <img src="<?= h(UPLOAD_URL . $post['image']) ?>" class="post-image" alt="">
        <?php endif; ?>
        <div class="post-footer">
            <div class="action" style="cursor:pointer;" id="like-btn">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 3c1.1 0 2 .9 2 2s0 2 -1 1 1 1 1 2L9 19l-5 -5 -4 -4 1 -1"/></svg>
                <span><?= $post['like_count'] ?? 0 ?> Like<?= ($post['like_count'] ?? 0) != 1 ? 's' : '' ?></span>
            </div>
            <div class="action" style="cursor:pointer;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span><?= count(get_comments($post_id)) ?> Comment</span>
            </div>
            <div class="action"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M7 10l7 7"/></svg><span>Share</span></div>
        </div>
        <div class="post-comments">
            <?php foreach (get_comments($post_id) as $c): ?>
            <div class="comment">
                <img src="<?= h(UPLOAD_URL . $c['avatar']) ?>" class="comment-avatar" alt="">
                <div class="comment-body">
                    <div class="comment-header"><strong><?= h($c['first_name'] . ' ' . $c['last_name']) ?></strong><span class="time"><?= time_ago($c['created_at']) ?></span></div>
                    <div class="comment-content"><?= h($c['content']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <form class="comment-form" method="POST">
                <?= csrf_field() ?>
                <textarea name="content" class="auto-grow" placeholder="Write a comment…" required></textarea>
                <button type="submit" name="add_comment" class="btn btn-primary btn-sm">Comment</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="create-post">
            <form method="POST" action="" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="create_post" value="1">
                <div class="composer">
                    <img src="<?= h(UPLOAD_URL . $user['avatar']) ?>" class="avatar" alt="">
                    <textarea name="content" placeholder="Write a comment…"></textarea>
                </div>
                <div class="tools">
                    <div class="tool"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 0 4.82 0l.06.06a3 3 0 0 0 0 4.82l.06.06"/></svg><span>Photo/video</span><input type="file" name="photo" accept="image/*" class="hidden"></div>
                    <button type="submit" class="btn btn-primary btn-post">Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
