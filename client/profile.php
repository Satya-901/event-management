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

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Organizer Space Settings</h4>
        <p class="text-muted small mb-0">Manage organization credentials, public presence, and contact info.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
        <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
        <span><?= e($error) ?></span>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
        <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
        <span><?= e($success) ?></span>
    </div>
<?php endif; ?>

<div class="row g-3 g-md-4">
    <div class="col-lg-4">
        <div class="card p-4 shadow-xs text-center">
            <div class="mx-auto mb-3 p-3 bg-warning bg-opacity-10 rounded-circle text-warning d-inline-flex" style="color:#ea580c !important;">
                <i data-lucide="building-2" style="width:36px;height:36px;"></i>
            </div>
            <h5 class="fw-bold mb-1" style="color:#0f172a;"><?= e($currentClient['name']) ?></h5>
            <p class="text-muted small mb-3"><?= e($currentClient['company_name']) ?></p>

            <div class="bg-light p-3 rounded-3 text-start mb-3 border">
                <div class="small text-muted mb-1">Public Space URL:</div>
                <a href="/<?= e($currentClient['slug']) ?>/" target="_blank" class="fw-bold text-break text-decoration-none d-flex align-items-center gap-1 text-dark small">
                    <span>utsavam.com/<?= e($currentClient['slug']) ?>/</span>
                    <i data-lucide="external-link" style="width:13px;height:13px;"></i>
                </a>
                <div class="small text-muted mt-2.5 mb-1">Tenant ID Code:</div>
                <span class="badge bg-secondary font-monospace"><?= e($currentClient['code']) ?></span>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form method="POST" action="/client/profile" class="card p-4 shadow-xs">
            <?= csrfInput() ?>

            <h5 class="fw-bold mb-3" style="color:#0f172a;">Company Information</h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Organizer Brand Name</label>
                    <input type="text" class="form-control" value="<?= e($currentClient['name']) ?>" disabled>
                    <div class="text-xs text-muted mt-1" style="font-size:11px;">Managed by Super Administrator</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Public Slug</label>
                    <input type="text" class="form-control font-monospace" value="<?= e($currentClient['slug']) ?>" disabled>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold text-secondary">Registered Company Legal Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= e($currentClient['company_name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Official Contact Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($currentClient['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Support Phone / WhatsApp</label>
                    <input type="text" name="mobile" class="form-control" value="<?= e($currentClient['mobile']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold text-secondary">Registered Physical Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= e($currentClient['address']) ?></textarea>
                </div>

                <div class="col-12 mt-3 pt-3 border-top text-end">
                    <button type="submit" class="btn btn-warning px-4 py-2 fw-semibold text-white shadow-xs" style="background-color:#ea580c !important; border-color:#ea580c !important;">
                        Save Organizer Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
