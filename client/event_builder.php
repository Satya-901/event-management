<?php
$pageTitle = "Event Form Builder";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/EventService.php';

$clientId = getCurrentClientId();
$eventId = $_GET['id'] ?? '';

$event = EventService::getEvent($eventId, $clientId);
if (!$event) {
    redirect('/403.php');
}

$fields = EventService::getFormFields($eventId);
$error = null;
$success = null;

// Handle Form Builder Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token invalid.";
    } else {
        $labels = $_POST['field_label'] ?? [];
        $types = $_POST['field_type'] ?? [];
        $placeholders = $_POST['placeholder'] ?? [];
        $requireds = $_POST['required'] ?? [];

        $newFields = [];
        for ($i = 0; $i < count($labels); $i++) {
            $lbl = trim($labels[$i] ?? '');
            if (!empty($lbl)) {
                $newFields[] = [
                    'field_key' => slugify($lbl),
                    'field_label' => $lbl,
                    'field_type' => $types[$i] ?? 'text',
                    'placeholder' => $placeholders[$i] ?? '',
                    'required' => isset($requireds[$i]) ? 1 : 0
                ];
            }
        }

        EventService::saveFormFields($eventId, $newFields, $clientId);
        $success = "Booking form fields updated successfully!";
        $fields = EventService::getFormFields($eventId);
    }
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Dynamic Form Builder</h4>
        <p class="text-muted small mb-0">Customize registration questions and pass requirements for <strong><?= e($event['name']) ?></strong>.</p>
    </div>
    <div class="d-flex gap-2 w-100 w-sm-auto justify-content-start justify-content-sm-end">
        <a href="/<?= e($currentClient['slug']) ?>/<?= e($event['slug']) ?>/#bookingSection" target="_blank" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i data-lucide="eye" style="width:14px;height:14px;"></i> Test Live Form
        </a>
        <a href="/client/events" class="btn btn-light border btn-sm shadow-xs">Return to Events</a>
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
    <!-- Form Fields Editor -->
    <div class="col-lg-7">
        <form method="POST" action="/client/events/builder?id=<?= e($eventId) ?>">
            <?= csrfInput() ?>

            <div class="card p-3 p-md-4 mb-3 shadow-xs">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0" style="color:#0f172a;">Custom Questions & Form Fields</h6>
                    <button type="button" class="btn btn-warning btn-sm fw-semibold text-white d-inline-flex align-items-center gap-1.5 shadow-xs" id="addFieldBtn" style="background-color:#ea580c !important; border-color:#ea580c !important;">
                        <i data-lucide="plus" style="width:14px;height:14px;"></i> Add New Question
                    </button>
                </div>

                <div id="fieldsContainer">
                    <?php if (empty($fields)): ?>
                        <div class="text-center py-4 text-muted small" id="noFieldsMsg">
                            No custom questions defined. Click "+ Add New Question" above.
                        </div>
                    <?php endif; ?>

                    <?php foreach ($fields as $index => $f): ?>
                        <div class="card p-3 bg-light border mb-2 field-row">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-5">
                                    <label class="form-label text-xs fw-semibold mb-1 text-secondary" style="font-size:11px;">Field Label / Question</label>
                                    <input type="text" name="field_label[]" class="form-control form-control-sm field-label-input" value="<?= e($f['field_label']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-xs fw-semibold mb-1 text-secondary" style="font-size:11px;">Input Type</label>
                                    <select name="field_type[]" class="form-select form-select-sm">
                                        <option value="text" <?= $f['field_type'] === 'text' ? 'selected' : '' ?>>Text</option>
                                        <option value="email" <?= $f['field_type'] === 'email' ? 'selected' : '' ?>>Email</option>
                                        <option value="phone" <?= $f['field_type'] === 'phone' ? 'selected' : '' ?>>Phone</option>
                                        <option value="number" <?= $f['field_type'] === 'number' ? 'selected' : '' ?>>Number</option>
                                        <option value="date" <?= $f['field_type'] === 'date' ? 'selected' : '' ?>>Date</option>
                                        <option value="textarea" <?= $f['field_type'] === 'textarea' ? 'selected' : '' ?>>Long Text</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-xs fw-semibold mb-1 text-secondary" style="font-size:11px;">Placeholder</label>
                                    <input type="text" name="placeholder[]" class="form-control form-control-sm" value="<?= e($f['placeholder'] ?? '') ?>" placeholder="e.g. Type here...">
                                </div>
                                <div class="col-md-1 text-end pt-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm p-1 delete-field-btn" title="Remove Field">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                                <div class="col-12 mt-1">
                                    <div class="form-check form-check-inline small">
                                        <input class="form-check-input" type="checkbox" name="required[<?= $index ?>]" value="1" <?= !empty($f['required']) ? 'checked' : '' ?>>
                                        <label class="form-check-label text-muted" style="font-size:12px;">Mandatory (Required to Book)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-3 pt-3 border-top text-end">
                    <button type="submit" class="btn btn-warning px-4 py-2 fw-semibold text-white shadow-xs" style="background-color:#ea580c !important; border-color:#ea580c !important;">
                        Save Form Configuration
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Preview -->
    <div class="col-lg-5">
        <div class="card p-4 shadow-xs sticky-top" style="top: 20px;">
            <div class="badge bg-light text-secondary border align-self-start mb-2 font-monospace" style="font-size:11px;">Attendee View Preview</div>
            <h5 class="fw-bold mb-1" style="color:#0f172a;">Pass Reservation Form</h5>
            <p class="text-muted small mb-3">Live preview of how questions appear to customers.</p>

            <div class="border rounded-3 p-3 bg-light">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" placeholder="e.g. Rahul Sharma" disabled>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control form-control-sm" placeholder="you@example.com" disabled>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Phone Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" placeholder="+91 98765 43210" disabled>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Number of Passes <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" disabled>
                        <option>1 Pass</option>
                    </select>
                </div>

                <!-- Custom Fields Preview Container -->
                <div id="previewCustomFields">
                    <?php foreach ($fields as $f): ?>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">
                                <?= e($f['field_label']) ?> <?= !empty($f['required']) ? '<span class="text-danger">*</span>' : '' ?>
                            </label>
                            <input type="<?= e($f['field_type'] === 'textarea' ? 'text' : $f['field_type']) ?>" class="form-control form-control-sm" placeholder="<?= e($f['placeholder']) ?>" disabled>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-3">
                    <button class="btn btn-warning w-100 btn-sm fw-bold text-dark" disabled>
                        Confirm & Generate QR Pass
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('addFieldBtn').addEventListener('click', function() {
    const noMsg = document.getElementById('noFieldsMsg');
    if (noMsg) noMsg.remove();

    const container = document.getElementById('fieldsContainer');
    const index = container.querySelectorAll('.field-row').length;

    const row = document.createElement('div');
    row.className = 'card p-3 bg-light border mb-2 field-row';
    row.innerHTML = `
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="form-label text-xs fw-semibold mb-1" style="font-size:11px;">Field Label / Question</label>
                <input type="text" name="field_label[]" class="form-control form-control-sm field-label-input" placeholder="e.g. Dietary Preference" required>
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs fw-semibold mb-1" style="font-size:11px;">Input Type</label>
                <select name="field_type[]" class="form-select form-select-sm">
                    <option value="text">Text</option>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="textarea">Long Text</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs fw-semibold mb-1" style="font-size:11px;">Placeholder</label>
                <input type="text" name="placeholder[]" class="form-control form-control-sm" placeholder="e.g. Type here...">
            </div>
            <div class="col-md-1 text-end pt-3">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 delete-field-btn" title="Remove Field">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                </button>
            </div>
            <div class="col-12 mt-1">
                <div class="form-check form-check-inline small">
                    <input class="form-check-input" type="checkbox" name="required[${index}]" value="1">
                    <label class="form-check-label text-muted" style="font-size:12px;">Mandatory (Required to Book)</label>
                </div>
            </div>
        </div>
    `;
    container.appendChild(row);
    lucide.createIcons();
    bindDeleteBtns();
});

function bindDeleteBtns() {
    document.querySelectorAll('.delete-field-btn').forEach(btn => {
        btn.onclick = function() {
            this.closest('.field-row').remove();
        };
    });
}
bindDeleteBtns();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
