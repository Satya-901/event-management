<?php
$pageTitle = "Create New Event";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/EventService.php';

$clientId = getCurrentClientId();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF validation failed.";
    } else {
        try {
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? 'Festival & Cultural');
            $startDate = trim($_POST['start_date'] ?? '');
            $startTime = trim($_POST['start_time'] ?? '18:00');
            $venueName = trim($_POST['venue_name'] ?? '');
            $city = trim($_POST['city'] ?? 'Bengaluru');
            $capacity = max(1, (int)($_POST['max_capacity'] ?? 500));

            if (empty($name) || empty($startDate) || empty($venueName)) {
                throw new Exception("Please fill in event name, date, and venue.");
            }

            $newEvent = EventService::createEvent([
                'client_id' => $clientId,
                'name' => $name,
                'category' => $category,
                'short_description' => trim($_POST['short_description'] ?? ''),
                'full_description' => trim($_POST['full_description'] ?? ''),
                'banner' => trim($_POST['banner'] ?? ''),
                'start_date' => $startDate,
                'start_time' => $startTime,
                'end_date' => trim($_POST['end_date'] ?? $startDate),
                'end_time' => trim($_POST['end_time'] ?? '23:00'),
                'venue_name' => $venueName,
                'address' => trim($_POST['address'] ?? ''),
                'city' => $city,
                'google_maps_url' => trim($_POST['google_maps_url'] ?? ''),
                'max_capacity' => $capacity,
                'available_seats' => $capacity,
                'price_label' => trim($_POST['price_label'] ?? 'Standard Pass'),
                'price_amount' => (float)($_POST['price_amount'] ?? 0),
                'booking_open' => isset($_POST['booking_open']) ? 1 : 0,
                'status' => 'published'
            ], $currentUser);

            redirect('/client/events/builder?id=' . $newEvent['id']);
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Create New Event</h4>
        <p class="text-muted small mb-0">Set up event details, dates, venue, and ticketing capacity.</p>
    </div>
    <a href="/client/events" class="btn btn-outline-secondary btn-sm shadow-xs">Cancel & Return</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
        <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
        <span><?= e($error) ?></span>
    </div>
<?php endif; ?>

<form method="POST" action="/client/events/create" class="card p-3 p-md-4 shadow-xs">
    <?= csrfInput() ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label small fw-semibold text-secondary">Event Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Annual Grand Celebration 2026" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Event Category</label>
            <select name="category" class="form-select">
                <option value="Festival & Cultural">Festival & Cultural</option>
                <option value="Music Concert">Music Concert</option>
                <option value="Gala & Celebrations">Gala & Celebrations</option>
                <option value="Conference & Expo">Conference & Expo</option>
                <option value="Sports & Fitness">Sports & Fitness</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold text-secondary">Short Teaser Description</label>
            <input type="text" name="short_description" class="form-control" placeholder="A brief one-line summary for event cards">
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold text-secondary">Full Event Details / About</label>
            <textarea name="full_description" class="form-control" rows="4" placeholder="Detailed schedule, music bands, food arrangements, rules..."></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Banner Image URL</label>
            <input type="url" name="banner" class="form-control" placeholder="https://images.unsplash.com/photo-..." value="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=80">
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">Total Capacity (Passes) <span class="text-danger">*</span></label>
            <input type="number" name="max_capacity" class="form-control" value="1000" min="1" required>
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">Pass Price (₹)</label>
            <input type="number" name="price_amount" class="form-control" value="0" min="0" step="0.01">
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="18:30" required>
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">End Time</label>
            <input type="time" name="end_time" class="form-control" value="23:30">
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Venue Name <span class="text-danger">*</span></label>
            <input type="text" name="venue_name" class="form-control" placeholder="e.g. Palace Grounds, Gayatri Vihar" required>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">City</label>
            <input type="text" name="city" class="form-control" value="Bengaluru">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Google Maps URL</label>
            <input type="url" name="google_maps_url" class="form-control" placeholder="https://maps.google.com/...">
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold text-secondary">Venue Full Street Address</label>
            <input type="text" name="address" class="form-control" placeholder="Door #, Street, Locality">
        </div>

        <div class="col-12">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="booking_open" id="bookingOpenSwitch" checked>
                <label class="form-check-label fw-semibold text-dark" for="bookingOpenSwitch">Accept Online Registrations Immediately</label>
            </div>
        </div>

        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
            <a href="/client/events" class="btn btn-light border">Cancel</a>
            <button type="submit" class="btn btn-warning fw-semibold text-white px-4 shadow-xs" style="background-color:#ea580c !important; border-color:#ea580c !important;">
                Save & Configure Booking Form &rarr;
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
