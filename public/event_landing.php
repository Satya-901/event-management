<?php
/**
 * Utsavam - Premium Event Landing Page (12 Sections)
 * Responsive Indian festive theme with dynamic custom booking form.
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

            $bookingData = [
                'event_id' => $event['id'],
                'customer_name' => $customerName,
                'email' => $email,
                'phone' => $phone,
                'pass_count' => $passCount
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($event['meta_title'] ?: $event['name'] . ' | ' . ($client['name'] ?? '')) ?></title>
    <meta name="description" content="<?= e($event['meta_description'] ?: $event['short_description']) ?>">
    <meta property="og:title" content="<?= e($event['meta_title'] ?: $event['name']) ?>">
    <meta property="og:description" content="<?= e($event['meta_description'] ?: $event['short_description']) ?>">
    <meta property="og:image" content="<?= e($event['og_image'] ?: $event['banner']) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --brand-primary: #c2410c;
            --brand-dark: #7c2d12;
            --brand-gold: #d97706;
            --brand-cream: #fffaf5;
        }
        body {
            background-color: var(--brand-cream);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #292524;
            scroll-behavior: smooth;
        }
        /* Navbar */
        .festive-nav {
            background: #ffffff;
            border-bottom: 1px solid #fed7aa;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        /* 1. Hero Section */
        .event-hero {
            position: relative;
            background: linear-gradient(rgba(124, 45, 18, 0.85), rgba(194, 65, 12, 0.8)), url('<?= e($event['banner'] ?: 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1600&q=80') ?>');
            background-size: cover;
            background-position: center;
            color: #ffffff;
            padding: 90px 0 70px;
        }
        .hero-badge {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(4px);
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        /* Cards & Accents */
        .section-title {
            font-weight: 800;
            color: #431407;
            letter-spacing: -0.5px;
        }
        .info-pill-card {
            background: #ffffff;
            border: 1px solid #ffedd5;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(194, 65, 12, 0.04);
            height: 100%;
        }
        .highlight-item {
            background: #ffffff;
            border-left: 4px solid #ea580c;
            border-radius: 0 12px 12px 0;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .booking-card {
            background: #ffffff;
            border: 2px solid #fed7aa;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(194, 65, 12, 0.08);
        }
        .btn-brand {
            background: #c2410c;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-brand:hover {
            background: #9a3412;
            color: #ffffff;
            transform: translateY(-1px);
        }
        .faq-item .accordion-button:not(.collapsed) {
            background-color: #fff7ed;
            color: #9a3412;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg festive-nav py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/<?= e($client['slug']) ?>/">
            <span class="badge bg-warning text-dark p-2 rounded-circle"><i data-lucide="sparkles" style="width:16px;height:16px;"></i></span>
            <span class="fw-bold text-dark fs-5"><?= e($client['name']) ?></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="#bookingSection" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="ticket" style="width:16px;height:16px;"></i> Book Passes
            </a>
        </div>
    </div>
</nav>

<!-- 1. HERO SECTION -->
<header class="event-hero">
    <div class="container text-center">
        <div class="hero-badge mb-3">
            <i data-lucide="sparkles" style="width:14px;height:14px;"></i> <?= e($event['category']) ?> • Organized by <?= e($client['company_name'] ?? $client['name']) ?>
        </div>
        <h1 class="display-4 fw-bold mb-3"><?= e($event['name']) ?></h1>
        <p class="lead opacity-90 mx-auto mb-4" style="max-width: 750px;">
            <?= e($event['short_description']) ?>
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
            <div class="badge bg-black bg-opacity-30 px-3 py-2 fs-6 fw-normal d-inline-flex align-items-center gap-2">
                <i data-lucide="calendar" style="width:18px;height:18px;"></i> <?= formatDate($event['start_date']) ?>
            </div>
            <div class="badge bg-black bg-opacity-30 px-3 py-2 fs-6 fw-normal d-inline-flex align-items-center gap-2">
                <i data-lucide="clock" style="width:18px;height:18px;"></i> <?= formatTime($event['start_time']) ?> onwards
            </div>
            <div class="badge bg-black bg-opacity-30 px-3 py-2 fs-6 fw-normal d-inline-flex align-items-center gap-2">
                <i data-lucide="map-pin" style="width:18px;height:18px;"></i> <?= e($event['venue_name']) ?>, <?= e($event['city']) ?>
            </div>
        </div>
        <div>
            <a href="#bookingSection" class="btn btn-warning btn-lg px-4 py-3 fw-bold text-dark shadow-sm">
                Reserve Your Passes Now <i data-lucide="chevron-right" style="width:18px;height:18px;"></i>
            </a>
        </div>
    </div>
</header>

<main class="container py-5">
    <?php if ($error): ?>
        <div class="alert alert-danger shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
            <i data-lucide="alert-triangle" style="width:20px;height:20px;"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- 2. EVENT INFORMATION CARDS -->
    <section class="mb-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="info-pill-card text-center">
                    <div class="text-warning mb-2"><i data-lucide="calendar-check" style="width:32px;height:32px;"></i></div>
                    <h5 class="fw-bold mb-1">Date & Time</h5>
                    <p class="text-muted small mb-0"><?= formatDate($event['start_date']) ?><br><?= formatTime($event['start_time']) ?> to <?= formatTime($event['end_time']) ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-pill-card text-center">
                    <div class="text-danger mb-2"><i data-lucide="map-pin" style="width:32px;height:32px;"></i></div>
                    <h5 class="fw-bold mb-1">Venue Location</h5>
                    <p class="text-muted small mb-0"><?= e($event['venue_name']) ?><br><?= e($event['city']) ?>, Karnataka</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-pill-card text-center">
                    <div class="text-success mb-2"><i data-lucide="users" style="width:32px;height:32px;"></i></div>
                    <h5 class="fw-bold mb-1">Available Passes</h5>
                    <p class="text-muted small mb-0"><strong><?= (int)($event['available_seats'] ?? 0) ?></strong> seats remaining<br><span class="badge bg-success bg-opacity-10 text-success">Instant QR Pass</span></p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. ABOUT SECTION -->
    <section class="mb-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="text-uppercase fw-bold small text-warning">About The Celebration</span>
                <h2 class="section-title mb-3">Immerse Yourself in Joy and Rhythm</h2>
                <div class="text-muted lead fs-6 lh-base mb-4">
                    <?= nl2br(e($event['full_description'] ?: $event['short_description'])) ?>
                </div>
                <div class="d-flex gap-3">
                    <div class="d-flex align-items-center gap-2 small text-dark fw-medium">
                        <i data-lucide="shield-check" class="text-success" style="width:18px;height:18px;"></i> Official Verified Host
                    </div>
                    <div class="d-flex align-items-center gap-2 small text-dark fw-medium">
                        <i data-lucide="award" class="text-warning" style="width:18px;height:18px;"></i> Traditional Folk Music
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <img src="<?= e($event['banner']) ?>" alt="<?= e($event['name']) ?>" class="img-fluid rounded-4 shadow-sm" style="max-height:380px; width:100%; object-fit:cover;">
            </div>
        </div>
    </section>

    <!-- 4. HIGHLIGHTS SECTION -->
    <?php if (!empty($event['highlights'])): ?>
    <section class="mb-5">
        <div class="text-center mb-4">
            <span class="text-uppercase fw-bold small text-warning">Event Attractions</span>
            <h2 class="section-title">Key Highlights</h2>
        </div>
        <div class="row g-3">
            <?php foreach ($event['highlights'] as $highlight): ?>
                <div class="col-md-6">
                    <div class="highlight-item d-flex align-items-center gap-3">
                        <i data-lucide="sparkle" class="text-warning flex-shrink-0" style="width:20px;height:20px;"></i>
                        <span class="fw-medium text-dark"><?= e($highlight) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- 5. GALLERY SECTION -->
    <?php if (!empty($event['gallery'])): ?>
    <section class="mb-5">
        <div class="text-center mb-4">
            <span class="text-uppercase fw-bold small text-warning">Visual Memories</span>
            <h2 class="section-title">Celebration Gallery</h2>
        </div>
        <div class="row g-3">
            <?php foreach ($event['gallery'] as $imgUrl): ?>
                <div class="col-6 col-md-3">
                    <img src="<?= e($imgUrl) ?>" alt="Gallery" class="img-fluid rounded-3 shadow-sm" style="height:180px; width:100%; object-fit:cover;">
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- 6 & 7. PASS / PRICING & DYNAMIC BOOKING FORM -->
    <section class="mb-5" id="bookingSection">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="booking-card">
                    <div class="text-center mb-4">
                        <span class="badge bg-warning text-dark px-3 py-1 mb-2">Fast Online Reservation</span>
                        <h2 class="section-title mb-1">Book Your Event Passes</h2>
                        <p class="text-muted small">Fill out the attendee details below. Instant digital QR pass issued upon submission.</p>
                        <div class="d-inline-block bg-light px-4 py-2 rounded-pill mt-2">
                            <span class="text-muted small">Price: </span>
                            <span class="fw-bold text-dark fs-5">
                                <?= (float)($event['price_amount'] ?? 0) > 0 ? '₹' . number_format($event['price_amount'], 2) : 'Free Entry' ?>
                            </span>
                            <span class="text-muted small"> / <?= e($event['price_label'] ?: 'Person') ?></span>
                        </div>
                    </div>

                    <?php if (empty($event['booking_open'])): ?>
                        <div class="alert alert-warning text-center">
                            <i data-lucide="lock" style="width:24px;height:24px;"></i>
                            <div class="fw-bold mt-2">Bookings are currently closed for this event.</div>
                        </div>
                    <?php elseif (($event['available_seats'] ?? 0) <= 0): ?>
                        <div class="alert alert-danger text-center">
                            <i data-lucide="users" style="width:24px;height:24px;"></i>
                            <div class="fw-bold mt-2">Housefull! All passes have been reserved.</div>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="#bookingSection">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="book_event">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Your Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" required class="form-control" placeholder="e.g. Rahul Sharma" value="<?= e($_POST['customer_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" required class="form-control" placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Phone / WhatsApp <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" required class="form-control" placeholder="+91 9876543210" value="<?= e($_POST['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Number of Passes <span class="text-danger">*</span></label>
                                    <select name="pass_count" class="form-select">
                                        <?php for ($i = 1; $i <= min(10, $event['available_seats']); $i++): ?>
                                            <option value="<?= $i ?>" <?= (isset($_POST['pass_count']) && (int)$_POST['pass_count'] === $i) ? 'selected' : '' ?>>
                                                <?= $i ?> <?= $i === 1 ? 'Pass' : 'Passes' ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <!-- Dynamic Custom Form Fields Configured by Event Organizer -->
                                <?php foreach ($formFields as $field): ?>
                                    <?php 
                                        $fkey = $field['field_key'];
                                        // Skip if redundant with core fields
                                        if (in_array($fkey, ['full_name', 'email_address', 'mobile_number', 'attendees_count'])) continue;
                                    ?>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">
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

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-brand w-100 py-3 fs-5 fw-bold d-flex align-items-center justify-content-center gap-2">
                                        <i data-lucide="qr-code" style="width:22px;height:22px;"></i> Confirm & Generate QR Pass
                                    </button>
                                    <div class="text-center text-muted small mt-2">
                                        <i data-lucide="lock" style="width:12px;height:12px;"></i> Safe & secure booking • Instant entry ticket
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- 8 & 9. VENUE & GOOGLE MAP SECTION -->
    <section class="mb-5">
        <div class="text-center mb-4">
            <span class="text-uppercase fw-bold small text-warning">Getting There</span>
            <h2 class="section-title">Venue & Location</h2>
        </div>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="row g-0">
                <div class="col-lg-5 p-4 d-flex flex-column justify-content-center bg-white">
                    <h4 class="fw-bold text-dark mb-2"><?= e($event['venue_name']) ?></h4>
                    <p class="text-muted mb-3"><i data-lucide="map-pin" class="text-danger" style="width:16px;height:16px;"></i> <?= e($event['address']) ?>, <?= e($event['city']) ?>, <?= e($event['pincode'] ?? '560006') ?></p>
                    <p class="small text-muted mb-4">Palace Grounds offers convenient central access, valet parking, and ample designated vehicle holding areas.</p>
                    <?php if (!empty($event['google_maps_url'])): ?>
                        <div>
                            <a href="<?= e($event['google_maps_url']) ?>" target="_blank" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2">
                                <i data-lucide="external-link" style="width:14px;height:14px;"></i> Open in Google Maps
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-7">
                    <!-- Embedded Responsive Map -->
                    <iframe 
                        src="https://maps.google.com/maps?q=<?= urlencode($event['venue_name'] . ' ' . $event['city']) ?>&t=&z=13&ie=UTF8&iwloc=&output=embed" 
                        width="100%" 
                        height="320" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- 10. FAQ SECTION -->
    <?php if (!empty($event['faqs'])): ?>
    <section class="mb-5">
        <div class="text-center mb-4">
            <span class="text-uppercase fw-bold small text-warning">Got Questions?</span>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="accordion faq-item shadow-sm rounded-3 overflow-hidden" id="eventFaqAccordion">
            <?php foreach ($event['faqs'] as $index => $faq): ?>
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?> fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>">
                            <?= e($faq['question']) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#eventFaqAccordion">
                        <div class="accordion-body text-muted">
                            <?= e($faq['answer']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- 11. CONTACT SECTION -->
    <section class="mb-5">
        <div class="bg-white p-4 rounded-4 border shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h4 class="fw-bold mb-1">Need Assistance with Your Booking?</h4>
                    <p class="text-muted small mb-0">Our dedicated organizer support desk is ready to answer questions regarding passes, group bookings, or venue directions.</p>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <a href="tel:<?= e($event['contact_phone'] ?: '+91 98860 12345') ?>" class="btn btn-outline-dark btn-sm me-2">
                        <i data-lucide="phone" style="width:14px;height:14px;"></i> <?= e($event['contact_phone'] ?: '+91 98860 12345') ?>
                    </a>
                    <a href="mailto:<?= e($event['contact_email'] ?: 'contact@utsavam.com') ?>" class="btn btn-outline-secondary btn-sm">
                        <i data-lucide="mail" style="width:14px;height:14px;"></i> Email
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- 12. FOOTER SECTION -->
<footer class="bg-white border-top py-4">
    <div class="container text-center text-muted small">
        <div class="mb-2">
            <strong><?= e($event['name']) ?></strong> • Presented by <?= e($client['company_name'] ?? $client['name']) ?>
        </div>
        <p class="mb-0 text-muted" style="font-size:12px;">Powered by <strong><?= e(APP_NAME) ?></strong> — <?= e(APP_TAGLINE) ?></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
