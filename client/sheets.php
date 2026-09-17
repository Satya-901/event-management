<?php
$pageTitle = "Interactive Booking Sheet";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/BookingService.php';

$clientId = getCurrentClientId();
$events = EventService::getEvents($clientId);

$selectedEventId = $_GET['event_id'] ?? '';
$selectedStatus = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$filters = [
    'status' => $selectedStatus,
    'search' => $searchQuery
];

$bookings = BookingService::getBookings($clientId, $selectedEventId ?: null, $filters);

// CSV Export Mode
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=bookings-' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Booking Number', 'Event ID', 'Customer Name', 'Email', 'Phone', 'Pass Count', 'Booking Date', 'Status', 'Checked In At', 'Checked In By']);
    foreach ($bookings as $b) {
        fputcsv($output, [
            $b['booking_number'],
            $b['event_id'],
            $b['customer_name'],
            $b['email'],
            $b['phone'],
            $b['pass_count'],
            $b['booking_date'],
            $b['status'],
            $b['checked_in_at'] ?? '',
            $b['checked_in_by'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Live Booking Sheet</h4>
        <p class="text-muted small mb-0">High-density Excel-like ledger with live sorting, filtering, and single-click check-ins.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1">
            <i data-lucide="download" style="width:14px;height:14px;"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1">
            <i data-lucide="printer" style="width:14px;height:14px;"></i> Print Sheet
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="card p-3 bg-white mb-3">
    <form method="GET" action="/client/sheets" class="row g-2 align-items-center">
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i data-lucide="search" style="width:14px;height:14px;"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search name, phone, booking #..." value="<?= e($searchQuery) ?>">
            </div>
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
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="confirmed" <?= $selectedStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="checked_in" <?= $selectedStatus === 'checked_in' ? 'selected' : '' ?>>Checked-In</option>
                <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="cancelled" <?= $selectedStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-dark btn-sm w-100">Filter</button>
        </div>
        <div class="col-md-2 text-end">
            <span class="badge bg-light text-dark border px-2 py-1">
                <?= count($bookings) ?> Records Found
            </span>
        </div>
    </form>
</div>

<!-- Excel Sheet Table -->
<div class="card bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0" id="bookingGridTable" style="font-size:13px;">
            <thead class="table-light text-uppercase text-muted" style="font-size:11px; letter-spacing:0.5px;">
                <tr>
                    <th style="width: 130px;">Booking #</th>
                    <th>Customer Name</th>
                    <th>Email Address</th>
                    <th>Phone Number</th>
                    <th style="width: 80px;" class="text-center">Passes</th>
                    <th style="width: 110px;">Status</th>
                    <th>Check-In Details</th>
                    <th style="width: 140px;" class="text-center">Quick Gate Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i data-lucide="inbox" style="width:36px;height:36px;" class="d-block mx-auto mb-2 opacity-50"></i>
                            No bookings matching criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr id="row-<?= e($b['id']) ?>">
                            <td class="font-monospace fw-bold text-dark">
                                <?= e($b['booking_number']) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($b['customer_name']) ?></div>
                                <div class="text-xs text-muted"><?= formatDate($b['booking_date']) ?></div>
                            </td>
                            <td class="text-muted"><?= e($b['email']) ?></td>
                            <td class="font-monospace"><?= e($b['phone']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?= (int)($b['pass_count'] ?? 1) ?></span>
                            </td>
                            <td id="status-cell-<?= e($b['id']) ?>">
                                <?= statusBadge($b['status']) ?>
                            </td>
                            <td class="small text-muted" id="checkin-cell-<?= e($b['id']) ?>">
                                <?php if ($b['status'] === 'checked_in'): ?>
                                    <span class="text-success fw-medium"><i data-lucide="check" style="width:12px;height:12px;"></i> Checked In</span>
                                    <div style="font-size:11px;"><?= formatDateTime($b['checked_in_at']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted opacity-50">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <button onclick="quickCheckIn('<?= e($b['id']) ?>', '<?= e(addslashes($b['customer_name'])) ?>')" class="btn btn-sm btn-success py-1 px-2 text-xs d-inline-flex align-items-center gap-1">
                                        <i data-lucide="user-check" style="width:13px;height:13px;"></i> Check In
                                    </button>
                                <?php elseif ($b['status'] === 'checked_in'): ?>
                                    <span class="badge bg-success bg-opacity-15 text-success py-1 px-2">Admitted</span>
                                <?php else: ?>
                                    <a href="/verify/<?= e($b['qr_token']) ?>" target="_blank" class="btn btn-sm btn-light border py-1 px-2 text-xs">
                                        Pass
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function quickCheckIn(bookingId, name) {
    Swal.fire({
        title: 'Check-In ' + name + '?',
        text: 'Admit attendee and mark pass as checked in.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'Yes, Admit'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/api/check-in', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    notyf.success(name + ' checked in successfully!');
                    document.getElementById('status-cell-' + bookingId).innerHTML = '<span class="badge bg-success text-uppercase">CHECKED_IN</span>';
                    document.getElementById('checkin-cell-' + bookingId).innerHTML = '<span class="text-success fw-medium">Just now</span>';
                    const row = document.getElementById('row-' + bookingId);
                    row.querySelector('td:last-child').innerHTML = '<span class="badge bg-success bg-opacity-15 text-success py-1 px-2">Admitted</span>';
                } else {
                    Swal.fire('Error', data.error || 'Failed to check in.', 'error');
                }
            })
            .catch(e => {
                notyf.error('Network error during check-in.');
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
