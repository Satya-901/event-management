<?php
/**
 * Utsavam - Public Client Space / Event Listing
 * Shows events strictly belonging to this client.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/services/ClientService.php';
require_once __DIR__ . '/../includes/services/EventService.php';

// $client is provided by index.php router, with fallback for direct requests
if (!isset($client) || empty($client)) {
    $clientSlug = $_GET['client_slug'] ?? '';
    $client = $clientSlug ? ClientService::getClientBySlug($clientSlug) : null;
}
if (!$client) {
    require __DIR__ . '/../404.php';
    exit;
}
$events = EventService::getEvents($client['id']);
// Filter to published / active
$activeEvents = array_values(array_filter($events, fn($e) => ($e['status'] ?? '') === 'published'));

// URL LOGIC CHECK:
// - If 0 active events: show "No active events available."
// - If exactly 1 active event: directly open that event!
if (count($activeEvents) === 1) {
    redirect('/' . rawurlencode($client['slug']) . '/' . rawurlencode($activeEvents[0]['slug']) . '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($client['company_name'] ?? $client['name']) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --brand-primary: #c2410c;
            --brand-dark: #7c2d12;
            --brand-cream: #fffaf5;
        }
        body {
            background-color: var(--brand-cream);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #292524;
            min-height: 100vh;
        }
        .client-hero {
            background: linear-gradient(135deg, #7c2d12, #c2410c);
            color: #ffffff;
            padding: 60px 0 40px;
            position: relative;
        }
        .event-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e7e5e4;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 30px rgba(0,0,0,0.08);
        }
        .event-banner-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }
        .btn-brand {
            background: #c2410c;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: 500;
        }
        .btn-brand:hover {
            background: #9a3412;
            color: #ffffff;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="client-hero text-center">
    <div class="container">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-20 text-white small mb-3">
            <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Official Organizer Space
        </div>
        <h1 class="display-5 fw-bold mb-2"><?= e($client['company_name'] ?? $client['name']) ?></h1>
        <p class="lead opacity-90 mx-auto" style="max-width: 600px;">
            Explore upcoming celebrations, festivals, and cultural events organized by <?= e($client['name']) ?>.
        </p>
        <div class="mt-3 text-white-50 small">
            <span><i data-lucide="map-pin" style="width:14px;height:14px;"></i> <?= e($client['address'] ?? 'Bengaluru, India') ?></span>
            <?php if (!empty($client['mobile'])): ?>
                <span class="ms-3"><i data-lucide="phone" style="width:14px;height:14px;"></i> <?= e($client['mobile']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Events Section -->
<main class="container py-5">
    <?php if (empty($activeEvents)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-5">
            <div class="text-muted mb-3">
                <i data-lucide="calendar-x" style="width:54px;height:54px;"></i>
            </div>
            <h3 class="fw-bold">No active events available.</h3>
            <p class="text-muted">There are currently no active public events listed for <?= e($client['name']) ?>. Please check back soon!</p>
            <div class="mt-2">
                <a href="/" class="btn btn-outline-secondary btn-sm">Explore Utsavam Platform</a>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Upcoming Events</h3>
                <p class="text-muted small mb-0">Browse and book passes for live events</p>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2 fs-6 fw-normal">
                <?= count($activeEvents) ?> Active <?= count($activeEvents) === 1 ? 'Event' : 'Events' ?>
            </span>
        </div>

        <div class="row g-4">
            <?php foreach ($activeEvents as $evt): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="event-card">
                        <img src="<?= e($evt['banner'] ?: 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80') ?>" alt="<?= e($evt['name']) ?>" class="event-banner-img">
                        <div class="p-4 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-warning text-dark text-uppercase small"><?= e($evt['category']) ?></span>
                                <span class="text-muted small"><i data-lucide="clock" style="width:13px;height:13px;"></i> <?= formatTime($evt['start_time']) ?></span>
                            </div>
                            <h4 class="fw-bold mb-2 fs-5">
                                <a href="/<?= e($client['slug']) ?>/<?= e($evt['slug']) ?>/" class="text-dark text-decoration-none hover-underline">
                                    <?= e($evt['name']) ?>
                                </a>
                            </h4>
                            <p class="text-muted small flex-grow-1 mb-3">
                                <?= e(mb_strimwidth($evt['short_description'] ?: $evt['full_description'], 0, 110, '...')) ?>
                            </p>
                            <div class="border-top pt-3 mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3 text-muted small">
                                    <span><i data-lucide="calendar" style="width:14px;height:14px;"></i> <?= formatDate($evt['start_date']) ?></span>
                                    <span><i data-lucide="map-pin" style="width:14px;height:14px;"></i> <?= e($evt['city']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark">
                                        <?= (float)($evt['price_amount'] ?? 0) > 0 ? '₹' . number_format($evt['price_amount'], 2) : 'Free Entry' ?>
                                    </span>
                                    <a href="/<?= e($client['slug']) ?>/<?= e($evt['slug']) ?>/" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-1">
                                        Book Pass <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<footer class="bg-white border-top py-4 mt-5">
    <div class="container text-center text-muted small">
        <p class="mb-1">&copy; <?= date('Y') ?> <?= e($client['company_name'] ?? $client['name']) ?> • Powered by <strong><?= e(APP_NAME) ?></strong></p>
        <p class="mb-0 text-muted" style="font-size:12px;"><?= e(APP_TAGLINE) ?></p>
    </div>
</footer>

<script>lucide.createIcons();</script>
</body>
</html>
