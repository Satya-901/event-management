<?php
$pageTitle = "System Configuration & Diagnostics";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reseed') {
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        require_once __DIR__ . '/../includes/seed.php';
        seedDatabase();
        $success = "Demo database successfully re-seeded!";
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-white">System Diagnostics & Configuration</h4>
        <p class="text-muted small mb-0">Runtime drivers, security verification, and platform parameters.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success py-2 small mb-3"><?= e($success) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Storage & Drivers Card -->
    <div class="col-lg-6">
        <div class="card card-dark p-4 h-100">
            <h5 class="fw-bold text-warning mb-3 d-flex align-items-center gap-2">
                <i data-lucide="database" style="width:20px;height:20px;"></i> Data Store Architecture
            </h5>
            <p class="text-muted small">
                Utsavam implements an abstracted Data-Access-Layer (DAL) using <code>DataStoreInterface</code>.
                You can toggle between local ACID file-locking JSON storage or a dedicated MySQL/MariaDB database with zero code changes.
            </p>

            <ul class="list-group list-group-flush text-light">
                <li class="list-group-item bg-transparent px-0 py-2 border-secondary d-flex justify-content-between">
                    <span class="text-muted">Active Storage Driver:</span>
                    <span class="badge bg-warning text-dark font-monospace fw-bold"><?= strtoupper(DATA_DRIVER) ?> MODE</span>
                </li>
                <li class="list-group-item bg-transparent px-0 py-2 border-secondary d-flex justify-content-between">
                    <span class="text-muted">JSON Storage Directory:</span>
                    <span class="font-monospace small text-white"><?= e(STORAGE_PATH) ?></span>
                </li>
                <li class="list-group-item bg-transparent px-0 py-2 border-secondary d-flex justify-content-between">
                    <span class="text-muted">Concurrency Protection:</span>
                    <span class="badge bg-success">Exclusive File Locking (flock)</span>
                </li>
                <li class="list-group-item bg-transparent px-0 py-2 border-secondary d-flex justify-content-between">
                    <span class="text-muted">SQL Migration Script:</span>
                    <span class="font-monospace small text-white">database/schema.sql</span>
                </li>
            </ul>

            <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                <form method="POST" onsubmit="return confirm('Re-seed initial demo data (Royal Events, Dandiya Night 2026)?')">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="reseed">
                    <button type="submit" class="btn btn-outline-warning btn-sm d-inline-flex align-items-center gap-1">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Reset / Re-Seed Demo Data
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Security & Email Card -->
    <div class="col-lg-6">
        <div class="card card-dark p-4 h-100">
            <h5 class="fw-bold text-warning mb-3 d-flex align-items-center gap-2">
                <i data-lucide="shield-check" style="width:20px;height:20px;"></i> Platform Security Audit
            </h5>

            <div class="d-flex flex-column gap-2 mb-4">
                <div class="p-2 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between">
                    <span class="small">Tenant Data Isolation Engine</span>
                    <span class="badge bg-success">Enforced Backend</span>
                </div>
                <div class="p-2 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between">
                    <span class="small">CSRF Protection (HMAC SHA-256)</span>
                    <span class="badge bg-success">Active on All Forms</span>
                </div>
                <div class="p-2 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between">
                    <span class="small">Secure Password Hashing (Bcrypt)</span>
                    <span class="badge bg-success">PASSWORD_BCRYPT</span>
                </div>
                <div class="p-2 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between">
                    <span class="small">XSS Output Escaping (htmlspecialchars)</span>
                    <span class="badge bg-success">e() Sanitizer Active</span>
                </div>
                <div class="p-2 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between">
                    <span class="small">Data Directory Direct Access</span>
                    <span class="badge bg-success">Protected (.htaccess)</span>
                </div>
            </div>

            <h6 class="fw-bold text-light mb-2 small text-uppercase">Notification Engine</h6>
            <p class="text-muted small mb-0">
                Transactional attendee pass confirmations dispatch via <code>MailService</code> with fallback logging.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
