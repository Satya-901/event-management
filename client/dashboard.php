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
<div class="card p-3 p-md-4 mb-4 shadow-xs">
    <div class="row align-items-center g-3">
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning bg-opacity-10 text-uppercase fw-bold" style="color:#ea580c !important; font-size:10.5px; letter-spacing:0.5px;">
                    <?= e($currentClient['company_name'] ?? $currentClient['name']) ?>
                </span>
                <span class="badge bg-light text-secondary border font-monospace" style="font-size:10px;">ID: <?= e($currentClient['code']) ?></span>
            </div>
            <h4 class="fw-bold mb-1" style="color:#0f172a;">Namaste, <?= e($currentUser['name']) ?> 👋</h4>
            <p class="text-muted small mb-0">Multi-event command dashboard for your celebrations, bookings, and digital check-ins.</p>
        </div>
        <div class="col-lg-5 text-lg-end">
            <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                <a href="/client/scanner" class="btn btn-warning px-3 py-2 fw-semibold text-white shadow-xs d-inline-flex align-items-center gap-1.5">
                    <i data-lucide="scan-line" style="width:16px;height:16px;"></i> Scan Pass QR
                </a>
                <a href="/client/events/create" class="btn btn-outline-secondary px-3 py-2 fw-medium btn-sm d-inline-flex align-items-center gap-1.5">
                    <i data-lucide="plus" style="width:15px;height:15px;"></i> New Event
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Total Events</span>
                <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary"><i data-lucide="calendar" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $report['total_events'] ?></h3>
            <span class="text-muted text-xs mt-1" style="font-size:11px;">Active celebrations</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Total Bookings</span>
                <span class="p-2 rounded-3 bg-warning bg-opacity-10" style="color:#ea580c !important;"><i data-lucide="ticket" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $report['total_bookings'] ?></h3>
            <span class="text-muted text-xs mt-1" style="font-size:11px;"><?= $report['total_passes'] ?> Total Passes</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Checked-In</span>
                <span class="p-2 rounded-3 bg-success bg-opacity-10 text-success"><i data-lucide="user-check" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $report['checked_in'] ?></h3>
            <span class="text-success text-xs fw-semibold mt-1" style="font-size:11px;"><?= $report['checkin_progress_pct'] ?>% Turnout Rate</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Remaining Seats</span>
                <span class="p-2 rounded-3 bg-info bg-opacity-10 text-info"><i data-lucide="users" style="width:16px;height:16px;"></i></span>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= $report['remaining_capacity'] ?></h3>
            <span class="text-muted text-xs mt-1" style="font-size:11px;">Open capacity</span>
        </div>
    </div>
</div>

<!-- Active Events and Quick Access -->
<div class="row g-3 g-md-4 mb-4">
    <div class="col-lg-7">
        <div class="card p-3 p-md-4 h-100 shadow-xs">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0" style="color:#0f172a;">Active Events Portfolio</h5>
                    <span class="text-muted small">Live public registration pages</span>
                </div>
                <a href="/client/events/create" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-xs">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i> New Event
                </a>
            </div>
            <?php if (empty($events)): ?>
                <div class="text-center py-4 text-muted">
                    <i data-lucide="calendar-x" style="width:36px;height:36px;" class="mb-2 opacity-50"></i>
                    <p class="small mb-0">No events created yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($events as $ev): ?>
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                <div>
                                    <h6 class="fw-bold mb-1">
                                        <a href="/<?= e($currentClient['slug']) ?>/<?= e($ev['slug']) ?>/" target="_blank" class="text-dark text-decoration-none hover-primary">
                                            <?= e($ev['name']) ?> <i data-lucide="external-link" style="width:13px;height:13px;vertical-align:-1px;color:#94a3b8;"></i>
                                        </a>
                                    </h6>
                                    <div class="text-secondary small d-flex flex-wrap align-items-center gap-2">
                                        <span><i data-lucide="calendar" style="width:13px;height:13px;vertical-align:-1px;"></i> <?= formatDate($ev['start_date']) ?></span>
                                        <span>•</span>
                                        <span><i data-lucide="map-pin" style="width:13px;height:13px;vertical-align:-1px;"></i> <?= e($ev['venue_name']) ?></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1.5 align-self-end align-self-sm-center">
                                    <span class="badge bg-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?> border border-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?> border-opacity-25 me-1">
                                        <?= strtoupper($ev['status'] ?? 'published') ?>
                                    </span>
                                    <a href="/client/sheets?event_id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-dark" style="font-size:12px;" title="Booking Sheet">
                                        <i data-lucide="table" style="width:13px;height:13px;"></i>
                                    </a>
                                    <a href="/client/events/builder?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-dark" style="font-size:12px;" title="Form Builder">
                                        <i data-lucide="sliders" style="width:13px;height:13px;"></i>
                                    </a>
                                    <a href="/client/events/edit?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-dark" style="font-size:12px;" title="Edit Event">
                                        <i data-lucide="edit" style="width:13px;height:13px;"></i>
                                    </a>
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
        <div class="card p-3 p-md-4 h-100 shadow-xs position-relative overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color:#ffffff !important;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge bg-warning text-white px-2.5 py-1" style="background-color:#ea580c !important;">Gate Verification Desk</span>
                <span class="badge bg-white bg-opacity-10 text-light font-monospace" style="font-size:10px;">Turnstile Engine</span>
            </div>
            <h4 class="fw-bold mb-2 text-white">Live Camera QR Scanner</h4>
            <p class="text-light text-opacity-75 small mb-4">
                Use your smartphone or tablet camera directly at the gate. Features fast continuous optical scanning, audio check-in chimes, and automatic fraud prevention.
            </p>
            <div class="mt-auto">
                <a href="/client/scanner" class="btn btn-warning w-100 py-2.5 fw-semibold text-white d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background-color:#ea580c !important; border-color:#ea580c !important;">
                    <i data-lucide="camera" style="width:18px;height:18px;"></i> Open Gate Camera Scanner &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings Table -->
<div class="card p-3 p-md-4 shadow-xs">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0" style="color:#0f172a;">Recent Registrations</h5>
            <span class="text-muted small">Real-time attendee pass requests</span>
        </div>
        <a href="/client/bookings" class="btn btn-outline-secondary btn-sm shadow-xs">View All Bookings</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
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
                            <td class="small text-secondary">
                                <div><?= e($b['email']) ?></div>
                                <div class="text-muted" style="font-size:11.5px;"><?= e($b['phone']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1"><?= (int)($b['pass_count'] ?? 1) ?></span>
                            </td>
                            <td class="small text-muted">
                                <?= formatDate($b['booking_date']) ?>
                            </td>
                            <td>
                                <?= statusBadge($b['status']) ?>
                            </td>
                            <td class="text-end">
                                <a href="/verify/<?= e($b['qr_token']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2.5 shadow-xs" title="Inspect Digital Pass">
                                    <i data-lucide="qr-code" style="width:14px;height:14px;vertical-align:-1px;"></i> Pass
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
