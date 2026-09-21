<?php
$pageTitle = "Client Analytics & Reports";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/ReportService.php';

$clientId = getCurrentClientId();
$events = EventService::getEvents($clientId);

$selectedEventId = $_GET['event_id'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

$report = ReportService::getClientReport($clientId, $selectedEventId ?: null, $fromDate ?: null, $toDate ?: null);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Analytics & Gate Turnout Reports</h4>
        <p class="text-muted small mb-0">Track attendee turnout, capacity metrics, and registration velocity.</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-xs">
        <i data-lucide="printer" style="width:14px;height:14px;"></i> Print Report
    </button>
</div>

<!-- Filters -->
<div class="card p-3 mb-4 shadow-xs">
    <form method="GET" action="/client/reports" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <select name="event_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Events Portfolio</option>
                <?php foreach ($events as $ev): ?>
                    <option value="<?= e($ev['id']) ?>" <?= $selectedEventId === $ev['id'] ? 'selected' : '' ?>>
                        <?= e($ev['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= e($fromDate) ?>" placeholder="From Date">
        </div>
        <div class="col-6 col-md-3">
            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= e($toDate) ?>" placeholder="To Date">
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-medium shadow-xs" style="background-color:#ea580c !important; border-color:#ea580c !important;">Apply Filter</button>
        </div>
    </form>
</div>

<!-- Key Stat Cards -->
<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 shadow-xs h-100">
            <div class="text-muted small mb-1 fw-medium">Total Pass Bookings</div>
            <h3 class="fw-bold text-dark mb-0"><?= $report['total_bookings'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;"><?= $report['total_passes'] ?> Total Passes</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 shadow-xs h-100">
            <div class="text-muted small mb-1 fw-medium">Checked-In Attendees</div>
            <h3 class="fw-bold text-success mb-0"><?= $report['checked_in'] ?></h3>
            <span class="text-success text-xs fw-semibold" style="font-size:11px;"><?= $report['checkin_progress_pct'] ?>% Turnout</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 shadow-xs h-100">
            <div class="text-muted small mb-1 fw-medium">Pending Approvals</div>
            <h3 class="fw-bold text-warning mb-0" style="color:#ea580c !important;"><?= $report['pending'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;">Awaiting verification</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 shadow-xs h-100">
            <div class="text-muted small mb-1 fw-medium">Remaining Capacity</div>
            <h3 class="fw-bold text-info mb-0"><?= $report['remaining_capacity'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;">Seats open to public</span>
        </div>
    </div>
</div>

<!-- Progress and Breakdown -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card p-4 bg-white h-100">
            <h5 class="fw-bold mb-3 text-dark">Gate Turnout Progress</h5>
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Admitted Check-Ins (<?= $report['checked_in'] ?>)</span>
                    <span class="fw-bold text-success"><?= $report['checkin_progress_pct'] ?>%</span>
                </div>
                <div class="progress" style="height: 12px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $report['checkin_progress_pct'] ?>%"></div>
                </div>
            </div>

            <h6 class="fw-bold mt-4 mb-2 text-dark small text-uppercase">Status Breakdown</h6>
            <div class="list-group list-group-flush">
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <span><span class="badge bg-success me-2">●</span> Confirmed & Ready</span>
                    <span class="fw-bold"><?= $report['confirmed'] ?></span>
                </div>
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <span><span class="badge bg-primary me-2">●</span> Checked-In at Venue</span>
                    <span class="fw-bold text-primary"><?= $report['checked_in'] ?></span>
                </div>
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <span><span class="badge bg-warning me-2">●</span> Pending Payment / Approval</span>
                    <span class="fw-bold text-warning"><?= $report['pending'] ?></span>
                </div>
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <span><span class="badge bg-danger me-2">●</span> Cancelled or Rejected</span>
                    <span class="fw-bold text-danger"><?= $report['cancelled'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Booking Velocity -->
    <div class="col-md-6">
        <div class="card p-4 bg-white h-100">
            <h5 class="fw-bold mb-3 text-dark">Registration Timeline & Velocity</h5>
            <?php if (empty($report['daily_trend'])): ?>
                <p class="text-muted small py-4 text-center">No trend data available for selected range.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr style="font-size:11px;" class="text-uppercase text-muted">
                                <th>Date</th>
                                <th class="text-end">Registrations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['daily_trend'] as $day => $cnt): ?>
                                <tr>
                                    <td class="font-monospace small"><?= formatDate($day) ?></td>
                                    <td class="text-end fw-bold text-dark"><?= $cnt ?> passes</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
