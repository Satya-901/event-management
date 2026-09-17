<?php
/**
 * Utsavam Helper Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

// Safe HTML Escaping
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

// Redirect Helper
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

// JSON response helper
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Flash messaging
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// URL slug generator
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// Unique Code Generator (e.g. UTS-8F42K)
function generateClientCode(): string {
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return 'UTS-' . $code;
}

// Unique Booking Number Generator (e.g. UTS-2026-000124)
function generateBookingNumber(): string {
    $year = date('Y');
    $num = str_pad((string)random_int(100, 999999), 6, '0', STR_PAD_LEFT);
    return "UTS-{$year}-{$num}";
}

// Secure QR Token Generator (Unpredictable, cryptographically secure 48 hex chars)
function generateSecureQrToken(): string {
    return bin2hex(random_bytes(24));
}

// Unique ID Generator
function generateId(string $prefix = ''): string {
    return ($prefix ? $prefix . '_' : '') . bin2hex(random_bytes(8)) . '_' . time();
}

// Date Formatting Helpers
function formatDate(?string $date, string $format = 'd M Y'): string {
    if (!$date) return 'N/A';
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

function formatDateTime(?string $dateTime, string $format = 'd M Y, h:i A'): string {
    if (!$dateTime) return 'N/A';
    try {
        $dt = new DateTime($dateTime);
        return $dt->format($format);
    } catch (Exception $e) {
        return $dateTime;
    }
}

function formatTime(?string $time, string $format = 'h:i A'): string {
    if (!$time) return 'N/A';
    try {
        $dt = new DateTime($time);
        return $dt->format($format);
    } catch (Exception $e) {
        return $time;
    }
}

// URL helper
function appUrl(string $path = ''): string {
    $base = APP_URL;
    if (empty($base)) {
        // Fallback to relative or host
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
        $base = "{$protocol}://{$host}";
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

// Status Badges
function getStatusBadge(string $status): string {
    $statusLower = strtolower($status);
    $map = [
        'confirmed' => ['bg' => 'success', 'text' => 'Confirmed', 'icon' => 'check-circle'],
        'checked_in' => ['bg' => 'primary', 'text' => 'Checked-In', 'icon' => 'user-check'],
        'pending' => ['bg' => 'warning text-dark', 'text' => 'Pending', 'icon' => 'clock'],
        'cancelled' => ['bg' => 'secondary', 'text' => 'Cancelled', 'icon' => 'x-circle'],
        'rejected' => ['bg' => 'danger', 'text' => 'Rejected', 'icon' => 'slash'],
        'expired' => ['bg' => 'dark', 'text' => 'Expired', 'icon' => 'alert-triangle'],
        'active' => ['bg' => 'success', 'text' => 'Active', 'icon' => 'check'],
        'inactive' => ['bg' => 'secondary', 'text' => 'Inactive', 'icon' => 'minus-circle'],
        'draft' => ['bg' => 'info text-dark', 'text' => 'Draft', 'icon' => 'file-text'],
        'published' => ['bg' => 'success', 'text' => 'Published', 'icon' => 'globe'],
        'closed' => ['bg' => 'danger', 'text' => 'Closed', 'icon' => 'lock'],
    ];

    $cfg = $map[$statusLower] ?? ['bg' => 'secondary', 'text' => ucfirst($status), 'icon' => 'circle'];
    return '<span class="badge bg-' . $cfg['bg'] . ' d-inline-flex align-items-center gap-1"><i data-lucide="' . $cfg['icon'] . '" style="width:13px;height:13px;"></i> ' . e($cfg['text']) . '</span>';
}

// Safe File Upload Helper
function handleFileUpload(array $file, string $targetSubDir, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg'], int $maxBytes = 5242880): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload parameters.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file uploaded.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error code: ' . $file['error']];
    }

    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'error' => 'File exceeds maximum allowed size of ' . ($maxBytes / 1048576) . 'MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)];
    }

    // MIME verification
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml'
    ];

    if (!isset($allowedMimes[$ext]) || ($ext !== 'svg' && $mime !== $allowedMimes[$ext])) {
        return ['success' => false, 'error' => 'MIME type verification failed for image.'];
    }

    $targetDir = UPLOADS_PATH . '/' . trim($targetSubDir, '/');
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }

    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $targetDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }

    $publicUrl = '/uploads/' . trim($targetSubDir, '/') . '/' . $safeName;
    return ['success' => true, 'filename' => $safeName, 'url' => $publicUrl];
}
