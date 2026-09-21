<?php
/**
 * Utsavam Authentication & Multi-Tenant Authorization
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Auth State Helpers
function isLoggedIn(?string $role = null): bool {
    $hasUser = !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
    if (!$hasUser) {
        return false;
    }
    if ($role === null) {
        return true;
    }
    $userRole = $_SESSION['user']['role'] ?? '';
    if ($role === 'admin' || $role === 'super_admin') {
        return $userRole === 'super_admin';
    }
    if ($role === 'client') {
        return in_array($userRole, ['client', 'staff']);
    }
    return $userRole === $role;
}

function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isSuperAdmin(): bool {
    return isLoggedIn() && (getCurrentUser()['role'] ?? '') === 'super_admin';
}

function isClient(): bool {
    return isLoggedIn() && (in_array(getCurrentUser()['role'] ?? '', ['client', 'staff']));
}

function isClientUser(): bool {
    return isClient();
}

function getCurrentClientId(): ?string {
    if (isClient()) {
        return getCurrentUser()['client_id'] ?? null;
    }
    return null;
}

// Access Guard Helpers
function requireSuperAdmin(string $redirectTo = '/admin/login'): void {
    if (!isSuperAdmin()) {
        setFlash('error', 'Super Admin privileges required to access this page.');
        redirect($redirectTo);
    }
}

function requireClient(string $redirectTo = '/client/login'): void {
    if (!isClient()) {
        setFlash('error', 'Client login required to access this portal.');
        redirect($redirectTo);
    }
}

function requireAuth(string $redirectTo = '/admin/login'): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirect($redirectTo);
    }
}

/**
 * Strict Tenant Boundary Check
 * - Super admin can access anything.
 * - Client can ONLY access resources belonging to their own client_id.
 * - If check fails, immediately terminates with 403 or redirect, preventing any ID tampering!
 */
function enforceTenantOwnership(?string $resourceClientId, bool $isAjax = false): void {
    if (isSuperAdmin()) {
        return; // Super Admin has global access
    }

    $currentClientId = getCurrentClientId();
    if (!$currentClientId || empty($resourceClientId) || $currentClientId !== $resourceClientId) {
        if ($isAjax || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            jsonResponse([
                'success' => false,
                'error' => 'Unauthorized access: You do not have permission to view or manipulate this resource.'
            ], 403);
        }
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }
}

// Login Throttling Protection
function checkLoginThrottling(string $username): bool {
    $key = 'login_attempts_' . md5($username);
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];
    
    // Reset window after 15 minutes
    if (time() - $attempts['first_attempt'] > 900) {
        $attempts = ['count' => 0, 'first_attempt' => time()];
        $_SESSION[$key] = $attempts;
    }

    if ($attempts['count'] >= 5) {
        return false; // Throttled
    }
    return true;
}

function recordFailedLogin(string $username): void {
    $key = 'login_attempts_' . md5($username);
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];
    $attempts['count']++;
    $_SESSION[$key] = $attempts;
}

function clearLoginAttempts(string $username): void {
    $key = 'login_attempts_' . md5($username);
    unset($_SESSION[$key]);
}

// User Login Processor
function performLogin(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'name' => $user['name'] ?? $user['username'],
        'role' => $user['role'],
        'client_id' => $user['client_id'] ?? null,
        'client_name' => $user['client_name'] ?? null,
        'logged_in_at' => time()
    ];
}

function loginUser(array $user): void {
    performLogin($user);
}

// User Logout Processor
function performLogout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function logoutUser(?string $type = null): void {
    performLogout();
}

/**
 * Password Hashing and Verification
 */
function verifyPassword(string $password, string $hash): bool {
    if (password_verify($password, $hash)) {
        return true;
    }
    // Safe timing-attack resistant fallback for plain text in demo setups
    return hash_equals($hash, $password);
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}
