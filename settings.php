<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['update_profile'])) {
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
        $_SESSION['message'] = 'Profile updated.';
        redirect('settings.php');
    }
    if (isset($_POST['change_password'])) {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($old, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            db()->prepare('UPDATE users SET password = ?, updated_at = datetime(\'now\') WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            $message = 'Password changed.';
        }
    }
}
$genderOptions = ['female' => 'Female', 'male' => 'Male', 'custom' => 'Custom'];
$relOptions = ['Single' => 'Single', 'In a relationship' => 'In a relationship', 'Engaged' => 'Engaged', 'Married' => 'Married', 'complicated' => "It's complicated"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings | FaceConnect</title>
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
    <h2 style="font-size:20px;font-weight:600;margin-bottom:20px;">Settings</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <div class="card settings-form">
        <div class="card-body">
            <h3 style="font-size:16px;font-weight:600;margin-bottom:16px;">General Account Settings</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="update_profile" value="1">
                <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:20px;">
                    <div style="position:relative;">
                        <img src="<?= h(UPLOAD_URL . $user['avatar']) ?>" class="profile-picker" id="avatarPreview" alt="">
                        <input type="file" name="avatar" accept="image/*" class="hidden" onchange="previewImage(this,'avatarPreview')">
                        <label style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,0.6);color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;">Change photo</label>
                    </div>
                    <div style="position:relative;width:260px;height:140px;flex-shrink:0;">
                        <img src="<?= h(UPLOAD_URL . $user['cover_photo']) ?>" id="coverPreview" style="width:100%;height:100%;object-fit:cover;border-radius:12px;border:4px solid #fff;">
                        <input type="file" name="cover" accept="image/*" class="hidden" onchange="previewImage(this,'coverPreview')">
                        <label style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,0.6);color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;">Change cover</label>
                    </div>
                </div>
                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>First name</label>
                        <input type="text" name="first_name" value="<?= h($user['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last name</label>
                        <input type="text" name="last_name" value="<?= h($user['last_name']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= h($user['email']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Bio / About</label>
                    <textarea name="bio" rows="3"><?= h($user['bio']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Work</label>
                    <input type="text" name="work" value="<?= h($user['work']) ?>" placeholder="e.g. Software Engineer">
                </div>
                <div class="form-group">
                    <label>Education</label>
                    <input type="text" name="education" value="<?= h($user['education']) ?>" placeholder="e.g. University">
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="<?= h($user['city']) ?>">
                </div>
                <div class="form-group">
                    <label>Relationship status</label>
                    <select name="relationship_status">
                        <option value="">Select</option>
                        <?php foreach ($relOptions as $k=>$v): ?>
                        <option value="<?= h($k) ?>" <?= ($user['relationship_status']===$k)?'selected':'' ?>><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <?php foreach ($genderOptions as $k=>$v): ?>
                        <option value="<?= h($k) ?>" <?= ($user['gender']===$k)?'selected':'' ?>><?= h($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" name="birthday" value="<?= h($user['birthday']) ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= h($user['phone']) ?>">
                </div>
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website" value="<?= h($user['website']) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </div>
    </div>

    <div class="card settings-form" style="margin-top:12px;">
        <div class="card-body">
            <h3 style="font-size:16px;font-weight:600;margin-bottom:16px;">Change Password</h3>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label>Current password</label>
                    <input type="password" name="old_password" required>
                </div>
                <div class="form-group">
                    <label>New password</label>
                    <input type="password" name="new_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const file = input.files && input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => document.getElementById(previewId).src = e.target.result;
    reader.readAsDataURL(file);
}
</script>
</body>
</html>
