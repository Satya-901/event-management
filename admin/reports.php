<?php
$pageTitle = "Platform Reports";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/ReportService.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$clients = ClientService::getAllClients();
$selectedClientId = $_GET['client_id'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

$report = ReportService::getAdminReport($selectedClientId ?: null, $fromDate ?: null, $toDate ?: null);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-white">Platform Analytical Reports</h4>
        <p class="text-muted small mb-0">Cross-tenant statistics, registration velocity, and capacity monitoring.</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-1">
        <i data-lucide="printer" style="width:14px;height:14px;"></i> Print Platform Summary
    </button>
</div>

<!-- Filters -->
<div class="card card-dark p-3 mb-4">
    <form method="GET" action="/admin/reports" class="row g-2 align-items-center">
        <div class="col-md-4">
            <select name="client_id" class="form-select form-select-sm form-control-dark" onchange="this.form.submit()">
                <option value="">All Client Tenants</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= $selectedClientId === $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="from_date" class="form-control form-control-sm form-control-dark" value="<?= e($fromDate) ?>" placeholder="From Date">
        </div>
        <div class="col-md-3">
            <input type="date" name="to_date" class="form-control form-control-sm form-control-dark" value="<?= e($toDate) ?>" placeholder="To Date">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark w-100">Apply Filter</button>
        </div>
    </form>
</div>

<!-- Key Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-dark p-3">
            <div class="text-muted small mb-1">Total Registrations</div>
            <h3 class="fw-bold text-white mb-0"><?= $report['total_bookings'] ?></h3>
            <span class="text-muted text-xs"><?= $report['total_passes'] ?> Total Passes Issued</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dark p-3">
            <div class="text-muted small mb-1">Turnstile Checked-In</div>
            <h3 class="fw-bold text-success mb-0"><?= $report['checked_in'] ?></h3>
            <span class="text-success text-xs">Admitted to Venues</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dark p-3">
            <div class="text-muted small mb-1">Confirmed Pending Entry</div>
            <h3 class="fw-bold text-warning mb-0"><?= $report['confirmed'] ?></h3>
            <span class="text-muted text-xs">Valid Passes Holding</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dark p-3">
            <div class="text-muted small mb-1">Cancelled / Expired</div>
            <h3 class="fw-bold text-danger mb-0"><?= $report['cancelled'] ?></h3>
            <span class="text-muted text-xs">Voided Tickets</span>
        </div>
    </div>
</div>

<!-- Client-Wise Performance Comparison -->
<div class="card card-dark p-4 mb-4">
    <h5 class="fw-bold text-white mb-3">Tenant Organization Breakdown</h5>
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr style="font-size:11px;" class="text-uppercase text-muted">
                    <th>Organization</th>
                    <th>Code</th>
                    <th>Total Events</th>
                    <th>Total Bookings</th>
                    <th>Checked-In Attendees</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['client_stats'] as $cs): ?>
                    <tr>
                        <td class="fw-bold text-white"><?= e($cs['name']) ?></td>
                        <td><span class="badge bg-secondary font-monospace"><?= e($cs['code']) ?></span></td>
                        <td><?= $cs['events_count'] ?> Events</td>
                        <td><?= $cs['bookings_count'] ?> Bookings</td>
                        <td><span class="text-success fw-bold"><?= $cs['checkins_count'] ?> Admitted</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Event-Wise Performance Comparison -->
<div class="card card-dark p-4">
    <h5 class="fw-bold text-white mb-3">Event Capacity & Gate Utilization</h5>
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr style="font-size:11px;" class="text-uppercase text-muted">
                    <th>Event Name</th>
                    <th>Capacity</th>
                    <th>Available</th>
                    <th>Bookings</th>
                    <th>Checked-In</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['event_stats'] as $es): ?>
                    <tr>
                        <td class="fw-semibold text-white"><?= e($es['name']) ?></td>
                        <td><?= $es['max_capacity'] ?></td>
                        <td><?= $es['available_seats'] ?></td>
                        <td><?= $es['total_bookings'] ?></td>
                        <td><span class="text-success fw-bold"><?= $es['checked_in'] ?></span></td>
                        <td>
                            <?php $pct = $es['max_capacity'] > 0 ? round((($es['max_capacity'] - $es['available_seats']) / $es['max_capacity']) * 100) : 0; ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar bg-warning" style="width: <?= $pct ?>%"></div>
                                </div>
                                <span class="small font-monospace"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
