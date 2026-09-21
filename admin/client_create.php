<?php
$pageTitle = "Provision New Client Tenant";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$error = null;

// Pre-fill values from GET if onboarding from a lead
$prefillName = $_POST['name'] ?? ($_GET['name'] ?? '');
$prefillCompany = $_POST['company_name'] ?? ($_GET['company_name'] ?? '');
$prefillEmail = $_POST['email'] ?? ($_GET['email'] ?? '');
$prefillMobile = $_POST['mobile'] ?? ($_GET['mobile'] ?? '');
$prefillSlug = $_POST['slug'] ?? (slugify($_GET['slug'] ?? $prefillName));
$prefillUsername = $_POST['username'] ?? (slugify($prefillName) ? str_replace('-', '_', slugify($prefillName)) . '_admin' : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF verification failed.";
    } else {
        try {
            $name = trim($_POST['name'] ?? '');
            $companyName = trim($_POST['company_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $slug = slugify($_POST['slug'] ?? $name);
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($name) || empty($email) || empty($slug)) {
                throw new Exception("Organization name, email, and slug are required.");
            }

            // Check slug collision
            if (ClientService::getClientBySlug($slug)) {
                throw new Exception("A client with the slug '{$slug}' already exists.");
            }

            $clientCode = generateClientCode();

            $tempPassword = $password ?: bin2hex(random_bytes(6));

            $newClient = ClientService::createClient([
                'name' => $name,
                'company_name' => $companyName ?: $name,
                'email' => $email,
                'mobile' => trim($_POST['mobile'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'slug' => $slug,
                'code' => $clientCode,
                'username' => $username ?: slugify($name),
                'password' => $tempPassword,
                'status' => 'active'
            ], $currentUser);

            redirect('/admin/clients');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Provision New Client Tenant</h4>
        <p class="text-muted small mb-0">Create an isolated organization tenant with a dedicated public portal and organizer credentials.</p>
    </div>
    <a href="/admin/clients" class="btn btn-outline-secondary btn-sm shadow-xs">Cancel</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="/admin/clients/create" class="card card-dark p-3 p-md-4">
    <?= csrfInput() ?>

    <h5 class="fw-bold mb-3" style="color:#0f172a;">1. Organization Details</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Brand / Display Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="clientNameInput" class="form-control" placeholder="Organization or brand name" value="<?= e($prefillName) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Public URL Slug <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border" style="font-size:12.5px;">utsavam.com/</span>
                <input type="text" name="slug" id="clientSlugInput" class="form-control font-monospace" placeholder="brand-slug" value="<?= e($prefillSlug) ?>" required>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label small fw-medium text-secondary">Registered Legal Company Name</label>
            <input type="text" name="company_name" class="form-control" placeholder="Official registered business or entity name" value="<?= e($prefillCompany) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Official Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="organizer@domain.com" value="<?= e($prefillEmail) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Support Mobile / WhatsApp</label>
            <input type="text" name="mobile" class="form-control" placeholder="Phone number with country code" value="<?= e($prefillMobile) ?>">
        </div>
        <div class="col-12">
            <label class="form-label small fw-medium text-secondary">Physical Registered Address</label>
            <textarea name="address" class="form-control" rows="2" placeholder="Street address, city, state, postal code"></textarea>
        </div>
    </div>

    <h5 class="fw-bold mb-3" style="color:#0f172a;">2. Initial Organizer Staff Credentials</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Initial Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" placeholder="Unique account username" value="<?= e($prefillUsername) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Initial Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Secure password" value="" required>
        </div>
    </div>

    <div class="pt-3 border-top d-flex justify-content-end gap-2">
        <a href="/admin/clients" class="btn btn-outline-secondary">Back</a>
        <button type="submit" class="btn btn-primary fw-semibold px-4 shadow-sm">
            Provision Organization Tenant &rarr;
        </button>
    </div>
</form>

<script>
document.getElementById('clientNameInput').addEventListener('input', function() {
    const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    document.getElementById('clientSlugInput').value = slug;
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
