<?php
/**
 * Utsavam - Super Admin Login
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/storage/DataStoreFactory.php';
require_once __DIR__ . '/../includes/seed.php';

// Ensure demo data is seeded
seedUtsavamDemoData();

// If already logged in as super admin, redirect to dashboard
if (isSuperAdmin()) {
    redirect('/admin/dashboard');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token expired. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password.";
        } else {
            $user = getDataStore()->getUserByUsername($username);
            if ($user && ($user['role'] ?? '') === 'super_admin' && verifyPassword($password, $user['password'])) {
                loginUser($user);
                redirect('/admin/dashboard');
            } else {
                $error = "Invalid Super Admin credentials. (Demo: admin / admin123)";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 36px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }
        .form-control {
            background-color: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        .form-control:focus {
            background-color: #0f172a;
            border-color: #f59e0b;
            color: #f8fafc;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }
        .btn-admin {
            background: #f59e0b;
            color: #0f172a;
            border: none;
            font-weight: 600;
            padding: 10px;
            border-radius: 8px;
        }
        .btn-admin:hover {
            background: #d97706;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-2">
                <i data-lucide="shield" style="width:32px;height:32px;"></i>
            </div>
            <h4 class="fw-bold mb-1"><?= e(APP_NAME) ?> Platform</h4>
            <span class="badge bg-danger text-uppercase px-2 py-1" style="font-size:10px; letter-spacing:1px;">Super Admin Control</span>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/login">
            <?= csrfInput() ?>
            <div class="mb-3">
                <label class="form-label small text-muted">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-muted"><i data-lucide="user" style="width:16px;height:16px;"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="admin" value="admin" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-muted">Master Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-muted"><i data-lucide="lock" style="width:16px;height:16px;"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" value="admin123" required>
                </div>
            </div>

            <button type="submit" class="btn btn-admin w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                <i data-lucide="log-in" style="width:16px;height:16px;"></i> Authenticate as Super Admin
            </button>

            <div class="bg-black bg-opacity-30 p-2 rounded text-center small text-muted">
                Demo Credentials: <span class="text-warning">admin</span> / <span class="text-warning">admin123</span>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="/client/login" class="text-muted small text-decoration-none hover-underline">
                Switch to Client Organizer Portal &rarr;
            </a>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
