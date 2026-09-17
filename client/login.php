<?php
/**
 * Utsavam - Client Organizer Login
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/storage/DataStoreFactory.php';
require_once __DIR__ . '/../includes/seed.php';

// Ensure demo data is seeded
seedUtsavamDemoData();

// If already logged in as client organizer, redirect to dashboard
if (isClientUser()) {
    redirect('/client/dashboard');
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
            if ($user && in_array($user['role'] ?? '', ['client', 'staff']) && verifyPassword($password, $user['password'])) {
                // Ensure client is active
                $client = getDataStore()->getClientById($user['client_id'] ?? '');
                if (!$client || ($client['status'] ?? '') !== 'active') {
                    $error = "Your organizer space is inactive or suspended. Please contact platform support.";
                } else {
                    loginUser($user);
                    redirect('/client/dashboard');
                }
            } else {
                $error = "Invalid organizer credentials. (Demo: organizer / password123)";
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
    <title>Organizer Login | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background-color: #fffaf5;
            color: #292524;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-card {
            background: #ffffff;
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 36px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px -10px rgba(194, 65, 12, 0.1);
        }
        .btn-brand {
            background: #c2410c;
            color: #ffffff;
            border: none;
            font-weight: 600;
            padding: 10px;
            border-radius: 8px;
        }
        .btn-brand:hover {
            background: #9a3412;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-2">
                <i data-lucide="sparkles" style="width:32px;height:32px;"></i>
            </div>
            <h4 class="fw-bold mb-1"><?= e(APP_NAME) ?></h4>
            <span class="badge bg-warning text-dark text-uppercase px-2 py-1" style="font-size:10px; letter-spacing:1px;">Client Organizer Portal</span>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/client/login">
            <?= csrfInput() ?>
            <div class="mb-3">
                <label class="form-label small text-muted">Organizer Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i data-lucide="user" style="width:16px;height:16px;"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="organizer" value="organizer" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-muted">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i data-lucide="lock" style="width:16px;height:16px;"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" value="password123" required>
                </div>
            </div>

            <button type="submit" class="btn btn-brand w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                <i data-lucide="log-in" style="width:16px;height:16px;"></i> Sign In to Organizer Space
            </button>

            <div class="bg-light p-2 rounded text-center small text-muted">
                Demo Credentials: <span class="fw-bold text-dark">organizer</span> / <span class="fw-bold text-dark">password123</span>
                <div class="text-xs text-secondary mt-1">Tenant: Royal Events (royal-events)</div>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="/admin/login" class="text-muted small text-decoration-none hover-underline">
                Access Platform Super Admin &rarr;
            </a>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
