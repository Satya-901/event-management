<?php
$pageTitle = "Organizer Profile & Settings";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$clientId = getCurrentClientId();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF verification failed.";
    } else {
        try {
            $companyName = trim($_POST['company_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($companyName) || empty($email)) {
                throw new Exception("Company name and contact email are required.");
            }

            ClientService::updateClient($clientId, [
                'company_name' => $companyName,
                'email' => $email,
                'mobile' => $mobile,
                'address' => $address
            ], $currentUser);

            $success = "Organizer profile updated successfully!";
            $currentClient = ClientService::getClient($clientId);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Organizer Space Settings</h4>
        <p class="text-muted small mb-0">Manage organization credentials, public presence, and contact info.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success py-2 small mb-3"><?= e($success) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card p-4 bg-white shadow-sm text-center">
            <div class="mx-auto mb-3 p-3 bg-light rounded-circle text-warning d-inline-flex">
                <i data-lucide="building-2" style="width:36px;height:36px;"></i>
            </div>
            <h5 class="fw-bold mb-1"><?= e($currentClient['name']) ?></h5>
            <p class="text-muted small mb-3"><?= e($currentClient['company_name']) ?></p>

            <div class="bg-light p-3 rounded-3 text-start mb-3">
                <div class="small text-muted mb-1">Public Space URL:</div>
                <a href="/<?= e($currentClient['slug']) ?>/" target="_blank" class="fw-bold text-break text-decoration-none d-flex align-items-center gap-1 text-dark small">
                    <span>utsavam.com/<?= e($currentClient['slug']) ?>/</span>
                    <i data-lucide="external-link" style="width:13px;height:13px;"></i>
                </a>
                <div class="small text-muted mt-2 mb-1">Tenant ID Code:</div>
                <span class="badge bg-secondary font-monospace"><?= e($currentClient['code']) ?></span>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form method="POST" action="/client/profile" class="card p-4 bg-white shadow-sm">
            <?= csrfInput() ?>

            <h5 class="fw-bold mb-3 text-dark">Company Information</h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Organizer Brand Name</label>
                    <input type="text" class="form-control" value="<?= e($currentClient['name']) ?>" disabled>
                    <div class="text-xs text-muted mt-1" style="font-size:11px;">Managed by Super Administrator</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Public Slug</label>
                    <input type="text" class="form-control font-monospace" value="<?= e($currentClient['slug']) ?>" disabled>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Registered Company Legal Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= e($currentClient['company_name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Official Contact Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($currentClient['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Support Phone / WhatsApp</label>
                    <input type="text" name="mobile" class="form-control" value="<?= e($currentClient['mobile']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Registered Physical Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= e($currentClient['address']) ?></textarea>
                </div>

                <div class="col-12 mt-4 pt-3 border-top text-end">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold" style="background:#c2410c; border-color:#c2410c;">
                        Save Organizer Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
