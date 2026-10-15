<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$received = get_received_requests($user['id']);
$sent = get_sent_requests($user['id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['accept'])) {
        accept_friend_request((int)$_POST['from_id'], $user['id']);
        $_SESSION['message'] = 'Friend request accepted.';
    }
    if (isset($_POST['reject'])) {
        reject_friend_request((int)$_POST['from_id'], $user['id']);
        $_SESSION['message'] = 'Request rejected.';
    }
    if (isset($_POST['cancel'])) {
        reject_friend_request($user['id'], (int)$_POST['to_id']);
        $_SESSION['message'] = 'Request cancelled.';
    }
    redirect('friend_requests.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Friend requests | FaceConnect</title>
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
    <h2 style="font-size:20px;font-weight:600;margin-bottom:16px;">Friend requests</h2>
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success"><?= h($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:600;margin-bottom:12px;">Requests you received (<?= count($received) ?>)</h3>
            <?php foreach ($received as $r): ?>
            <div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);">
                <img src="<?= h(UPLOAD_URL . $r['avatar']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;background:var(--hover);">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($r['first_name'] . ' ' . $r['last_name']) ?></div>
                    <div style="color:var(--gray-text);font-size:12px;">wants to be friends</div>
                </div>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Accept this friend request?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accept" value="1">
                    <input type="hidden" name="from_id" value="<?= (int)$r['user_one_id'] ?>">
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-right:8px;">Accept</button>
                </form>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Reject this request?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="reject" value="1">
                    <input type="hidden" name="from_id" value="<?= (int)$r['user_one_id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm">Reject</button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php if (!$received): ?>
            <div style="padding:20px;color:var(--gray-text);text-align:center;">No pending requests.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top:12px;">
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:600;margin-bottom:12px;">Requests you sent (<?= count($sent) ?>)</h3>
            <?php foreach ($sent as $s): ?>
            <div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);">
                <img src="<?= h(UPLOAD_URL . $s['avatar']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;background:var(--hover);">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($s['first_name'] . ' ' . $s['last_name']) ?></div>
                    <div style="color:var(--gray-text);font-size:12px;">Request pending</div>
                </div>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Cancel this friend request?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cancel" value="1">
                    <input type="hidden" name="to_id" value="<?= (int)$s['user_two_id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm">Cancel</button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php if (!$sent): ?>
            <div style="padding:20px;color:var(--gray-text);text-align:center;">No sent requests.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
