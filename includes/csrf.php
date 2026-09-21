<?php
/**
 * CSRF Protection
 * Provides dual session-bound and cryptographic HMAC signed tokens
 * to ensure reliable form submission across cross-origin iframes and standalone views.
 */

function getCsrfSecret(): string {
    return defined('QR_VERIFY_SECRET') ? QR_VERIFY_SECRET : 'utsavam_csrf_secret_salt_fallback_2026';
}

function generateCsrfToken(): string {
    $time = time();
    $nonce = bin2hex(random_bytes(16));
    $secret = getCsrfSecret();
    $signature = hash_hmac('sha256', $nonce . '|' . $time, $secret);
    $token = $nonce . '.' . $time . '.' . $signature;

    $_SESSION['csrf_token'] = $token;
    return $token;
}

function getCsrfToken(): string {
    if (!empty($_SESSION['csrf_token']) && validateCsrfToken($_SESSION['csrf_token'])) {
        return $_SESSION['csrf_token'];
    }
    return generateCsrfToken();
}

function csrfField(): string {
    $token = getCsrfToken();
    $sessionId = session_id();
    $html = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    if (!empty($sessionId)) {
        $html .= "\n" . '<input type="hidden" name="_session_id" value="' . htmlspecialchars($sessionId, ENT_QUOTES, 'UTF-8') . '">';
    }
    return $html;
}

function csrfInput(): string {
    return csrfField();
}

function validateCsrfToken(?string $token): bool {
    if (empty($token) || !is_string($token)) {
        return false;
    }

    // 1. Direct match with session token if present
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }

    // 2. Cryptographic HMAC validation (protects against session drops in restrictive iframes)
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        [$nonce, $timeStr, $sig] = $parts;
        if (ctype_xdigit($nonce) && is_numeric($timeStr)) {
            $time = (int)$timeStr;
            $now = time();
            // Valid within 24 hours (86400 seconds) and not clock-skewed ahead by > 120s
            if ($time <= ($now + 120) && ($now - $time) <= 86400) {
                $expectedSig = hash_hmac('sha256', $nonce . '|' . $time, getCsrfSecret());
                if (hash_equals($expectedSig, $sig)) {
                    // Synchronize session token
                    $_SESSION['csrf_token'] = $token;
                    return true;
                }
            }
        }
    }

    // 3. Fallback: simple hex match if legacy 32/64 char token was set in session
    if (!empty($_SESSION['csrf_token']) && strlen($token) >= 32 && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }

    return false;
}

function verifyCsrfRequest(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!validateCsrfToken($token)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json', true, 403);
                echo json_encode(['success' => false, 'error' => 'Invalid or expired CSRF token. Please refresh the page.']);
                exit;
            }
            http_response_code(403);
            die('Invalid or expired CSRF token. Please return to the previous page and try again.');
        }
    }
}
