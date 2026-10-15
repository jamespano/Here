<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('feed.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $birthday = $_POST['birthday'] ?? null;

    if (!$first || !$last || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare('INSERT INTO users (first_name, last_name, email, password, gender, birthday, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime(\'now\'))');
            $stmt->execute([$first, $last, $email, $hash, $gender, $birthday]);
            $newId = db()->lastInsertId();
            $_SESSION['user_id'] = $newId;
            $_SESSION['user_email'] = $email;
            redirect('settings.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create account | FaceConnect</title>
<link rel="stylesheet" href="css/style.css">
<style>
body { background: #1877f2; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
.auth-card { max-width: 440px; width: 100%; background: #fff; border-radius: 12px; padding: 32px 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.auth-card .logo { font-size: 40px; font-weight: bold; color: #1877f2; text-align: center; margin-bottom: 8px; letter-spacing: -1px; }
.auth-card .tagline { text-align: center; color: #1c1e21; font-size: 18px; margin-bottom: 24px; font-weight: 500; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 14px; font-weight: 500; color: #1c1e21; margin-bottom: 6px; }
.form-group input, .form-group select { width: 100%; padding: 12px 14px; border: 1px solid #dddfe2; border-radius: 6px; font-size: 14px; outline: none; box-sizing: border-box; }
.form-group input:focus, .form-group select:focus { border-color: #1877f2; }
.btn-signup { width: 100%; padding: 14px; background: #42b72a; color: #fff; border: none; border-radius: 6px; font-size: 18px; font-weight: 700; cursor: pointer; margin-top: 10px; }
.btn-signup:hover { background: #36a423; }
.btn-signup:disabled { opacity: 0.7; cursor: not-allowed; }
.link-text { text-align: center; font-size: 14px; color: #1c1e21; margin-top: 20px; }
.link-text a { color: #1877f2; font-weight: 600; }
.alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; text-align: center; }
.alert-error { background: #fbe9e7; color: #b3261e; border: 1px solid #f5c2c7; }
.gender-group { display: flex; gap: 14px; margin-top: 2px; }
.gender-item { display: flex; align-items: center; gap: 6px; font-size: 14px; color: #1c1e21; }
</style>
</head>
<body>
<div class="auth-card">
    <div class="logo">FaceConnect</div>
    <div class="tagline">Create an account to connect with friends and family.</div>
    <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <form method="POST" action="" id="regForm">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label>First name</label>
                <input type="text" name="first_name" required autocomplete="given-name" autofocus>
            </div>
            <div class="form-group">
                <label>Last name</label>
                <input type="text" name="last_name" required autocomplete="family-name">
            </div>
        </div>
        <div class="form-group">
            <label>Email address or phone number</label>
            <input type="text" name="email" required autocomplete="email">
        </div>
        <div class="form-group">
            <label>New password</label>
            <input type="password" name="password" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label>Gender</label>
            <div class="gender-group">
                <label class="gender-item"><input type="radio" name="gender" value="female" checked> Female</label>
                <label class="gender-item"><input type="radio" name="gender" value="male"> Male</label>
                <label class="gender-item"><input type="radio" name="gender" value="custom"> Custom</label>
            </div>
        </div>
        <div class="form-group">
            <label>Birthday</label>
            <input type="date" name="birthday">
        </div>
        <button type="submit" class="btn-signup">Sign up</button>
    </form>
    <div class="link-text">Already have an account? <a href="login.php">Log in</a></div>
</div>
</body>
</html>
