<?php
$pageTitle = "Utsavam - A place for celebrations and events";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/services/ClientService.php';
require_once __DIR__ . '/../includes/services/EventService.php';

$clients = ClientService::getAllClients();
$events = EventService::getEvents();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - A place for celebrations and events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --uts-maroon: #781d42;
            --uts-gold: #c2410c;
            --uts-gold-light: #f59e0b;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #fafaf9;
            color: #1c1917;
        }
        .hero-banner {
            background: linear-gradient(135deg, #431407 0%, #781d42 50%, #1e1b4b 100%);
            color: white;
            padding: 80px 0 60px 0;
            position: relative;
            overflow: hidden;
        }
        .hero-banner::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 70% 30%, rgba(245, 158, 11, 0.15) 0%, transparent 60%);
            pointer-events: none;
        }
        .feature-card {
            border: 1px solid #e7e5e4;
            border-radius: 12px;
            background: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3" style="background-color: #1c1917 !important;">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/">
            <span class="badge bg-warning text-dark p-2 rounded-3"><i data-lucide="sparkles" style="width:18px;height:18px;"></i></span>
            <span class="fs-4"><?= e(APP_NAME) ?></span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="/client/login" class="btn btn-outline-light btn-sm px-3">
                <i data-lucide="building" style="width:14px;height:14px;"></i> Organizer Portal
            </a>
            <a href="/admin/login" class="btn btn-warning btn-sm fw-bold text-dark px-3">
                <i data-lucide="shield" style="width:14px;height:14px;"></i> Super Admin
            </a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-banner">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-20 text-warning text-xs fw-semibold mb-3">
                    <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Multi-Tenant Event & Pass Verification Engine
                </div>
                <h1 class="display-4 fw-black text-white mb-3" style="font-weight: 800;">
                    A place for celebrations and events.
                </h1>
                <p class="lead text-light opacity-90 mb-4" style="max-width: 650px;">
                    Utsavam empowers event organizers with dedicated white-labeled portal spaces, custom registration forms, instant QR pass generation, and turnstile gate scanner verification.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="/royal-events/" class="btn btn-warning btn-lg fw-bold text-dark px-4 shadow-sm">
                        <i data-lucide="compass" style="width:18px;height:18px;"></i> Explore Royal Events Space
                    </a>
                    <a href="/royal-events/dandiya-night-2026/" class="btn btn-outline-light btn-lg px-4">
                        <i data-lucide="ticket" style="width:18px;height:18px;"></i> Book Dandiya 2026
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-center">
                <div class="p-4 bg-white bg-opacity-10 backdrop-blur rounded-4 border border-white border-opacity-20 text-start">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-warning text-dark font-monospace">DIGITAL GATE PASS</span>
                        <i data-lucide="qr-code" class="text-warning"></i>
                    </div>
                    <div class="fw-bold text-white fs-5">Dandiya Night 2026</div>
                    <div class="text-light opacity-75 small mb-3">Attendee: Priya Sharma (2 Passes)</div>
                    <a href="/verify/uts_token_priya_confirmed_demo_2026" class="btn btn-light btn-sm w-100 fw-semibold text-dark">
                        Inspect Demo QR Pass &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Quick Navigation Matrix -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h3 class="fw-bold text-dark">Platform Interactive Showcase</h3>
            <p class="text-muted">Test each persona and operational workflow of the Utsavam ecosystem.</p>
        </div>

        <div class="row g-4">
            <!-- 1. Super Admin -->
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 d-flex flex-column">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 d-inline-flex mb-3 align-self-start">
                        <i data-lucide="shield-check" style="width:28px;height:28px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Super Admin Command</h5>
                    <p class="text-muted small mb-3 flex-grow-1">
                        Manage all client tenants, provision new event spaces, oversee global bookings, view platform audit logs, and configure system drivers.
                    </p>
                    <div class="bg-light p-2 rounded small font-monospace text-muted mb-3" style="font-size:11px;">
                        User: <strong>superadmin</strong> | Pass: <strong>password123</strong>
                    </div>
                    <a href="/admin/login" class="btn btn-dark w-100 btn-sm fw-semibold">
                        Enter Super Admin Portal &rarr;
                    </a>
                </div>
            </div>

            <!-- 2. Client Organizer Dashboard -->
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 d-flex flex-column">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 d-inline-flex mb-3 align-self-start">
                        <i data-lucide="layout-dashboard" style="width:28px;height:28px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Client Organizer Portal</h5>
                    <p class="text-muted small mb-3 flex-grow-1">
                        Strict multi-tenant dashboard for Royal Events: manage portfolio, build custom registration questions, view Excel sheet, and inspect turnout.
                    </p>
                    <div class="bg-light p-2 rounded small font-monospace text-muted mb-3" style="font-size:11px;">
                        User: <strong>royal_admin</strong> | Pass: <strong>password123</strong>
                    </div>
                    <a href="/client/login" class="btn btn-warning w-100 btn-sm fw-bold text-dark">
                        Login as Organizer &rarr;
                    </a>
                </div>
            </div>

            <!-- 3. Gate QR Scanner & Turnstile -->
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 d-flex flex-column">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 d-inline-flex mb-3 align-self-start">
                        <i data-lucide="scan" style="width:28px;height:28px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Gate Turnstile Scanner</h5>
                    <p class="text-muted small mb-3 flex-grow-1">
                        Mobile-friendly optical camera scanner and manual fallback tool. Instant pass verification with audio chimes and anti-duplicate check-in protection.
                    </p>
                    <div class="bg-light p-2 rounded small text-muted mb-3" style="font-size:11px;">
                        Requires Client Login (Auto redirects to login if unauthenticated)
                    </div>
                    <a href="/client/scanner" class="btn btn-success w-100 btn-sm fw-semibold">
                        Open Gate Scanner &rarr;
                    </a>
                </div>
            </div>

            <!-- 4. Client Public Space -->
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 d-flex flex-column">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex mb-3 align-self-start">
                        <i data-lucide="globe" style="width:28px;height:28px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Tenant Public Space</h5>
                    <p class="text-muted small mb-3 flex-grow-1">
                        Unique client URL: <code>/royal-events/</code>. Displays only Royal Events' active celebrations, brand header, search filters, and booking buttons.
                    </p>
                    <a href="/royal-events/" class="btn btn-outline-dark w-100 btn-sm">
                        View /royal-events/ &rarr;
                    </a>
                </div>
            </div>

            <!-- 5. Event Landing Page -->
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 d-flex flex-column">
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 d-inline-flex mb-3 align-self-start">
                        <i data-lucide="calendar" style="width:28px;height:28px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Event Landing Page</h5>
                    <p class="text-muted small mb-3 flex-grow-1">
                        Dedicated booking portal for <code>/royal-events/dandiya-night-2026/</code>. Venue maps, live countdown timer, and dynamic reservation form.
                    </p>
                    <a href="/royal-events/dandiya-night-2026/" class="btn btn-outline-dark w-100 btn-sm">
                        View Event Page &rarr;
                    </a>
                </div>
            </div>

            <!-- 6. Mobile Gate Pass -->
            <div class="col-md-4">
                <div class="feature-card p-4 h-100 d-flex flex-column">
                    <div class="p-3 bg-secondary bg-opacity-10 text-dark rounded-3 d-inline-flex mb-3 align-self-start">
                        <i data-lucide="qr-code" style="width:28px;height:28px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Attendee Digital Pass</h5>
                    <p class="text-muted small mb-3 flex-grow-1">
                        Real-time verified attendee pass with QR code, security watermark, Apple/Google Wallet styled badge, and print button.
                    </p>
                    <a href="/verify/uts_token_priya_confirmed_demo_2026" class="btn btn-outline-dark w-100 btn-sm">
                        View Verified Pass &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-white border-top py-4">
    <div class="container text-center text-muted small">
        <p class="mb-1 fw-bold text-dark"><?= e(APP_NAME) ?> - <?= e(APP_TAGLINE) ?></p>
        <p class="mb-0">Production-ready Core PHP 8+ architecture • Strict Multi-Tenant Backend Isolation • Dual Storage (JSON/SQL) DAL</p>
    </div>
</footer>

<script>
    lucide.createIcons();
</script>
</body>
</html>
