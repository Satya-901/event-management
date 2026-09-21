<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | <?= e(APP_NAME) ?></title>
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
            border: 1px solid #e7e5e4;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
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
        <div class="brand-badge">
            <i data-lucide="sparkles" style="width:14px;height:14px;"></i> <?= e(APP_NAME) ?>
        </div>
        <h1 class="display-4 fw-bold text-danger mb-2">404</h1>
        <h4 class="fw-semibold mb-3">Page or Event Not Found</h4>
        <p class="text-muted mb-4">The event, client space, or page you are searching for might have ended, been moved, or does not exist.</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="/" class="btn btn-brand d-inline-flex align-items-center gap-2">
                <i data-lucide="home" style="width:16px;height:16px;"></i> Return Home
            </a>
            <a href="/#list-your-event" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i data-lucide="mail" style="width:16px;height:16px;"></i> Contact Support
            </a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
