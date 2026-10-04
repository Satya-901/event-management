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

            // Dynamic multi-package resolution
            $selectedPackagesList = [];
            $totalPassCount = 0;
            $totalAmount = 0.0;
            $primaryPkgId = null;

            // 1. Check if JSON payload was sent
            if (!empty($_POST['selected_packages_json'])) {
                $decoded = json_decode($_POST['selected_packages_json'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $item) {
                        $qty = max(0, (int)($item['qty'] ?? 0));
                        $pid = trim($item['id'] ?? '');
                        if ($qty > 0 && !empty($pid)) {
                            $found = false;
                            foreach (($event['packages'] ?? []) as $p) {
                                if (($p['id'] ?? '') === $pid) {
                                    $price = (float)($p['price'] ?? 0);
                                    $totalPassCount += $qty;
                                    $totalAmount += ($price * $qty);
                                    $selectedPackagesList[] = [
                                        'id' => $p['id'],
                                        'name' => $p['name'],
                                        'price' => $price,
                                        'qty' => $qty,
                                        'subtotal' => $price * $qty
                                    ];
                                    if (!$primaryPkgId) $primaryPkgId = $p['id'];
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found && $pid === 'default') {
                                $price = (float)($event['price_amount'] ?? 0);
                                $totalPassCount += $qty;
                                $totalAmount += ($price * $qty);
                                $selectedPackagesList[] = [
                                    'id' => 'default',
                                    'name' => 'Standard Entry Pass',
                                    'price' => $price,
                                    'qty' => $qty,
                                    'subtotal' => $price * $qty
                                ];
                                if (!$primaryPkgId) $primaryPkgId = 'default';
                            }
                        }
                    }
                }
            }

            // 2. Check package_qty associative array
            if (empty($selectedPackagesList) && !empty($_POST['package_qty']) && is_array($_POST['package_qty'])) {
                foreach ($_POST['package_qty'] as $pid => $qtyVal) {
                    $qty = max(0, (int)$qtyVal);
                    if ($qty > 0) {
                        $pid = trim((string)$pid);
                        foreach (($event['packages'] ?? []) as $p) {
                            if (($p['id'] ?? '') === $pid) {
                                $price = (float)($p['price'] ?? 0);
                                $totalPassCount += $qty;
                                $totalAmount += ($price * $qty);
                                $selectedPackagesList[] = [
                                    'id' => $p['id'],
                                    'name' => $p['name'],
                                    'price' => $price,
                                    'qty' => $qty,
                                    'subtotal' => $price * $qty
                                ];
                                if (!$primaryPkgId) $primaryPkgId = $p['id'];
                                break;
                            }
                        }
                    }
                }
            }

            // 3. Fallback to legacy single package
            if (empty($selectedPackagesList)) {
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

                $passCount = max(1, (int)($_POST['pass_count'] ?? 1));
                $pkgName = $selectedPackage['name'] ?? ($event['price_label'] ?: 'Standard Pass');
                $pkgPrice = (float)($selectedPackage['price'] ?? ($event['price_amount'] ?? 0));
                $totalPassCount = $passCount;
                $totalAmount = $pkgPrice * $passCount;
                $primaryPkgId = $selectedPackage['id'] ?? 'default';
                $selectedPackagesList[] = [
                    'id' => $primaryPkgId,
                    'name' => $pkgName,
                    'price' => $pkgPrice,
                    'qty' => $passCount,
                    'subtotal' => $totalAmount
                ];
            }

            if ($totalPassCount < 1) {
                throw new Exception("Please select at least 1 pass to continue.");
            }

            $summaryNames = [];
            foreach ($selectedPackagesList as $sp) {
                $summaryNames[] = $sp['qty'] . ' × ' . $sp['name'];
            }
            $finalPkgName = implode(', ', $summaryNames);
            $firstPkgPrice = $selectedPackagesList[0]['price'] ?? 0;
            $utrNumber = trim($_POST['utr_number'] ?? '');

            if ($totalAmount > 0) {
                $organizerUpi = trim($client['upi_id'] ?? '');
                if (empty($organizerUpi)) {
                    throw new Exception("Organizer UPI ID is not configured in the system. Online payments cannot be accepted. Please contact the event organizer.");
                }
                if (empty($utrNumber)) {
                    throw new Exception("Please enter your 12-digit UTR / UPI Transaction Reference Number after completing payment.");
                }
            }

            $bookingData = [
                'event_id' => $event['id'],
                'customer_name' => $customerName,
                'email' => $email,
                'phone' => $phone,
                'pass_count' => $totalPassCount,
                'package_id' => $primaryPkgId,
                'package_name' => $finalPkgName,
                'package_price' => $firstPkgPrice,
                'total_amount' => $totalAmount,
                'utr_number' => $utrNumber,
                'payment_method' => 'upi',
                'payment_status' => ($totalAmount > 0) ? 'pending_verification' : 'free',
                'status' => ($totalAmount > 0) ? 'pending' : 'confirmed',
                'packages_breakdown' => $selectedPackagesList
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

// Organizer UPI Configuration Check (NO DUMMY FALLBACKS)
$organizerUpiId = trim($client['upi_id'] ?? '');
$organizerUpiName = trim($client['upi_name'] ?? ($client['company_name'] ?? $client['name'] ?? ''));
$organizerCustomQr = trim($client['upi_qr_code'] ?? '');
$hasUpiConfigured = !empty($organizerUpiId);

// Extract 10-digit mobile number if present in UPI ID (e.g. 8874268474@pthdfc) or client profile
$organizerMobile = '';
if (preg_match('/^([6-9]\d{9})@/i', $organizerUpiId, $matches)) {
    $organizerMobile = $matches[1];
} elseif (!empty($client['mobile']) && preg_match('/^[6-9]\d{9}$/', trim($client['mobile']))) {
    $organizerMobile = trim($client['mobile']);
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
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.85);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            position: sticky;
            top: 0;
            z-index: 1020;
            transition: all 0.2s ease;
        }
        .nav-brand-title {
            font-weight: 700;
            font-size: 1rem;
            color: #111827;
            letter-spacing: -0.2px;
        }
        .nav-brand-logo {
            height: 34px;
            width: auto;
            border-radius: 6px;
            object-fit: contain;
        }
        .nav-brand-avatar {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #dc2626;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.5px;
        }
        .nav-breadcrumb-link {
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .nav-breadcrumb-link:hover {
            color: #0f172a;
        }
        .btn-nav-outline {
            border: 1px solid #e2e8f0;
            color: #475569;
            background: #ffffff;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .btn-nav-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .btn-nav-primary {
            background-color: var(--brand-red);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 7px 18px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 1px 3px rgba(220, 38, 38, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-nav-primary:hover {
            background-color: var(--brand-red-hover);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35);
            transform: translateY(-1px);
        }

        /* Event Header (Title, Date, Time & Venue exactly matching reference) */
        .event-header-block {
            margin-bottom: 24px;
            padding-top: 8px;
        }
        .event-main-title {
            font-size: 2.35rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.025em;
            line-height: 1.22;
            margin-bottom: 8px;
            word-wrap: break-word;
        }
        .event-meta-line {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            font-size: 1.05rem;
            line-height: 1.5;
        }
        .event-datetime-highlight {
            color: var(--brand-red);
            font-weight: 700;
            white-space: nowrap;
        }
        .event-pipe-separator {
            color: #9ca3af;
            margin: 0 12px;
            font-weight: 300;
            user-select: none;
        }
        .event-venue-highlight {
            color: #4b5563;
            font-weight: 400;
        }

        @media (max-width: 768px) {
            .event-main-title {
                font-size: 1.65rem;
                letter-spacing: -0.015em;
                line-height: 1.25;
                margin-bottom: 10px;
            }
            .event-meta-line {
                font-size: 0.95rem;
            }
            .event-pipe-separator {
                margin: 0 8px;
            }
        }

        @media (max-width: 576px) {
            .event-header-block {
                margin-bottom: 18px;
                padding-top: 2px;
            }
            .event-pipe-separator {
                display: none;
            }
            .event-venue-highlight {
                display: block;
                width: 100%;
                margin-top: 4px;
                font-size: 0.92rem;
                color: #6b7280;
            }
            .event-datetime-highlight {
                font-size: 0.95rem;
            }
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

        /* Booking Modal Scroll & Viewport Discipline */
        #bookingModal.modal {
            overflow-x: hidden;
            overflow-y: auto;
        }
        #bookingModal .modal-dialog {
            max-width: 720px;
            margin: 1.75rem auto;
            max-height: calc(100vh - 3.5rem);
            display: flex;
        }
        #bookingModal .modal-content {
            max-height: calc(100vh - 3.5rem);
            display: flex;
            flex-direction: column;
            width: 100%;
            overflow: hidden;
            border-radius: 16px;
        }
        #bookingModal .modal-body {
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            flex: 1 1 auto;
            overscroll-behavior: contain;
        }
        #bookingModal .modal-header,
        #bookingModal .modal-footer {
            flex-shrink: 0;
        }

        /* Step Form Tracker */
        .step-tracker-container {
            position: relative;
            padding: 8px 12px 2px 12px;
            margin-top: 4px;
        }
        .step-progress-line {
            position: absolute;
            top: 22px;
            left: 50px;
            right: 50px;
            height: 3px;
            background: #e2e8f0;
            z-index: 1;
        }
        .step-progress-fill {
            position: absolute;
            top: 22px;
            left: 50px;
            height: 3px;
            background: var(--brand-red);
            z-index: 2;
            transition: width 0.3s ease;
        }
        .step-indicators-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 3;
        }
        .step-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
        }
        .step-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .step-indicator.active .step-circle {
            background: var(--brand-red);
            border-color: var(--brand-red);
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.15);
        }
        .step-indicator.active .step-label {
            color: var(--brand-red);
            font-weight: 700;
        }
        .step-indicator.completed .step-circle {
            background: #10b981;
            border-color: #10b981;
            color: #ffffff;
        }
        .step-indicator.completed .step-label {
            color: #10b981;
        }
    </style>
</head>
<body>

<!-- Top Sleek Navbar -->
<header class="site-header py-2">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <a href="/<?= e($client['slug']) ?>/" class="d-flex align-items-center gap-2.5 text-decoration-none">
                <?php if (!empty($client['logo'])): ?>
                    <img src="<?= e($client['logo']) ?>" alt="<?= e($client['name']) ?>" class="nav-brand-logo">
                <?php else: ?>
                    <div class="nav-brand-avatar"><?= strtoupper(substr($client['name'] ?? 'AK', 0, 2)) ?></div>
                <?php endif; ?>
                <span class="nav-brand-title"><?= e($client['company_name'] ?? $client['name']) ?></span>
            </a>
            
            <div class="d-none d-md-flex align-items-center gap-2 text-xs ps-3 border-start" style="border-color:#e2e8f0 !important;">
                <a href="/<?= e($client['slug']) ?>/" class="nav-breadcrumb-link">Events</a>
                <span class="text-muted" style="font-size:11px;">/</span>
                <span class="text-dark fw-semibold text-truncate" style="max-width: 220px;"><?= e($event['name']) ?></span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="/<?= e($client['slug']) ?>/" class="btn-nav-outline d-none d-sm-inline-flex">
                <i data-lucide="grid" style="width:14px;height:14px;"></i>
                <span>All Events</span>
            </a>
            <button type="button" class="btn-nav-primary" data-bs-toggle="modal" data-bs-target="#bookingModal">
                <i data-lucide="ticket" style="width:15px;height:15px;"></i>
                <span>Book Tickets</span>
            </button>
        </div>
    </div>
</header>

<main class="container py-3 py-md-4">

    <!-- Flash Error Message if any -->
    <?php if ($error): ?>
        <div class="alert alert-danger py-2.5 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2 shadow-xs border-0">
            <i data-lucide="alert-circle" style="width:18px;height:18px;" class="flex-shrink-0"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- 1. TOP HEADER: Event Title & Red Subtitle Date/Venue (Exact Match to Reference) -->
    <div class="event-header-block">
        <h1 class="event-main-title">
            <?= e($event['name']) ?>
        </h1>
        <div class="event-meta-line">
            <span class="event-datetime-highlight">
                <?= e($formattedDate) ?>, <?= e($timeRangeString) ?>
            </span>
            <span class="event-pipe-separator">|</span>
            <span class="event-venue-highlight">
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

<!-- ================= TICKET BOOKING MODAL (MULTI-STEP FORM) ================= -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>" id="eventBookingForm" class="modal-content border-0 shadow">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="book_event">
            
            <!-- Step Tracker Header -->
            <div class="modal-header border-bottom py-3 px-4 bg-light flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-2.5">
                    <div>
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0" id="bookingModalLabel">
                            <i data-lucide="ticket" class="text-danger" style="width:20px;height:20px;"></i>
                            Book Passes — <?= e($event['name']) ?>
                        </h5>
                        <div class="text-muted text-xs mt-0.5"><?= e($formattedDate) ?> • <?= e($timeRangeString) ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Step Tracker Bar -->
                <div class="step-tracker-container">
                    <div class="step-progress-line"></div>
                    <div class="step-progress-fill" id="stepProgressFill" style="width: 0%;"></div>
                    <div class="step-indicators-wrap">
                        <div class="step-indicator active" id="stepInd1" onclick="goToStep(1)">
                            <div class="step-circle" id="stepCircle1">1</div>
                            <div class="step-label">Passes</div>
                        </div>
                        <div class="step-indicator" id="stepInd2" onclick="goToStep(2)">
                            <div class="step-circle" id="stepCircle2">2</div>
                            <div class="step-label">Attendee Details</div>
                        </div>
                        <div class="step-indicator" id="stepInd3" onclick="goToStep(3)">
                            <div class="step-circle" id="stepCircle3">3</div>
                            <div class="step-label">Payment & UTR</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-body p-4">
                
                <!-- ================= STEP 1: CHOOSE YOUR PASS CATEGORY (MULTI-SELECT SUPPORTED) ================= -->
                <div id="bookingStep1" class="booking-step">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                                <i data-lucide="layers" class="text-danger" style="width:16px;height:16px;"></i>
                                Step 1: Choose Your Pass Category
                            </h6>
                            <div class="text-muted text-xs mt-0.5">Select multiple categories or quantities according to your group</div>
                        </div>
                        <span class="badge bg-light text-secondary border text-xs">Step 1 of 3</span>
                    </div>

                    <!-- Hidden input storing JSON of selected packages -->
                    <input type="hidden" name="selected_packages_json" id="selectedPackagesJsonInput" value="">
                    <input type="hidden" name="pass_count" id="modalPassCountInput" value="1">

                    <?php if (!empty($packages) && is_array($packages)): ?>
                        <div class="row g-2 mb-3" id="packageListContainer">
                            <?php foreach ($packages as $idx => $pkg): ?>
                                <?php 
                                    $initQty = ($idx === 0) ? 1 : 0;
                                    $isSelected = ($initQty > 0);
                                ?>
                                <div class="col-md-6">
                                    <div class="ticket-pkg-card <?= $isSelected ? 'active' : '' ?>" id="pkg_card_<?= e($pkg['id']) ?>" onclick="toggleCardSelection('<?= e($pkg['id']) ?>', event)">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div class="d-flex align-items-start gap-2">
                                                <input type="checkbox" id="pkg_check_<?= e($pkg['id']) ?>" class="form-check-input mt-1 pkg-checkbox" <?= $isSelected ? 'checked' : '' ?> onclick="onCheckboxClick('<?= e($pkg['id']) ?>', this.checked, event)">
                                                <div>
                                                    <span class="fw-bold text-dark small d-block"><?= e($pkg['name']) ?></span>
                                                    <div class="text-muted text-xs"><?= e($pkg['description'] ?? 'General Entry') ?></div>
                                                </div>
                                            </div>
                                            <?php if (!empty($pkg['badge'])): ?>
                                                <span class="badge bg-danger text-white rounded-pill text-xs flex-shrink-0"><?= e($pkg['badge']) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-2.5 pt-2 border-top border-light">
                                            <div>
                                                <span class="fw-bolder fs-6 text-danger">₹ <?= number_format($pkg['price']) ?></span>
                                                <span class="text-muted text-xs">/ pass</span>
                                            </div>

                                            <!-- Individual Stepper Counter for Each Package -->
                                            <div class="d-inline-flex align-items-center bg-white border rounded-2 shadow-2xs" onclick="event.stopPropagation()">
                                                <button type="button" class="btn btn-sm btn-light py-0.5 px-2 text-secondary fw-bold border-0 rounded-start" onclick="changePackageQty('<?= e($pkg['id']) ?>', -1)">−</button>
                                                <input type="number" id="pkg_qty_<?= e($pkg['id']) ?>" name="package_qty[<?= e($pkg['id']) ?>]" class="form-control form-control-sm text-center fw-bold p-0 border-0 bg-transparent font-monospace" style="width:36px;font-size:13px;" value="<?= $initQty ?>" min="0" max="99" oninput="onQtyInput('<?= e($pkg['id']) ?>', this.value)">
                                                <button type="button" class="btn btn-sm btn-light py-0.5 px-2 text-danger fw-bold border-0 rounded-end" onclick="changePackageQty('<?= e($pkg['id']) ?>', 1)">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Single / Default Event Pass -->
                        <div class="ticket-pkg-card active mb-3" id="pkg_card_default">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="pkg_check_default" class="form-check-input mt-0" checked disabled>
                                    <div>
                                        <span class="fw-bold text-dark d-block">Standard Entry Pass</span>
                                        <div class="text-muted text-xs">Access to main event arena</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-bolder fs-5 text-danger">
                                        <?= (float)$event['price_amount'] > 0 ? '₹ ' . number_format($event['price_amount']) : 'Free' ?>
                                    </span>
                                    <div class="d-inline-flex align-items-center bg-white border rounded-2 shadow-2xs">
                                        <button type="button" class="btn btn-sm btn-light py-0.5 px-2 text-secondary fw-bold border-0 rounded-start" onclick="changePackageQty('default', -1)">−</button>
                                        <input type="number" id="pkg_qty_default" name="package_qty[default]" class="form-control form-control-sm text-center fw-bold p-0 border-0 bg-transparent font-monospace" style="width:36px;font-size:13px;" value="1" min="1" max="99" oninput="onQtyInput('default', this.value)">
                                        <button type="button" class="btn btn-sm btn-light py-0.5 px-2 text-danger fw-bold border-0 rounded-end" onclick="changePackageQty('default', 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Selection Summary Box -->
                    <div class="card bg-light border p-3 rounded-3 mb-3 shadow-xs">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                            <span class="small fw-bold text-dark d-flex align-items-center gap-1.5">
                                <i data-lucide="shopping-cart" style="width:14px;height:14px;" class="text-danger"></i>
                                <span>Selected Passes Summary</span>
                            </span>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" id="totalPassCountBadge">1 Pass</span>
                        </div>

                        <!-- Dynamic Selected Packages Breakdown -->
                        <div id="selectedPackagesBreakdownList" class="d-flex flex-column gap-1 mb-2">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="text-xs text-muted text-uppercase fw-semibold" style="letter-spacing:0.5px;">Total Amount</span>
                            <div class="fw-bolder fs-5 text-danger" id="modalTotalDisplay">₹ <?= number_format($startingPrice) ?></div>
                        </div>
                    </div>

                    <div class="p-2.5 bg-light rounded-3 border small text-muted d-flex align-items-center gap-2">
                        <i data-lucide="info" class="text-danger flex-shrink-0" style="width:16px;height:16px;"></i>
                        <span style="font-size:12px;">You can select multiple pass categories or quantities for your group. Click <strong>Continue</strong> to proceed.</span>
                    </div>
                </div>

                <!-- ================= STEP 2: ATTENDEE REGISTRATION DETAILS ================= -->
                <div id="bookingStep2" class="booking-step d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                            <i data-lucide="user-check" class="text-danger" style="width:16px;height:16px;"></i>
                            Step 2: Attendee Registration Information
                        </h6>
                        <span class="badge bg-light text-secondary border text-xs">Step 2 of 3</span>
                    </div>

                    <!-- Pass recap card -->
                    <div class="p-2.5 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 d-flex justify-content-between align-items-center mb-3">
                        <div class="small text-dark">
                            <span class="text-muted">Selected Pass:</span> <strong id="step2SummaryPass">1 × Pass</strong>
                        </div>
                        <div class="fw-bolder text-danger" id="step2SummaryTotal">₹ <?= number_format($startingPrice) ?></div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" id="inputCustomerName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">WhatsApp Mobile <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="inputPhone" class="form-control font-monospace" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="inputEmail" class="form-control" required>
                            <div class="form-text text-xs">Official entry QR pass will be emailed to this address once verified.</div>
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
                                    <textarea name="custom_<?= e($fkey) ?>" class="form-control" rows="2" <?= !empty($field['required']) ? 'required' : '' ?>></textarea>
                                <?php else: ?>
                                    <input type="<?= e($field['field_type'] ?: 'text') ?>" name="custom_<?= e($fkey) ?>" class="form-control" <?= !empty($field['required']) ? 'required' : '' ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ================= STEP 3: UPI PAYMENT & UTR ================= -->
                <div id="bookingStep3" class="booking-step d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                            <i data-lucide="qr-code" class="text-danger" style="width:16px;height:16px;"></i>
                            Step 3: UPI Payment & UTR Reference
                        </h6>
                        <span class="badge bg-light text-secondary border text-xs">Step 3 of 3</span>
                    </div>

                    <?php if (!$hasUpiConfigured): ?>
                        <!-- STRICT ERROR: Organizer has not configured UPI ID (NO DUMMY FALLBACKS) -->
                        <div class="alert alert-danger p-3.5 rounded-3 border-danger mb-3 shadow-xs">
                            <div class="d-flex align-items-center gap-2 mb-2 text-danger fw-bold fs-6">
                                <i data-lucide="alert-octagon" style="width:20px;height:20px;"></i>
                                <span>Organizer Payment Setup Incomplete</span>
                            </div>
                            <p class="small text-dark mb-2" style="line-height:1.5;">
                                The event organizer (<strong><?= e($client['company_name'] ?? $client['name']) ?></strong>) has not configured their receiver UPI ID in the backend system.
                            </p>
                            <div class="text-xs text-danger fw-semibold mb-2">
                                <i data-lucide="shield-x" style="width:14px;height:14px;vertical-align:-2px;"></i>
                                For security, dummy/fake payment accounts are strictly disabled. Online payments cannot be accepted until the organizer adds their official UPI ID.
                            </div>
                            <div class="text-xs text-muted pt-2 border-top">
                                Organizer Support: <strong><?= e($event['contact_phone'] ?? $client['mobile'] ?? 'Contact Organizer') ?></strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- VALID UPI CONFIGURED: Direct App Intent, QR, and Instructions -->
                        <div class="card p-3 border-danger border-opacity-25 bg-light rounded-3 mb-3">
                            <!-- Payee & Amount Highlight -->
                            <div class="d-flex align-items-center justify-content-between p-2.5 bg-white rounded-3 border mb-3">
                                <div>
                                    <span class="text-xs text-muted text-uppercase d-block fw-semibold" style="letter-spacing:0.5px;font-size:10px;">Payee / Organizer</span>
                                    <div class="fw-bold text-dark small text-truncate" style="max-width:210px;"><?= e($organizerUpiName) ?></div>
                                </div>
                                <div class="text-end">
                                    <span class="text-xs text-muted text-uppercase d-block fw-semibold" style="letter-spacing:0.5px;font-size:10px;">Amount to Pay</span>
                                    <div class="fw-bolder fs-5 text-danger" id="upiAmountBox">₹ <?= number_format($startingPrice) ?></div>
                                </div>
                            </div>

                            <!-- Payment Mode Switcher Tabs -->
                            <ul class="nav nav-pills nav-fill gap-1 bg-white p-1 rounded-3 border mb-3" id="paymentTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-1.5 px-2 text-xs fw-bold rounded-2 d-flex align-items-center justify-content-center gap-1.5" id="tab-app-pay" data-bs-toggle="pill" data-bs-target="#panel-app-pay" type="button" role="tab">
                                        <i data-lucide="smartphone" style="width:14px;height:14px;"></i>
                                        <span>Pay on this Phone</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1.5 px-2 text-xs fw-bold rounded-2 d-flex align-items-center justify-content-center gap-1.5" id="tab-qr-pay" data-bs-toggle="pill" data-bs-target="#panel-qr-pay" type="button" role="tab">
                                        <i data-lucide="qr-code" style="width:14px;height:14px;"></i>
                                        <span>Scan QR Code</span>
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="paymentTabsContent">
                                <!-- TAB 1: Direct 1-Click UPI Apps on Phone -->
                                <div class="tab-pane fade show active" id="panel-app-pay" role="tabpanel">
                                    <div class="text-xs text-muted text-center mb-2.5">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                            ⚡ Recommended for Mobile Users (No Screenshot Needed)
                                        </span>
                                    </div>

                                    <div class="d-grid gap-2 mb-3">
                                        <!-- PhonePe Direct -->
                                        <button type="button" class="btn text-white fw-bold py-2 px-3 rounded-3 d-flex align-items-center justify-content-between shadow-xs" style="background-color: #5f259f;" onclick="openUpiApp('phonepe')">
                                            <span class="d-flex align-items-center gap-2">
                                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                    <circle cx="12" cy="12" r="12" fill="#5f259f"/>
                                                    <path d="M14.8 7.5h-4.3c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h1.7v-3.7h2.6c2.2 0 3.7-1.3 3.7-3.2 0-1.8-1.5-3.1-3.7-3.1zm-.2 4.3h-2.4V9.3h2.4c1.1 0 1.8.6 1.8 1.2 0 .7-.7 1.3-1.8 1.3z" fill="#ffffff"/>
                                                </svg>
                                                <span>Pay with PhonePe</span>
                                            </span>
                                            <span class="badge bg-white text-dark text-xs px-2 py-1 font-monospace">1-Click</span>
                                        </button>

                                        <!-- Google Pay Direct -->
                                        <button type="button" class="btn text-white fw-bold py-2 px-3 rounded-3 d-flex align-items-center justify-content-between shadow-xs" style="background-color: #1f1f1f;" onclick="openUpiApp('gpay')">
                                            <span class="d-flex align-items-center gap-2">
                                                <svg viewBox="0 0 24 24" width="20" height="20">
                                                    <rect width="24" height="24" rx="4" fill="#ffffff"/>
                                                    <path d="M12 9.2v2.8h4.5c-.2 1.2-1.3 3.5-4.5 3.5-2.7 0-4.9-2.2-4.9-5s2.2-5 4.9-5c1.5 0 2.6.7 3.2 1.2l2.2-2.1C16 3.3 14.2 2.5 12 2.5 6.8 2.5 2.5 6.8 2.5 12s4.3 9.5 9.5 9.5c5.5 0 9.2-3.9 9.2-9.3 0-.6-.1-1.1-.2-1.5H12z" fill="#4285F4"/>
                                                </svg>
                                                <span>Pay with Google Pay</span>
                                            </span>
                                            <span class="badge bg-white text-dark text-xs px-2 py-1 font-monospace">1-Click</span>
                                        </button>

                                        <!-- Paytm Direct -->
                                        <button type="button" class="btn text-white fw-bold py-2 px-3 rounded-3 d-flex align-items-center justify-content-between shadow-xs" style="background-color: #002e6e;" onclick="openUpiApp('paytm')">
                                            <span class="d-flex align-items-center gap-2">
                                                <span class="fw-black fs-6" style="letter-spacing:-0.5px;">Paytm</span>
                                                <span class="small fw-normal">UPI</span>
                                            </span>
                                            <span class="badge bg-white text-dark text-xs px-2 py-1 font-monospace">1-Click</span>
                                        </button>

                                        <!-- Generic Any UPI App -->
                                        <button type="button" class="btn btn-outline-dark fw-bold py-2 px-3 rounded-3 d-flex align-items-center justify-content-between bg-white shadow-xs" onclick="openUpiApp('upi')">
                                            <span class="d-flex align-items-center gap-2">
                                                <i data-lucide="credit-card" style="width:16px;height:16px;"></i>
                                                <span>Other UPI App (BHIM / Cred / Amazon Pay)</span>
                                            </span>
                                            <i data-lucide="chevron-right" style="width:16px;height:16px;"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- TAB 2: Dynamic QR Code Box for Desktop / Other Device -->
                                <div class="tab-pane fade" id="panel-qr-pay" role="tabpanel">
                                    <div class="text-center py-2">
                                        <div class="bg-white p-2.5 rounded-3 border d-inline-block shadow-xs mb-2">
                                            <img id="modalUpiQrImage" src="<?= !empty($organizerCustomQr) ? e($organizerCustomQr) : ('https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode('upi://pay?pa=' . $organizerUpiId . '&pn=' . urlencode($organizerUpiName) . '&am=' . $startingPrice . '&cu=INR&tn=' . urlencode($event['name']))) ?>" alt="UPI QR Code" style="width:160px;height:160px;display:block;">
                                        </div>
                                        <div class="text-xs text-muted font-monospace mb-1">Scan using any UPI App camera</div>
                                        <div class="text-xs text-muted" style="font-size:11px;">(Best when viewing on laptop/PC or using second phone)</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Copyable UPI ID and Mobile Number Details -->
                            <div class="p-2.5 bg-white rounded-3 border mt-1">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div>
                                        <span class="text-xs text-muted text-uppercase d-block" style="font-size:10px;">Receiver UPI ID / VPA</span>
                                        <code class="fw-bold text-dark font-monospace small" id="textUpiVpa"><?= e($organizerUpiId) ?></code>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2.5 text-xs fw-semibold" id="btnCopyUpiId">
                                        Copy UPI ID
                                    </button>
                                </div>

                                <?php if (!empty($organizerMobile)): ?>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-xs text-muted text-uppercase d-block" style="font-size:10px;">Payee Mobile Number (PhonePe/GPay)</span>
                                            <code class="fw-bold text-dark font-monospace small" id="textPayeeMobile"><?= e($organizerMobile) ?></code>
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2.5 text-xs fw-semibold" id="btnCopyPayeeMobile">
                                            Copy Number
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- PhonePe Gallery Notice (Directly addresses the user's reported popup) -->
                            <div class="alert alert-warning border-warning border-opacity-25 bg-warning bg-opacity-10 p-2.5 rounded-3 mb-0 mt-3 text-start">
                                <div class="d-flex align-items-center gap-1.5 fw-bold text-dark small mb-1">
                                    <i data-lucide="info" class="text-warning flex-shrink-0" style="width:16px;height:16px;"></i>
                                    <span>PhonePe Gallery QR Notice:</span>
                                </div>
                                <div class="text-xs text-dark" style="font-size:11.5px;line-height:1.5;">
                                    If you scanned a screenshot using PhonePe Gallery and see <em>"You can pay up to ₹2,000 with QR codes via gallery"</em>:
                                    <ul class="ps-3 my-1">
                                        <li><strong>For transactions up to ₹2,000:</strong> Tap <strong>"DISMISS"</strong> in PhonePe and enter your UPI PIN to finish payment.</li>
                                        <li><strong>To bypass gallery limits completely:</strong> Use the direct <strong>"Pay with PhonePe / Google Pay"</strong> button above.</li>
                                        <?php if (!empty($organizerMobile)): ?>
                                            <li><strong>Or pay via Mobile Number:</strong> In PhonePe, select "To Mobile Number" and enter <code><?= e($organizerMobile) ?></code>.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- UTR / Transaction Reference Input -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between">
                                <span>12-Digit UTR / UPI Reference Number <span class="text-danger">*</span></span>
                                <span class="badge bg-light text-secondary border fw-normal" style="font-size:10.5px;">From payment receipt</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted font-monospace"><i data-lucide="hash" style="width:14px;height:14px;"></i></span>
                                <input type="text" name="utr_number" id="modalUtrInput" class="form-control font-monospace" minlength="6" maxlength="30" <?= ($startingPrice > 0) ? 'required' : '' ?>>
                            </div>
                            <div class="text-muted text-xs mt-1">
                                <i data-lucide="info" style="width:12px;height:12px;vertical-align:-1px;"></i>
                                Open your Google Pay, PhonePe, or Paytm receipt and copy the 12-digit UTR / UPI Reference Number.
                            </div>
                        </div>

                        <div class="alert alert-warning py-2 px-3 small rounded-2 mb-0 d-flex align-items-start gap-2 border-0 bg-warning bg-opacity-10 text-dark">
                            <i data-lucide="clock" class="text-warning flex-shrink-0" style="width:16px;height:16px;margin-top:2px;"></i>
                            <span style="font-size:12px;"><strong>Verification Notice:</strong> Upon submission, your pass will enter <em>Pending Verification</em> until organizer verifies this UTR on their panel.</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Multi-Step Footer Navigation Buttons -->
            <div class="modal-footer border-top bg-light d-flex justify-content-between align-items-center py-3 px-4">
                <div>
                    <div class="text-xs text-muted">Total Payable</div>
                    <div class="fw-bolder fs-5 text-danger" id="modalBottomTotalDisplay">₹ <?= number_format($startingPrice) ?></div>
                </div>

                <!-- Footer Buttons: Step 1 -->
                <div id="footerButtonsStep1" class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger fw-bold px-4 shadow-sm d-inline-flex align-items-center gap-1.5" onclick="goToStep(2)">
                        <span>Continue &rarr;</span>
                    </button>
                </div>

                <!-- Footer Buttons: Step 2 -->
                <div id="footerButtonsStep2" class="d-none gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="goToStep(1)">&larr; Back</button>
                    <button type="button" class="btn btn-danger fw-bold px-4 shadow-sm d-inline-flex align-items-center gap-1.5" id="btnStep2Proceed" onclick="goToStep(3)">
                        <span>Proceed to Payment &rarr;</span>
                    </button>
                </div>

                <!-- Footer Buttons: Step 3 -->
                <div id="footerButtonsStep3" class="d-none gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="goToStep(2)">&larr; Back</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm d-inline-flex align-items-center gap-1.5" id="btnStep3Submit" <?= (!$hasUpiConfigured && $startingPrice > 0) ? 'disabled title="UPI ID is not configured in backend"' : '' ?>>
                        <span>Confirm & Book Passes</span>
                        <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                    </button>
                </div>
            </div>

        </form>
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

// 3. Ticket Booking Multi-Step Form Logic & Live Calculations (Multi-Package Selection Support)
let currentStep = 1;

const availablePackages = <?= json_encode(!empty($packages) ? $packages : [[
    'id' => 'default',
    'name' => 'Standard Entry Pass',
    'price' => (float)($event['price_amount'] ?? 0),
    'description' => 'General Entry'
]]) ?>;

// State of quantities for each package: { [pkg_id]: count }
let packageQuantities = {};
availablePackages.forEach((pkg, index) => {
    packageQuantities[pkg.id] = (index === 0) ? 1 : 0;
});

let currentTotalAmount = 0;
let currentTotalPasses = 0;
let currentSummaryString = '';

const clientUpiId = <?= json_encode($organizerUpiId) ?>;
const clientUpiName = <?= json_encode($organizerUpiName) ?>;
const clientCustomQr = <?= json_encode($organizerCustomQr) ?>;
const hasUpiConfigured = <?= json_encode($hasUpiConfigured) ?>;
const currentEventName = <?= json_encode($event['name'] ?? 'Event') ?>;
const clientMobile = <?= json_encode($organizerMobile) ?>;

function changePackageQty(pkgId, delta) {
    const current = packageQuantities[pkgId] || 0;
    const next = Math.max(0, current + delta);
    setPackageQty(pkgId, next);
}

function onQtyInput(pkgId, val) {
    let next = parseInt(val);
    if (isNaN(next) || next < 0) next = 0;
    setPackageQty(pkgId, next);
}

function onCheckboxClick(pkgId, isChecked, evt) {
    if (evt) evt.stopPropagation();
    if (isChecked) {
        if (!packageQuantities[pkgId] || packageQuantities[pkgId] <= 0) {
            setPackageQty(pkgId, 1);
        }
    } else {
        setPackageQty(pkgId, 0);
    }
}

function toggleCardSelection(pkgId, evt) {
    const current = packageQuantities[pkgId] || 0;
    if (current <= 0) {
        setPackageQty(pkgId, 1);
    }
}

function setPackageQty(pkgId, qty) {
    packageQuantities[pkgId] = qty;
    
    // Update input
    const input = document.getElementById('pkg_qty_' + pkgId);
    if (input) input.value = qty;

    // Update checkbox
    const check = document.getElementById('pkg_check_' + pkgId);
    if (check) check.checked = (qty > 0);

    // Update card styling
    const card = document.getElementById('pkg_card_' + pkgId);
    if (card) {
        card.classList.toggle('active', qty > 0);
    }

    updateModalTotals();
}

function updateModalTotals() {
    let totalPasses = 0;
    let totalAmount = 0;
    const selectedList = [];

    availablePackages.forEach(pkg => {
        const qty = packageQuantities[pkg.id] || 0;
        if (qty > 0) {
            const price = parseFloat(pkg.price) || 0;
            const subtotal = price * qty;
            totalPasses += qty;
            totalAmount += subtotal;
            selectedList.push({
                id: pkg.id,
                name: pkg.name,
                price: price,
                qty: qty,
                subtotal: subtotal
            });
        }
    });

    currentTotalPasses = totalPasses;
    currentTotalAmount = totalAmount;

    // Hidden inputs for form submit
    const hiddenJson = document.getElementById('selectedPackagesJsonInput');
    if (hiddenJson) hiddenJson.value = JSON.stringify(selectedList);

    const hiddenPassCount = document.getElementById('modalPassCountInput');
    if (hiddenPassCount) hiddenPassCount.value = totalPasses;

    // Human-readable summary string: e.g. "1 × Female Stag Entry, 2 × Couple Pass"
    if (selectedList.length > 0) {
        currentSummaryString = selectedList.map(item => item.qty + ' × ' + item.name).join(', ');
    } else {
        currentSummaryString = 'No passes selected';
    }

    const formattedTotal = totalAmount > 0 ? ('₹ ' + totalAmount.toLocaleString('en-IN')) : (totalPasses > 0 ? 'Free' : '₹ 0');

    // Update Badge
    const badge = document.getElementById('totalPassCountBadge');
    if (badge) {
        badge.textContent = totalPasses + ' Pass' + (totalPasses !== 1 ? 'es' : '');
    }

    // Update dynamic breakdown list in Step 1
    const breakdownContainer = document.getElementById('selectedPackagesBreakdownList');
    if (breakdownContainer) {
        if (selectedList.length > 0) {
            breakdownContainer.innerHTML = selectedList.map(item => `
                <div class="d-flex justify-content-between align-items-center text-xs text-dark py-0.5">
                    <span class="text-secondary fw-semibold">${item.qty} × ${item.name}</span>
                    <span class="fw-bold font-monospace">${item.subtotal > 0 ? '₹ ' + item.subtotal.toLocaleString('en-IN') : 'Free'}</span>
                </div>
            `).join('');
        } else {
            breakdownContainer.innerHTML = '<div class="text-danger small fst-italic">Please select at least 1 pass to continue.</div>';
        }
    }

    const modalTotalDisp = document.getElementById('modalTotalDisplay');
    if (modalTotalDisp) modalTotalDisp.textContent = formattedTotal;

    const modalBottomDisp = document.getElementById('modalBottomTotalDisplay');
    if (modalBottomDisp) modalBottomDisp.textContent = formattedTotal;

    const step2Pass = document.getElementById('step2SummaryPass');
    if (step2Pass) step2Pass.textContent = currentSummaryString;

    const step2Total = document.getElementById('step2SummaryTotal');
    if (step2Total) step2Total.textContent = formattedTotal;

    const upiAmountBox = document.getElementById('upiAmountBox');
    if (upiAmountBox) upiAmountBox.textContent = formattedTotal;

    const utrInput = document.getElementById('modalUtrInput');
    if (utrInput) {
        utrInput.required = (totalAmount > 0 && hasUpiConfigured);
    }

    const btnStep2Proceed = document.getElementById('btnStep2Proceed');
    if (btnStep2Proceed) {
        if (totalAmount > 0) {
            btnStep2Proceed.innerHTML = '<span>Proceed to Payment &rarr;</span>';
            btnStep2Proceed.setAttribute('onclick', 'goToStep(3)');
        } else {
            btnStep2Proceed.innerHTML = '<span>Confirm Free Pass &rarr;</span>';
            btnStep2Proceed.setAttribute('onclick', 'submitBookingForm()');
        }
    }

    // Dynamic QR & Pay Links Update if UPI is configured
    if (hasUpiConfigured && totalAmount > 0) {
        const qrImg = document.getElementById('modalUpiQrImage');

        const upiUri = 'upi://pay?pa=' + encodeURIComponent(clientUpiId) +
                       '&pn=' + encodeURIComponent(clientUpiName) +
                       '&am=' + totalAmount +
                       '&cu=INR&tn=' + encodeURIComponent(currentEventName);

        if (qrImg) {
            if (clientCustomQr) {
                qrImg.src = clientCustomQr;
            } else {
                qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(upiUri);
            }
        }
    }
}

// Open specific UPI application directly (Bypasses gallery screenshot limit)
function openUpiApp(appType) {
    if (currentTotalAmount <= 0 || !clientUpiId) return;

    const params = 'pa=' + encodeURIComponent(clientUpiId) +
                   '&pn=' + encodeURIComponent(clientUpiName) +
                   '&am=' + encodeURIComponent(currentTotalAmount) +
                   '&cu=INR' +
                   '&tn=' + encodeURIComponent(currentEventName);

    let targetUri = 'upi://pay?' + params;
    if (appType === 'phonepe') {
        targetUri = 'phonepe://pay?' + params;
    } else if (appType === 'gpay') {
        targetUri = 'tez://upi/pay?' + params;
    } else if (appType === 'paytm') {
        targetUri = 'paytmmp://pay?' + params;
    }

    // Launch targeted intent
    window.location.href = targetUri;

    // Fallback to standard upi:// chooser if app is not installed
    if (appType !== 'upi') {
        setTimeout(function() {
            if (!document.hidden) {
                window.location.href = 'upi://pay?' + params;
            }
        }, 1200);
    }
}

// Multi-Step Form Navigation Controller
function goToStep(step) {
    if (step === 2) {
        // Validate Step 1: Ensure at least 1 pass selected across all categories
        if (currentTotalPasses < 1) {
            alert('Please select at least 1 pass category to continue.');
            return;
        }
    } else if (step === 3) {
        // Validate Step 2: Required attendee inputs
        const step2 = document.getElementById('bookingStep2');
        const requiredInputs = step2.querySelectorAll('input[required], textarea[required], select[required]');
        for (let input of requiredInputs) {
            if (!input.checkValidity()) {
                input.reportValidity();
                input.focus();
                return;
            }
        }

        if (currentTotalAmount <= 0) {
            submitBookingForm();
            return;
        }
    }

    currentStep = step;

    // Toggle Step Panels
    document.getElementById('bookingStep1').classList.toggle('d-none', step !== 1);
    document.getElementById('bookingStep2').classList.toggle('d-none', step !== 2);
    document.getElementById('bookingStep3').classList.toggle('d-none', step !== 3);

    // Toggle Footer Button Groups
    const f1 = document.getElementById('footerButtonsStep1');
    const f2 = document.getElementById('footerButtonsStep2');
    const f3 = document.getElementById('footerButtonsStep3');

    if (f1) { f1.classList.toggle('d-none', step !== 1); f1.classList.toggle('d-flex', step === 1); }
    if (f2) { f2.classList.toggle('d-none', step !== 2); f2.classList.toggle('d-flex', step === 2); }
    if (f3) { f3.classList.toggle('d-none', step !== 3); f3.classList.toggle('d-flex', step === 3); }

    // Update Step Indicators
    for (let i = 1; i <= 3; i++) {
        const ind = document.getElementById('stepInd' + i);
        const circle = document.getElementById('stepCircle' + i);
        if (ind && circle) {
            ind.classList.remove('active', 'completed');
            if (i === step) {
                ind.classList.add('active');
                circle.innerHTML = i;
            } else if (i < step) {
                ind.classList.add('completed');
                circle.innerHTML = '✓';
            } else {
                circle.innerHTML = i;
            }
        }
    }

    // Update Progress Fill Line
    const fill = document.getElementById('stepProgressFill');
    if (fill) {
        if (step === 1) fill.style.width = '0%';
        else if (step === 2) fill.style.width = '50%';
        else if (step === 3) fill.style.width = '100%';
    }

    // Scroll modal body smoothly to top
    const modalBody = document.querySelector('#bookingModal .modal-body');
    if (modalBody) modalBody.scrollTop = 0;

    // Refresh icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function submitBookingForm() {
    const form = document.getElementById('eventBookingForm');
    if (form) form.submit();
}

document.addEventListener("DOMContentLoaded", function() {
    // Initial calculation of packages and modal totals
    updateModalTotals();

    const btnCopyUpi = document.getElementById('btnCopyUpiId');
    if (btnCopyUpi && clientUpiId) {
        btnCopyUpi.addEventListener('click', function() {
            navigator.clipboard.writeText(clientUpiId).then(() => {
                const originalText = btnCopyUpi.textContent;
                btnCopyUpi.textContent = 'Copied!';
                btnCopyUpi.classList.replace('btn-outline-secondary', 'btn-success');
                setTimeout(() => {
                    btnCopyUpi.textContent = originalText;
                    btnCopyUpi.classList.replace('btn-success', 'btn-outline-secondary');
                }, 2000);
            });
        });
    }

    const btnCopyMobile = document.getElementById('btnCopyPayeeMobile');
    if (btnCopyMobile && clientMobile) {
        btnCopyMobile.addEventListener('click', function() {
            navigator.clipboard.writeText(clientMobile).then(() => {
                const originalText = btnCopyMobile.textContent;
                btnCopyMobile.textContent = 'Copied!';
                btnCopyMobile.classList.replace('btn-outline-secondary', 'btn-success');
                setTimeout(() => {
                    btnCopyMobile.textContent = originalText;
                    btnCopyMobile.classList.replace('btn-success', 'btn-outline-secondary');
                }, 2000);
            });
        });
    }

    // Reset to Step 1 whenever modal opens
    const bookingModalEl = document.getElementById('bookingModal');
    if (bookingModalEl) {
        bookingModalEl.addEventListener('show.bs.modal', function() {
            goToStep(1);
            updateModalTotals();
        });
    }
});

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
