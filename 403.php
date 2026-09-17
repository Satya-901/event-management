<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Restricted | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background-color: #fcfbf9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #292524;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: #ffffff;
            border: 1px solid #fee2e2;
            border-radius: 16px;
            padding: 40px;
            max-width: 520px;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fef2f2;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .btn-brand {
            background: #c2410c;
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-brand:hover {
            background: #9a3412;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-circle">
            <i data-lucide="shield-alert" style="width:32px;height:32px;"></i>
        </div>
        <h3 class="fw-bold mb-2">403 - Access Forbidden</h3>
        <p class="text-danger fw-medium mb-3">Tenant Security Boundary Enforced</p>
        <p class="text-muted mb-4">You do not have authorization to view, edit, or access this organization's data. Cross-tenant queries and tampering attempts are strictly blocked and logged.</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="/client/dashboard" class="btn btn-brand d-inline-flex align-items-center gap-2">
                <i data-lucide="layout-dashboard" style="width:16px;height:16px;"></i> Return to Dashboard
            </a>
            <a href="/client/login" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i data-lucide="log-in" style="width:16px;height:16px;"></i> Switch Account
            </a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
