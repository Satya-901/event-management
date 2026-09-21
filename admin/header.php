<?php
/**
 * Utsavam - Super Admin Header
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/storage/DataStoreFactory.php';
require_once __DIR__ . '/../includes/services/LeadService.php';

requireSuperAdmin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$unreadLeadCount = LeadService::getStats()['new'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>Super Admin - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --admin-bg: #f8fafc;
            --admin-sidebar-bg: #090d16;
            --admin-sidebar-border: #1e293b;
            --admin-sidebar-text: #94a3b8;
            --admin-card-bg: #ffffff;
            --admin-card-border: #e2e8f0;
            --admin-card-text: #0f172a;
            --admin-primary: #2563eb;
            --admin-primary-hover: #1d4ed8;
            --admin-primary-subtle: #eff6ff;
            --admin-accent: #2563eb;
            --admin-text-main: #0f172a;
            --admin-text-muted: #64748b;
        }
        body {
            background-color: var(--admin-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--admin-text-main);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .admin-sidebar {
            width: 260px;
            background-color: var(--admin-sidebar-bg);
            border-right: 1px solid var(--admin-sidebar-border);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .admin-brand {
            padding: 22px 20px;
            border-bottom: 1px solid var(--admin-sidebar-border);
        }
        .admin-menu {
            list-style: none;
            padding: 16px 12px;
            margin: 0;
            flex-grow: 1;
        }
        .admin-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--admin-sidebar-text);
            text-decoration: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.15s ease-in-out;
        }
        .admin-link:hover {
            color: #ffffff;
            background-color: #1e293b;
        }
        .admin-link.active {
            color: #ffffff;
            background-color: var(--admin-primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            font-weight: 600;
        }
        .admin-content {
            margin-left: 260px;
            padding: 28px 32px;
            min-height: 100vh;
            background-color: var(--admin-bg);
        }
        .admin-topbar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
        .card-dark {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 14px !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02) !important;
            color: #0f172a !important;
        }
        .admin-content h1, .admin-content h2, .admin-content h3, .admin-content h4, .admin-content h5, .admin-content h6,
        .admin-content .text-white {
            color: #0f172a !important;
        }
        .table-dark-custom {
            --bs-table-bg: transparent;
            --bs-table-color: #1e293b;
            --bs-table-border-color: #f1f5f9;
            color: #1e293b;
            margin-bottom: 0;
        }
        .table-dark-custom thead th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0 !important;
            white-space: nowrap;
        }
        .table-dark-custom tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-size: 13.5px;
        }
        .table-dark-custom tbody tr:hover td {
            background-color: #f8fafc;
        }
        .form-control-dark,
        .form-control,
        .form-select {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-control-dark:focus,
        .form-control:focus,
        .form-select:focus {
            background-color: #ffffff !important;
            border-color: #2563eb !important;
            color: #0f172a !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        }
        .form-control-dark::placeholder,
        .form-control::placeholder {
            color: #94a3b8 !important;
        }
        .admin-content .btn-outline-light {
            border-color: #cbd5e1 !important;
            color: #334155 !important;
            background-color: #ffffff !important;
        }
        .admin-content .btn-outline-light:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #94a3b8 !important;
        }
        .admin-content .btn-warning,
        .admin-mobile-topbar .btn-warning {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }
        .admin-content .btn-warning:hover,
        .admin-mobile-topbar .btn-warning:hover {
            background-color: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            color: #ffffff !important;
        }
        .admin-content .badge.bg-dark {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
        }

        /* Mobile App Navigation & Responsive Utilities */
        .admin-mobile-topbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 14px;
            padding-top: max(10px, env(safe-area-inset-top));
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }
        .admin-mobile-topbar .text-white {
            color: #0f172a !important;
        }
        .admin-mobile-topbar .text-warning {
            color: #2563eb !important;
        }
        .admin-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1045;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding-top: 6px;
            padding-bottom: max(8px, env(safe-area-inset-bottom));
            box-shadow: 0 -4px 20px rgba(15, 23, 42, 0.06);
        }
        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            padding: 3px 2px;
            min-height: 50px;
            border-radius: 10px;
            transition: all 0.15s ease-in-out;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }
        .bottom-nav-item:active {
            transform: scale(0.92);
        }
        .bottom-nav-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 26px;
            border-radius: 14px;
            margin-bottom: 2px;
            transition: all 0.2s ease;
        }
        .bottom-nav-icon i {
            width: 19px;
            height: 19px;
        }
        .bottom-nav-label {
            font-size: 11px;
            font-weight: 500;
            line-height: 1.1;
            letter-spacing: -0.2px;
        }
        .bottom-nav-item.active {
            color: #2563eb;
        }
        .bottom-nav-item.active .bottom-nav-icon {
            background-color: rgba(37, 99, 235, 0.1);
            color: #2563eb;
        }
        .bottom-nav-item.active .bottom-nav-label {
            font-weight: 700;
            color: #2563eb;
        }

        /* Offcanvas Drawer Styling */
        .admin-drawer {
            background-color: #ffffff !important;
            color: #0f172a;
            border-right: 1px solid #e2e8f0;
            max-width: 320px;
        }
        .admin-drawer .offcanvas-header {
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 20px;
        }
        .admin-drawer .btn-close-white {
            filter: none;
        }
        .drawer-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            color: #334155;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.15s;
        }
        .drawer-nav-item:hover, .drawer-nav-item:active {
            color: #0f172a;
            background-color: #f1f5f9;
        }
        .drawer-nav-item.active {
            background-color: #2563eb;
            color: #ffffff;
            font-weight: 600;
        }

        @media (max-width: 991px) {
            .admin-sidebar { display: none !important; }
            .admin-content {
                margin-left: 0 !important;
                padding: 18px 16px 96px 16px !important;
            }
            .admin-topbar { display: none !important; }
            .card-dark {
                border-radius: 14px;
            }
            .table-responsive {
                border-radius: 12px;
                -webkit-overflow-scrolling: touch;
                border: 1px solid #e2e8f0;
            }
            .table-responsive table th, .table-responsive table td {
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>

<!-- Mobile App Top Bar -->
<header class="admin-mobile-topbar d-lg-none">
    <div class="d-flex align-items-center justify-content-between w-100">
        <a href="/admin/dashboard" class="text-decoration-none d-flex align-items-center gap-2">
            <span class="badge bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                <i data-lucide="shield" style="width:18px;height:18px;"></i>
            </span>
            <div>
                <div class="fw-bold fs-6 lh-1" style="color:#0f172a;"><?= e(APP_NAME) ?></div>
                <div class="text-uppercase" style="font-size:10px; font-weight:700; letter-spacing:0.5px; color:#2563eb;">Super Admin</div>
            </div>
        </a>

        <div class="d-flex align-items-center gap-2">
            <a href="/admin/clients/create" class="btn btn-warning btn-sm fw-bold px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-sm" style="font-size:12px;">
                <i data-lucide="plus" style="width:14px;height:14px;"></i>
                <span>Add Tenant</span>
            </a>
            <button class="btn btn-white border text-dark p-1.5 rounded-3 d-flex align-items-center justify-content-center shadow-xs" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileDrawer" aria-label="Open navigation menu">
                <i data-lucide="menu" style="width:19px;height:19px;"></i>
            </button>
        </div>
    </div>
</header>

<!-- Desktop Sidebar -->
<aside class="admin-sidebar d-none d-lg-flex">
    <div class="admin-brand">
        <div class="d-flex align-items-center gap-2.5">
            <span class="badge bg-primary text-white p-2 rounded-3 shadow-sm"><i data-lucide="shield" style="width:18px;height:18px;"></i></span>
            <div>
                <div class="fw-bold text-white fs-6 lh-1"><?= e(APP_NAME) ?></div>
                <span class="text-primary text-uppercase" style="font-size:10px; font-weight:700; letter-spacing:0.5px;">Super Admin</span>
            </div>
        </div>
    </div>

    <ul class="admin-menu">
        <li>
            <a href="/admin/dashboard" class="admin-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" style="width:18px;height:18px;"></i> Overview
            </a>
        </li>
        <li>
            <a href="/admin/leads" class="admin-link <?= $currentPage === 'leads' ? 'active' : '' ?>">
                <i data-lucide="inbox" style="width:18px;height:18px;"></i>
                <span class="flex-grow-1">Event Inquiries</span>
                <?php if (!empty($unreadLeadCount)): ?>
                    <span class="badge bg-danger rounded-pill px-2 py-0.5 text-white" style="font-size:10px;"><?= $unreadLeadCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="/admin/clients" class="admin-link <?= in_array($currentPage, ['clients', 'client_create']) ? 'active' : '' ?>">
                <i data-lucide="building" style="width:18px;height:18px;"></i> Client Tenants
            </a>
        </li>
        <li>
            <a href="/admin/events" class="admin-link <?= $currentPage === 'events' ? 'active' : '' ?>">
                <i data-lucide="calendar" style="width:18px;height:18px;"></i> Global Events
            </a>
        </li>
        <li>
            <a href="/admin/bookings" class="admin-link <?= $currentPage === 'bookings' ? 'active' : '' ?>">
                <i data-lucide="ticket" style="width:18px;height:18px;"></i> All Bookings
            </a>
        </li>
        <li>
            <a href="/admin/reports" class="admin-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i data-lucide="bar-chart-2" style="width:18px;height:18px;"></i> Platform Reports
            </a>
        </li>
        <li>
            <a href="/admin/activity" class="admin-link <?= $currentPage === 'activity' ? 'active' : '' ?>">
                <i data-lucide="activity" style="width:18px;height:18px;"></i> Audit Activity
            </a>
        </li>
        <li>
            <a href="/admin/settings" class="admin-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                <i data-lucide="sliders" style="width:18px;height:18px;"></i> System Config
            </a>
        </li>
    </ul>

    <div class="p-3 border-top border-secondary border-opacity-25 mt-auto">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-truncate">
                <div class="text-white small fw-semibold text-truncate"><?= e($currentUser['name'] ?? 'Super Admin') ?></div>
                <div class="text-muted text-xs" style="font-size:11px;">Master Operator</div>
            </div>
            <a href="/admin/logout" class="text-danger text-decoration-none" title="Log Out">
                <i data-lucide="log-out" style="width:16px;height:16px;"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Main Container -->
<div class="admin-content">
    <div class="admin-topbar d-none d-lg-flex">
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-light text-dark border border-secondary border-opacity-25 font-monospace small px-2.5 py-1.5">
                Storage: <?= strtoupper(DATA_DRIVER) ?> Mode
            </span>
            <span class="text-muted small">
                Environment: <span class="fw-semibold text-dark"><?= e(APP_ENV) ?></span>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="/admin/clients/create" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm">
                <i data-lucide="plus" style="width:15px;height:15px;"></i> Provision New Client
            </a>
            <a href="/" target="_blank" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5">
                <i data-lucide="external-link" style="width:14px;height:14px;"></i> View Public Website
            </a>
        </div>
    </div>
