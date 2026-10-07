<?php
/**
 * ChinAI — API bootstrap.
 *
 * Loads on every /api/*.php request: error handling, security headers,
 * hardened session cookie, and shared paths. Chat uses the verified
 * UnlimitedAI browser AJAX/SSE protocol through api/lib/UnlimitedAiClient.php.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('UTC');

define('CHINAI_ROOT', dirname(__DIR__, 2));
define('CHINAI_API_DIR', dirname(__DIR__));
define('CHINAI_DATA_DIR', CHINAI_ROOT . '/data');
define('CHINAI_STORAGE_DIR', CHINAI_ROOT . '/storage');
define('CHINAI_LOG_DIR', CHINAI_DATA_DIR . '/_logs');
define('CHINAI_VERSION', '1.1.0');
/*
 * ------------------------------------------------------------------
 * THE ANSWER ENGINE — UnlimitedAI browser AJAX/SSE transport
 * ------------------------------------------------------------------
 * The public chat page refreshes a frontend nonce, caches the message through
 * WordPress AJAX, then streams its reply through EventSource. The PHP client
 * follows that documented browser sequence directly. No local Node service,
 * browser process, account credential, or fabricated session value is needed.
 */
// Keep the actual chat-page URL explicit. The AJAX endpoint lives at the same
// origin, but its nonce/cache/SSE contract is the one served by this page.
$chinaiUnlimitedAiChatUrl = rtrim((string) (getenv('CHINAI_UNLIMITEDAI_CHAT_URL') ?: 'https://unlimitedai.org/chat/'), '/') . '/';
define('CHINAI_UNLIMITEDAI_CHAT_URL', $chinaiUnlimitedAiChatUrl);

// Separate only because the provider's AJAX endpoint is origin-rooted. An
// operator may set the origin explicitly; otherwise derive it from the chat
// page URL instead of silently pointing at a different provider host.
$chinaiUnlimitedAiOrigin = (string) getenv('CHINAI_UNLIMITEDAI_ORIGIN');
if ($chinaiUnlimitedAiOrigin === '') {
    $parts = parse_url(CHINAI_UNLIMITEDAI_CHAT_URL);
    $scheme = is_array($parts) ? (string) ($parts['scheme'] ?? 'https') : 'https';
    $host = is_array($parts) ? (string) ($parts['host'] ?? 'unlimitedai.org') : 'unlimitedai.org';
    $port = is_array($parts) && isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    $chinaiUnlimitedAiOrigin = $scheme . '://' . $host . $port;
}
define('CHINAI_UNLIMITEDAI_ORIGIN', rtrim($chinaiUnlimitedAiOrigin, '/'));

// VISION hand-off — HMAC key that signs the temporary, public image URLs the
// vision stage reads from (Security::visionSig()). Change this on a live
// install to invalidate every previously issued URL.

define('CHINAI_VISION_KEY', 'vvm-' . str_repeat('K9', 16) . '-chinai-vision-system');

// Public origin (used for origin checks; empty = any same-host request).
define('CHINAI_ORIGIN', 'https://app.chinai.uk');

/*
 * The private status hook's shared secret (see api/status_ai_probe.php).
 *
 * status.chinai.uk sends this value as the `X-ChinAI-Status-Token` header
 * (its `STATUS_MONITOR_TOKEN` in .env). Set the SAME value here and on the
 * status host. Empty = the hook answers without a token — convenient on a
 * fresh install; set it in production.
 */
define('CHINAI_STATUS_TOKEN', '');

/*
 * The owner account.
 *
 * This was defined inside api/admin.php, which meant any other endpoint asking
 * "is this the owner?" got a false answer — the constant simply was not there,
 * so the owner had no role and the console fell back to its legacy email check.
 * One definition, loaded everywhere, is the only version that stays true.
 */
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'cjpanong7@gmail.com');
}

/**
 * Internal wrapper around PHP's session handler. Returns the active
 * session marker (2) when a session is already started, otherwise 1.
 */
function chinai_session_state(): int
{
    // PHP starts a session implicitly once any output or header is sent,
    // and session_id() returns the active session id only after that. The
    // helper is used only to gate writes to $_SESSION before the explicit
    // start call, so a plain session_id() presence check is enough.
    if (\function_exists('session_id') && (string) \session_id() !== '') {
        return 2;
    }
    return 1;
}

foreach ([CHINAI_DATA_DIR, CHINAI_STORAGE_DIR, CHINAI_LOG_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

// Session save path: prefer our private data dir, fall back to system temp.
$savePath = CHINAI_DATA_DIR . '/_sessions';
if (!is_dir($savePath)) {
    @mkdir($savePath, 0775, true);
}
if (is_dir($savePath) && is_writable($savePath)) {
    ini_set('session.save_path', $savePath);
} else {
    ini_set('session.save_path', sys_get_temp_dir());
}

// Hardened session cookie: HttpOnly, SameSite=Lax, Secure behind HTTPS,
// 30-day persistence.
session_name('chinai_sid');
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 30,
    'path' => '/',
    'domain' => '',
    'secure' => (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/JsonDb.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/DocText.php';
require_once __DIR__ . '/Models.php';
require_once __DIR__ . '/Text.php';
require_once __DIR__ . '/Features.php';
require_once __DIR__ . '/Support.php';
require_once __DIR__ . '/SecurityLog.php';
require_once __DIR__ . '/Blocks.php';
require_once __DIR__ . '/Jobs.php';
require_once __DIR__ . '/DataPurge.php';

Response::installHandlers();

// Security headers on every API response.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
