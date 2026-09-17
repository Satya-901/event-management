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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-white">Global Events Registry</h4>
        <p class="text-muted small mb-0">Overview of all events hosted across all client tenants.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <label class="small text-muted mb-0">Tenant:</label>
        <form method="GET" action="/admin/events">
            <select name="client_id" class="form-select form-select-sm bg-dark text-light border-secondary" onchange="this.form.submit()">
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

<div class="card card-dark overflow-hidden">
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr style="font-size:11px;" class="text-uppercase text-muted">
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
                                <div class="fw-bold text-white"><?= e($ev['name']) ?></div>
                                <span class="badge bg-warning text-dark text-xs" style="font-size:10px;"><?= e($ev['category']) ?></span>
                            </td>
                            <td>
                                <span class="text-warning fw-semibold"><?= e($cl['name'] ?? 'Unknown Client') ?></span>
                                <div class="text-xs text-muted"><?= e($cl['code'] ?? '') ?></div>
                            </td>
                            <td class="small text-muted">
                                <div><?= formatDate($ev['start_date']) ?></div>
                                <div><?= e($ev['venue_name']) ?>, <?= e($ev['city']) ?></div>
                            </td>
                            <td class="small">
                                <div>Total: <strong><?= (int)($ev['max_capacity'] ?? 0) ?></strong></div>
                                <div class="text-muted">Available: <?= (int)($ev['available_seats'] ?? 0) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?>">
                                    <?= strtoupper($ev['status'] ?? 'published') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($cl): ?>
                                    <a href="/<?= e($cl['slug']) ?>/<?= e($ev['slug']) ?>/" target="_blank" class="btn btn-sm btn-outline-light py-1 px-2 text-xs">
                                        <i data-lucide="external-link" style="width:13px;height:13px;"></i> View
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
