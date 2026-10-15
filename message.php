<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$conv_id = (int)($_GET['id'] ?? 0);
$target_user = (int)($_GET['user'] ?? 0);

if ($target_user) {
    $conv_id = get_or_create_conversation($user['id'], $target_user);
}

$stmt = db()->prepare('SELECT * FROM conversation_participants WHERE conversation_id = ? AND user_id = ?');
$stmt->execute([$conv_id, $user['id']]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = 'Conversation not found.';
    redirect('messages.php');
}

$stmt = db()->prepare('SELECT cp.user_id FROM conversation_participants cp WHERE cp.conversation_id = ? AND cp.user_id != ?');
$stmt->execute([$conv_id, $user['id']]);
$other = get_user((int)$stmt->fetchColumn());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $content = trim($_POST['content'] ?? '');
    $image = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $image = upload_file($_FILES['image'], 'messages');
    }
    if (!$content && !$image) {
        $_SESSION['error'] = 'Type a message.';
    } else {
        send_message($conv_id, $user['id'], $content, $image);
        add_notification((int)$other['id'], $user['id'], 'message', null, null, null, $conv_id);
    }
    redirect('message.php?id=' . $conv_id);
}

$messages = get_messages($conv_id, $user['id']);
mark_messages_read($conv_id, $user['id']);
$conversations = get_conversations($user['id']);
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
        <a href="messages.php" class="logo" style="font-size:18px;">&larr; Messages</a>
        <div class="search" style="max-width:none;flex:1;max-width:340px;"><input type="text" placeholder="Search FaceConnect"></div>
        <div class="actions">
            <img src="<?= h(UPLOAD_URL . $user['avatar']) ?>" class="avatar-sm" alt="">
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:360px 1fr;height:calc(100vh - 52px);">
    <div class="sidebar-left" style="height:100%;border-right:1px solid var(--border);overflow-y:auto;padding:0;">
        <div style="padding:16px;">
            <h3 style="font-size:17px;font-weight:600;color:var(--dark);margin-bottom:8px;"><?= h($other['first_name'] . ' ' . $other['last_name']) ?></h3>
            <div style="color:var(--gray-text);font-size:13px;"><?= h($other['first_name']) ?> is on FaceConnect</div>
        </div>
        <div style="padding:12px 16px;border-top:1px solid var(--border);color:var(--gray-text);font-size:13px;">
            <strong>Conversation options:</strong><br>
            <a href="#" style="color:var(--link);">Mute conversation</a><br>
            <a href="#" style="color:var(--link);">Ignore messages</a><br>
            <a href="#" style="color:var(--link);">Block messages</a><br>
            <a href="#" style="color:var(--link);">Delete conversation</a>
        </div>
    </div>

    <div class="main-content" style="height:100%;display:flex;flex-direction:column;">
        <div class="chat-header">
            <div style="display:flex;align-items:center;gap:12px;">
                <img src="<?= h(UPLOAD_URL . $other['avatar']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;background:var(--hover);">
                <div>
                    <div style="font-weight:600;color:var(--dark);"><?= h($other['first_name'] . ' ' . $other['last_name']) ?></div>
                    <div style="color:var(--gray-text);font-size:12px;">Active now</div>
                </div>
            </div>
        </div>
        <div class="chat-messages" id="chat-messages">
            <?php foreach ($messages as $m): 
                $me = $m['sender_id'] == $user['id'];
            ?>
            <div class="message <?= $me ? 'me' : 'them' ?>">
                <?php if ($m['image']): ?><img src="<?= h(UPLOAD_URL . $m['image']) ?>" alt="" onclick="window.open(this.src)"><?php endif; ?>
                <?= h($m['content']) ?>
                <span class="message-time"><?= time_ago($m['created_at']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <form class="chat-input" method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="file" name="image" accept="image/*" class="hidden" style="display:none;">
            <label style="display:flex;align-items:center;cursor:pointer;padding:6px;background:var(--hover);border-radius:50%;width:40px;height:40px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 0 4.82 0l.06.06a3 3 0 0 0 0 4.82l.06.06"/></svg>
                <input type="file" name="image" accept="image/*" class="hidden" style="display:none;">
            </label>
            <textarea id="msg-input" name="content" placeholder="Type a message…" required></textarea>
            <button type="submit" class="btn btn-primary" style="height:40px;padding:0 16px;">Send</button>
        </form>
    </div>
</div>

<script src="js/script.js"></script>
<script>
document.getElementById('msg-input').addEventListener('input', function(){
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});
</script>
</body>
</html>
