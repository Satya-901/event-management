<?php
$pageTitle = "Organizer Profile & Settings";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$clientId = getCurrentClientId();
$currentClient = ClientService::getClient($clientId);

$error = null;
$success = null;

$currentTerms = $currentClient['terms_and_conditions'] ?? '';
$currentRefund = $currentClient['cancellation_policy'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF verification failed.";
    } else {
        try {
            $companyName = trim($_POST['company_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $terms = trim($_POST['terms_and_conditions'] ?? '');
            $refund = trim($_POST['cancellation_policy'] ?? '');

            if (empty($companyName) || empty($email)) {
                throw new Exception("Company name and contact email are required.");
            }

            ClientService::updateClient($clientId, [
                'company_name' => $companyName,
                'email' => $email,
                'mobile' => $mobile,
                'address' => $address,
                'terms_and_conditions' => $terms,
                'cancellation_policy' => $refund
            ], $currentUser);

            $success = "Organizer profile, Terms & Conditions, and Cancellation Policy updated successfully!";
            $currentClient = ClientService::getClient($clientId);
            $currentTerms = $currentClient['terms_and_conditions'] ?? '';
            $currentRefund = $currentClient['cancellation_policy'] ?? '';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Organizer Space Settings</h4>
        <p class="text-muted small mb-0">Manage organization credentials, public presence, contact info, and legal customer policies.</p>
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

<div class="row g-3 g-md-4 mb-5">
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

            <div class="border-top pt-3 text-start">
                <div class="small fw-semibold text-secondary mb-2">Policy Status:</div>
                <div class="d-flex justify-content-between align-items-center mb-1.5 small">
                    <span>Terms & Conditions</span>
                    <span class="badge <?= !empty($currentTerms) ? 'bg-success' : 'bg-secondary' ?> text-xs">
                        <?= !empty($currentTerms) ? 'Configured' : 'Not Set' ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                    <span>Cancellation Policy</span>
                    <span class="badge <?= !empty($currentRefund) ? 'bg-success' : 'bg-secondary' ?> text-xs">
                        <?= !empty($currentRefund) ? 'Configured' : 'Not Set' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form method="POST" action="/client/profile" id="clientProfileForm" class="card p-4 shadow-xs">
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
                    <textarea name="address" class="form-control" rows="2"><?= e($currentClient['address']) ?></textarea>
                </div>

                <!-- Terms & Conditions and Cancellation/Refund Policy Section -->
                <div class="col-12 border-top pt-3 mt-3">
                    <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i data-lucide="shield-check" class="text-warning" style="width:18px;height:18px;"></i>
                        Customer Legal Policies
                    </h6>
                    <p class="text-muted text-xs mb-3" style="font-size:12px;">Configure binding event admission terms and pass refund policies using popup editors with CKEditor.</p>
                </div>

                <div class="col-md-6">
                    <div class="card p-3 border bg-light bg-opacity-40 rounded-3 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                                    <i data-lucide="file-text" class="text-primary" style="width:15px;height:15px;"></i> Terms & Conditions
                                </span>
                                <span id="profileTermsStatus" class="badge <?= !empty($currentTerms) ? 'bg-success bg-opacity-15 text-success border border-success border-opacity-25' : 'bg-secondary bg-opacity-25 text-dark border' ?> text-xs">
                                    <?= !empty($currentTerms) ? 'Configured' : 'Not Set' ?>
                                </span>
                            </div>
                            <div id="profileTermsPreview" class="p-2 border rounded bg-white text-muted text-xs mb-2 font-monospace" style="max-height: 55px; overflow: hidden; font-size:11px;">
                                <?= !empty($currentTerms) ? htmlspecialchars(mb_substr(strip_tags($currentTerms), 0, 120)) . '...' : '(No terms entered yet)' ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-xs w-100" data-bs-toggle="modal" data-bs-target="#termsModal">
                            <i data-lucide="edit-3" style="width:14px;height:14px;"></i> Edit Terms & Conditions
                        </button>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3 border bg-light bg-opacity-40 rounded-3 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                                    <i data-lucide="refresh-cw" class="text-danger" style="width:15px;height:15px;"></i> Cancellation Policy
                                </span>
                                <span id="profileRefundStatus" class="badge <?= !empty($currentRefund) ? 'bg-success bg-opacity-15 text-success border border-success border-opacity-25' : 'bg-secondary bg-opacity-25 text-dark border' ?> text-xs">
                                    <?= !empty($currentRefund) ? 'Configured' : 'Not Set' ?>
                                </span>
                            </div>
                            <div id="profileRefundPreview" class="p-2 border rounded bg-white text-muted text-xs mb-2 font-monospace" style="max-height: 55px; overflow: hidden; font-size:11px;">
                                <?= !empty($currentRefund) ? htmlspecialchars(mb_substr(strip_tags($currentRefund), 0, 120)) . '...' : '(No refund policy entered yet)' ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-xs w-100" data-bs-toggle="modal" data-bs-target="#refundModal">
                            <i data-lucide="edit-3" style="width:14px;height:14px;"></i> Edit Cancellation Policy
                        </button>
                    </div>
                </div>

                <!-- Hidden textareas -->
                <textarea name="terms_and_conditions" id="terms_and_conditions" class="d-none"><?= htmlspecialchars($currentTerms) ?></textarea>
                <textarea name="cancellation_policy" id="cancellation_policy" class="d-none"><?= htmlspecialchars($currentRefund) ?></textarea>

                <div class="col-12 mt-3 pt-3 border-top text-end">
                    <button type="submit" class="btn btn-warning px-4 py-2 fw-semibold text-white shadow-xs" style="background-color:#ea580c !important; border-color:#ea580c !important;">
                        Save Organizer Settings & Policies
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

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
        const plain = stripHtml(data).substring(0, 120);
        document.getElementById('profileTermsPreview').textContent = plain ? (plain + '...') : '(Empty)';
        document.getElementById('profileTermsStatus').textContent = 'Saved (' + plain.split(/\s+/).filter(Boolean).length + ' words)';
        document.getElementById('profileTermsStatus').className = 'badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 text-xs';
    }
    const modal = bootstrap.Modal.getInstance(termsModalEl);
    if (modal) modal.hide();
});

// Save Refund from Modal
document.getElementById('btnSaveRefund').addEventListener('click', function() {
    if (refundEditorInstance) {
        const data = refundEditorInstance.getData();
        document.getElementById('cancellation_policy').value = data;
        const plain = stripHtml(data).substring(0, 120);
        document.getElementById('profileRefundPreview').textContent = plain ? (plain + '...') : '(Empty)';
        document.getElementById('profileRefundStatus').textContent = 'Saved (' + plain.split(/\s+/).filter(Boolean).length + ' words)';
        document.getElementById('profileRefundStatus').className = 'badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 text-xs';
    }
    const modal = bootstrap.Modal.getInstance(refundModalEl);
    if (modal) modal.hide();
});

// Sync on form submit
document.getElementById('clientProfileForm').addEventListener('submit', function() {
    if (termsEditorInstance) {
        document.getElementById('terms_and_conditions').value = termsEditorInstance.getData();
    }
    if (refundEditorInstance) {
        document.getElementById('cancellation_policy').value = refundEditorInstance.getData();
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
