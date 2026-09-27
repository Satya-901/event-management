<?php
$pageTitle = "Global Events";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$clients = ClientService::getAllClients();
$clientMap = [];
foreach ($clients as $c) {
    $clientMap[$c['id']] = $c;
}

$selectedClientId = $_GET['client_id'] ?? '';
$events = EventService::getEvents($selectedClientId ?: null);
$flash = getFlash();
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : $flash['type']) ?> py-2.5 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2 shadow-xs">
        <i data-lucide="<?= $flash['type'] === 'error' ? 'alert-circle' : 'check-circle' ?>" style="width:16px;height:16px;"></i>
        <span><?= e($flash['message']) ?></span>
    </div>
<?php endif; ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Global Events Registry</h4>
        <p class="text-muted small mb-0">Overview of all events hosted across all client tenants.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-md-auto">
        <a href="/admin/events/create" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
            <i data-lucide="plus-circle" style="width:15px;height:15px;"></i> Create Event
        </a>
        <div class="d-flex align-items-center gap-1.5 ms-auto ms-md-0">
            <label class="small text-muted mb-0 text-nowrap fw-medium">Tenant:</label>
            <form method="GET" action="/admin/events" class="m-0">
                <select name="client_id" class="form-select form-select-sm bg-white text-dark border" onchange="this.form.submit()">
                    <option value="">All Client Tenants</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= e($c['id']) ?>" <?= $selectedClientId === $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?> (<?= e($c['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
</div>

<div class="card card-dark overflow-hidden">
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Client Organization</th>
                    <th>Date & Venue</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No events found.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $ev): ?>
                        <?php $cl = $clientMap[$ev['client_id']] ?? null; ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($ev['name']) ?></div>
                                <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                    <span class="badge bg-light text-dark border text-xs" style="font-size:10.5px;"><?= e($ev['category']) ?></span>
                                    <?php 
                                        $pkgCount = is_array($ev['packages'] ?? null) ? count($ev['packages']) : 0;
                                    ?>
                                    <?php if ($pkgCount > 0): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size:10px;">
                                            <?= $pkgCount ?> <?= $pkgCount === 1 ? 'Package' : 'Packages' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="text-primary fw-semibold"><?= e($cl['name'] ?? 'Unknown Client') ?></span>
                                <div class="text-xs text-muted"><?= e($cl['code'] ?? '') ?></div>
                            </td>
                            <td class="small text-secondary">
                                <div><?= formatDate($ev['start_date']) ?></div>
                                <div class="text-muted"><?= e($ev['venue_name']) ?>, <?= e($ev['city']) ?></div>
                            </td>
                            <td class="small">
                                <div>Total: <strong class="text-dark"><?= (int)($ev['max_capacity'] ?? 0) ?></strong></div>
                                <div class="text-muted">Available: <?= (int)($ev['available_seats'] ?? 0) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?> border border-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?> border-opacity-25 px-2 py-1">
                                    <?= strtoupper($ev['status'] ?? 'published') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <?php if ($cl): ?>
                                        <a href="/<?= e($cl['slug']) ?>/<?= e($ev['slug']) ?>/" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2 text-xs d-inline-flex align-items-center gap-1" title="View Public Landing Page">
                                            <i data-lucide="external-link" style="width:13px;height:13px;"></i> View
                                        </a>
                                    <?php endif; ?>
                                    <a href="/admin/events/edit?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2 text-xs d-inline-flex align-items-center gap-1" title="Edit Event & Packages">
                                        <i data-lucide="edit-3" style="width:13px;height:13px;"></i> Edit
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

<?php require_once __DIR__ . '/footer.php'; ?>
