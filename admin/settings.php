<?php
$pageTitle = "System Configuration & Diagnostics";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$success = null;
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">System Diagnostics & Configuration</h4>
        <p class="text-muted small mb-0">Runtime drivers, security verification, and platform parameters.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success py-2 small mb-3"><?= e($success) ?></div>
<?php endif; ?>

<div class="row g-3 g-md-4">
    <!-- Storage & Drivers Card -->
    <div class="col-lg-6">
        <div class="card card-dark p-3 p-md-4 h-100">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color:#0f172a;">
                <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex"><i data-lucide="database" style="width:18px;height:18px;"></i></span>
                <span>Data Store Architecture</span>
            </h5>
            <p class="text-muted small">
                Utsavam implements an abstracted Data-Access-Layer (DAL) using <code>DataStoreInterface</code>.
                You can toggle between local ACID file-locking JSON storage or a dedicated MySQL/MariaDB database with zero code changes.
            </p>

            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent px-0 py-2.5 d-flex justify-content-between">
                    <span class="text-muted small">Active Storage Driver:</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace fw-bold"><?= strtoupper(DATA_DRIVER) ?> MODE</span>
                </li>
                <li class="list-group-item bg-transparent px-0 py-2.5 d-flex justify-content-between">
                    <span class="text-muted small">JSON Storage Directory:</span>
                    <span class="font-monospace small text-dark"><?= e(STORAGE_PATH) ?></span>
                </li>
                <li class="list-group-item bg-transparent px-0 py-2.5 d-flex justify-content-between">
                    <span class="text-muted small">Concurrency Protection:</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Exclusive File Locking (flock)</span>
                </li>
                <li class="list-group-item bg-transparent px-0 py-2.5 d-flex justify-content-between">
                    <span class="text-muted small">SQL Migration Script:</span>
                    <span class="font-monospace small text-dark">database/schema.sql</span>
                </li>
            </ul>

            <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2 text-success small">
                <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                <span class="fw-medium">Storage Engine Healthy & Production-Ready</span>
            </div>
        </div>
    </div>

    <!-- Security & Email Card -->
    <div class="col-lg-6">
        <div class="card card-dark p-3 p-md-4 h-100">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color:#0f172a;">
                <span class="p-2 rounded-3 bg-success bg-opacity-10 text-success d-inline-flex"><i data-lucide="shield-check" style="width:18px;height:18px;"></i></span>
                <span>Platform Security Audit</span>
            </h5>

            <div class="d-flex flex-column gap-2 mb-4">
                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                    <span class="small fw-medium text-dark">Tenant Data Isolation Engine</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Enforced Backend</span>
                </div>
                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                    <span class="small fw-medium text-dark">CSRF Protection (HMAC SHA-256)</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active on All Forms</span>
                </div>
                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                    <span class="small fw-medium text-dark">Secure Password Hashing (Bcrypt)</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">PASSWORD_BCRYPT</span>
                </div>
                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                    <span class="small fw-medium text-dark">XSS Output Escaping (htmlspecialchars)</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">e() Sanitizer Active</span>
                </div>
                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                    <span class="small fw-medium text-dark">Data Directory Direct Access</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Protected (.htaccess)</span>
                </div>
            </div>

            <h6 class="fw-bold text-dark mb-1 small text-uppercase" style="letter-spacing:0.5px;">Notification Engine</h6>
            <p class="text-muted small mb-0">
                Transactional attendee pass confirmations dispatch via <code>MailService</code> with fallback logging.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
