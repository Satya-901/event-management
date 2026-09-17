<?php
$pageTitle = "Client Tenants";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$clients = ClientService::getAllClients();

// Handle Status Toggle or Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $cId = $_POST['client_id'] ?? '';
        if ($_POST['action'] === 'toggle_status') {
            $newStatus = $_POST['status'] ?? 'active';
            ClientService::updateClient($cId, ['status' => $newStatus], $currentUser);
        } elseif ($_POST['action'] === 'delete_client') {
            ClientService::deleteClient($cId, $currentUser);
        }
        redirect('/admin/clients');
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-white">Client Organizations (Tenants)</h4>
        <p class="text-muted small mb-0">Manage all registered event organizations, their isolated databases, and public slugs.</p>
    </div>
    <a href="/admin/clients/create" class="btn btn-warning btn-sm fw-bold text-dark d-inline-flex align-items-center gap-1">
        <i data-lucide="plus" style="width:16px;height:16px;"></i> Provision New Client
    </a>
</div>

<div class="card card-dark overflow-hidden">
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr style="font-size:11px;" class="text-uppercase text-muted">
                    <th>Organization</th>
                    <th>Code</th>
                    <th>Public Slug / URL</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No client tenants registered.</td></tr>
                <?php else: ?>
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
                            <td class="small text-muted">
                                <div><?= e($c['email']) ?></div>
                                <div style="font-size:11px;"><?= e($c['mobile']) ?></div>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="client_id" value="<?= e($c['id']) ?>">
                                    <input type="hidden" name="status" value="<?= $c['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="btn btn-sm btn-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?> py-0 px-2 text-xs" title="Click to toggle status">
                                        <?= strtoupper($c['status']) ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="/<?= e($c['slug']) ?>/" target="_blank" class="btn btn-sm btn-outline-light py-1 px-2 text-xs" title="Visit Public URL">
                                        <i data-lucide="globe" style="width:13px;height:13px;"></i> View
                                    </a>
                                    <?php if ($c['slug'] !== 'royal-events'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete client <?= e(addslashes($c['name'])) ?> and all their events/bookings?')">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="action" value="delete_client">
                                            <input type="hidden" name="client_id" value="<?= e($c['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 text-xs">
                                                <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
