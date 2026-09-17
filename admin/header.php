<?php
/**
 * Utsavam - Super Admin Header
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/storage/DataStoreFactory.php';

requireSuperAdmin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
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
            --admin-dark: #0f172a;
            --admin-card: #1e293b;
            --admin-border: #334155;
            --admin-accent: #f59e0b;
        }
        body {
            background-color: #0b0f19;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #f1f5f9;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 260px;
            background-color: var(--admin-dark);
            border-right: 1px solid var(--admin-border);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .admin-brand {
            padding: 24px 20px;
            border-bottom: 1px solid var(--admin-border);
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
            color: #94a3b8;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.15s;
        }
        .admin-link:hover {
            color: #ffffff;
            background-color: #1e293b;
        }
        .admin-link.active {
            color: #0f172a;
            background-color: var(--admin-accent);
            font-weight: 600;
        }
        .admin-content {
            margin-left: 260px;
            padding: 24px 32px;
            min-height: 100vh;
        }
        .admin-topbar {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-dark {
            background-color: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            color: #f1f5f9;
        }
        .table-dark-custom {
            --bs-table-bg: transparent;
            --bs-table-border-color: #334155;
            color: #e2e8f0;
        }
        .form-control-dark {
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
        }
        .form-control-dark:focus {
            background-color: #0f172a;
            border-color: #f59e0b;
            color: #f8fafc;
        }
        @media (max-width: 991px) {
            .admin-sidebar { width: 100%; height: auto; min-height: 0; position: relative; }
            .admin-content { margin-left: 0; padding: 16px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="admin-brand">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark p-2 rounded-3"><i data-lucide="shield" style="width:18px;height:18px;"></i></span>
            <div>
                <div class="fw-bold text-white fs-6"><?= e(APP_NAME) ?></div>
                <span class="badge bg-danger text-uppercase px-2" style="font-size:10px; letter-spacing:0.5px;">Super Admin</span>
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
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-dark border border-secondary text-light font-monospace small">
                Storage: <?= strtoupper(DATA_DRIVER) ?> Mode
            </span>
            <span class="text-muted small">
                Environment: <?= e(APP_ENV) ?>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="/admin/clients/create" class="btn btn-warning btn-sm fw-semibold text-dark d-inline-flex align-items-center gap-1">
                <i data-lucide="plus" style="width:14px;height:14px;"></i> Provision New Client
            </a>
            <a href="/royal-events/" target="_blank" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-1">
                <i data-lucide="external-link" style="width:14px;height:14px;"></i> Demo Space
            </a>
        </div>
    </div>
