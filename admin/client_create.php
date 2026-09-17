<?php
$pageTitle = "Provision New Client Tenant";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$error = null;

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

            $newClient = ClientService::createClient([
                'name' => $name,
                'company_name' => $companyName ?: $name,
                'email' => $email,
                'mobile' => trim($_POST['mobile'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'slug' => $slug,
                'code' => $clientCode,
                'username' => $username ?: slugify($name),
                'password' => $password ?: 'password123',
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-white">Provision New Client Tenant</h4>
        <p class="text-muted small mb-0">Create an isolated organization tenant with a dedicated public portal and organizer credentials.</p>
    </div>
    <a href="/admin/clients" class="btn btn-outline-light btn-sm">Cancel</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="/admin/clients/create" class="card card-dark p-4">
    <?= csrfInput() ?>

    <h5 class="fw-bold text-warning mb-3">1. Organization Details</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label small text-muted">Brand / Display Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="clientNameInput" class="form-control form-control-dark" placeholder="e.g. Apex Celebrations" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small text-muted">Public URL Slug <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-muted" style="font-size:12px;">utsavam.com/</span>
                <input type="text" name="slug" id="clientSlugInput" class="form-control form-control-dark font-monospace" placeholder="apex-celebrations" required>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label small text-muted">Registered Legal Company Name</label>
            <input type="text" name="company_name" class="form-control form-control-dark" placeholder="Apex Celebrations & Entertainment LLP">
        </div>
        <div class="col-md-6">
            <label class="form-label small text-muted">Official Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control form-control-dark" placeholder="contact@apexevents.com" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small text-muted">Support Mobile / WhatsApp</label>
            <input type="text" name="mobile" class="form-control form-control-dark" placeholder="+91 9876543210">
        </div>
        <div class="col-12">
            <label class="form-label small text-muted">Physical Registered Address</label>
            <textarea name="address" class="form-control form-control-dark" rows="2" placeholder="Suite, Street, City, State"></textarea>
        </div>
    </div>

    <h5 class="fw-bold text-warning mb-3">2. Initial Organizer Staff Credentials</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label small text-muted">Initial Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control form-control-dark" placeholder="apex_organizer" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small text-muted">Initial Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control form-control-dark" placeholder="••••••••" value="password123" required>
        </div>
    </div>

    <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-end gap-2">
        <a href="/admin/clients" class="btn btn-outline-secondary">Back</a>
        <button type="submit" class="btn btn-warning fw-bold text-dark px-4">
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
