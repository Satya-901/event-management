<?php
$pageTitle = "Platform Overview";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/ReportService.php';
require_once __DIR__ . '/../includes/services/ClientService.php';
require_once __DIR__ . '/../includes/services/BookingService.php';

$report = ReportService::getAdminReport();
$clients = ClientService::getAllClients();
$recentBookings = BookingService::getBookings(null, null, []);
$recentBookings = array_slice($recentBookings, 0, 6);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-white">Super Admin Command Center</h4>
        <p class="text-muted small mb-0">Multi-tenant management for Utsavam celebrations platform.</p>
    </div>
    <a href="/admin/clients/create" class="btn btn-warning btn-sm fw-bold text-dark d-inline-flex align-items-center gap-1">
        <i data-lucide="plus" style="width:16px;height:16px;"></i> Create Client Space
    </a>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-dark p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Client Tenants</span>
                <span class="p-2 rounded-2 bg-primary bg-opacity-20 text-primary"><i data-lucide="building" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-white"><?= $report['total_clients'] ?></h3>
            <span class="text-success text-xs fw-medium" style="font-size:11px;"><?= $report['active_clients'] ?> Active Organizations</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-dark p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Total Events</span>
                <span class="p-2 rounded-2 bg-warning bg-opacity-20 text-warning"><i data-lucide="calendar" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-white"><?= $report['total_events'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;"><?= $report['active_events'] ?> Published Live</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-dark p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Total Bookings</span>
                <span class="p-2 rounded-2 bg-info bg-opacity-20 text-info"><i data-lucide="ticket" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-white"><?= $report['total_bookings'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;"><?= $report['total_passes'] ?> Total Passes Issued</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-dark p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Checked In</span>
                <span class="p-2 rounded-2 bg-success bg-opacity-20 text-success"><i data-lucide="user-check" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-white"><?= $report['checked_in'] ?></h3>
            <span class="text-success text-xs fw-medium" style="font-size:11px;">Verified Turnstile Passes</span>
        </div>
    </div>
</div>

<!-- Registered Client Tenants -->
<div class="card card-dark p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold text-white mb-0">Active Client Organizations (Tenants)</h5>
            <span class="text-muted small">Each client possesses complete data isolation and a unique public portal.</span>
        </div>
        <a href="/admin/clients" class="btn btn-outline-light btn-sm">View All Tenants</a>
    </div>

    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr style="font-size:11px;" class="text-uppercase text-muted">
                    <th>Client / Organization</th>
                    <th>Tenant Code</th>
                    <th>Public URL</th>
                    <th>Contact Email</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-white"><?= e($c['name']) ?></div>
                            <div class="small text-muted"><?= e($c['company_name']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary font-monospace"><?= e($c['code']) ?></span>
                        </td>
                        <td>
                            <a href="/<?= e($c['slug']) ?>/" target="_blank" class="text-warning text-decoration-none small d-inline-flex align-items-center gap-1">
                                <span>/<?= e($c['slug']) ?>/</span>
                                <i data-lucide="external-link" style="width:12px;height:12px;"></i>
                            </a>
                        </td>
                        <td class="small text-muted"><?= e($c['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= strtoupper($c['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="/client/login" target="_blank" class="btn btn-sm btn-outline-warning py-1 px-2 text-xs" title="Login as Client Organizer">
                                <i data-lucide="log-in" style="width:13px;height:13px;"></i> Organizer Portal
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Global Recent Bookings -->
<div class="card card-dark p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold text-white mb-0">Recent Platform Registrations</h5>
            <span class="text-muted small">Live activity across all client events.</span>
        </div>
        <a href="/admin/bookings" class="btn btn-outline-light btn-sm">All Bookings</a>
    </div>

    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr style="font-size:11px;" class="text-uppercase text-muted">
                    <th>Booking #</th>
                    <th>Attendee</th>
                    <th>Contact</th>
                    <th>Passes</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Verification</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentBookings as $b): ?>
                    <tr>
                        <td class="font-monospace fw-bold text-warning"><?= e($b['booking_number']) ?></td>
                        <td class="fw-semibold text-white"><?= e($b['customer_name']) ?></td>
                        <td class="small text-muted"><?= e($b['email']) ?></td>
                        <td><span class="badge bg-dark border border-secondary"><?= (int)($b['pass_count'] ?? 1) ?></span></td>
                        <td class="small text-muted"><?= formatDate($b['booking_date']) ?></td>
                        <td><?= statusBadge($b['status']) ?></td>
                        <td class="text-end">
                            <a href="/verify/<?= e($b['qr_token']) ?>" target="_blank" class="btn btn-sm btn-outline-light py-1 px-2 text-xs">
                                <i data-lucide="qr-code" style="width:12px;height:12px;"></i> View Pass
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
