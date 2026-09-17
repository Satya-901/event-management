<?php
$pageTitle = "Client Bookings";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/BookingService.php';

$clientId = getCurrentClientId();
$events = EventService::getEvents($clientId);

$selectedEventId = $_GET['event_id'] ?? '';
$selectedStatus = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Handle Status Change Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $bId = $_POST['booking_id'] ?? '';
        $newStatus = $_POST['new_status'] ?? '';
        BookingService::updateStatus($bId, $newStatus, $clientId, $currentUser);
        redirect($_SERVER['REQUEST_URI']);
    }
}

$bookings = BookingService::getBookings($clientId, $selectedEventId ?: null, [
    'status' => $selectedStatus,
    'search' => $search
]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Bookings & Pass Holders</h4>
        <p class="text-muted small mb-0">View attendee reservations and manage booking statuses.</p>
    </div>
    <a href="/client/sheets" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
        <i data-lucide="table" style="width:14px;height:14px;"></i> Open Spreadsheet View
    </a>
</div>

<!-- Filters -->
<div class="card p-3 bg-white mb-4 shadow-sm">
    <form method="GET" action="/client/bookings" class="row g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by customer, phone, booking #..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="event_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Events</option>
                <?php foreach ($events as $ev): ?>
                    <option value="<?= e($ev['id']) ?>" <?= $selectedEventId === $ev['id'] ? 'selected' : '' ?>>
                        <?= e($ev['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="confirmed" <?= $selectedStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="checked_in" <?= $selectedStatus === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="cancelled" <?= $selectedStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-dark btn-sm w-100">Filter</button>
        </div>
    </form>
</div>

<!-- Bookings List Table -->
<div class="card bg-white shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase text-muted" style="font-size:11px;">
                <tr>
                    <th>Booking #</th>
                    <th>Customer Name</th>
                    <th>Email & Phone</th>
                    <th>Passes</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td class="font-monospace fw-bold text-dark">
                                <?= e($b['booking_number']) ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($b['customer_name']) ?></div>
                            </td>
                            <td class="small text-muted">
                                <div><?= e($b['email']) ?></div>
                                <div class="font-monospace" style="font-size:11px;"><?= e($b['phone']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)($b['pass_count'] ?? 1) ?></span>
                            </td>
                            <td class="small text-muted">
                                <?= formatDate($b['booking_date']) ?>
                            </td>
                            <td>
                                <?= statusBadge($b['status']) ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="/verify/<?= e($b['qr_token']) ?>" target="_blank" class="btn btn-sm btn-outline-dark py-1 px-2 text-xs" title="View Pass">
                                        <i data-lucide="qr-code" style="width:13px;height:13px;"></i> Pass
                                    </a>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Confirm this booking?')">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                            <input type="hidden" name="new_status" value="confirmed">
                                            <button type="submit" class="btn btn-sm btn-success py-1 px-2 text-xs">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($b['status'] !== 'cancelled'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Cancel this pass?')">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?= e($b['id']) ?>">
                                            <input type="hidden" name="new_status" value="cancelled">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 text-xs">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
