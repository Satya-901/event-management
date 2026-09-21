<?php
/**
 * Utsavam - Client Organizer Portal Header
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/storage/DataStoreFactory.php';

requireClient();
$currentUser = getCurrentUser();
$clientId = getCurrentClientId();
$currentClient = getDataStore()->getClientById($clientId);

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= e($currentClient['name'] ?? 'Organizer Portal') ?> - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --client-bg: #f8fafc;
            --client-sidebar-bg: #0c0a09;
            --client-sidebar-border: #292524;
            --client-sidebar-text: #a8a29e;
            --client-card-bg: #ffffff;
            --client-card-border: #e2e8f0;
            --client-card-text: #0f172a;
            --client-primary: #ea580c;
            --client-primary-hover: #c2410c;
            --client-primary-subtle: #fff7ed;
            --client-accent: #ea580c;
            --client-text-main: #0f172a;
            --client-text-muted: #64748b;
        }
        body {
            background-color: var(--client-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--client-text-main);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .sidebar {
            width: 260px;
            background-color: var(--client-sidebar-bg);
            border-right: 1px solid var(--client-sidebar-border);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 22px 20px;
            border-bottom: 1px solid var(--client-sidebar-border);
        }
        .sidebar-menu {
            list-style: none;
            padding: 16px 12px;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-item {
            margin-bottom: 4px;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--client-sidebar-text);
            text-decoration: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }
        .sidebar-link:hover {
            color: #ffffff;
            background-color: #1c1917;
        }
        .sidebar-link.active {
            color: #ffffff;
            background-color: var(--client-primary);
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
            font-weight: 600;
        }
        .sidebar-link.scanner-highlight {
            color: #fb923c;
            font-weight: 600;
        }
        .sidebar-link.scanner-highlight:hover {
            color: #fdba74;
            background-color: #292524;
        }
        .sidebar-link.scanner-highlight.active {
            color: #ffffff;
            background-color: var(--client-primary);
        }
        .main-content {
            margin-left: 260px;
            padding: 28px 32px;
            min-height: 100vh;
            background-color: var(--client-bg);
        }
        .top-bar {
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
        .tenant-badge {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        .card {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 14px !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02) !important;
            color: #0f172a !important;
        }
        .main-content h1, .main-content h2, .main-content h3, .main-content h4, .main-content h5, .main-content h6 {
            color: #0f172a !important;
        }
        .table-hover tbody tr:hover td {
            background-color: #f8fafc !important;
        }
        .table thead th {
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
        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-size: 13.5px;
        }
        .form-control,
        .form-select {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-control:focus,
        .form-select:focus {
            background-color: #ffffff !important;
            border-color: #ea580c !important;
            color: #0f172a !important;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15) !important;
        }
        .form-control::placeholder {
            color: #94a3b8 !important;
        }
        .btn-brand,
        .btn-warning {
            background-color: #ea580c !important;
            border-color: #ea580c !important;
            color: #ffffff !important;
            font-weight: 600;
        }
        .btn-brand:hover,
        .btn-warning:hover {
            background-color: #c2410c !important;
            border-color: #c2410c !important;
            color: #ffffff !important;
        }
        .btn-outline-warning {
            color: #ea580c !important;
            border-color: #ea580c !important;
        }
        .btn-outline-warning:hover {
            background-color: #ea580c !important;
            color: #ffffff !important;
        }

        /* Mobile App Navigation & Responsive Utilities */
        .client-mobile-topbar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            z-index: 1020;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.06);
        }
        .client-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            z-index: 1040;
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 4px 8px;
            box-shadow: 0 -4px 16px rgba(15, 23, 42, 0.06);
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.96);
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            height: 100%;
            text-decoration: none;
            color: #64748b;
            font-size: 11px;
            font-weight: 500;
            border-radius: 8px;
            padding: 4px 2px;
            transition: all 0.15s ease-in-out;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
        .bottom-nav-item:hover,
        .bottom-nav-item:focus {
            color: #ea580c;
        }
        .bottom-nav-item.active {
            color: #ea580c;
            font-weight: 700;
        }
        .bottom-nav-item.scanner-highlight {
            position: relative;
        }
        .bottom-nav-item.scanner-highlight .bottom-nav-icon {
            background: #ea580c;
            color: #ffffff;
            border-radius: 12px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35);
            margin-bottom: 2px;
        }
        .bottom-nav-item.scanner-highlight.active .bottom-nav-icon {
            background: #c2410c;
            transform: scale(1.05);
        }
        .bottom-nav-icon {
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3px;
        }
        .bottom-nav-icon svg {
            width: 20px;
            height: 20px;
        }
        .bottom-nav-label {
            line-height: 1;
            letter-spacing: -0.2px;
            white-space: nowrap;
        }

        /* Offcanvas Drawer for Mobile */
        .client-drawer {
            max-width: 300px;
            border-right: 1px solid #e2e8f0;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 16px 14px !important;
                padding-bottom: 90px !important;
            }
            .top-bar {
                display: none !important;
            }
        }
        @media (min-width: 992px) {
            .client-mobile-topbar {
                display: none !important;
            }
            .client-bottom-nav {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Mobile App Top Bar (Phone Viewport Only) -->
<header class="client-mobile-topbar d-lg-none" id="clientMobileHeader">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-light border p-1.5 rounded-3 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="offcanvas" data-bs-target="#clientMobileDrawer" aria-label="Toggle navigation drawer" id="mobileDrawerOpenBtn">
            <i data-lucide="menu" style="width:20px;height:20px;color:#0f172a;"></i>
        </button>
        <div class="d-flex flex-column">
            <span class="fw-bold text-dark text-truncate" style="font-size:14px; max-width:170px; line-height:1.2;">
                <?= e($currentClient['name'] ?? APP_NAME) ?>
            </span>
            <span class="text-xs text-muted" style="font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#ea580c !important;">
                Organizer Portal
            </span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-1.5">
        <a href="/client/scanner" class="btn btn-sm btn-warning d-flex align-items-center gap-1 px-2.5 py-1 rounded-3 shadow-xs" title="Open QR Gate Scanner">
            <i data-lucide="scan-line" style="width:14px;height:14px;"></i>
            <span style="font-size:12px; font-weight:600;">Scan</span>
        </a>
        <a href="/<?= e($currentClient['slug']) ?>/" target="_blank" class="btn btn-sm btn-light border p-1.5 rounded-3 text-secondary" title="View Public Space">
            <i data-lucide="external-link" style="width:16px;height:16px;"></i>
        </a>
    </div>
</header>

<!-- Desktop Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-white p-2 rounded-3" style="background-color: #ea580c !important;"><i data-lucide="sparkles" style="width:18px;height:18px;"></i></span>
            <div>
                <div class="fw-bold text-white fs-6"><?= e(APP_NAME) ?></div>
                <div class="text-xs text-secondary" style="font-size:11px;"><?= e(APP_TAGLINE) ?></div>
            </div>
        </div>
        <div class="mt-3 pt-3 border-top border-secondary border-opacity-25">
            <span class="tenant-badge d-inline-block text-truncate" style="max-width: 210px;">
                <i data-lucide="building" style="width:13px;height:13px;vertical-align:-1px;"></i> <?= e($currentClient['name'] ?? 'Client Space') ?>
            </span>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="/client/dashboard" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" style="width:18px;height:18px;"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-item">
            <a href="/client/scanner" class="sidebar-link scanner-highlight <?= $currentPage === 'scanner' ? 'active' : '' ?>">
                <i data-lucide="scan-line" style="width:18px;height:18px;"></i> Live QR Scanner
            </a>
        </li>
        <li class="sidebar-item">
            <a href="/client/sheets" class="sidebar-link <?= $currentPage === 'sheets' ? 'active' : '' ?>">
                <i data-lucide="table" style="width:18px;height:18px;"></i> Booking Sheet
            </a>
        </li>
        <li class="sidebar-item">
            <a href="/client/events" class="sidebar-link <?= in_array($currentPage, ['events', 'event_create', 'event_edit', 'event_builder']) ? 'active' : '' ?>">
                <i data-lucide="calendar" style="width:18px;height:18px;"></i> My Events
            </a>
        </li>
        <li class="sidebar-item">
            <a href="/client/bookings" class="sidebar-link <?= $currentPage === 'bookings' ? 'active' : '' ?>">
                <i data-lucide="ticket" style="width:18px;height:18px;"></i> All Bookings
            </a>
        </li>
        <li class="sidebar-item">
            <a href="/client/reports" class="sidebar-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i data-lucide="bar-chart-3" style="width:18px;height:18px;"></i> Analytics & Reports
            </a>
        </li>
        <li class="sidebar-item">
            <a href="/client/profile" class="sidebar-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <i data-lucide="settings" style="width:18px;height:18px;"></i> Space Settings
            </a>
        </li>
    </ul>

    <div class="p-3 border-top border-secondary border-opacity-25 mt-auto">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-truncate me-2">
                <div class="text-white small fw-semibold text-truncate"><?= e($currentUser['name'] ?? 'Organizer') ?></div>
                <div class="text-muted text-xs" style="font-size:11px;">Organizer Staff</div>
            </div>
            <a href="/client/logout" class="text-danger text-decoration-none p-1.5 rounded hover-bg" title="Log Out">
                <i data-lucide="log-out" style="width:17px;height:17px;"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Main Container -->
<div class="main-content">
    <!-- Desktop Topbar -->
    <div class="top-bar">
        <div class="d-flex align-items-center gap-3">
            <a href="/<?= e($currentClient['slug']) ?>/" target="_blank" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" title="Open Public Organizer URL">
                <i data-lucide="external-link" style="width:14px;height:14px;"></i> Public Space: <strong>/<?= e($currentClient['slug']) ?>/</strong>
            </a>
            <span class="badge bg-light text-secondary border font-monospace" style="font-size:11px;">
                Code: <?= e($currentClient['code']) ?>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="/client/scanner" class="btn btn-warning btn-sm d-inline-flex align-items-center gap-2 fw-semibold text-white shadow-sm">
                <i data-lucide="qr-code" style="width:15px;height:15px;"></i> Scan & Check-In
            </a>
        </div>
    </div>
