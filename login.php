<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('feed.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        db()->prepare('UPDATE users SET is_online = 1, last_seen = datetime(\'now\') WHERE id = ?')->execute([$user['id']]);
        redirect('feed.php');
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in to FaceConnect</title>
<link rel="stylesheet" href="css/style.css">
<style>
body { background: #1877f2; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
.auth-card { max-width: 400px; width: 100%; background: #fff; border-radius: 12px; padding: 32px 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.auth-card .logo { font-size: 40px; font-weight: bold; color: #1877f2; text-align: center; margin-bottom: 8px; letter-spacing: -1px; }
.auth-card .tagline { text-align: center; color: #1c1e21; font-size: 18px; margin-bottom: 24px; font-weight: 500; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 14px; font-weight: 500; color: #1c1e21; margin-bottom: 6px; }
.form-group input { width: 100%; padding: 12px 14px; border: 1px solid #dddfe2; border-radius: 6px; font-size: 14px; outline: none; box-sizing: border-box; }
.form-group input:focus { border-color: #1877f2; }
.btn-login { width: 100%; padding: 12px; background: #1877f2; color: #fff; border: none; border-radius: 6px; font-size: 18px; font-weight: 700; cursor: pointer; margin-bottom: 16px; }
.btn-login:hover { background: #166fe5; }
.auth-divider { display: flex; align-items: center; gap: 12px; margin: 20px 0; font-size: 13px; color: #656770; }
.auth-divider hr { flex: 1; height: 1px; background: #e4e6eb; border: none; }
.btn-create { width: 100%; padding: 12px; background: #42b72a; color: #fff; border: none; border-radius: 6px; font-size: 17px; font-weight: 700; cursor: pointer; }
.btn-create:hover { background: #36a423; }
.link-text { text-align: center; font-size: 14px; color: #1c1e21; margin-top: 16px; }
.link-text a { color: #1877f2; font-weight: 600; }
.alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: center; }
.alert-error { background: #fbe9e7; color: #b3261e; border: 1px solid #f5c2c7; }
</style>
</head>
<body>
<div class="auth-card">
    <div class="logo">FaceConnect</div>
    <div class="tagline">FaceConnect helps you connect and share with the people in your life.</div>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Email address or phone number</label>
            <input type="text" name="email" required autocomplete="email" autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login">Log in</button>
    </form>
    <div class="auth-divider"><hr><span>or</span><hr></div>
    <button type="button" class="btn-create" onclick="location.href='register.php'">Create new account</button>
    <div class="link-text"><a href="#">Forgot password?</a></div>
</div>
</body>
</html>
