<?php
$pageTitle = "Event Listing Inquiries (Leads)";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/LeadService.php';

// Handle Actions (Status change, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired. Please retry.');
        redirect('/admin/leads');
    }

    $action = $_POST['action'] ?? '';
    $leadId = $_POST['lead_id'] ?? '';

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'new';
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        LeadService::updateLeadStatus($leadId, $status, $adminNotes);
        setFlash('success', 'Inquiry status updated successfully.');
        redirect('/admin/leads');
    } elseif ($action === 'delete') {
        LeadService::deleteLead($leadId);
        setFlash('success', 'Inquiry deleted.');
        redirect('/admin/leads');
    }
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $allLeads = LeadService::getAllLeads();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=utsavam_event_leads_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Inquiry Ref', 'Organizer Name', 'Organization', 'Email', 'Phone', 'Event Title', 'Category', 'Expected Attendees', 'Target Date', 'Venue City', 'Ticketing', 'Requirements', 'Status', 'Admin Notes', 'Date Submitted']);
    foreach ($allLeads as $l) {
        fputcsv($out, [
            $l['inquiry_number'] ?? '',
            $l['organizer_name'] ?? '',
            $l['organization_name'] ?? '',
            $l['email'] ?? '',
            $l['phone'] ?? '',
            $l['event_title'] ?? '',
            $l['event_category'] ?? '',
            $l['expected_attendees'] ?? '',
            $l['event_date'] ?? '',
            $l['venue_city'] ?? '',
            $l['ticketing_type'] ?? '',
            $l['requirements'] ?? '',
            $l['status'] ?? '',
            $l['admin_notes'] ?? '',
            $l['created_at'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

$currentFilter = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$leads = LeadService::getAllLeads($currentFilter, $searchQuery);
$stats = LeadService::getStats();
$flash = getFlash();
?>

<!-- Breadcrumbs and Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0" style="color:#0f172a;">Event Listing Inquiries & Leads</h4>
            <span class="badge bg-primary rounded-pill px-2.5 py-1 text-white" style="font-size:12px;"><?= $stats['total'] ?> Total</span>
            <?php if ($stats['new'] > 0): ?>
                <span class="badge bg-danger rounded-pill px-2.5 py-1 text-white animate-pulse" style="font-size:12px;"><?= $stats['new'] ?> Unreviewed</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">Prospective event organizers requesting to list their festivals, concerts, and conferences on Utsavam.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="/admin/leads?export=csv" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-xs">
            <i data-lucide="download" style="width:15px;height:15px;"></i> Export to CSV
        </a>
        <a href="/admin/clients/create" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm">
            <i data-lucide="plus" style="width:15px;height:15px;"></i> Create Tenant
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show small py-2.5 px-3 mb-4 shadow-xs" role="alert">
        <i data-lucide="<?= $flash['type'] === 'error' ? 'alert-circle' : 'check-circle' ?>" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;" class="me-1"></i>
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Stats Metrics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="/admin/leads?status=new" class="text-decoration-none">
            <div class="card card-dark p-3 h-100 <?= $currentFilter === 'new' ? 'border-primary' : '' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">New / Unreviewed</span>
                    <span class="p-2 rounded-3 bg-danger bg-opacity-10 text-danger"><i data-lucide="bell" style="width:17px;height:17px;"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-danger"><?= $stats['new'] ?></h3>
                <span class="text-muted text-xs" style="font-size:11px;">Requires contact & outreach</span>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/admin/leads?status=contacted" class="text-decoration-none">
            <div class="card card-dark p-3 h-100 <?= $currentFilter === 'contacted' ? 'border-primary' : '' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">In Discussion</span>
                    <span class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning"><i data-lucide="message-square" style="width:17px;height:17px;"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= $stats['contacted'] ?></h3>
                <span class="text-muted text-xs" style="font-size:11px;">Follow-up / Proposal sent</span>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/admin/leads?status=converted" class="text-decoration-none">
            <div class="card card-dark p-3 h-100 <?= $currentFilter === 'converted' ? 'border-primary' : '' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">Converted to Tenant</span>
                    <span class="p-2 rounded-3 bg-success bg-opacity-10 text-success"><i data-lucide="check-circle-2" style="width:17px;height:17px;"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-success"><?= $stats['converted'] ?></h3>
                <span class="text-success text-xs fw-semibold" style="font-size:11px;">Onboarded to platform</span>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/admin/leads?status=all" class="text-decoration-none">
            <div class="card card-dark p-3 h-100 <?= $currentFilter === 'all' ? 'border-primary' : '' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-medium">Total Inquiries</span>
                    <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary"><i data-lucide="inbox" style="width:17px;height:17px;"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= $stats['total'] ?></h3>
                <span class="text-muted text-xs" style="font-size:11px;">All recorded submissions</span>
            </div>
        </a>
    </div>
</div>

<!-- Filter Bar & Search -->
<div class="card card-dark p-3 mb-4">
    <div class="row g-2 align-items-center justify-content-between">
        <div class="col-md-auto">
            <div class="d-flex flex-wrap gap-1">
                <a href="/admin/leads?status=all" class="btn btn-sm <?= $currentFilter === 'all' ? 'btn-primary' : 'btn-outline-light' ?>">
                    All Leads (<?= $stats['total'] ?>)
                </a>
                <a href="/admin/leads?status=new" class="btn btn-sm <?= $currentFilter === 'new' ? 'btn-danger' : 'btn-outline-light' ?>">
                    New (<?= $stats['new'] ?>)
                </a>
                <a href="/admin/leads?status=contacted" class="btn btn-sm <?= $currentFilter === 'contacted' ? 'btn-warning text-dark' : 'btn-outline-light' ?>">
                    Contacted (<?= $stats['contacted'] ?>)
                </a>
                <a href="/admin/leads?status=converted" class="btn btn-sm <?= $currentFilter === 'converted' ? 'btn-success' : 'btn-outline-light' ?>">
                    Converted (<?= $stats['converted'] ?>)
                </a>
                <a href="/admin/leads?status=archived" class="btn btn-sm <?= $currentFilter === 'archived' ? 'btn-secondary' : 'btn-outline-light' ?>">
                    Archived (<?= $stats['archived'] ?>)
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <form method="GET" action="/admin/leads" class="d-flex gap-2">
                <?php if ($currentFilter !== 'all'): ?>
                    <input type="hidden" name="status" value="<?= e($currentFilter) ?>">
                <?php endif; ?>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i data-lucide="search" style="width:14px;height:14px;"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search organizer, event, city, email..." value="<?= e($searchQuery) ?>">
                    <?php if ($searchQuery): ?>
                        <a href="/admin/leads?status=<?= e($currentFilter) ?>" class="btn btn-outline-secondary" title="Clear search">&times;</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Leads Data Table -->
<div class="card card-dark p-0 overflow-hidden mb-4">
    <?php if (empty($leads)): ?>
        <div class="text-center py-5">
            <div class="p-3 bg-light rounded-circle d-inline-flex align-items-center justify-content-center text-muted mb-3">
                <i data-lucide="inbox" style="width:32px;height:32px;"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">No Event Listing Inquiries Found</h6>
            <p class="text-muted small mb-0">When organizers fill the "List Your Event" form on the landing page, their inquiry appears here in real-time.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th>Inquiry Ref & Date</th>
                        <th>Organizer & Brand</th>
                        <th>Event Name & Category</th>
                        <th>Attendees & City</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): 
                        $statusClass = match(strtolower($lead['status'] ?? 'new')) {
                            'new' => 'bg-danger text-white',
                            'contacted' => 'bg-warning text-dark',
                            'converted' => 'bg-success text-white',
                            'archived' => 'bg-secondary text-white',
                            default => 'bg-light text-dark'
                        };
                        $telLink = preg_replace('/[^0-9+]/', '', $lead['phone'] ?? '');
                    ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace fw-bold mb-1"><?= e($lead['inquiry_number']) ?></span>
                                <div class="text-muted small" style="font-size:11.5px;">
                                    <i data-lucide="clock" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i>
                                    <?= formatDate($lead['created_at'], 'd M Y, h:i A') ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($lead['organizer_name']) ?></div>
                                <?php if (!empty($lead['organization_name']) && $lead['organization_name'] !== $lead['organizer_name']): ?>
                                    <div class="text-muted small"><?= e($lead['organization_name']) ?></div>
                                <?php endif; ?>
                                <div class="d-flex flex-wrap align-items-center gap-1.5 mt-1">
                                    <a href="tel:<?= e($telLink) ?>" class="badge bg-primary text-white text-decoration-none shadow-xs px-2 py-1" title="Call Organizer Directly">
                                        <i data-lucide="phone-call" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> Call <?= e($lead['phone']) ?>
                                    </a>
                                    <a href="https://wa.me/<?= e($telLink) ?>" target="_blank" class="badge bg-success text-white text-decoration-none shadow-xs px-2 py-1" title="Chat on WhatsApp">
                                        <i data-lucide="message-circle" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> WhatsApp
                                    </a>
                                    <?php if (!empty($lead['email'])): ?>
                                        <a href="mailto:<?= e($lead['email']) ?>" class="badge bg-light text-secondary border text-decoration-none" title="Send Email">
                                            <i data-lucide="mail" style="width:11px;height:11px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> <?= e($lead['email']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($lead['event_title']) ?></div>
                                <div class="d-flex align-items-center gap-1.5 mt-1">
                                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:11px;">
                                        <?= e($lead['event_category']) ?>
                                    </span>
                                    <span class="badge bg-light text-muted border" style="font-size:11px;">
                                        <?= e($lead['ticketing_type'] ?: 'Free') ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">
                                    <i data-lucide="users" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-top:-2px;" class="text-muted"></i>
                                    <?= e($lead['expected_attendees'] ?: 'Not specified') ?>
                                </div>
                                <div class="text-muted small" style="font-size:12px;">
                                    <i data-lucide="map-pin" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i>
                                    <?= e($lead['venue_city'] ?: 'TBD') ?>
                                    <?php if (!empty($lead['event_date'])): ?>
                                        &bull; <?= e($lead['event_date']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $statusClass ?> text-uppercase px-2 py-1" style="font-size:10px; letter-spacing:0.5px; font-weight:700;">
                                    <?= e($lead['status'] ?? 'new') ?>
                                </span>
                                <?php if (!empty($lead['admin_notes'])): ?>
                                    <div class="text-muted small mt-1 text-truncate" style="max-width:140px; font-size:11px;" title="<?= e($lead['admin_notes']) ?>">
                                        <i data-lucide="file-text" style="width:11px;height:11px;display:inline-block;vertical-align:middle;"></i> <?= e($lead['admin_notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick='showLeadDetails(<?= json_encode($lead) ?>)' 
                                            title="View Full Inquiry Details">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i> Details
                                    </button>
                                    
                                    <!-- Convert to Client Tenant -->
                                    <a href="/admin/clients/create?name=<?= urlencode($lead['organization_name'] ?: $lead['organizer_name']) ?>&company_name=<?= urlencode($lead['organization_name']) ?>&email=<?= urlencode($lead['email']) ?>&mobile=<?= urlencode($lead['phone']) ?>&slug=<?= urlencode(slugify($lead['organization_name'] ?: $lead['organizer_name'])) ?>" 
                                       class="btn btn-primary" 
                                       title="Convert this lead into an active Client Tenant">
                                        <i data-lucide="user-plus" style="width:14px;height:14px;"></i> Onboard
                                    </a>

                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px;">
                                        <li><h6 class="dropdown-header">Update Status</h6></li>
                                        <li>
                                            <form method="POST" action="/admin/leads">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="lead_id" value="<?= e($lead['id']) ?>">
                                                <input type="hidden" name="status" value="contacted">
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-warning">
                                                    <i data-lucide="message-square" style="width:14px;height:14px;"></i> Mark as Contacted
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="/admin/leads">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="lead_id" value="<?= e($lead['id']) ?>">
                                                <input type="hidden" name="status" value="converted">
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-success">
                                                    <i data-lucide="check-circle" style="width:14px;height:14px;"></i> Mark as Converted
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="/admin/leads">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="lead_id" value="<?= e($lead['id']) ?>">
                                                <input type="hidden" name="status" value="archived">
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-muted">
                                                    <i data-lucide="archive" style="width:14px;height:14px;"></i> Archive Lead
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="/admin/leads" onsubmit="return confirm('Delete this inquiry permanently?');">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="lead_id" value="<?= e($lead['id']) ?>">
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Delete Inquiry
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Lead Detail Modal -->
<div class="modal fade" id="leadDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalLeadTitle">Event Listing Inquiry</h5>
                    <span class="badge bg-dark font-monospace mt-1" id="modalInquiryNumber">INQ-REF</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-uppercase text-muted fw-bold mb-2" style="font-size:11px; letter-spacing:0.5px;">Organizer Profile</div>
                            <h6 class="fw-bold text-dark mb-1" id="modalOrganizerName">-</h6>
                            <div class="small text-muted mb-2" id="modalOrganizationName">-</div>
                            <div class="d-flex flex-column gap-1.5 small">
                                <div id="modalEmailContainer"><i data-lucide="mail" style="width:13px;height:13px;" class="text-muted me-1"></i> <a href="#" id="modalEmailLink" class="text-decoration-none"></a></div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <a href="#" id="modalCallBtn" class="btn btn-sm btn-primary py-1 px-2.5 d-inline-flex align-items-center gap-1">
                                        <i data-lucide="phone-call" style="width:13px;height:13px;"></i> Call <span id="modalCallNumber"></span>
                                    </a>
                                    <a href="#" id="modalPhoneLink" target="_blank" class="btn btn-sm btn-success py-1 px-2.5 d-inline-flex align-items-center gap-1">
                                        <i data-lucide="message-circle" style="width:13px;height:13px;"></i> WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-uppercase text-muted fw-bold mb-2" style="font-size:11px; letter-spacing:0.5px;">Event Specifications</div>
                            <h6 class="fw-bold text-dark mb-1" id="modalEventTitle">-</h6>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <span class="badge bg-primary text-white" id="modalEventCategory">-</span>
                                <span class="badge bg-secondary text-white" id="modalTicketingType">-</span>
                            </div>
                            <div class="d-flex flex-column gap-1 small text-dark">
                                <div><i data-lucide="users" style="width:13px;height:13px;" class="text-muted me-1"></i> Volume: <strong id="modalAttendees">-</strong></div>
                                <div><i data-lucide="calendar" style="width:13px;height:13px;" class="text-muted me-1"></i> Date: <strong id="modalDate">-</strong></div>
                                <div><i data-lucide="map-pin" style="width:13px;height:13px;" class="text-muted me-1"></i> City / Venue: <strong id="modalCity">-</strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-uppercase text-muted fw-bold small" style="font-size:11px; letter-spacing:0.5px;">Organizer Requirements & Special Notes</label>
                    <div class="p-3 bg-light rounded-3 border text-dark" id="modalRequirements" style="white-space: pre-wrap; font-size: 13.5px; min-height: 70px;">
                        No special notes provided.
                    </div>
                </div>

                <!-- Admin Status & Notes Form inside modal -->
                <form method="POST" action="/admin/leads" class="p-3 border rounded-3 bg-light bg-opacity-50">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="lead_id" id="modalLeadId">

                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Inquiry Status</label>
                            <select name="status" id="modalStatusSelect" class="form-select form-select-sm">
                                <option value="new">New (Unreviewed)</option>
                                <option value="contacted">Contacted / Follow-up</option>
                                <option value="converted">Converted to Tenant</option>
                                <option value="archived">Archived / Closed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Internal Admin Notes</label>
                            <input type="text" name="admin_notes" id="modalAdminNotes" class="form-control form-control-sm" placeholder="Enter follow-up remarks or onboarding notes...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100 mt-md-4">Save</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top py-2.5 d-flex justify-content-between">
                <div class="text-muted small" style="font-size:11.5px;" id="modalTimestamp">Submitted: -</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="modalOnboardBtn" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1">
                        <i data-lucide="user-plus" style="width:14px;height:14px;"></i> Onboard as Tenant
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showLeadDetails(lead) {
    document.getElementById('modalLeadId').value = lead.id || '';
    document.getElementById('modalInquiryNumber').textContent = lead.inquiry_number || '';
    document.getElementById('modalOrganizerName').textContent = lead.organizer_name || '';
    document.getElementById('modalOrganizationName').textContent = lead.organization_name || lead.organizer_name || '';
    
    const emailContainer = document.getElementById('modalEmailContainer');
    const emailLink = document.getElementById('modalEmailLink');
    if (lead.email) {
        emailContainer.classList.remove('d-none');
        emailLink.textContent = lead.email;
        emailLink.href = 'mailto:' + lead.email;
    } else {
        emailContainer.classList.add('d-none');
    }

    const cleanPhone = (lead.phone || '').replace(/[^0-9+]/g, '');
    const callBtn = document.getElementById('modalCallBtn');
    document.getElementById('modalCallNumber').textContent = lead.phone || '';
    callBtn.href = 'tel:' + cleanPhone;

    const phoneLink = document.getElementById('modalPhoneLink');
    phoneLink.href = 'https://wa.me/' + cleanPhone;

    document.getElementById('modalEventTitle').textContent = lead.event_title || '';
    document.getElementById('modalEventCategory').textContent = lead.event_category || 'General';
    document.getElementById('modalTicketingType').textContent = lead.ticketing_type || 'Free';
    document.getElementById('modalAttendees').textContent = lead.expected_attendees || 'N/A';
    document.getElementById('modalDate').textContent = lead.event_date || 'Tentative';
    document.getElementById('modalCity').textContent = lead.venue_city || 'Not specified';
    document.getElementById('modalRequirements').textContent = lead.requirements || 'No special requirements noted.';
    document.getElementById('modalStatusSelect').value = lead.status || 'new';
    document.getElementById('modalAdminNotes').value = lead.admin_notes || '';
    document.getElementById('modalTimestamp').textContent = 'Submitted: ' + (lead.created_at || 'Unknown') + ' (IP: ' + (lead.ip_address || 'N/A') + ')';

    // Onboard button URL with pre-filled parameters
    const orgName = lead.organization_name || lead.organizer_name || '';
    const onboardUrl = '/admin/clients/create?name=' + encodeURIComponent(orgName) +
                       '&company_name=' + encodeURIComponent(orgName) +
                       '&email=' + encodeURIComponent(lead.email || '') +
                       '&mobile=' + encodeURIComponent(lead.phone || '');
    document.getElementById('modalOnboardBtn').href = onboardUrl;

    const modal = new bootstrap.Modal(document.getElementById('leadDetailModal'));
    modal.show();
    setTimeout(() => { lucide.createIcons(); }, 150);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
