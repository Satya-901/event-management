<?php
/**
 * Utsavam - Configuration & Bootstrap
 * Brand: Utsavam - A place for celebrations and events.
 */

// Strict error reporting in development
error_reporting(E_ALL);

// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Strip surrounding quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Config Helper
function env($key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    if ($val === 'true' || $val === '(true)') return true;
    if ($val === 'false' || $val === '(false)') return false;
    if ($val === 'null' || $val === '(null)') return null;
    return $val;
}

// Core Constants
define('APP_NAME', env('APP_NAME', 'Utsavam'));
define('APP_TAGLINE', 'A place for celebrations and events.');
define('APP_ENV', env('APP_ENV', 'local'));
define('APP_DEBUG', (bool)env('APP_DEBUG', true));
define('APP_URL', rtrim(env('APP_URL', ''), '/'));

define('DATA_DRIVER', env('DATA_DRIVER', 'json'));
define('JSON_STORAGE_PATH', __DIR__ . '/../' . ltrim(env('JSON_STORAGE_PATH', 'storage/json'), '/'));
define('STORAGE_PATH', JSON_STORAGE_PATH);
define('UPLOADS_PATH', __DIR__ . '/../uploads');

define('SESSION_NAME', env('SESSION_NAME', 'utsavam_session'));
define('QR_VERIFY_SECRET', env('QR_VERIFY_SECRET', 'utsavam_secret_token_verify_key_2026'));

// Database Constants (Future MySQL)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'utsavam'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

// SMTP Mail Constants
define('MAIL_HOST', env('MAIL_HOST', ''));
define('MAIL_PORT', (int)env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'support@utsavam.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Utsavam'));

// Setup session security for cross-origin iframe and standalone execution
if (session_status() === PHP_SESSION_NONE) {
    $sessionName = SESSION_NAME;

    // Check for fallback session ID from POST or GET when cookies are restricted in iframes
    if (empty($_COOKIE[$sessionName])) {
        $candidateId = $_POST['_session_id'] ?? $_GET['_session_id'] ?? null;
        if (is_string($candidateId) && preg_match('/^[a-zA-Z0-9,-]{16,64}$/', $candidateId)) {
            session_id($candidateId);
        }
    }

    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '0');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'None');

    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None'
    ]);

    session_name($sessionName);
    session_start();
}

// Debug display configuration
if (!APP_DEBUG) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

// Ensure required storage and uploads directories exist
$requiredDirs = [
    JSON_STORAGE_PATH,
    UPLOADS_PATH,
    UPLOADS_PATH . '/clients',
    UPLOADS_PATH . '/events',
    UPLOADS_PATH . '/gallery',
];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
