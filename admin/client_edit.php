<?php
$pageTitle = "Edit Client Tenant";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$clientId = $_GET['id'] ?? '';
$client = ClientService::getClient($clientId);

if (!$client) {
    setFlash('error', "Client organization not found.");
    redirect('/admin/clients');
    exit;
}

$error = null;
$success = null;

$currentTerms = $client['terms_and_conditions'] ?? '';
$currentRefund = $client['cancellation_policy'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF verification failed.";
    } else {
        try {
            $name = trim($_POST['name'] ?? '');
            $companyName = trim($_POST['company_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $status = trim($_POST['status'] ?? 'active');
            $terms = trim($_POST['terms_and_conditions'] ?? '');
            $refund = trim($_POST['cancellation_policy'] ?? '');

            if (empty($name) || empty($email)) {
                throw new Exception("Organization name and email are required.");
            }

            ClientService::updateClient($clientId, [
                'name' => $name,
                'company_name' => $companyName ?: $name,
                'email' => $email,
                'mobile' => $mobile,
                'address' => $address,
                'status' => $status,
                'terms_and_conditions' => $terms,
                'cancellation_policy' => $refund
            ], $currentUser);

            $success = "Organization details, Terms & Conditions, and Cancellation Policy updated successfully!";
            $client = ClientService::getClient($clientId);
            $currentTerms = $client['terms_and_conditions'] ?? '';
            $currentRefund = $client['cancellation_policy'] ?? '';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Edit Client Tenant: <?= e($client['name']) ?></h4>
        <p class="text-muted small mb-0">Update organization settings, credentials, legal Terms & Conditions, and Cancellation/Refund Policy.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/<?= e($client['slug']) ?>/" target="_blank" class="btn btn-outline-primary btn-sm shadow-xs d-inline-flex align-items-center gap-1.5">
            <i data-lucide="external-link" style="width:14px;height:14px;"></i> View Public Space
        </a>
        <a href="/admin/clients" class="btn btn-outline-secondary btn-sm shadow-xs">Back to List</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2 shadow-xs">
        <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
        <span><?= e($error) ?></span>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2 shadow-xs">
        <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
        <span><?= e($success) ?></span>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/clients/edit?id=<?= e($client['id']) ?>" id="clientEditForm" class="card card-dark p-3 p-md-4 shadow-xs mb-5">
    <?= csrfInput() ?>

    <!-- 1. Organization Details -->
    <div class="border-bottom pb-2 mb-3">
        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2" style="color:#0f172a;">
            <i data-lucide="building" class="text-primary" style="width:18px;height:18px;"></i> 1. Organization Profile
        </h5>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Brand / Display Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($client['name']) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-medium text-secondary">Tenant Code</label>
            <input type="text" class="form-control font-monospace bg-light" value="<?= e($client['code']) ?>" disabled>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-medium text-secondary">Status</label>
            <select name="status" class="form-select">
                <option value="active" <?= ($client['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($client['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label small fw-medium text-secondary">Registered Legal Company Name</label>
            <input type="text" name="company_name" class="form-control" value="<?= e($client['company_name']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Official Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= e($client['email']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-medium text-secondary">Support Mobile / WhatsApp</label>
            <input type="text" name="mobile" class="form-control" value="<?= e($client['mobile']) ?>">
        </div>
        <div class="col-12">
            <label class="form-label small fw-medium text-secondary">Physical Registered Address</label>
            <textarea name="address" class="form-control" rows="2"><?= e($client['address']) ?></textarea>
        </div>
    </div>

    <!-- 2. Terms & Conditions and Cancellation/Refund Policy Section (CKEditor Popups) -->
    <div class="border-bottom pb-2 mb-3">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
            <div>
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2" style="color:#0f172a;">
                    <i data-lucide="shield-check" class="text-primary" style="width:18px;height:18px;"></i> 2. Terms & Conditions & Cancellation/Refund Policy
                </h5>
                <p class="text-muted text-xs mb-0 mt-0.5" style="font-size:12px;">Manage organizational terms and refund rules using rich-text popup editors powered by CKEditor.</p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Terms & Conditions Button & Card -->
        <div class="col-md-6">
            <div class="card p-3 border rounded-3 bg-light bg-opacity-40 h-100 d-flex flex-column justify-content-between shadow-xs">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                            <i data-lucide="file-text" class="text-primary" style="width:16px;height:16px;"></i> Terms & Conditions
                        </span>
                        <span id="termsStatusBadge" class="badge <?= !empty($currentTerms) ? 'bg-success bg-opacity-15 text-success border border-success border-opacity-25' : 'bg-secondary bg-opacity-25 text-dark border' ?> text-xs">
                            <?= !empty($currentTerms) ? 'Configured' : 'Not Set' ?>
                        </span>
                    </div>
                    <p class="text-muted text-xs mb-2" style="font-size:12px;">
                        Event entry requirements, code of conduct, liability waiver, and attendee admission rules.
                    </p>
                    <div id="termsPreviewBox" class="p-2 border rounded-2 bg-white text-muted text-xs mb-3 font-monospace" style="max-height: 65px; overflow: hidden; line-height: 1.4; font-size: 11px;">
                        <?= !empty($currentTerms) ? htmlspecialchars(mb_substr(strip_tags($currentTerms), 0, 150)) . '...' : '(No terms entered yet - click below to add)' ?>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-xs w-100 py-2" data-bs-toggle="modal" data-bs-target="#termsModal">
                    <i data-lucide="edit-3" style="width:14px;height:14px;"></i> Edit Terms & Conditions (Popup)
                </button>
            </div>
        </div>

        <!-- Cancellation & Refund Policy Button & Card -->
        <div class="col-md-6">
            <div class="card p-3 border rounded-3 bg-light bg-opacity-40 h-100 d-flex flex-column justify-content-between shadow-xs">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                            <i data-lucide="refresh-cw" class="text-danger" style="width:16px;height:16px;"></i> Cancellation & Refund Policy
                        </span>
                        <span id="refundStatusBadge" class="badge <?= !empty($currentRefund) ? 'bg-success bg-opacity-15 text-success border border-success border-opacity-25' : 'bg-secondary bg-opacity-25 text-dark border' ?> text-xs">
                            <?= !empty($currentRefund) ? 'Configured' : 'Not Set' ?>
                        </span>
                    </div>
                    <p class="text-muted text-xs mb-2" style="font-size:12px;">
                        Ticket cancellation policy, event postponement rules, refund timelines, and pass transfers.
                    </p>
                    <div id="refundPreviewBox" class="p-2 border rounded-2 bg-white text-muted text-xs mb-3 font-monospace" style="max-height: 65px; overflow: hidden; line-height: 1.4; font-size: 11px;">
                        <?= !empty($currentRefund) ? htmlspecialchars(mb_substr(strip_tags($currentRefund), 0, 150)) . '...' : '(No refund policy entered yet - click below to add)' ?>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-xs w-100 py-2" data-bs-toggle="modal" data-bs-target="#refundModal">
                    <i data-lucide="edit-3" style="width:14px;height:14px;"></i> Edit Cancellation / Refund Policy (Popup)
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden form textareas that will be submitted -->
    <textarea name="terms_and_conditions" id="terms_and_conditions" class="d-none"><?= htmlspecialchars($currentTerms) ?></textarea>
    <textarea name="cancellation_policy" id="cancellation_policy" class="d-none"><?= htmlspecialchars($currentRefund) ?></textarea>

    <div class="pt-3 border-top d-flex justify-content-end gap-2">
        <a href="/admin/clients" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary fw-semibold px-4 shadow-sm">
            Save Company Details & Policies &rarr;
        </button>
    </div>
</form>

<!-- POPUP MODAL 1: Terms & Conditions with CKEditor -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-bottom">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="termsModalLabel">
                        <i data-lucide="file-text" class="text-primary" style="width:18px;height:18px;"></i>
                        Company Terms & Conditions
                    </h5>
                    <p class="text-muted text-xs mb-0 mt-0.5" style="font-size:11.5px;">Rich text editor for attendee rules, entrance terms, and company disclaimers.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-light border small py-2 px-3 mb-2 text-muted" style="font-size:12px;">
                    <i data-lucide="info" style="width:14px;height:14px;vertical-align:-2px;" class="me-1 text-primary"></i>
                    Format your terms with headings, bullet lists, bold emphasis, and tables. These will appear on attendee booking portals.
                </div>
                <div id="termsEditorWrapper">
                    <textarea id="terms_editor_input"><?= htmlspecialchars($currentTerms) ?></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel / Close</button>
                <button type="button" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1.5" id="btnSaveTerms">
                    <i data-lucide="check" style="width:15px;height:15px;"></i> Apply & Save Terms
                </button>
            </div>
        </div>
    </div>
</div>

<!-- POPUP MODAL 2: Cancellation & Refund Policy with CKEditor -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow">
            <div class="modal-header bg-light border-bottom">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="refundModalLabel">
                        <i data-lucide="refresh-cw" class="text-danger" style="width:18px;height:18px;"></i>
                        Cancellation & Refund Policy
                    </h5>
                    <p class="text-muted text-xs mb-0 mt-0.5" style="font-size:11.5px;">Rich text editor for pass cancellation windows, refund timelines, and exceptions.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-light border small py-2 px-3 mb-2 text-muted" style="font-size:12px;">
                    <i data-lucide="info" style="width:14px;height:14px;vertical-align:-2px;" class="me-1 text-danger"></i>
                    Define clear refund policies for cancellations, event postponements, or emergency rescheduling.
                </div>
                <div id="refundEditorWrapper">
                    <textarea id="refund_editor_input"><?= htmlspecialchars($currentRefund) ?></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel / Close</button>
                <button type="button" class="btn btn-danger btn-sm fw-semibold d-inline-flex align-items-center gap-1.5" id="btnSaveRefund">
                    <i data-lucide="check" style="width:15px;height:15px;"></i> Apply & Save Policy
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CKEditor 5 Classic Build -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.3.1/classic/ckeditor.js"></script>

<style>
    .ck-editor__editable_inline {
        min-height: 260px;
        max-height: 480px;
        background-color: #ffffff !important;
        color: #0f172a !important;
        font-size: 14px;
        line-height: 1.6;
        border-bottom-left-radius: 8px !important;
        border-bottom-right-radius: 8px !important;
    }
    .ck.ck-toolbar {
        background-color: #f8fafc !important;
        border-top-left-radius: 8px !important;
        border-top-right-radius: 8px !important;
        border-color: #cbd5e1 !important;
    }
</style>

<script>
// CKEditor Instances for Terms and Refund Modals
let termsEditorInstance = null;
let refundEditorInstance = null;

const ckConfig = {
    toolbar: [
        'heading', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'bulletedList', 'numberedList', 'blockQuote', '|',
        'insertTable', '|',
        'undo', 'redo'
    ],
    heading: {
        options: [
            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
            { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
        ]
    }
};

// Initialize Terms Editor when modal opens
const termsModalEl = document.getElementById('termsModal');
termsModalEl.addEventListener('shown.bs.modal', function () {
    if (!termsEditorInstance) {
        ClassicEditor
            .create(document.querySelector('#terms_editor_input'), ckConfig)
            .then(editor => {
                termsEditorInstance = editor;
            })
            .catch(error => {
                console.error('Error initializing Terms CKEditor:', error);
            });
    }
});

// Initialize Refund Editor when modal opens
const refundModalEl = document.getElementById('refundModal');
refundModalEl.addEventListener('shown.bs.modal', function () {
    if (!refundEditorInstance) {
        ClassicEditor
            .create(document.querySelector('#refund_editor_input'), ckConfig)
            .then(editor => {
                refundEditorInstance = editor;
            })
            .catch(error => {
                console.error('Error initializing Refund CKEditor:', error);
            });
    }
});

function stripHtml(html) {
    const tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || "";
}

// Save Terms from Modal
document.getElementById('btnSaveTerms').addEventListener('click', function() {
    if (termsEditorInstance) {
        const data = termsEditorInstance.getData();
        document.getElementById('terms_and_conditions').value = data;
        const plain = stripHtml(data).substring(0, 150);
        document.getElementById('termsPreviewBox').textContent = plain ? (plain + '...') : '(Empty)';
        document.getElementById('termsStatusBadge').textContent = 'Saved (' + plain.split(/\s+/).filter(Boolean).length + ' words)';
        document.getElementById('termsStatusBadge').className = 'badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 text-xs';
    }
    const modal = bootstrap.Modal.getInstance(termsModalEl);
    if (modal) modal.hide();
});

// Save Refund from Modal
document.getElementById('btnSaveRefund').addEventListener('click', function() {
    if (refundEditorInstance) {
        const data = refundEditorInstance.getData();
        document.getElementById('cancellation_policy').value = data;
        const plain = stripHtml(data).substring(0, 150);
        document.getElementById('refundPreviewBox').textContent = plain ? (plain + '...') : '(Empty)';
        document.getElementById('refundStatusBadge').textContent = 'Saved (' + plain.split(/\s+/).filter(Boolean).length + ' words)';
        document.getElementById('refundStatusBadge').className = 'badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 text-xs';
    }
    const modal = bootstrap.Modal.getInstance(refundModalEl);
    if (modal) modal.hide();
});

// Sync on form submit
document.getElementById('clientEditForm').addEventListener('submit', function() {
    if (termsEditorInstance) {
        document.getElementById('terms_and_conditions').value = termsEditorInstance.getData();
    }
    if (refundEditorInstance) {
        document.getElementById('cancellation_policy').value = refundEditorInstance.getData();
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
