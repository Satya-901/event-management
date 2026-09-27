<?php
$pageTitle = "Organizer Bookings & Payment Verification";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/BookingService.php';

$clientId = getCurrentClientId();
$events = EventService::getEvents($clientId);
$eventMap = [];
foreach ($events as $ev) {
    $eventMap[$ev['id']] = $ev;
}

$actionMessage = null;
$actionSuccess = false;
$whatsAppUrlForLastAction = null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $bId = $_POST['booking_id'] ?? '';
        $targetBooking = BookingService::getBooking($bId, $clientId);

        if ($targetBooking) {
            $ev = $eventMap[$targetBooking['event_id']] ?? null;
            $vUrl = appUrl('/verify/' . $targetBooking['qr_token']);

            if ($_POST['action'] === 'verify_payment') {
                $ok = BookingService::verifyPayment($bId, $clientId, $currentUser);
                if ($ok) {
                    $actionSuccess = true;
                    if ($ev) {
                        $whatsAppUrlForLastAction = getCustomerWhatsAppTicketUrl($targetBooking, $ev, $vUrl);
                    }
                    $actionMessage = "Payment verified and pass unlocked for #{$targetBooking['booking_number']} ({$targetBooking['customer_name']}).";
                    if (!empty($targetBooking['email'])) {
                        $actionMessage .= " Confirmation email sent.";
                    }
                }
            } elseif ($_POST['action'] === 'reject_payment') {
                $reason = trim($_POST['rejection_reason'] ?? 'UTR could not be verified');
                $ok = BookingService::rejectPayment($bId, $reason, $clientId, $currentUser);
                if ($ok) {
                    $actionSuccess = true;
                    $actionMessage = "Payment rejected for booking #{$targetBooking['booking_number']}.";
                }
            } elseif ($_POST['action'] === 'update_status') {
                $newStatus = $_POST['new_status'] ?? '';
                BookingService::updateStatus($bId, $newStatus, $clientId, $currentUser);
                $actionSuccess = true;
                $actionMessage = "Booking #{$targetBooking['booking_number']} status changed to " . ucfirst($newStatus) . ".";
            }
        }
    }
}

$selectedEventId = $_GET['event_id'] ?? '';
$selectedStatus = $_GET['status'] ?? '';
$paymentFilter = $_GET['payment_status'] ?? '';
$search = $_GET['search'] ?? '';

$bookings = BookingService::getBookings($clientId, $selectedEventId ?: null, [
    'status' => $selectedStatus,
    'search' => $search
]);

// Filter by payment_status if requested
if (!empty($paymentFilter)) {
    $bookings = array_values(array_filter($bookings, function($b) use ($paymentFilter) {
        return strtolower($b['payment_status'] ?? '') === strtolower($paymentFilter);
    }));
}

$pendingCount = BookingService::getPendingPaymentsCount($clientId);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Bookings & Payment Verification</h4>
        <p class="text-muted small mb-0">Check attendee UPI payments, verify submitted UTR numbers, unlock QR entry passes, and send tickets via WhatsApp.</p>
    </div>
    <a href="/client/sheets" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-xs">
        <i data-lucide="table" style="width:14px;height:14px;"></i> Open Spreadsheet View
    </a>
</div>

<?php if ($actionMessage): ?>
    <div class="alert <?= $actionSuccess ? 'alert-success' : 'alert-danger' ?> py-3 px-4 rounded-3 mb-4 shadow-xs d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="<?= $actionSuccess ? 'check-circle' : 'alert-circle' ?>" style="width:20px;height:20px;"></i>
            <span><?= e($actionMessage) ?></span>
        </div>
        <?php if ($whatsAppUrlForLastAction): ?>
            <a href="<?= e($whatsAppUrlForLastAction) ?>" target="_blank" class="btn btn-success btn-sm fw-semibold d-inline-flex align-items-center gap-1.5 shadow-xs text-nowrap">
                <i data-lucide="message-circle" style="width:15px;height:15px;"></i> Send Ticket via WhatsApp Now &rarr;
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($pendingCount > 0 && empty($paymentFilter)): ?>
    <div class="alert alert-warning py-3 px-4 rounded-3 mb-4 shadow-xs d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 border border-warning border-opacity-50">
        <div class="d-flex align-items-center gap-2.5">
            <span class="p-2 rounded-circle bg-warning text-dark"><i data-lucide="bell-ring" style="width:18px;height:18px;"></i></span>
            <div>
                <strong class="text-dark">Action Required: <?= $pendingCount ?> Ticket Payment(s) Awaiting Your Verification</strong>
                <div class="text-muted small">Attendees have paid via UPI and entered their UTR number. Check your UPI app and verify them to release tickets.</div>
            </div>
        </div>
        <a href="/client/bookings?payment_status=pending_verification" class="btn btn-warning btn-sm fw-bold text-dark px-3 shadow-xs text-nowrap">
            Verify <?= $pendingCount ?> Payments &rarr;
        </a>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card p-3 mb-4 shadow-xs">
    <form method="GET" action="/client/bookings" class="row g-2 align-items-center">
        <div class="col-12 col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted"><i data-lucide="search" style="width:14px;height:14px;"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search customer, phone, UTR..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="event_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Events</option>
                <?php foreach ($events as $ev): ?>
                    <option value="<?= e($ev['id']) ?>" <?= $selectedEventId === $ev['id'] ? 'selected' : '' ?>>
                        <?= e($ev['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Payments</option>
                <option value="pending_verification" <?= $paymentFilter === 'pending_verification' ? 'selected' : '' ?>>Pending Verification (<?= $pendingCount ?>)</option>
                <option value="verified" <?= $paymentFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
                <option value="rejected" <?= $paymentFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="free" <?= $paymentFilter === 'free' ? 'selected' : '' ?>>Free</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Pass Statuses</option>
                <option value="confirmed" <?= $selectedStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="checked_in" <?= $selectedStatus === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                <option value="cancelled" <?= $selectedStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                <option value="rejected" <?= $selectedStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-medium shadow-xs" style="background-color:#ea580c !important; border-color:#ea580c !important;">
                Filter
            </button>
        </div>
    </form>
</div>

<!-- Bookings List Table -->
<div class="card overflow-hidden shadow-xs">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Booking #</th>
                    <th>Customer Contact</th>
                    <th>Event & Tier</th>
                    <th>Amount & Passes</th>
                    <th>Payment & UTR</th>
                    <th>Status</th>
                    <th class="text-end">Verification & Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No bookings found matching current filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <?php 
                            $ev = $eventMap[$b['event_id']] ?? null;
                            $pStatus = strtolower($b['payment_status'] ?? '');
                            $bStatus = strtolower($b['status'] ?? '');
                            $vUrl = appUrl('/verify/' . $b['qr_token']);
                            $waUrl = ($ev) ? getCustomerWhatsAppTicketUrl($b, $ev, $vUrl) : '#';
                        ?>
                        <tr class="<?= ($pStatus === 'pending_verification' && $bStatus !== 'cancelled') ? 'table-warning bg-opacity-25' : '' ?>">
                            <td>
                                <span class="font-monospace fw-bold text-dark"><?= e($b['booking_number']) ?></span>
                                <div class="text-muted text-xs" style="font-size:11px;"><?= formatDate($b['booking_date']) ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($b['customer_name']) ?></div>
                                <div class="font-monospace text-secondary small"><?= e($b['phone']) ?></div>
                                <?php if (!empty($b['email'])): ?>
                                    <div class="text-muted text-xs"><?= e($b['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small text-truncate" style="max-width:180px;"><?= e($ev['name'] ?? 'Event') ?></div>
                                <?php if (!empty($b['package_name'])): ?>
                                    <span class="badge bg-light text-secondary border text-xs" style="font-size:10.5px;"><?= e($b['package_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">₹ <?= number_format((float)($b['total_amount'] ?? 0)) ?></div>
                                <span class="badge bg-light text-dark border px-2 py-0.5"><?= (int)($b['pass_count'] ?? 1) ?> Pass(es)</span>
                            </td>
                            <td>
                                <div class="mb-1"><?= paymentStatusBadge($b['payment_status'] ?? 'pending_verification') ?></div>
                                <?php if (!empty($b['utr_number'])): ?>
                                    <div class="d-flex align-items-center gap-1">
                                        <code class="fw-bold text-dark bg-white px-1.5 py-0.5 rounded border small font-monospace"><?= e($b['utr_number']) ?></code>
                                        <button type="button" class="btn btn-link p-0 text-muted" onclick="navigator.clipboard.writeText('<?= e($b['utr_number']) ?>'); this.innerHTML='✓'; setTimeout(()=>this.innerHTML='<i data-lucide=\'copy\' style=\'width:12px;height:12px;\'></i>', 1200);" title="Copy UTR">
                                            <i data-lucide="copy" style="width:12px;height:12px;"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted text-xs">No UTR (<?= e($b['payment_method'] ?? 'N/A') ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= statusBadge($b['status']) ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap gap-1 justify-content-end align-items-center">
                                    <!-- Verify / Approve Payment Action -->
                                    <?php if ($pStatus === 'pending_verification' && $bStatus !== 'cancelled'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Verify UTR <?= e($b['utr_number'] ?: 'payment') ?> and approve pass for <?= e($b['customer_name']) ?>?')">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="action" value="verify_payment">
                                            <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-success py-1 px-2.5 text-xs fw-semibold shadow-xs d-inline-flex align-items-center gap-1">
                                                <i data-lucide="check-check" style="width:13px;height:13px;"></i> Verify & Issue Pass
                                            </button>
                                        </form>

                                        <!-- Reject Payment Button -->
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 text-xs shadow-xs" onclick="promptClientReject('<?= e($b['id']) ?>', '<?= e($b['booking_number']) ?>')">
                                            Reject
                                        </button>
                                    <?php endif; ?>

                                    <!-- WhatsApp Send Ticket Button -->
                                    <?php if ($bStatus === 'confirmed' || $bStatus === 'checked_in'): ?>
                                        <a href="<?= e($waUrl) ?>" target="_blank" class="btn btn-sm btn-outline-success py-1 px-2 text-xs shadow-xs d-inline-flex align-items-center gap-1" title="Send Ticket via WhatsApp to <?= e($b['phone']) ?>">
                                            <i data-lucide="message-circle" style="width:13px;height:13px;"></i> WhatsApp
                                        </a>
                                    <?php endif; ?>

                                    <!-- Digital Pass View -->
                                    <a href="/verify/<?= e($b['qr_token']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2 text-xs shadow-xs" title="View Digital Pass">
                                        <i data-lucide="qr-code" style="width:13px;height:13px;vertical-align:-1px;"></i> Pass
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reject Payment Hidden Form -->
<form method="POST" id="clientRejectPaymentForm" class="d-none">
    <?= csrfInput() ?>
    <input type="hidden" name="action" value="reject_payment">
    <input type="hidden" name="booking_id" id="clientRejectBookingId">
    <input type="hidden" name="rejection_reason" id="clientRejectReasonInput">
</form>

<script>
function promptClientReject(bookingId, bookingNo) {
    const reason = prompt("Enter reason for rejecting payment for booking #" + bookingNo + ":", "UTR not found in bank ledger");
    if (reason !== null) {
        document.getElementById('clientRejectBookingId').value = bookingId;
        document.getElementById('clientRejectReasonInput').value = reason;
        document.getElementById('clientRejectPaymentForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
