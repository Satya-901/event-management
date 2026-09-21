<?php
$pageTitle = "Global Bookings";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/BookingService.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$clients = ClientService::getAllClients();
$clientMap = [];
foreach ($clients as $c) {
    $clientMap[$c['id']] = $c;
}

$selectedClientId = $_GET['client_id'] ?? '';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$bookings = BookingService::getBookings($selectedClientId ?: null, null, [
    'search' => $search,
    'status' => $status
]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0f172a;">Global Bookings Ledger</h4>
        <p class="text-muted small mb-0">Cross-tenant reservations, passes, and turnstile verification logs.</p>
    </div>
</div>

<div class="card card-dark p-3 mb-4">
    <form method="GET" action="/admin/bookings" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer, email, booking #..." value="<?= e($search) ?>">
        </div>
        <div class="col-6 col-md-3">
            <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Client Tenants</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= $selectedClientId === $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="checked_in" <?= $status === 'checked_in' ? 'selected' : '' ?>>Checked-In</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary btn-sm fw-semibold w-100 shadow-xs">Filter</button>
        </div>
    </form>
</div>

<div class="card card-dark overflow-hidden">
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Booking #</th>
                    <th>Customer Name</th>
                    <th>Tenant Organization</th>
                    <th>Passes</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Pass Inspection</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No bookings match filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <?php $cl = $clientMap[$b['client_id']] ?? null; ?>
                        <tr>
                            <td class="font-monospace fw-bold text-primary"><?= e($b['booking_number']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($b['customer_name']) ?></div>
                                <div class="small text-secondary"><?= e($b['email']) ?> • <?= e($b['phone']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= e($cl['name'] ?? 'Organizer') ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= (int)($b['pass_count'] ?? 1) ?></span></td>
                            <td class="small text-muted"><?= formatDate($b['booking_date']) ?></td>
                            <td><?= statusBadge($b['status']) ?></td>
                            <td class="text-end">
                                <a href="/verify/<?= e($b['qr_token']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2.5 text-xs d-inline-flex align-items-center gap-1">
                                    <i data-lucide="qr-code" style="width:13px;height:13px;"></i> Inspect Pass
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
