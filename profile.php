<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$view_id = (int)($_GET['id'] ?? $user['id']);
$profile = get_user($view_id);
if (!$profile) {
    $_SESSION['error'] = 'User not found.';
    redirect('feed.php');
}
$isOwn = $view_id == $user['id'];
$rel = friendship_status($user['id'], $view_id);
$friends = get_friends($view_id);
$friendsCount = friends_count($view_id);
$posts = get_feed_posts($view_id, 50);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['send_request'])) {
        send_friend_request($user['id'], $view_id);
        redirect('profile.php?id=' . $view_id);
    }
    if (isset($_POST['accept_request'])) {
        accept_friend_request($rel['user_one_id'], $rel['user_two_id']);
        redirect('profile.php?id=' . $view_id);
    }
    if (isset($_POST['reject_request'])) {
        reject_friend_request($rel['user_one_id'], $rel['user_two_id']);
        redirect('profile.php?id=' . $view_id);
    }
    if (isset($_POST['remove_friend'])) {
        remove_friend($user['id'], $view_id);
        redirect('profile.php?id=' . $view_id);
    }
    if (isset($_POST['update_profile']) && $isOwn) {
        $fields = [];
        foreach (['first_name','last_name','bio','work','education','city','relationship_status','phone','website','gender','birthday'] as $f) {
            if (isset($_POST[$f])) $fields[$f] = trim($_POST[$f]);
        }
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $avatar = upload_file($_FILES['avatar'], 'avatars');
            if ($avatar) $fields['avatar'] = basename($avatar);
        }
        if (!empty($_FILES['cover']['tmp_name'])) {
            $cover = upload_file($_FILES['cover'], 'covers');
            if ($cover) $fields['cover_photo'] = basename($cover);
        }
        update_user_profile($user['id'], $fields);
        redirect('profile.php?id=' . $user['id']);
    }
}

$genderOptions = ['female' => 'Female', 'male' => 'Male', 'custom' => 'Custom'];
$relOptions = ['Single' => 'Single', 'In a relationship' => 'In a relationship', 'Engaged' => 'Engaged', 'Married' => 'Married', 'Complicated' => "It's complicated"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($profile['first_name'] . ' ' . $profile['last_name']) ?> | FaceConnect</title>
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
    <div class="profile-header" style="background-image:url('<?= h(UPLOAD_URL . $profile['cover_photo']) ?>')">
        <div class="profile-avatar-wrap">
            <img src="<?= h(UPLOAD_URL . $profile['avatar']) ?>" class="profile-avatar" alt="">
        </div>
    </div>

    <div class="profile-info">
        <?php if ($isOwn): ?>
        <form method="POST" action="" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:flex-start;">
            <div style="position:relative;">
                <img src="<?= h(UPLOAD_URL . $profile['avatar']) ?>" class="profile-picker" id="avatarPreview" alt="">
                <input type="file" name="avatar" accept="image/*" class="hidden" onchange="previewImage(this, 'avatarPreview')">
                <label style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,0.6);color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;">Change</label>
            </div>
            <div style="position:relative; width:140px; height:140px;">
                <img src="<?= h(UPLOAD_URL . $profile['cover_photo']) ?>" id="coverPreview" style="width:100%;height:100%;object-fit:cover;border-radius:12px;border:4px solid #fff;">
                <input type="file" name="cover" accept="image/*" class="hidden" onchange="previewImage(this, 'coverPreview')">
                <label style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,0.6);color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;">Change</label>
            </div>
        </form>
        <?php else: ?>
        <img src="<?= h(UPLOAD_URL . $profile['avatar']) ?>" class="profile-avatar" alt="" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid #fff;">
        <?php endif; ?>

        <div class="profile-name"><?= h($profile['first_name'] . ' ' . $profile['last_name']) ?></div>
        <?php if ($profile['work']): ?><div class="profile-username"><?= h($profile['work']) ?></div><?php endif; ?>
        <?php if ($profile['bio']): ?><div class="profile-bio"><?= h($profile['bio']) ?></div><?php endif; ?>
        <div class="profile-details">
            <?php if ($profile['city']): ?><span class="profile-detail"><?= h($profile['city']) ?></span><?php endif; ?>
            <?php if ($profile['education']): ?><span class="profile-detail"><?= h($profile['education']) ?></span><?php endif; ?>
            <?php if ($profile['relationship_status']): ?><span class="profile-detail"><?= h($profile['relationship_status']) ?></span><?php endif; ?>
        </div>

        <div class="profile-actions">
            <?php if ($isOwn): ?>
            <button class="btn btn-primary btn-full" onclick="location.href='settings.php'">Edit profile</button>
            <a href="logout.php" class="btn btn-outline btn-full">Log out</a>
            <?php else: ?>
            <?php if (!$rel): ?>
            <button class="btn btn-primary btn-full" onclick="sendFriendRequest(<?= $view_id ?>, this)">Add friend</button>
            <?php elseif ($rel['status'] === 'pending'): ?>
            <button class="btn btn-outline btn-full" disabled>Friend request sent</button>
            <?php elseif ($rel['status'] === 'accepted'): ?>
            <button class="btn btn-danger btn-full" onclick="if(confirm('Remove this friend?')){ removeFriend(<?= $view_id ?>) }">Unfriend</button>
            <?php endif; ?>
            <button class="btn btn-outline btn-full">Message</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isOwn && isset($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= h($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="profile-tabs">
        <div class="profile-tab active" onclick="location.href='profile.php?id=<?= $view_id ?>'">Posts</div>
        <div class="profile-tab" onclick="location.href='friends.php?user=<?= $view_id ?>'">Friends <span style="font-weight:400"><?= $friendsCount ?></span></div>
    </div>

    <div class="friends-grid">
        <?php foreach ($posts as $p): ?>
        <div class="post" style="width:100%;">
            <div class="post-header">
                <a href="profile.php?id=<?= (int)$p['user_id'] ?>">
                    <img src="<?= h(UPLOAD_URL . $p['avatar']) ?>" class="post-avatar" alt="">
                </a>
                <div class="post-meta">
                    <div class="post-name"><a href="profile.php?id=<?= (int)$p['user_id'] ?>"><?= h($p['first_name'] . ' ' . $p['last_name']) ?></a></div>
                    <div class="post-time"><?= time_ago($p['created_at']) ?></div>
                    <?php if ($isOwn && $p['user_id'] == $user['id']): ?>
                    <div class="post-actions">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this post?')">
                            <?= csrf_field() ?>
                            <button type="submit" name="delete_post" class="btn btn-sm btn-outline">Delete</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($p['content']): ?><div class="post-content"><?= h($p['content']) ?></div><?php endif; ?>
            <?php if ($p['image']): ?><img src="<?= h(UPLOAD_URL . $p['image']) ?>" class="post-image" alt=""><?php endif; ?>
            <div class="post-footer">
                <div class="action" onclick="toggleLike(<?= (int)$p['id'] ?>, this)" id="like-btn-<?= (int)$p['id'] ?>">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 3c1.1 0 2 .9 2 2s0 2 -1 1 1 1 1 2L9 19l-5 -5 -4 -4 1 -1"/></svg>
                    <span class="like-count"><?= $p['like_count'] ?? 0 ?></span><span>Like</span>
                </div>
                <div class="action" onclick="document.getElementById('comment-input-<?= (int)$p['id'] ?>').focus()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span><?= $p['comment_count'] ?? 0 ?> Comment</span>
                </div>
                <div class="action"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M7 10l7 7"/></svg><span>Share</span></div>
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
        <div class="empty-state" style="grid-column:1/-1;"><h3>No posts yet</h3></div>
        <?php endif; ?>
    </div>
</div>

<script src="js/script.js"></script>
<script>
function previewImage(input, previewId) {
    const file = input.files && input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => document.getElementById(previewId).src = e.target.result;
    reader.readAsDataURL(file);
}
function removeFriend(uid) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="csrf_token" value="">' +
        '<input type="hidden" name="remove_friend" value="1">';
    const csrf = document.querySelector('input[name="csrf_token"]');
    if (csrf) form.querySelector('input[name="csrf_token"]').value = csrf.value;
    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>
