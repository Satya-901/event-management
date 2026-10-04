<?php
/**
 * Utsavam - Public Pass Verification & Digital Pass View
 * URL: /verify/{token}
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/storage/DataStoreFactory.php';
require_once __DIR__ . '/../includes/services/QrService.php';

$token = $_GET['token'] ?? '';
$store = getDataStore();
$booking = $token ? $store->getBookingByQrToken($token) : null;
$event = $booking ? $store->getEventById($booking['event_id']) : null;
$client = $booking ? $store->getClientById($booking['client_id']) : null;

$verifyUrl = appUrl('/verify/' . $token);

$isVerified = false;
$isRejected = false;
$isPending = false;

if ($booking) {
    $bStatus = strtolower($booking['status'] ?? '');
    $pStatus = strtolower($booking['payment_status'] ?? '');

    if (in_array($bStatus, ['confirmed', 'checked_in']) && in_array($pStatus, ['verified', 'free', ''])) {
        $isVerified = true;
    } elseif ($bStatus === 'rejected' || $pStatus === 'rejected') {
        $isRejected = true;
    } else {
        $isPending = true;
    }
}

// Organizer support phone for WhatsApp
$organizerPhone = $event['contact_phone'] ?? $client['mobile'] ?? '';
$organizerWhatsAppUrl = '';
if ($booking && $event && $organizerPhone) {
    $organizerWhatsAppUrl = getOrganizerWhatsAppProofUrl($organizerPhone, $booking, $event, $verifyUrl);
}

// Customer WhatsApp link for confirmed ticket
$customerWhatsAppUrl = '';
if ($booking && $event && $isVerified) {
    $customerWhatsAppUrl = getCustomerWhatsAppTicketUrl($booking, $event, $verifyUrl);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $booking ? 'Official Pass #' . e($booking['booking_number']) . ' | ' . e(APP_NAME) : 'Verify Pass | ' . e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --brand-primary: #ea580c;
            --brand-dark: #7c2d12;
            --brand-light: #fff7ed;
        }
        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            min-height: 100vh;
            padding: 30px 15px;
        }
        .pass-container {
            max-width: 520px;
            margin: 0 auto;
        }
        .ticket-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0,0,0,0.04);
            overflow: hidden;
            position: relative;
        }
        .ticket-header {
            background: linear-gradient(135deg, #7c2d12, #c2410c);
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .ticket-body {
            padding: 24px;
        }
        .ticket-divider {
            position: relative;
            border-top: 2px dashed #cbd5e1;
            margin: 20px -24px;
        }
        .ticket-divider::before, .ticket-divider::after {
            content: '';
            position: absolute;
            top: -12px;
            width: 24px;
            height: 24px;
            background-color: #f8fafc;
            border-radius: 50%;
        }
        .ticket-divider::before { left: -12px; }
        .ticket-divider::after { right: -12px; }
        .qr-box {
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            border: 2px solid #fdba74;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(194, 65, 12, 0.08);
        }
        .pulse-dot {
            width: 10px;
            height: 10px;
            background-color: #f59e0b;
            border-radius: 50%;
            display: inline-block;
            animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .pass-container { max-width: 100%; }
            .ticket-card { box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>

<div class="pass-container">
    <div class="text-center mb-3 no-print">
        <a href="/" class="text-decoration-none d-inline-flex align-items-center gap-2 text-dark fw-bold">
            <span class="badge bg-warning text-dark px-2 py-1"><i data-lucide="sparkles" style="width:14px;height:14px;"></i></span>
            <span class="fs-5 tracking-tight"><?= e(APP_NAME) ?></span>
        </a>
    </div>

    <?php if (!$booking || !$event): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="text-danger mb-3">
                <i data-lucide="alert-octagon" style="width:48px;height:48px;"></i>
            </div>
            <h4 class="fw-bold text-dark">Invalid or Expired Pass</h4>
            <p class="text-muted">The verification token provided does not correspond to any active booking in the system.</p>
            <div class="mt-3">
                <a href="/" class="btn btn-outline-secondary">Go to Homepage</a>
            </div>
        </div>

    <?php elseif ($isPending): ?>
        <!-- ============================================== -->
        <!-- STATE 1: PAYMENT VERIFICATION PENDING -->
        <!-- ============================================== -->
        <div class="ticket-card shadow-sm border">
            <!-- Header -->
            <div class="p-4 text-center" style="background: linear-gradient(135deg, #451a03, #9a3412); color:#fff;">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-warning text-dark fw-bold small mb-2 shadow-xs">
                    <span class="pulse-dot"></span>
                    <span>Payment Under Verification</span>
                </div>
                <h4 class="fw-bold mb-1" style="letter-spacing:-0.5px;"><?= e($event['name']) ?></h4>
                <p class="mb-0 opacity-90 small font-monospace">Booking #<?= e($booking['booking_number']) ?></p>
            </div>

            <div class="p-4">
                <div class="text-center mb-4">
                    <div class="mx-auto mb-3 d-inline-flex p-3 rounded-circle bg-warning bg-opacity-15 text-warning" style="color:#d97706 !important;">
                        <i data-lucide="shield-alert" style="width:44px;height:44px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Pass Locked: Verification in Progress</h5>
                    <p class="text-muted small mb-0 px-2" style="line-height:1.5;">
                        Your payment reference has been submitted. Once the organizer verifies your UTR transaction, your <strong>Official QR Entry Ticket</strong> will automatically unlock here and be sent to your email.
                    </p>
                </div>

                <!-- Verification Details Card -->
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="row g-2.5 small">
                        <div class="col-6">
                            <span class="text-muted d-block text-xs text-uppercase" style="font-size:11px;">Attendee Name</span>
                            <span class="fw-bold text-dark"><?= e($booking['customer_name']) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block text-xs text-uppercase" style="font-size:11px;">Passes</span>
                            <span class="fw-bold text-dark"><?= (int)($booking['pass_count'] ?? 1) ?> Person(s)</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block text-xs text-uppercase" style="font-size:11px;">Amount Payable</span>
                            <span class="fw-bold text-danger fs-6">₹ <?= number_format((float)($booking['total_amount'] ?? 0)) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block text-xs text-uppercase" style="font-size:11px;">Payment Mode</span>
                            <span class="badge bg-light text-dark border font-monospace">UPI</span>
                        </div>
                        <div class="col-12 pt-2 border-top">
                            <span class="text-muted d-block text-xs text-uppercase" style="font-size:11px;">Submitted UTR / Reference No.</span>
                            <div class="d-flex align-items-center justify-content-between mt-0.5">
                                <code class="fw-bold text-dark fs-6 bg-white px-2 py-1 rounded border"><?= e($booking['utr_number'] ?: 'Under Review') ?></code>
                                <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-50">Pending Approval</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Auto-Check Poller Status -->
                <div class="p-2.5 rounded-3 border border-warning border-opacity-30 bg-warning bg-opacity-10 text-center mb-3">
                    <div class="d-flex align-items-center justify-content-center gap-2 small text-dark fw-semibold">
                        <span class="spinner-border spinner-border-sm text-warning" role="status"></span>
                        <span id="pollerStatusText">Checking with Organizer Server... (Auto-refresh active)</span>
                    </div>
                </div>

                <!-- Action: WhatsApp Organizer to Fast-Track -->
                <?php if ($organizerPhone): ?>
                    <div class="mb-2">
                        <a href="<?= e($organizerWhatsAppUrl) ?>" target="_blank" class="btn btn-success w-100 fw-semibold d-flex align-items-center justify-content-center gap-2 py-2.5 shadow-sm">
                            <i data-lucide="message-circle" style="width:18px;height:18px;"></i>
                            <span>Send Payment Details to Organizer on WhatsApp</span>
                        </a>
                        <div class="text-center text-muted text-xs mt-1" style="font-size:11px;">
                            Click to send your UTR directly to Organizer WhatsApp for instant verification.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="window.location.reload();">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px;vertical-align:-2px;"></i> Refresh Status
                    </button>
                    <a href="/<?= e($client['slug']) ?>/<?= e($event['slug']) ?>/" class="btn btn-outline-secondary btn-sm w-100">
                        Event Page
                    </a>
                </div>
            </div>
        </div>

    <?php elseif ($isRejected): ?>
        <!-- ============================================== -->
        <!-- STATE 2: PAYMENT REJECTED -->
        <!-- ============================================== -->
        <div class="ticket-card shadow-sm border">
            <div class="p-4 text-center bg-danger text-white">
                <div class="d-inline-flex p-3 rounded-circle bg-white text-danger mb-2 shadow-xs">
                    <i data-lucide="slash" style="width:36px;height:36px;"></i>
                </div>
                <h4 class="fw-bold mb-1">Payment Verification Unsuccessful</h4>
                <p class="mb-0 opacity-90 small">Booking #<?= e($booking['booking_number']) ?></p>
            </div>

            <div class="p-4 text-center">
                <p class="text-muted small mb-3">
                    The payment reference (UTR: <strong><?= e($booking['utr_number'] ?: 'N/A') ?></strong>) submitted for this booking could not be verified by the organizer bank ledger.
                </p>

                <?php if ($organizerPhone): ?>
                    <a href="<?= e($organizerWhatsAppUrl) ?>" target="_blank" class="btn btn-success fw-semibold d-inline-flex align-items-center gap-2 px-4 py-2 mb-3">
                        <i data-lucide="message-circle" style="width:18px;height:18px;"></i> Contact Organizer on WhatsApp
                    </a>
                <?php endif; ?>

                <div>
                    <a href="/<?= e($client['slug']) ?>/<?= e($event['slug']) ?>/" class="btn btn-outline-dark btn-sm">
                        Re-book or Try Again &rarr;
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ============================================== -->
        <!-- STATE 3: CONFIRMED & VERIFIED OFFICIAL PASS -->
        <!-- ============================================== -->
        <div class="ticket-card" id="printablePass">
            <!-- Ticket Header -->
            <div class="ticket-header">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-white text-dark px-3 py-1 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:0.5px;">
                        <?= e($client['company_name'] ?? $client['name'] ?? 'Organizer') ?>
                    </span>
                    <span class="badge bg-black bg-opacity-25 text-white" style="font-size:11px;">
                        <?= e($booking['booking_number']) ?>
                    </span>
                </div>
                <h3 class="fw-bold mb-1" style="letter-spacing:-0.5px;"><?= e($event['name']) ?></h3>
                <p class="mb-0 opacity-90 small"><?= e($event['category']) ?></p>
            </div>

            <!-- Verification Status Banner -->
            <div class="px-4 py-2 text-center text-uppercase fw-bold" style="font-size:12px; letter-spacing:1px;
                <?php if ($booking['status'] === 'checked_in'): ?>
                    background: #dbeafe; color: #1e40af;
                <?php else: ?>
                    background: #dcfce7; color: #15803d;
                <?php endif; ?>
            ">
                <?php if ($booking['status'] === 'checked_in'): ?>
                    <i data-lucide="check-check" style="width:16px;height:16px;vertical-align:-3px;"></i> Attendee Checked In (<?= formatDateTime($booking['checked_in_at']) ?>)
                <?php else: ?>
                    <i data-lucide="shield-check" style="width:16px;height:16px;vertical-align:-3px;"></i> Verified Official Pass - Valid for Entry
                <?php endif; ?>
            </div>

            <!-- Ticket Details -->
            <div class="ticket-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="text-muted small text-uppercase" style="font-size:11px;">Attendee Name</label>
                        <div class="fw-bold text-dark fs-6"><?= e($booking['customer_name']) ?></div>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small text-uppercase" style="font-size:11px;">Total Passes</label>
                        <div class="fw-bold text-dark fs-6"><?= (int)($booking['pass_count'] ?? 1) ?> Person(s)</div>
                        <?php if (!empty($booking['package_name'])): ?>
                            <div class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 text-xs px-2 py-0.5 mt-0.5" style="font-size:10.5px;">
                                <?= e($booking['package_name']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small text-uppercase" style="font-size:11px;">Event Date & Time</label>
                        <div class="fw-semibold text-dark small"><?= formatDate($event['start_date']) ?><br><?= formatTime($event['start_time']) ?> onwards</div>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small text-uppercase" style="font-size:11px;">Venue</label>
                        <div class="fw-semibold text-dark small"><?= e($event['venue_name']) ?><br><span class="text-muted"><?= e($event['city']) ?></span></div>
                    </div>
                </div>

                <div class="ticket-divider"></div>

                <!-- QR Code Box (Unlocked) -->
                <div class="text-center my-2">
                    <div class="qr-box">
                        <img id="qrImage" src="https://api.qrserver.com/v1/create-qr-code/?size=190x190&data=<?= urlencode($verifyUrl) ?>" alt="Pass QR" style="width:180px;height:180px;display:block;">
                    </div>
                    <div class="mt-2 text-muted small">
                        <span>Scan with Utsavam Organizer Scanner at Gate</span>
                    </div>
                    <div class="badge bg-light text-secondary font-monospace mt-1 px-2 py-1" style="font-size:10px;">
                        Token: <?= substr($booking['qr_token'], 0, 16) ?>...
                    </div>
                </div>

                <!-- WhatsApp Share Ticket Option -->
                <?php if ($customerWhatsAppUrl): ?>
                    <div class="mt-3 text-center no-print">
                        <a href="<?= e($customerWhatsAppUrl) ?>" target="_blank" class="btn btn-outline-success btn-sm w-100 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 py-2 shadow-xs">
                            <i data-lucide="message-circle" style="width:16px;height:16px;"></i> Send Ticket to My WhatsApp
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Venue Map Link & Organizer Note -->
                <div class="bg-light p-3 rounded-3 mt-3 small text-muted">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-semibold text-dark"><i data-lucide="map-pin" style="width:14px;height:14px;"></i> <?= e($event['address']) ?></span>
                        <?php if (!empty($event['google_maps_url'])): ?>
                            <a href="<?= e($event['google_maps_url']) ?>" target="_blank" class="text-primary text-decoration-none">Map</a>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1" style="font-size:12px;">
                        Need assistance? Contact <strong><?= e($event['contact_phone'] ?? $client['mobile']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Footer Actions inside Card -->
            <div class="bg-light p-3 border-top d-flex justify-content-between align-items-center no-print">
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1">
                    <i data-lucide="printer" style="width:14px;height:14px;"></i> Print Pass
                </button>
                <button id="downloadPassBtn" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" style="background:#c2410c; border-color:#c2410c;">
                    <i data-lucide="download" style="width:14px;height:14px;"></i> Save as Image
                </button>
            </div>
        </div>

        <div class="text-center mt-3 no-print">
            <a href="/<?= e($client['slug']) ?>/<?= e($event['slug']) ?>/" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Return to Event Page
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
lucide.createIcons();

// Download pass as image
const downloadBtn = document.getElementById('downloadPassBtn');
if (downloadBtn) {
    downloadBtn.addEventListener('click', function() {
        const target = document.getElementById('printablePass');
        downloadBtn.disabled = true;
        downloadBtn.innerHTML = 'Generating...';
        html2canvas(target, { scale: 2, useCORS: true }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Pass-<?= $booking ? e($booking['booking_number']) : 'utsavam' ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = '<i data-lucide="download" style="width:14px;height:14px;"></i> Save as Image';
            lucide.createIcons();
        }).catch(err => {
            alert('Failed to generate image. Please use Print button.');
            downloadBtn.disabled = false;
        });
    });
}

// Auto-polling for verification if status is pending
<?php if ($booking && $isPending): ?>
(function() {
    const token = <?= json_encode($token) ?>;
    let pollCount = 0;
    const maxPolls = 120; // 10 minutes (every 5 seconds)

    const interval = setInterval(async () => {
        pollCount++;
        if (pollCount > maxPolls) {
            clearInterval(interval);
            const statusText = document.getElementById('pollerStatusText');
            if (statusText) statusText.innerText = 'Verification pending. Click Refresh Status button above anytime.';
            return;
        }

        try {
            const res = await fetch('/api/booking-status?token=' + encodeURIComponent(token));
            if (!res.ok) return;
            const data = await res.json();
            if (data && data.success) {
                if (data.is_confirmed || data.status === 'confirmed' || data.payment_status === 'verified') {
                    clearInterval(interval);
                    const statusText = document.getElementById('pollerStatusText');
                    if (statusText) statusText.innerHTML = '<span class="text-success fw-bold">✓ Payment Verified! Unlocking pass...</span>';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else if (data.status === 'rejected' || data.payment_status === 'rejected') {
                    clearInterval(interval);
                    window.location.reload();
                }
            }
        } catch (e) {
            console.log('Poll check failed', e);
        }
    }, 5000);
})();
<?php endif; ?>
</script>
</body>
</html>
