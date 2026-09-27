<?php
/**
 * Utsavam - Modern Premium Event Page
 * Designed exactly to match modern Indian festival & event ticketing reference.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/ClientService.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/BookingService.php';

// Variables $client and $event are provided by index.php with fallback
if (!isset($client) || empty($client)) {
    $clientSlug = $_GET['client_slug'] ?? '';
    $client = $clientSlug ? ClientService::getClientBySlug($clientSlug) : null;
}
if (!isset($event) || empty($event)) {
    $eventSlug = $_GET['event_slug'] ?? '';
    if ($client && $eventSlug) {
        $event = EventService::getEventBySlug($client['id'], $eventSlug);
    }
}
if (!$client || !$event) {
    require __DIR__ . '/../404.php';
    exit;
}
$formFields = EventService::getFormFields($event['id']);

$error = null;
$successBooking = null;

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_event') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token mismatch. Please refresh and try again.";
    } else {
        try {
            $customerName = trim($_POST['customer_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $passCount = max(1, (int)($_POST['pass_count'] ?? 1));

            if (empty($customerName) || empty($email) || empty($phone)) {
                throw new Exception("Please provide your name, email, and phone number.");
            }

            // Extract dynamic answers
            $answers = [];
            foreach ($formFields as $field) {
                $k = $field['field_key'];
                if (isset($_POST['custom_' . $k])) {
                    $answers[$k] = is_array($_POST['custom_' . $k]) ? implode(', ', $_POST['custom_' . $k]) : trim($_POST['custom_' . $k]);
                }
            }

            // Dynamic package resolution
            $pkgId = trim($_POST['package_id'] ?? '');
            $selectedPackage = null;
            if (!empty($event['packages']) && is_array($event['packages'])) {
                foreach ($event['packages'] as $p) {
                    if (($p['id'] ?? '') === $pkgId) {
                        $selectedPackage = $p;
                        break;
                    }
                }
                if (!$selectedPackage && !empty($event['packages'][0])) {
                    $selectedPackage = $event['packages'][0];
                }
            }

            $pkgName = $selectedPackage['name'] ?? ($event['price_label'] ?: 'Standard Pass');
            $pkgPrice = (float)($selectedPackage['price'] ?? ($event['price_amount'] ?? 0));
            $totalAmount = $pkgPrice * $passCount;

            $bookingData = [
                'event_id' => $event['id'],
                'customer_name' => $customerName,
                'email' => $email,
                'phone' => $phone,
                'pass_count' => $passCount,
                'package_id' => $selectedPackage['id'] ?? null,
                'package_name' => $pkgName,
                'package_price' => $pkgPrice,
                'total_amount' => $totalAmount
            ];

            $newBooking = BookingService::createBooking($bookingData, $answers);

            // Redirect immediately to verification pass
            redirect('/verify/' . $newBooking['qr_token']);
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Calculate starting price
$packages = $event['packages'] ?? [];
$startingPrice = (float)($event['price_amount'] ?? 0);
if (!empty($packages) && is_array($packages)) {
    $prices = array_map(fn($p) => (float)($p['price'] ?? 0), $packages);
    if (!empty($prices)) {
        $startingPrice = min($prices);
    }
}

// Formatted Date & Time Strings
$startDateRaw = strtotime($event['start_date'] ?? date('Y-m-d'));
$formattedDate = date('D d M Y', $startDateRaw); // e.g. Sun 18 Oct 2026
$startTimeFormatted = date('g:i A', strtotime($event['start_time'] ?? '18:00'));
$endTimeFormatted = date('g:i A', strtotime($event['end_time'] ?? '23:00'));
$timeRangeString = "{$startTimeFormatted} – {$endTimeFormatted}";

// Venue Full Address
$venueAddressParts = array_filter([
    $event['venue_name'] ?? '',
    $event['address'] ?? '',
    $event['city'] ?? '',
    $event['state'] ?? '',
    $event['pincode'] ?? ''
]);
$fullVenueAddress = implode(', ', $venueAddressParts);

// Fallback Coordinates for Map (Gorakhpur / Venue default)
$mapLat = 26.7915;
$mapLng = 83.3985;
if (strpos(strtolower($fullVenueAddress), 'lucknow') !== false) {
    $mapLat = 26.8467; $mapLng = 80.9462;
} elseif (strpos(strtolower($fullVenueAddress), 'delhi') !== false) {
    $mapLat = 28.6139; $mapLng = 77.2090;
} elseif (strpos(strtolower($fullVenueAddress), 'bangalore') !== false || strpos(strtolower($fullVenueAddress), 'bengaluru') !== false) {
    $mapLat = 12.9716; $mapLng = 77.5946;
} elseif (strpos(strtolower($fullVenueAddress), 'mumbai') !== false) {
    $mapLat = 19.0760; $mapLng = 72.8777;
}

$googleMapsDirectionsUrl = !empty($event['google_maps_url']) 
    ? $event['google_maps_url'] 
    : 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($fullVenueAddress);

$bannerUrl = !empty($event['banner']) ? $event['banner'] : 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1600&q=85';
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($event['name']) ?> | <?= e($client['company_name'] ?? $client['name']) ?></title>
    <meta name="description" content="<?= e($event['short_description'] ?? $event['name']) ?>">
    <meta property="og:title" content="<?= e($event['name']) ?>">
    <meta property="og:description" content="<?= e($event['short_description'] ?? '') ?>">
    <meta property="og:image" content="<?= e($bannerUrl) ?>">
    <meta property="og:url" content="<?= e($currentUrl) ?>">

    <!-- Bootstrap 5 & Lucide Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        :root {
            --brand-red: #dc2626;
            --brand-red-hover: #b91c1c;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --card-border: #e5e7eb;
            --bg-page: #ffffff;
            --bg-subtle: #f9fafb;
        }

        body {
            background-color: var(--bg-page);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #374151;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Brand Navbar */
        .site-header {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        /* Event Header */
        .event-main-title {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
            line-height: 1.25;
        }
        @media (max-width: 768px) {
            .event-main-title {
                font-size: 1.75rem;
            }
        }

        .event-datetime-highlight {
            color: var(--brand-red);
            font-weight: 700;
            font-size: 1.05rem;
        }

        .event-venue-highlight {
            color: var(--text-muted);
            font-size: 1.05rem;
        }

        /* Hero Banner Image */
        .event-banner-box {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--card-border);
            background: #000;
        }
        .event-banner-img {
            width: 100%;
            height: auto;
            max-height: 520px;
            object-fit: cover;
            display: block;
        }

        /* Section Headings */
        .section-heading {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: -0.2px;
        }

        /* Expandable About text */
        .about-text-content {
            color: #4b5563;
            font-size: 0.98rem;
            line-height: 1.68;
        }
        .show-more-link {
            color: var(--brand-red);
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 6px;
        }
        .show-more-link:hover {
            color: var(--brand-red-hover);
            text-decoration: underline;
        }

        /* Collapsible Policy Cards (General Terms & Cancellation) */
        .policy-card {
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: #ffffff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
        .policy-card-btn {
            background: none;
            border: none;
            padding: 16px 20px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-align: left;
            cursor: pointer;
            text-decoration: none;
        }
        .policy-card-btn:focus {
            outline: none;
        }
        .policy-card-btn .policy-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .policy-chevron {
            transition: transform 0.25s ease;
            color: #9ca3af;
        }
        .policy-card-btn:not(.collapsed) .policy-chevron {
            transform: rotate(180deg);
        }
        .policy-content-body {
            padding: 0 20px 20px 20px;
            border-top: 1px solid #f3f4f6;
            color: #4b5563;
            font-size: 0.93rem;
            line-height: 1.65;
        }

        /* Right Column Cards */
        .specs-card {
            border: 1px solid var(--card-border);
            border-radius: 16px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }
        .specs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px 16px;
            padding: 24px;
        }
        @media (max-width: 480px) {
            .specs-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
        .spec-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .spec-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: #fee2e2;
            color: var(--brand-red);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .spec-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            margin-bottom: 2px;
        }
        .spec-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.3;
        }

        /* Red CTA Bar */
        .specs-cta-bar {
            background-color: var(--brand-red);
            padding: 16px 22px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cta-price-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            opacity: 0.9;
            font-weight: 600;
        }
        .cta-price-amount {
            font-size: 1.28rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }
        .cta-book-btn {
            background: transparent;
            color: #ffffff;
            border: none;
            font-weight: 800;
            font-size: 0.98rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .cta-book-btn:hover {
            color: #ffffff;
            background: rgba(0, 0, 0, 0.12);
        }
        .cta-arrow-circle {
            width: 26px;
            height: 26px;
            background: #ffffff;
            color: var(--brand-red);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Venue Map Card */
        .venue-map-card {
            border: 1px solid var(--card-border);
            border-radius: 16px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            margin-top: 24px;
        }
        #eventMap {
            height: 210px;
            width: 100%;
            background-color: #f3f4f6;
            z-index: 1;
        }
        .venue-info-bar {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: #ffffff;
        }
        .venue-address-text {
            font-size: 0.88rem;
            color: #4b5563;
            line-height: 1.4;
            font-weight: 500;
        }
        .btn-directions {
            color: var(--brand-red);
            font-weight: 700;
            font-size: 0.92rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .btn-directions:hover {
            color: var(--brand-red-hover);
        }
        .directions-circle-icon {
            width: 28px;
            height: 28px;
            background: var(--brand-red);
            color: #ffffff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Share Card */
        .share-card {
            border: 1px solid var(--card-border);
            border-radius: 14px;
            background: #ffffff;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .share-card:hover {
            border-color: #cbd5e1;
            background: #fafafa;
        }
        .share-card-text {
            color: var(--brand-red);
            font-weight: 700;
            font-size: 0.95rem;
        }

        /* Ticket Package Card in Modal */
        .ticket-pkg-card {
            border: 2px solid var(--card-border);
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
        }
        .ticket-pkg-card:hover {
            border-color: #fca5a5;
            background-color: #fffaf0;
        }
        .ticket-pkg-card.active {
            border-color: var(--brand-red);
            background-color: #fef2f2;
        }
    </style>
</head>
<body>

<!-- Top Minimal Navbar -->
<header class="site-header py-2.5">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="/<?= e($client['slug']) ?>/" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
            <?php if (!empty($client['logo'])): ?>
                <img src="<?= e($client['logo']) ?>" alt="<?= e($client['name']) ?>" style="height:32px;border-radius:4px;">
            <?php else: ?>
                <div class="bg-danger text-white fw-bold rounded-2 px-2 py-1 small"><?= strtoupper(substr($client['name'] ?? 'AK', 0, 2)) ?></div>
            <?php endif; ?>
            <span class="fw-bold fs-6 text-dark"><?= e($client['name']) ?></span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="/<?= e($client['slug']) ?>/" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex">All Events</a>
            <button type="button" class="btn btn-sm btn-danger fw-semibold px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#bookingModal">
                Book Tickets
            </button>
        </div>
    </div>
</header>

<main class="container py-4 py-md-5">

    <!-- Flash Error Message if any -->
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2 shadow-xs">
            <i data-lucide="alert-circle" style="width:18px;height:18px;"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- 1. TOP HEADER: Event Title & Red Subtitle Date/Venue (Exact Match to Reference) -->
    <div class="mb-4">
        <h1 class="event-main-title mb-2">
            <?= e($event['name']) ?>
        </h1>
        <div class="d-flex flex-wrap align-items-center">
            <span class="event-datetime-highlight">
                <?= e($formattedDate) ?>, <?= e($timeRangeString) ?>
            </span>
            <span class="text-muted mx-2 d-none d-sm-inline">|</span>
            <span class="event-venue-highlight d-block d-sm-inline mt-1 mt-sm-0">
                <?= e($fullVenueAddress) ?>
            </span>
        </div>
    </div>

    <!-- 2. TWO-COLUMN GRID: Banner + About on Left, Meta Specs + Map + Share on Right -->
    <div class="row g-4 g-lg-5">

        <!-- ================= LEFT COLUMN ================= -->
        <div class="col-lg-7">

            <!-- Hero Banner Image (Rounded, 16:9, High Impact) -->
            <div class="event-banner-box mb-4">
                <img src="<?= e($bannerUrl) ?>" alt="<?= e($event['name']) ?>" class="event-banner-img">
            </div>

            <!-- "About the Event" Section with Show More / Show Less Toggle -->
            <div class="mb-4 pt-1">
                <h2 class="section-heading mb-3">About the Event</h2>
                
                <?php
                $fullAbout = !empty($event['full_description']) ? $event['full_description'] : $event['short_description'];
                $cleanPlain = strip_tags($fullAbout);
                $isLong = mb_strlen($cleanPlain) > 230;
                $truncatedText = $isLong ? mb_substr($cleanPlain, 0, 230) . '...' : $cleanPlain;
                ?>

                <div class="about-text-content">
                    <div id="aboutCollapsedText" class="<?= $isLong ? '' : 'd-none' ?>">
                        <?= nl2br(e($truncatedText)) ?>
                    </div>
                    <div id="aboutFullText" class="<?= $isLong ? 'd-none' : '' ?>">
                        <?= renderRichText($fullAbout) ?>
                    </div>
                    
                    <?php if ($isLong): ?>
                        <a href="javascript:void(0)" class="show-more-link" id="btnToggleAbout">Show More</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Policy Accordion 1: "General Terms" (Exact match) -->
            <div class="policy-card mb-3">
                <button class="policy-card-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generalTermsCollapse" aria-expanded="false" aria-controls="generalTermsCollapse">
                    <span class="policy-title">
                        <i data-lucide="align-left" style="width:18px;height:18px;"></i>
                        General Terms
                    </span>
                    <i data-lucide="chevron-down" class="policy-chevron" style="width:18px;height:18px;"></i>
                </button>
                <div class="collapse" id="generalTermsCollapse">
                    <div class="policy-content-body">
                        <?php if (!empty($client['terms_and_conditions'])): ?>
                            <?= renderRichText($client['terms_and_conditions']) ?>
                        <?php else: ?>
                            <ul class="mb-0 ps-3">
                                <li>Every attendee must present a valid digital QR pass generated by Utsavam at the entrance gate.</li>
                                <li>Entry is subject to security checks and verification of a valid government photo ID.</li>
                                <li>Traditional festive attire is recommended for Garba and Dandiya dancers.</li>
                                <li>Outside food, drinks, and hazardous items are strictly prohibited inside the venue.</li>
                                <li>The organizers reserve the right of admission and security protocol enforcement.</li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Policy Accordion 2: "Cancellation/Refund Policy" (Exact match) -->
            <div class="policy-card mb-4">
                <button class="policy-card-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cancellationPolicyCollapse" aria-expanded="false" aria-controls="cancellationPolicyCollapse">
                    <span class="policy-title">
                        <i data-lucide="circle-slash" style="width:18px;height:18px;"></i>
                        Cancellation/Refund Policy
                    </span>
                    <i data-lucide="chevron-down" class="policy-chevron" style="width:18px;height:18px;"></i>
                </button>
                <div class="collapse" id="cancellationPolicyCollapse">
                    <div class="policy-content-body">
                        <?php if (!empty($client['cancellation_policy'])): ?>
                            <?= renderRichText($client['cancellation_policy']) ?>
                        <?php else: ?>
                            <ul class="mb-0 ps-3">
                                <li>Tickets and passes once booked are non-refundable and cannot be exchanged for cash.</li>
                                <li>Pass name transfers may be requested up to 24 hours prior to the event schedule by contacting event support.</li>
                                <li>If the event is rescheduled due to unforeseen circumstances, existing passes will remain valid for the new date.</li>
                                <li>In the event of total cancellation by the organizers, a 100% refund will be issued via the original payment mode.</li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================= RIGHT COLUMN (STICKY SIDEBAR) ================= -->
        <div class="col-lg-5">

            <!-- Card 1: Event Quick Specs & Attached Red CTA Bar (Exact Match) -->
            <div class="specs-card mb-4">
                <!-- Meta Grid (2 columns: Time, Date, Content Type, Language, Category) -->
                <div class="specs-grid">
                    
                    <!-- 1. TIME -->
                    <div class="spec-item">
                        <div class="spec-icon-box">
                            <i data-lucide="clock" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div class="spec-label">Time</div>
                            <div class="spec-value"><?= e($timeRangeString) ?></div>
                        </div>
                    </div>

                    <!-- 2. DATE -->
                    <div class="spec-item">
                        <div class="spec-icon-box">
                            <i data-lucide="calendar" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div class="spec-label">Date</div>
                            <div class="spec-value"><?= e($formattedDate) ?></div>
                        </div>
                    </div>

                    <!-- 3. CONTENT TYPE -->
                    <div class="spec-item">
                        <div class="spec-icon-box">
                            <i data-lucide="user" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div class="spec-label">Content Type</div>
                            <div class="spec-value">Family Friendly</div>
                        </div>
                    </div>

                    <!-- 4. LANGUAGE -->
                    <div class="spec-item">
                        <div class="spec-icon-box">
                            <i data-lucide="globe" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div class="spec-label">Language</div>
                            <div class="spec-value">Hindi, English</div>
                        </div>
                    </div>

                    <!-- 5. CATEGORY -->
                    <div class="spec-item" style="grid-column: span 2;">
                        <div class="spec-icon-box">
                            <i data-lucide="layout-grid" style="width:18px;height:18px;"></i>
                        </div>
                        <div>
                            <div class="spec-label">Category</div>
                            <div class="spec-value text-uppercase"><?= e($event['category'] ?: 'ENTERTAINMENT') ?></div>
                        </div>
                    </div>

                </div>

                <!-- Attached Vibrant Red Action Bar -->
                <div class="specs-cta-bar">
                    <div>
                        <div class="cta-price-label">Starting From</div>
                        <div class="cta-price-amount">
                            <?= $startingPrice > 0 ? '₹ ' . number_format($startingPrice) . ' ONWARDS' : 'FREE ENTRY' ?>
                        </div>
                    </div>
                    <button type="button" class="cta-book-btn" data-bs-toggle="modal" data-bs-target="#bookingModal">
                        <span>BOOK TICKETS</span>
                        <span class="cta-arrow-circle">
                            <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Card 2: Interactive Leaflet / OpenStreetMap & Venue Directions (Exact Match) -->
            <div class="venue-map-card">
                <!-- Map Container rendered by Leaflet -->
                <div id="eventMap"></div>

                <!-- Venue text & Get Directions button -->
                <div class="venue-info-bar">
                    <div class="venue-address-text">
                        <?= e($fullVenueAddress) ?>
                    </div>
                    <a href="<?= e($googleMapsDirectionsUrl) ?>" target="_blank" class="btn-directions">
                        <span>Get Directions</span>
                        <span class="directions-circle-icon">
                            <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                        </span>
                    </a>
                </div>
            </div>

            <!-- Card 3: Share this Event Card (Exact Match) -->
            <div class="share-card" id="btnShareCard" title="Share this Event">
                <span class="share-card-text">Share this Event</span>
                <i data-lucide="share-2" style="width:18px;height:18px;color:#111827;"></i>
            </div>

        </div>
    </div>
</main>

<!-- ================= TICKET BOOKING MODAL ================= -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header border-bottom py-3 px-4 bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="bookingModalLabel">
                        <i data-lucide="ticket" class="text-danger" style="width:20px;height:20px;"></i>
                        Book Passes — <?= e($event['name']) ?>
                    </h5>
                    <div class="text-muted text-xs"><?= e($formattedDate) ?> • <?= e($timeRangeString) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>" id="eventBookingForm">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="book_event">

                <div class="modal-body p-4">
                    
                    <!-- 1. Select Ticket Pass Package -->
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-1.5">
                        <i data-lucide="layers" class="text-danger" style="width:16px;height:16px;"></i>
                        1. Select Pass Category
                    </h6>

                    <?php if (!empty($packages) && is_array($packages)): ?>
                        <div class="row g-2 mb-3" id="packageListContainer">
                            <?php foreach ($packages as $idx => $pkg): ?>
                                <?php $isSelected = ($idx === 0); ?>
                                <div class="col-md-6">
                                    <div class="ticket-pkg-card <?= $isSelected ? 'active' : '' ?>" onclick="selectTicketPackage('<?= e($pkg['id']) ?>', <?= (float)$pkg['price'] ?>, '<?= e(addslashes($pkg['name'])) ?>', this)">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="radio" name="package_id" value="<?= e($pkg['id']) ?>" id="pkg_radio_<?= e($pkg['id']) ?>" class="form-check-input mt-0" <?= $isSelected ? 'checked' : '' ?>>
                                                <span class="fw-bold text-dark small"><?= e($pkg['name']) ?></span>
                                            </div>
                                            <?php if (!empty($pkg['badge'])): ?>
                                                <span class="badge bg-danger text-white rounded-pill text-xs"><?= e($pkg['badge']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span class="text-muted text-xs"><?= e($pkg['description'] ?? 'General Entry') ?></span>
                                            <span class="fw-bolder fs-6 text-danger">₹ <?= number_format($pkg['price']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="ticket-pkg-card active mb-3" onclick="selectTicketPackage('default', <?= (float)$event['price_amount'] ?>, 'Standard Pass', this)">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="radio" name="package_id" value="default" class="form-check-input mt-0" checked>
                                    <span class="fw-bold text-dark">Standard Entry Pass</span>
                                </div>
                                <span class="fw-bolder fs-5 text-danger">
                                    <?= (float)$event['price_amount'] > 0 ? '₹ ' . number_format($event['price_amount']) : 'Free' ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 2. Pass Count -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Number of Passes</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="changePassCount(-1)">-</button>
                                <input type="number" name="pass_count" id="modalPassCountInput" class="form-control text-center fw-bold" value="1" min="1" max="10" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="changePassCount(1)">+</button>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-secondary">Pass Summary</label>
                            <div class="p-2 border rounded-2 bg-light d-flex justify-content-between align-items-center" style="height:38px;">
                                <span class="small text-muted" id="modalSelectedPkgName">1 × Pass</span>
                                <span class="fw-bolder text-danger fs-6" id="modalTotalDisplay">₹ <?= number_format($startingPrice) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Attendee Information -->
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-1.5 border-top pt-3">
                        <i data-lucide="user-check" class="text-danger" style="width:16px;height:16px;"></i>
                        2. Attendee Contact Details
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Attendee name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">WhatsApp Mobile <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
                        </div>

                        <!-- Dynamic custom form fields if configured for this event -->
                        <?php foreach ($formFields as $field): ?>
                            <?php 
                                $fkey = $field['field_key'];
                                if (in_array($fkey, ['full_name', 'email_address', 'mobile_number', 'attendees_count'])) continue;
                            ?>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary">
                                    <?= e($field['field_label']) ?>
                                    <?php if (!empty($field['required'])): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <?php if ($field['field_type'] === 'textarea'): ?>
                                    <textarea name="custom_<?= e($fkey) ?>" class="form-control" rows="2" placeholder="<?= e($field['placeholder'] ?? '') ?>" <?= !empty($field['required']) ? 'required' : '' ?>></textarea>
                                <?php else: ?>
                                    <input type="<?= e($field['field_type'] ?: 'text') ?>" name="custom_<?= e($fkey) ?>" class="form-control" placeholder="<?= e($field['placeholder'] ?? '') ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-muted text-xs text-center">
                        <i data-lucide="shield-check" style="width:13px;height:13px;vertical-align:-2px;" class="text-success me-1"></i>
                        Instant Verifiable Digital QR Pass will be issued upon completion.
                    </div>

                </div>

                <div class="modal-footer border-top bg-light d-flex justify-content-between align-items-center py-3 px-4">
                    <div>
                        <div class="text-xs text-muted">Total Payable</div>
                        <div class="fw-bolder fs-5 text-danger" id="modalBottomTotalDisplay">₹ <?= number_format($startingPrice) ?></div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm d-inline-flex align-items-center gap-2">
                            <span>Confirm & Book Passes</span>
                            <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- ================= SHARE MODAL ================= -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0 text-center p-4">
            <h6 class="fw-bold mb-3 text-dark">Share this Event</h6>
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="https://api.whatsapp.com/send?text=<?= urlencode($event['name'] . ' - Book your passes here: ' . $currentUrl) ?>" target="_blank" class="btn btn-success rounded-circle p-2.5 d-inline-flex align-items-center justify-content-center shadow-xs" title="WhatsApp">
                    <i data-lucide="message-circle" style="width:20px;height:20px;"></i>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl) ?>" target="_blank" class="btn btn-primary rounded-circle p-2.5 d-inline-flex align-items-center justify-content-center shadow-xs" title="Facebook">
                    <i data-lucide="facebook" style="width:20px;height:20px;"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($event['name']) ?>&url=<?= urlencode($currentUrl) ?>" target="_blank" class="btn btn-dark rounded-circle p-2.5 d-inline-flex align-items-center justify-content-center shadow-xs" title="Twitter / X">
                    <i data-lucide="twitter" style="width:20px;height:20px;"></i>
                </a>
            </div>
            <div class="input-group">
                <input type="text" id="shareUrlInput" class="form-control form-control-sm text-truncate" value="<?= e($currentUrl) ?>" readonly>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCopyShareUrl">Copy</button>
            </div>
            <div id="copySuccessMsg" class="text-success small mt-2 d-none">Link copied to clipboard!</div>
        </div>
    </div>
</div>

<!-- ================= MINIMAL FOOTER ================= -->
<footer class="border-top py-4 bg-white mt-5">
    <div class="container text-center text-muted small">
        <p class="mb-1"><strong><?= e($event['name']) ?></strong> • Hosted by <?= e($client['company_name'] ?? $client['name']) ?></p>
        <p class="mb-0 text-xs" style="font-size:12px;">Powered by <strong><?= e(APP_NAME) ?></strong> — <?= e(APP_TAGLINE) ?></p>
    </div>
</footer>

<!-- Leaflet JS for Map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Bootstrap 5 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Initialize Lucide Icons
lucide.createIcons();

// 1. Initialize Interactive Leaflet Map
document.addEventListener("DOMContentLoaded", function() {
    const lat = <?= json_encode($mapLat) ?>;
    const lng = <?= json_encode($mapLng) ?>;
    const venueName = <?= json_encode($event['venue_name']) ?>;

    const map = L.map('eventMap', {
        center: [lat, lng],
        zoom: 15,
        zoomControl: true,
        scrollWheelZoom: false
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>'
    }).addTo(map);

    const marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup("<b>" + venueName + "</b><br><?= addslashes(e($fullVenueAddress)) ?>").openPopup();
});

// 2. Toggle "Show More" / "Show Less" on About Section
const btnToggleAbout = document.getElementById('btnToggleAbout');
if (btnToggleAbout) {
    btnToggleAbout.addEventListener('click', function() {
        const collapsed = document.getElementById('aboutCollapsedText');
        const full = document.getElementById('aboutFullText');
        if (full.classList.contains('d-none')) {
            full.classList.remove('d-none');
            collapsed.classList.add('d-none');
            this.textContent = 'Show Less';
        } else {
            full.classList.add('d-none');
            collapsed.classList.remove('d-none');
            this.textContent = 'Show More';
        }
    });
}

// 3. Ticket Booking Modal Selection & Live Total Calculation
let currentSelectedPrice = <?= json_encode($startingPrice) ?>;
let currentSelectedPkgName = 'Pass';

function selectTicketPackage(id, price, name, cardElem) {
    currentSelectedPrice = parseFloat(price) || 0;
    currentSelectedPkgName = name;
    
    // Select radio
    const radio = document.getElementById('pkg_radio_' + id);
    if (radio) radio.checked = true;

    // Toggle card styles
    document.querySelectorAll('.ticket-pkg-card').forEach(c => c.classList.remove('active'));
    if (cardElem) cardElem.classList.add('active');

    updateModalTotals();
}

function changePassCount(delta) {
    const input = document.getElementById('modalPassCountInput');
    let val = parseInt(input.value) || 1;
    val = Math.max(1, Math.min(10, val + delta));
    input.value = val;
    updateModalTotals();
}

function updateModalTotals() {
    const input = document.getElementById('modalPassCountInput');
    const count = parseInt(input.value) || 1;
    const total = currentSelectedPrice * count;
    
    const formattedTotal = total > 0 ? ('₹ ' + total.toLocaleString('en-IN')) : 'Free';
    
    document.getElementById('modalSelectedPkgName').textContent = count + ' × ' + currentSelectedPkgName;
    document.getElementById('modalTotalDisplay').textContent = formattedTotal;
    document.getElementById('modalBottomTotalDisplay').textContent = formattedTotal;
}

// 4. Share Event Handler (Native Web Share + Modal Fallback)
document.getElementById('btnShareCard').addEventListener('click', async function() {
    const shareData = {
        title: <?= json_encode($event['name']) ?>,
        text: <?= json_encode($event['short_description'] ?? $event['name']) ?>,
        url: <?= json_encode($currentUrl) ?>
    };

    if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
        try {
            await navigator.share(shareData);
        } catch (err) {
            // User cancelled or fallback
        }
    } else {
        const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
        shareModal.show();
    }
});

// Copy Share URL Button
document.getElementById('btnCopyShareUrl').addEventListener('click', function() {
    const copyInput = document.getElementById('shareUrlInput');
    copyInput.select();
    copyInput.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyInput.value).then(() => {
        const msg = document.getElementById('copySuccessMsg');
        msg.classList.remove('d-none');
        setTimeout(() => msg.classList.add('d-none'), 3000);
    });
});
</script>

</body>
</html>
