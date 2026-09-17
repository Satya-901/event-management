<?php
$pageTitle = "Edit Event";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/EventService.php';

$clientId = getCurrentClientId();
$eventId = $_GET['id'] ?? '';

// STRICT TENANT OWNERSHIP CHECK
$event = EventService::getEvent($eventId, $clientId);
if (!$event) {
    redirect('/403.php');
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF verification failed.";
    } else {
        try {
            $name = trim($_POST['name'] ?? '');
            $startDate = trim($_POST['start_date'] ?? '');
            $venueName = trim($_POST['venue_name'] ?? '');

            if (empty($name) || empty($startDate) || empty($venueName)) {
                throw new Exception("Event name, start date, and venue name are required.");
            }

            EventService::updateEvent($eventId, [
                'name' => $name,
                'category' => trim($_POST['category'] ?? 'Festival & Cultural'),
                'short_description' => trim($_POST['short_description'] ?? ''),
                'full_description' => trim($_POST['full_description'] ?? ''),
                'banner' => trim($_POST['banner'] ?? ''),
                'start_date' => $startDate,
                'start_time' => trim($_POST['start_time'] ?? '18:00'),
                'end_date' => trim($_POST['end_date'] ?? $startDate),
                'end_time' => trim($_POST['end_time'] ?? '23:00'),
                'venue_name' => $venueName,
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? 'Bengaluru'),
                'google_maps_url' => trim($_POST['google_maps_url'] ?? ''),
                'price_label' => trim($_POST['price_label'] ?? 'Standard Pass'),
                'price_amount' => (float)($_POST['price_amount'] ?? 0),
                'booking_open' => isset($_POST['booking_open']) ? 1 : 0,
                'status' => trim($_POST['status'] ?? 'published')
            ], $clientId, $currentUser);

            $success = "Event details updated successfully!";
            $event = EventService::getEvent($eventId, $clientId);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Edit: <?= e($event['name']) ?></h4>
        <p class="text-muted small mb-0">Update event details, timing, venue, and public status.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/<?= e($currentClient['slug']) ?>/<?= e($event['slug']) ?>/" target="_blank" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
            <i data-lucide="external-link" style="width:14px;height:14px;"></i> View Public Page
        </a>
        <a href="/client/events/builder?id=<?= e($event['id']) ?>" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
            <i data-lucide="sliders" style="width:14px;height:14px;"></i> Form Builder
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success py-2 small mb-3"><?= e($success) ?></div>
<?php endif; ?>

<form method="POST" action="/client/events/edit?id=<?= e($event['id']) ?>" class="card p-4 bg-white shadow-sm">
    <?= csrfInput() ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold">Event Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($event['name']) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" class="form-select">
                <option value="published" <?= ($event['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published (Visible)</option>
                <option value="draft" <?= ($event['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                <option value="closed" <?= ($event['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed / Finished</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold">Short Summary</label>
            <input type="text" name="short_description" class="form-control" value="<?= e($event['short_description']) ?>">
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold">Full Description</label>
            <textarea name="full_description" class="form-control" rows="4"><?= e($event['full_description']) ?></textarea>
        </div>

        <div class="col-md-8">
            <label class="form-label small fw-semibold">Banner Image URL</label>
            <input type="url" name="banner" class="form-control" value="<?= e($event['banner']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Ticket Price (₹)</label>
            <input type="number" name="price_amount" class="form-control" value="<?= e($event['price_amount'] ?? 0) ?>" step="0.01">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($event['start_date']) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="<?= e($event['start_time']) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($event['end_date']) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">End Time</label>
            <input type="time" name="end_time" class="form-control" value="<?= e($event['end_time']) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-semibold">Venue Name</label>
            <input type="text" name="venue_name" class="form-control" value="<?= e($event['venue_name']) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">City</label>
            <input type="text" name="city" class="form-control" value="<?= e($event['city']) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Google Maps URL</label>
            <input type="url" name="google_maps_url" class="form-control" value="<?= e($event['google_maps_url'] ?? '') ?>">
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold">Full Address</label>
            <input type="text" name="address" class="form-control" value="<?= e($event['address']) ?>">
        </div>

        <div class="col-12">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="booking_open" id="bookingOpenEdit" <?= !empty($event['booking_open']) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="bookingOpenEdit">Online Pass Reservations Open</label>
            </div>
        </div>

        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
            <a href="/client/events" class="btn btn-light border">Return</a>
            <button type="submit" class="btn btn-primary px-4 fw-semibold" style="background:#c2410c; border-color:#c2410c;">
                Save Changes
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
