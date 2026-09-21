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

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Platform Analytical Reports</h4>
        <p class="text-muted small mb-0">Cross-tenant statistics, registration velocity, and capacity monitoring.</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm">
        <i data-lucide="printer" style="width:14px;height:14px;"></i> Print Platform Summary
    </button>
</div>

<!-- Filters -->
<div class="card card-dark p-3 mb-4">
    <form method="GET" action="/admin/reports" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
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
            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= e($fromDate) ?>" placeholder="From Date">
        </div>
        <div class="col-6 col-md-3">
            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= e($toDate) ?>" placeholder="To Date">
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary btn-sm fw-semibold w-100 shadow-xs">Apply Filter</button>
        </div>
    </form>
</div>

<!-- Key Stat Cards -->
<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-dark p-3 h-100">
            <div class="text-muted small mb-1 fw-medium">Total Registrations</div>
            <h3 class="fw-bold mb-1" style="color:#0f172a;"><?= $report['total_bookings'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;"><?= $report['total_passes'] ?> Total Passes Issued</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-dark p-3 h-100">
            <div class="text-muted small mb-1 fw-medium">Turnstile Checked-In</div>
            <h3 class="fw-bold text-success mb-1"><?= $report['checked_in'] ?></h3>
            <span class="text-success text-xs fw-semibold" style="font-size:11px;">Admitted to Venues</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-dark p-3 h-100">
            <div class="text-muted small mb-1 fw-medium">Confirmed Pending</div>
            <h3 class="fw-bold text-primary mb-1"><?= $report['confirmed'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;">Valid Passes Holding</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-dark p-3 h-100">
            <div class="text-muted small mb-1 fw-medium">Cancelled / Expired</div>
            <h3 class="fw-bold text-danger mb-1"><?= $report['cancelled'] ?></h3>
            <span class="text-muted text-xs" style="font-size:11px;">Voided Tickets</span>
        </div>
    </div>
</div>

<!-- Client-Wise Performance Comparison -->
<div class="card card-dark p-3 p-md-4 mb-4">
    <h5 class="fw-bold mb-3" style="color:#0f172a;">Tenant Organization Breakdown</h5>
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
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
                        <td class="fw-bold text-dark"><?= e($cs['name']) ?></td>
                        <td><span class="badge bg-light text-dark border font-monospace"><?= e($cs['code']) ?></span></td>
                        <td class="text-secondary"><?= $cs['events_count'] ?> Events</td>
                        <td class="text-secondary"><?= $cs['bookings_count'] ?> Bookings</td>
                        <td><span class="text-success fw-bold"><?= $cs['checkins_count'] ?> Admitted</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Event-Wise Performance Comparison -->
<div class="card card-dark p-3 p-md-4">
    <h5 class="fw-bold mb-3" style="color:#0f172a;">Event Capacity & Gate Utilization</h5>
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
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
                        <td class="fw-semibold text-dark"><?= e($es['name']) ?></td>
                        <td><?= $es['max_capacity'] ?></td>
                        <td class="text-muted"><?= $es['available_seats'] ?></td>
                        <td><?= $es['total_bookings'] ?></td>
                        <td><span class="text-success fw-bold"><?= $es['checked_in'] ?></span></td>
                        <td>
                            <?php $pct = $es['max_capacity'] > 0 ? round((($es['max_capacity'] - $es['available_seats']) / $es['max_capacity']) * 100) : 0; ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1 bg-light border" style="height:7px;">
                                    <div class="progress-bar bg-primary" style="width: <?= $pct ?>%"></div>
                                </div>
                                <span class="small font-monospace text-muted"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
