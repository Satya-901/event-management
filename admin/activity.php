<?php
$pageTitle = "Platform Audit Logs";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/storage/DataStoreFactory.php';

$logs = getDataStore()->getActivityLogs();
// Show newest first
$logs = array_reverse($logs);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-white">System Activity & Audit Logs</h4>
        <p class="text-muted small mb-0">Immutable tracking of operator logins, client creations, gate check-ins, and pass status changes.</p>
    </div>
</div>

<div class="card card-dark overflow-hidden">
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle mb-0">
            <thead>
                <tr style="font-size:11px;" class="text-uppercase text-muted">
                    <th>Timestamp</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No activity records logged.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="font-monospace text-muted small" style="font-size:12px;">
                                <?= formatDateTime($log['created_at'] ?? '') ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-white"><?= e($log['actor_id'] ?? 'System') ?></div>
                                <span class="badge bg-secondary font-monospace" style="font-size:10px;"><?= e($log['actor_role'] ?? 'system') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-warning">
                                    <?= e($log['action'] ?? 'action') ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= e($log['entity_type'] ?? '') ?> #<?= e(substr($log['entity_id'] ?? '', 0, 12)) ?>
                            </td>
                            <td class="text-light small">
                                <?= e($log['description'] ?? '') ?>
                            </td>
                            <td class="font-monospace text-muted small">
                                <?= e($log['ip_address'] ?? '127.0.0.1') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
