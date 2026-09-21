<?php
/**
 * Utsavam - Master Front Controller & Clean URL Router
 * 
 * Works identically on Apache (mod_rewrite with .htaccess), Nginx,
 * or PHP 8+ CLI built-in web server.
 */

// Initialize Database Seed on First Run
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/seed.php';

// Auto-initialize administrator account if missing
$dataFile = STORAGE_PATH . '/users.json';
if (!file_exists($dataFile)) {
    seedProductionAdmin();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
if (empty($uri)) {
    $uri = '/';
}

// 1. Static Asset Passthrough for CLI Server
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . $uri;
    if (is_file($filePath)) {
        return false; // let built-in server serve static file
    }
}

// 2. Authentication Quick Logouts
if ($uri === '/admin/logout') {
    require_once __DIR__ . '/includes/auth.php';
    logoutUser('admin');
    redirect('/admin/login');
    exit;
}

if ($uri === '/client/logout') {
    require_once __DIR__ . '/includes/auth.php';
    logoutUser('client');
    redirect('/client/login');
    exit;
}

// 3. Exact Route Mapping
$routes = [
    // Super Admin Routes
    '/admin' => '/admin/dashboard.php',
    '/admin/dashboard' => '/admin/dashboard.php',
    '/admin/login' => '/admin/login.php',
    '/admin/leads' => '/admin/leads.php',
    '/admin/clients' => '/admin/clients.php',
    '/admin/clients/create' => '/admin/client_create.php',
    '/admin/events' => '/admin/events.php',
    '/admin/bookings' => '/admin/bookings.php',
    '/admin/reports' => '/admin/reports.php',
    '/admin/activity' => '/admin/activity.php',
    '/admin/settings' => '/admin/settings.php',

    // Client Organizer Routes
    '/client' => '/client/dashboard.php',
    '/client/dashboard' => '/client/dashboard.php',
    '/client/login' => '/client/login.php',
    '/client/events' => '/client/events.php',
    '/client/events/create' => '/client/event_create.php',
    '/client/events/edit' => '/client/event_edit.php',
    '/client/events/builder' => '/client/event_builder.php',
    '/client/bookings' => '/client/bookings.php',
    '/client/scanner' => '/client/scanner.php',
    '/client/sheets' => '/client/sheets.php',
    '/client/reports' => '/client/reports.php',
    '/client/profile' => '/client/profile.php',

    // APIs
    '/api/submit-lead' => '/api/submit-lead.php',
    '/api/book-event' => '/api/book-event.php',
    '/api/scan-qr' => '/api/scan-qr.php',
    '/api/check-in' => '/api/check-in.php',
];

if (isset($routes[$uri])) {
    require __DIR__ . $routes[$uri];
    exit;
}

// 4. Pass Verification Route: /verify/{token}
if (preg_match('#^/verify(?:/([a-zA-Z0-9_\-]+))?$#', $uri, $matches)) {
    $_GET['token'] = $matches[1] ?? '';
    require __DIR__ . '/verify/index.php';
    exit;
}

// 5. Root Homepage: / (Utsavam Showcase & Navigation Hub)
if ($uri === '/') {
    require __DIR__ . '/public/homepage.php';
    exit;
}

// 6. Dynamic Tenant Slugs:
// Pattern A: /{client_slug}/{event_slug}
if (preg_match('#^/([a-zA-Z0-9\-]+)/([a-zA-Z0-9\-]+)$#', $uri, $matches)) {
    $_GET['client_slug'] = $matches[1];
    $_GET['event_slug'] = $matches[2];
    require_once __DIR__ . '/includes/services/ClientService.php';
    require_once __DIR__ . '/includes/services/EventService.php';
    $client = ClientService::getClientBySlug($matches[1]);
    if (!$client) {
        require __DIR__ . '/404.php';
        exit;
    }
    $event = EventService::getEventBySlug($client['id'], $matches[2]);
    if (!$event) {
        require __DIR__ . '/404.php';
        exit;
    }
    require __DIR__ . '/public/event_landing.php';
    exit;
}

// Pattern B: /{client_slug}
if (preg_match('#^/([a-zA-Z0-9\-]+)$#', $uri, $matches)) {
    $_GET['client_slug'] = $matches[1];
    require_once __DIR__ . '/includes/services/ClientService.php';
    $client = ClientService::getClientBySlug($matches[1]);
    if (!$client) {
        require __DIR__ . '/404.php';
        exit;
    }
    require __DIR__ . '/public/client_events.php';
    exit;
}

// Fallback: 404 Not Found
require __DIR__ . '/404.php';
exit;
