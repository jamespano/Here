<?php
session_start();

define('DB_PATH', __DIR__ . '/database.sqlite');
define('BASE_URL', '');
define('APP_NAME', 'FaceConnect');
define('SITE_TITLE', 'FaceConnect');
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);

date_default_timezone_set('UTC');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function init_db() {
    $pdo = db();
    $schema = __DIR__ . '/schema.sql';
    if (file_exists($schema)) {
        $pdo->exec(file_get_contents($schema));
    }
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token.';
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error'] = 'Please log in to continue.';
        redirect('login.php');
    }
}

function current_user() {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            session_destroy();
            $user = null;
        }
    }
    return $user;
}

function slug($text) {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    if (!$time) return $datetime;
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('M d, Y', $time);
}

function upload_file($file, $folder = '') {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_FILE_SIZE) return null;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = UPLOAD_DIR . ($folder ? '/' . $folder : '') . '/' . $name;
    if ($folder && !is_dir(UPLOAD_DIR . '/' . $folder)) {
        mkdir(UPLOAD_DIR . '/' . $folder, 0755, true);
    }
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return UPLOAD_URL . ($folder ? $folder . '/' : '') . $name;
    }
    return null;
}

function flash($key = 'message') {
    if (!empty($_SESSION[$key])) {
        $val = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $val;
    }
    return null;
}

init_db();
