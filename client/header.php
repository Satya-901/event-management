<?php
/**
 * Utsavam - Client Portal Header
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
            --brand-primary: #c2410c;
            --brand-dark: #7c2d12;
            --sidebar-bg: #1c1917;
            --sidebar-hover: #292524;
        }
        body {
            background-color: #fcfbf9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #292524;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            min-height: 100vh;
            color: #d6d3d1;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 20px;
            border-bottom: 1px solid #292524;
        }
        .sidebar-menu {
            list-style: none;
            padding: 15px 10px;
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
            color: #a8a29e;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s;
        }
        .sidebar-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
        }
        .sidebar-link.active {
            color: #ffffff;
            background-color: #c2410c;
        }
        .main-content {
            margin-left: 250px;
            padding: 24px 32px;
            min-height: 100vh;
        }
        .top-bar {
            background: #ffffff;
            border: 1px solid #e7e5e4;
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .tenant-badge {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .card {
            border: 1px solid #e7e5e4;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        @media (max-width: 991px) {
            .sidebar { width: 100%; height: auto; min-height: 0; position: relative; }
            .main-content { margin-left: 0; padding: 16px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark p-2 rounded-3"><i data-lucide="sparkles" style="width:16px;height:16px;"></i></span>
            <div>
                <div class="fw-bold text-white fs-6"><?= e(APP_NAME) ?></div>
                <div class="text-xs text-muted" style="font-size:11px;"><?= e(APP_TAGLINE) ?></div>
            </div>
        </div>
        <div class="mt-3 pt-3 border-top border-secondary border-opacity-25">
            <span class="tenant-badge d-inline-block text-truncate" style="max-width: 200px;">
                <i data-lucide="building" style="width:12px;height:12px;"></i> <?= e($currentClient['name'] ?? 'Client Space') ?>
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
            <a href="/client/scanner" class="sidebar-link <?= $currentPage === 'scanner' ? 'active' : '' ?>" style="color: #fb923c;">
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
            <div class="text-truncate">
                <div class="text-white small fw-semibold text-truncate"><?= e($currentUser['name'] ?? 'Organizer') ?></div>
                <div class="text-muted text-xs" style="font-size:11px;">Organizer Staff</div>
            </div>
            <a href="/client/logout" class="text-danger text-decoration-none" title="Log Out">
                <i data-lucide="log-out" style="width:16px;height:16px;"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Main Container -->
<div class="main-content">
    <!-- Topbar -->
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
            <a href="/client/scanner" class="btn btn-warning btn-sm d-inline-flex align-items-center gap-2 fw-semibold text-dark">
                <i data-lucide="qr-code" style="width:15px;height:15px;"></i> Scan & Check-In
            </a>
        </div>
    </div>
