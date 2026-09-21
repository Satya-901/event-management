<?php
$pageTitle = "Platform Overview";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/ReportService.php';
require_once __DIR__ . '/../includes/services/ClientService.php';
require_once __DIR__ . '/../includes/services/BookingService.php';
require_once __DIR__ . '/../includes/services/LeadService.php';

$report = ReportService::getAdminReport();
$clients = ClientService::getAllClients();
$recentBookings = BookingService::getBookings(null, null, []);
$recentBookings = array_slice($recentBookings, 0, 6);

$leadStats = LeadService::getStats();
$recentLeads = array_slice(LeadService::getAllLeads(), 0, 5);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Super Admin Command Center</h4>
        <p class="text-muted small mb-0">Multi-tenant management for Utsavam celebrations platform.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="/admin/leads" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-xs">
            <i data-lucide="inbox" style="width:16px;height:16px;"></i> Event Inquiries (<?= $leadStats['total'] ?>)
        </a>
        <a href="/admin/clients/create" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm">
            <i data-lucide="plus" style="width:16px;height:16px;"></i> Create Client Space
        </a>
    </div>
</div>

<?php if ($leadStats['new'] > 0): ?>
    <div class="alert alert-warning border-warning border-opacity-50 d-flex align-items-center justify-content-between py-2.5 px-3 mb-4 rounded-3 shadow-xs">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger text-white rounded-pill px-2 py-0.5" style="font-size:11px;">NEW</span>
            <span class="fw-semibold text-dark small">
                You have <strong><?= $leadStats['new'] ?></strong> new event listing <?= $leadStats['new'] === 1 ? 'inquiry' : 'inquiries' ?> from prospective organizers waiting for outreach!
            </span>
        </div>
        <a href="/admin/leads?status=new" class="btn btn-warning btn-sm fw-bold px-3 py-1 text-dark text-nowrap shadow-xs">
            Review Inquiries &rarr;
        </a>
    </div>
<?php endif; ?>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg">
        <div class="card card-dark p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Client Tenants</span>
                <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary"><i data-lucide="building" style="width:17px;height:17px;"></i></span>
            </div>
            <h3 class="fw-bold mb-1" style="color:#0f172a;"><?= $report['total_clients'] ?></h3>
            <span class="text-success text-xs fw-semibold" style="font-size:11px;"><?= $report['active_clients'] ?> Active Organizations</span>
        </div>
    </div>
    <div class="col-6 col-lg">
        <a href="/admin/leads" class="text-decoration-none">
            <div class="card card-dark p-3 h-100 border-<?= $leadStats['new'] > 0 ? 'danger' : 'border' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">Event Leads</span>
                    <span class="p-2 rounded-3 bg-danger bg-opacity-10 text-danger"><i data-lucide="inbox" style="width:17px;height:17px;"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-<?= $leadStats['new'] > 0 ? 'danger' : 'dark' ?>"><?= $leadStats['total'] ?></h3>
                <span class="text-<?= $leadStats['new'] > 0 ? 'danger fw-bold' : 'muted' ?> text-xs" style="font-size:11px;">
                    <?= $leadStats['new'] ?> New Inquiries
                </span>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg">
        <div class="card card-dark p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Total Events</span>
                <span class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning"><i data-lucide="calendar" style="width:17px;height:17px;"></i></span>
            </div>
            <h3 class="fw-bold mb-1" style="color:#0f172a;"><?= $report['total_events'] ?></h3>
            <span class="text-muted text-xs fw-medium" style="font-size:11px;"><?= $report['active_events'] ?> Published Live</span>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="card card-dark p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Total Bookings</span>
                <span class="p-2 rounded-3 bg-info bg-opacity-10 text-info"><i data-lucide="ticket" style="width:17px;height:17px;"></i></span>
            </div>
            <h3 class="fw-bold mb-1" style="color:#0f172a;"><?= $report['total_bookings'] ?></h3>
            <span class="text-muted text-xs fw-medium" style="font-size:11px;"><?= $report['total_passes'] ?> Total Passes</span>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="card card-dark p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Checked In</span>
                <span class="p-2 rounded-3 bg-success bg-opacity-10 text-success"><i data-lucide="user-check" style="width:17px;height:17px;"></i></span>
            </div>
            <h3 class="fw-bold mb-1" style="color:#0f172a;"><?= $report['checked_in'] ?></h3>
            <span class="text-success text-xs fw-semibold" style="font-size:11px;">Verified Turnstile Passes</span>
        </div>
    </div>
</div>

<!-- Recent Event Listing Inquiries (Leads) -->
<div class="card card-dark p-3 p-md-4 mb-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0" style="color:#0f172a;">Recent Event Listing Inquiries (Organizer Leads)</h5>
                <?php if ($leadStats['new'] > 0): ?>
                    <span class="badge bg-danger text-white rounded-pill px-2 py-0.5" style="font-size:11px;"><?= $leadStats['new'] ?> New</span>
                <?php endif; ?>
            </div>
            <span class="text-muted small">Prospective hosts and organizers requesting to list their events on Utsavam.</span>
        </div>
        <a href="/admin/leads" class="btn btn-outline-secondary btn-sm px-3">View All Leads (<?= $leadStats['total'] ?>) &rarr;</a>
    </div>

    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Inquiry Ref</th>
                    <th>Organizer & Organization</th>
                    <th>Event Title</th>
                    <th>Category & Expected Volume</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLeads)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted small">No event listing inquiries received yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentLeads as $lead): 
                        $st = strtolower($lead['status'] ?? 'new');
                        $badgeBg = match($st) {
                            'new' => 'bg-danger text-white',
                            'contacted' => 'bg-warning text-dark',
                            'converted' => 'bg-success text-white',
                            default => 'bg-secondary text-white'
                        };
                    ?>
                        <tr>
                            <td class="font-monospace fw-bold text-dark" style="font-size:12.5px;">
                                <?= e($lead['inquiry_number']) ?>
                                <div class="text-muted small fw-normal" style="font-size:11px;"><?= formatDate($lead['created_at'], 'd M Y') ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($lead['organizer_name']) ?></div>
                                <div class="small text-muted"><?= e($lead['organization_name'] ?: $lead['organizer_name']) ?></div>
                                <div class="small text-secondary mt-1 d-flex flex-wrap gap-1">
                                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $lead['phone']) ?>" class="badge bg-primary text-white text-decoration-none" title="Call directly">
                                        <i data-lucide="phone" style="width:10px;height:10px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> <?= e($lead['phone']) ?>
                                    </a>
                                    <?php if (!empty($lead['email'])): ?>
                                        <a href="mailto:<?= e($lead['email']) ?>" class="badge bg-light text-muted border text-decoration-none">
                                            <i data-lucide="mail" style="width:10px;height:10px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> <?= e($lead['email']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($lead['event_title']) ?></div>
                                <div class="small text-muted"><?= e($lead['event_date'] ?: 'Date TBD') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border" style="font-size:11px;"><?= e($lead['event_category']) ?></span>
                                <div class="small text-muted mt-1"><?= e($lead['expected_attendees']) ?> attendees</div>
                            </td>
                            <td class="small text-dark">
                                <i data-lucide="map-pin" style="width:12px;height:12px;display:inline-block;" class="text-muted"></i> <?= e($lead['venue_city'] ?: 'TBD') ?>
                            </td>
                            <td>
                                <span class="badge <?= $badgeBg ?> text-uppercase px-2 py-1" style="font-size:10px; font-weight:700;">
                                    <?= e($lead['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="/admin/leads" class="btn btn-outline-secondary btn-sm py-1 px-2 text-xs">
                                        Review
                                    </a>
                                    <a href="/admin/clients/create?name=<?= urlencode($lead['organization_name'] ?: $lead['organizer_name']) ?>&company_name=<?= urlencode($lead['organization_name']) ?>&email=<?= urlencode($lead['email']) ?>&mobile=<?= urlencode($lead['phone']) ?>" 
                                       class="btn btn-primary btn-sm py-1 px-2 text-xs">
                                        Onboard
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

<!-- Registered Client Tenants -->
<div class="card card-dark p-3 p-md-4 mb-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0" style="color:#0f172a;">Active Client Organizations (Tenants)</h5>
            <span class="text-muted small">Each client possesses complete data isolation and a unique public portal.</span>
        </div>
        <a href="/admin/clients" class="btn btn-outline-secondary btn-sm px-3">View All Tenants</a>
    </div>

    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
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
                            <div class="fw-bold" style="color:#0f172a;"><?= e($c['name']) ?></div>
                            <div class="small text-muted"><?= e($c['company_name']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace"><?= e($c['code']) ?></span>
                        </td>
                        <td>
                            <a href="/<?= e($c['slug']) ?>/" target="_blank" class="text-primary fw-medium text-decoration-none small d-inline-flex align-items-center gap-1">
                                <span>/<?= e($c['slug']) ?>/</span>
                                <i data-lucide="external-link" style="width:12px;height:12px;"></i>
                            </a>
                        </td>
                        <td class="small text-secondary"><?= e($c['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?> border border-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?> border-opacity-25 px-2 py-1">
                                <?= strtoupper($c['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="/client/login" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2.5 text-xs d-inline-flex align-items-center gap-1" title="Login as Client Organizer">
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
<div class="card card-dark p-3 p-md-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0" style="color:#0f172a;">Recent Platform Registrations</h5>
            <span class="text-muted small">Live activity across all client events.</span>
        </div>
        <a href="/admin/bookings" class="btn btn-outline-secondary btn-sm px-3">All Bookings</a>
    </div>

    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
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
                        <td class="font-monospace fw-bold text-primary"><?= e($b['booking_number']) ?></td>
                        <td class="fw-semibold" style="color:#0f172a;"><?= e($b['customer_name']) ?></td>
                        <td class="small text-secondary"><?= e($b['email']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= (int)($b['pass_count'] ?? 1) ?></span></td>
                        <td class="small text-muted"><?= formatDate($b['booking_date']) ?></td>
                        <td><?= statusBadge($b['status']) ?></td>
                        <td class="text-end">
                            <a href="/verify/<?= e($b['qr_token']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2.5 text-xs d-inline-flex align-items-center gap-1">
                                <i data-lucide="qr-code" style="width:13px;height:13px;"></i> View Pass
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
