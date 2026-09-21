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
                $error = "Invalid organizer credentials. Please check your username and password.";
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
            background-color: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-image: radial-gradient(#cbd5e1 0.75px, transparent 0.75px);
            background-size: 24px 24px;
        }
        .login-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 32px 24px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
        }
        @media (min-width: 576px) {
            .login-card {
                padding: 40px 36px;
            }
        }
        .form-control {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a;
            font-size: 14.5px;
            border-radius: 9px;
        }
        .form-control:focus {
            background-color: #ffffff;
            border-color: #ea580c;
            color: #0f172a;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15);
        }
        .input-group-text {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: #64748b;
            border-radius: 9px;
        }
        .btn-brand {
            background: #ea580c;
            color: #ffffff;
            border: none;
            font-weight: 600;
            padding: 11px;
            border-radius: 9px;
            transition: all 0.15s ease-in-out;
            box-shadow: 0 2px 6px rgba(234, 88, 12, 0.25);
        }
        .btn-brand:hover {
            background: #c2410c;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-3 bg-warning bg-opacity-10 text-warning mb-2 shadow-xs" style="color: #ea580c !important;">
                <i data-lucide="sparkles" style="width:30px;height:30px;"></i>
            </div>
            <h4 class="fw-bold mb-1" style="color:#0f172a;"><?= e(APP_NAME) ?></h4>
            <span class="badge bg-warning bg-opacity-10 text-uppercase px-2.5 py-1 fw-bold" style="color: #ea580c !important; font-size:10.5px; letter-spacing:0.5px;">Client Organizer Space</span>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
                <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="/client/login">
            <?= csrfInput() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Organizer Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i data-lucide="user" style="width:16px;height:16px;"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-secondary">Staff Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i data-lucide="lock" style="width:16px;height:16px;"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" value="" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn btn-brand w-100 mb-2 d-flex align-items-center justify-content-center gap-2">
                <i data-lucide="log-in" style="width:16px;height:16px;"></i> Sign In to Organizer Space
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="/" class="text-secondary small text-decoration-none hover-underline d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width:13px;height:13px;"></i>
                <span>Back to Homepage</span>
            </a>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
