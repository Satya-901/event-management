<?php
$pageTitle = "Organizer Dashboard";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/BookingService.php';
require_once __DIR__ . '/../includes/services/ReportService.php';

// Fetch strictly tenant-isolated data
$clientId = getCurrentClientId();
$report = ReportService::getClientReport($clientId);
$events = EventService::getEvents($clientId);
$recentBookings = BookingService::getBookings($clientId, null, []);
$recentBookings = array_slice($recentBookings, 0, 8); // Top 8 recent
?>

<!-- Welcome Banner -->
<div class="card bg-white p-4 mb-4 border-0 shadow-sm">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h3 class="fw-bold text-dark mb-1">Namaste, <?= e($currentUser['name']) ?> 👋</h3>
            <p class="text-muted mb-0">Managing celebrations and events for <strong><?= e($currentClient['company_name'] ?? $currentClient['name']) ?></strong>.</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="/client/scanner" class="btn btn-warning px-4 py-2 fw-bold text-dark shadow-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="scan-line" style="width:18px;height:18px;"></i> Launch QR Scanner
            </a>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Total Events</span>
                <span class="p-2 rounded-2 bg-primary bg-opacity-10 text-primary"><i data-lucide="calendar" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $report['total_events'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;">Organizer portfolio</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Total Bookings</span>
                <span class="p-2 rounded-2 bg-warning bg-opacity-10 text-warning"><i data-lucide="ticket" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $report['total_bookings'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;"><?= $report['total_passes'] ?> Total Passes</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Checked-In</span>
                <span class="p-2 rounded-2 bg-success bg-opacity-10 text-success"><i data-lucide="user-check" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $report['checked_in'] ?></h3>
            <span class="text-success text-xs fw-medium" style="font-size:11px;"><?= $report['checkin_progress_pct'] ?>% Turnout Rate</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 bg-white h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small">Remaining Seats</span>
                <span class="p-2 rounded-2 bg-info bg-opacity-10 text-info"><i data-lucide="users" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $report['remaining_capacity'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;">Across all active venues</span>
        </div>
    </div>
</div>

<!-- Active Events and Quick Access -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card bg-white p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Your Active Events</h5>
                <a href="/client/events/create" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i> New Event
                </a>
            </div>
            <?php if (empty($events)): ?>
                <p class="text-muted small">No events created yet.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($events as $ev): ?>
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1">
                                        <a href="/<?= e($currentClient['slug']) ?>/<?= e($ev['slug']) ?>/" target="_blank" class="text-dark text-decoration-none">
                                            <?= e($ev['name']) ?> <i data-lucide="external-link" style="width:13px;height:13px;vertical-align:-1px;"></i>
                                        </a>
                                    </h6>
                                    <div class="text-muted small">
                                        <span><i data-lucide="calendar" style="width:13px;height:13px;"></i> <?= formatDate($ev['start_date']) ?></span> • 
                                        <span><i data-lucide="map-pin" style="width:13px;height:13px;"></i> <?= e($ev['venue_name']) ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?> mb-2">
                                        <?= strtoupper($ev['status'] ?? 'published') ?>
                                    </span>
                                    <div>
                                        <a href="/client/sheets?event_id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-dark text-xs" style="font-size:12px;">
                                            <i data-lucide="table" style="width:13px;height:13px;"></i> Sheet
                                        </a>
                                        <a href="/client/events/builder?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-dark text-xs" style="font-size:12px;">
                                            <i data-lucide="sliders" style="width:13px;height:13px;"></i> Form
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Scanner Promotion Card -->
    <div class="col-lg-5">
        <div class="card bg-dark text-white p-4 h-100 position-relative overflow-hidden" style="background: linear-gradient(145deg, #1c1917, #292524);">
            <div class="mb-3">
                <span class="badge bg-warning text-dark px-3 py-1">Gate Verification Desk</span>
            </div>
            <h4 class="fw-bold mb-2">Live Turnstile & Gate Check-in</h4>
            <p class="text-light opacity-75 small mb-4">
                Use your smartphone or tablet camera to instantly verify attendee digital passes. Supports fast continuous scanning and prevents duplicate entries.
            </p>
            <div class="mt-auto">
                <a href="/client/scanner" class="btn btn-warning w-100 py-2 fw-bold text-dark d-flex align-items-center justify-content-center gap-2">
                    <i data-lucide="camera" style="width:18px;height:18px;"></i> Open Live Camera Scanner
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings Table -->
<div class="card bg-white p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0">Recent Registrations</h5>
            <span class="text-muted small">Real-time attendee pass requests</span>
        </div>
        <a href="/client/bookings" class="btn btn-outline-secondary btn-sm">View All Bookings</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr style="font-size:12px;" class="text-uppercase text-muted">
                    <th>Booking #</th>
                    <th>Customer Name</th>
                    <th>Contact</th>
                    <th>Passes</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Pass Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentBookings)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No bookings recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentBookings as $b): ?>
                        <tr>
                            <td class="font-monospace fw-semibold text-dark">
                                <?= e($b['booking_number']) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($b['customer_name']) ?></div>
                            </td>
                            <td class="small text-muted">
                                <div><?= e($b['email']) ?></div>
                                <div style="font-size:11px;"><?= e($b['phone']) ?></div>
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
                                <a href="/verify/<?= e($b['qr_token']) ?>" target="_blank" class="btn btn-sm btn-outline-dark py-1 px-2" title="Inspect Digital Pass">
                                    <i data-lucide="qr-code" style="width:14px;height:14px;"></i> Pass
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
