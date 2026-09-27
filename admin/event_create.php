<?php
$pageTitle = "Create New Event";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/ClientService.php';

$clients = ClientService::getAllClients();
$error = null;
$preselectedClientId = $_GET['client_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF validation failed. Please try again.";
    } else {
        try {
            $clientId = trim($_POST['client_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? 'Festival & Cultural');
            $startDate = trim($_POST['start_date'] ?? '');
            $startTime = trim($_POST['start_time'] ?? '18:00');
            $venueName = trim($_POST['venue_name'] ?? '');
            $city = trim($_POST['city'] ?? 'Bengaluru');
            $capacity = max(1, (int)($_POST['max_capacity'] ?? 500));

            if (empty($clientId)) {
                throw new Exception("Please select a client tenant organization for this event.");
            }
            if (empty($name) || empty($startDate) || empty($venueName)) {
                throw new Exception("Please fill in event name, start date, and venue name.");
            }

            // Process packages
            $rawPackages = $_POST['packages'] ?? [];
            $packages = [];
            $totalPackageCapacity = 0;
            if (is_array($rawPackages)) {
                foreach ($rawPackages as $pkg) {
                    $pkgName = trim($pkg['name'] ?? '');
                    if (empty($pkgName)) continue;
                    $pkgPrice = max(0, (float)($pkg['price'] ?? 0));
                    $pkgCap = max(1, (int)($pkg['capacity'] ?? 100));
                    $totalPackageCapacity += $pkgCap;

                    $packages[] = [
                        'id' => !empty($pkg['id']) ? trim($pkg['id']) : generateId('pkg'),
                        'name' => $pkgName,
                        'price' => $pkgPrice,
                        'capacity' => $pkgCap,
                        'badge' => trim($pkg['badge'] ?? ''),
                        'description' => trim($pkg['description'] ?? '')
                    ];
                }
            }

            // Process Gallery uploads and URLs
            $uploadedGallery = handleGalleryUploads($_FILES['gallery_files'] ?? null);
            $urlGallery = [];
            if (!empty($_POST['gallery_urls']) && is_array($_POST['gallery_urls'])) {
                foreach ($_POST['gallery_urls'] as $gUrl) {
                    $gUrl = trim($gUrl);
                    if (!empty($gUrl)) {
                        $urlGallery[] = $gUrl;
                    }
                }
            }
            $finalGallery = array_values(array_unique(array_filter(array_merge($uploadedGallery, $urlGallery))));
            $showGallery = isset($_POST['show_gallery']) ? 1 : 0;

            // If no packages specified, seed a default package
            if (empty($packages)) {
                $basePrice = max(0, (float)($_POST['price_amount'] ?? 0));
                $baseLabel = trim($_POST['price_label'] ?? 'Standard Pass') ?: 'Standard Pass';
                $packages[] = [
                    'id' => generateId('pkg'),
                    'name' => $baseLabel,
                    'price' => $basePrice,
                    'capacity' => $capacity,
                    'badge' => '',
                    'description' => 'General event entry pass'
                ];
            } else {
                // If packages have explicit capacities that exceed basic capacity, update total capacity
                if ($totalPackageCapacity > $capacity) {
                    $capacity = $totalPackageCapacity;
                }
            }

            $primaryPrice = $packages[0]['price'] ?? 0;
            $primaryLabel = $packages[0]['name'] ?? 'Standard Pass';

            $newEvent = EventService::createEvent([
                'client_id' => $clientId,
                'name' => $name,
                'category' => $category,
                'short_description' => trim($_POST['short_description'] ?? ''),
                'full_description' => trim($_POST['full_description'] ?? ''),
                'banner' => trim($_POST['banner'] ?? 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=80'),
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
                'price_label' => $primaryLabel,
                'price_amount' => $primaryPrice,
                'packages' => $packages,
                'gallery' => $finalGallery,
                'show_gallery' => $showGallery,
                'booking_open' => isset($_POST['booking_open']) ? 1 : 0,
                'status' => 'published'
            ], $currentUser);

            setFlash('success', "Event '{$newEvent['name']}' created successfully with " . count($packages) . " package(s)!");
            redirect('/admin/events');
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
        <p class="text-muted small mb-0">Publish an event under any client organization with rich descriptions and customized packages.</p>
    </div>
    <a href="/admin/events" class="btn btn-outline-secondary btn-sm shadow-xs d-inline-flex align-items-center gap-1.5">
        <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Return to Global Registry
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2 shadow-xs">
        <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
        <span><?= e($error) ?></span>
    </div>
<?php endif; ?>

<?php if (empty($clients)): ?>
    <div class="alert alert-warning py-3 px-4 rounded-3 mb-4 shadow-xs">
        <h6 class="fw-bold mb-1"><i data-lucide="alert-triangle" style="width:18px;height:18px;vertical-align:-3px;" class="me-1"></i> No Client Tenants Found</h6>
        <p class="small mb-2">You need to have at least one client tenant registered before creating an event.</p>
        <a href="/admin/clients/create" class="btn btn-primary btn-sm fw-semibold">Register New Client Tenant</a>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/events/create" id="eventForm" enctype="multipart/form-data" class="card card-dark p-3 p-md-4 mb-5">
    <?= csrfInput() ?>

    <div class="row g-3">
        <!-- 1. Tenant Selection & Basic Info -->
        <div class="col-12 border-bottom pb-2 mb-1">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i data-lucide="building" class="text-primary" style="width:18px;height:18px;"></i> Organization & Event Identity
            </h6>
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Client Tenant Organization <span class="text-danger">*</span></label>
            <select name="client_id" class="form-select" required>
                <option value="">-- Choose Client Tenant --</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= ($preselectedClientId === $c['id'] || count($clients) === 1) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?> (Code: <?= e($c['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Event Category</label>
            <select name="category" class="form-select">
                <option value="Festival & Cultural">Festival & Cultural</option>
                <option value="Music Concert">Music Concert</option>
                <option value="Gala & Celebrations">Gala & Celebrations</option>
                <option value="Conference & Expo">Conference & Expo</option>
                <option value="Sports & Fitness">Sports & Fitness</option>
                <option value="Community & Networking">Community & Networking</option>
                <option value="Food & Nightlife">Food & Nightlife</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold text-secondary">Event Title / Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Utsavam Grand Dandiya Night 2026" required value="<?= e($_POST['name'] ?? '') ?>">
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold text-secondary">Short Teaser Summary</label>
            <input type="text" name="short_description" class="form-control" placeholder="A catchy one-line headline shown in cards and preview banners" value="<?= e($_POST['short_description'] ?? '') ?>">
        </div>

        <!-- 2. CKEditor for Full Description -->
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold text-secondary mb-0">
                    Full Description & Schedule <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1">CKEditor Rich Text</span>
                </label>
                <span class="text-muted text-xs" style="font-size:11px;">Format text with bold, headings, bullet lists, and tables</span>
            </div>
            <textarea name="full_description" id="full_description" class="form-control" rows="8"><?= e($_POST['full_description'] ?? "<h3>About the Celebration</h3>\n<p>Join us for an unforgettable evening of joy, traditional rhythms, and vibrant celebration. Featuring celebrated artists, delicious food stalls, and immersive entertainment for all age groups.</p>\n<h4>Event Schedule & Highlights</h4>\n<ul>\n  <li><strong>06:00 PM:</strong> Welcome & Gates Open</li>\n  <li><strong>07:30 PM:</strong> Grand Musical Performance & Live Stage Show</li>\n  <li><strong>09:30 PM:</strong> Festive Feast & Networking</li>\n</ul>\n<p><strong>Note:</strong> Digital pass verification is mandatory at the entrance.</p>") ?></textarea>
        </div>

        <!-- 3. Banner & Dates -->
        <div class="col-12 border-bottom pt-3 pb-2 mb-1">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i data-lucide="calendar" class="text-primary" style="width:18px;height:18px;"></i> Timing & Venue Details
            </h6>
        </div>

        <div class="col-md-12">
            <label class="form-label small fw-semibold text-secondary">Banner Cover Image URL</label>
            <input type="url" name="banner" class="form-control" placeholder="https://images.unsplash.com/..." value="<?= e($_POST['banner'] ?? 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=80') ?>">
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control" value="<?= e($_POST['start_date'] ?? date('Y-m-d', strtotime('+15 days'))) ?>" required>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="<?= e($_POST['start_time'] ?? '18:30') ?>" required>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($_POST['end_date'] ?? date('Y-m-d', strtotime('+15 days'))) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary">End Time</label>
            <input type="time" name="end_time" class="form-control" value="<?= e($_POST['end_time'] ?? '23:30') ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Venue Name <span class="text-danger">*</span></label>
            <input type="text" name="venue_name" class="form-control" placeholder="e.g. Palace Grounds, Gayatri Vihar" value="<?= e($_POST['venue_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">City</label>
            <input type="text" name="city" class="form-control" value="<?= e($_POST['city'] ?? 'Bengaluru') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Google Maps Link</label>
            <input type="url" name="google_maps_url" class="form-control" placeholder="https://maps.google.com/..." value="<?= e($_POST['google_maps_url'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold text-secondary">Full Street Address</label>
            <input type="text" name="address" class="form-control" placeholder="Door No., Street, Locality, Landmark" value="<?= e($_POST['address'] ?? '') ?>">
        </div>

        <!-- 4. Photo Gallery Showcase Tab & Uploads -->
        <div class="col-12 border-bottom pt-4 pb-2 mb-2">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i data-lucide="image" class="text-primary" style="width:18px;height:18px;"></i> Photo Gallery Showcase
                    </h6>
                    <p class="text-muted text-xs mb-0 mt-0.5" style="font-size:12px;">Display memorable photos and visual celebration memories on the public landing page.</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card p-3 border bg-light bg-opacity-40 rounded-3 shadow-xs">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="show_gallery" id="showGallerySwitch" value="1" <?= isset($_POST['show_gallery']) ? 'checked' : '' ?> onchange="toggleGalleryDisplay(this.checked)">
                    <label class="form-check-label fw-bold text-dark small" for="showGallerySwitch">
                        Show Gallery on Public Event Page
                    </label>
                    <div class="text-muted text-xs">Enable this option and upload/add photos to show the visual gallery on the attendee landing page.</div>
                </div>

                <div id="galleryContentArea" style="<?= isset($_POST['show_gallery']) ? '' : 'display:none;' ?>">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-pills nav-fill mb-3 bg-white p-1 border rounded-3" id="galleryTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active small py-1.5 fw-semibold d-flex align-items-center justify-content-center gap-1.5" id="upload-tab" data-bs-toggle="pill" data-bs-target="#tab-upload" type="button" role="tab">
                                <i data-lucide="upload" style="width:14px;height:14px;"></i> Upload Images from Device
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link small py-1.5 fw-semibold d-flex align-items-center justify-content-center gap-1.5" id="url-tab" data-bs-toggle="pill" data-bs-target="#tab-url" type="button" role="tab">
                                <i data-lucide="link" style="width:14px;height:14px;"></i> Add Image Web URLs
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="galleryTabsContent">
                        <!-- Tab 1: File Upload -->
                        <div class="tab-pane fade show active" id="tab-upload" role="tabpanel">
                            <div class="p-3.5 border-2 border-dashed rounded-3 text-center bg-white" style="border-color: #cbd5e1 !important;">
                                <i data-lucide="upload-cloud" class="text-primary mb-2" style="width:36px;height:36px;"></i>
                                <div class="fw-semibold text-dark small mb-1">Select photo files to upload</div>
                                <div class="text-muted text-xs mb-3">Supports JPG, PNG, WEBP, and GIF (Multiple photos allowed)</div>
                                <input type="file" name="gallery_files[]" id="galleryFileInput" class="form-control form-control-sm mx-auto" style="max-width:380px;" multiple accept="image/*" onchange="previewGalleryUploads(this)">
                            </div>
                            <div id="galleryPreviewContainer" class="row g-2 mt-2"></div>
                        </div>

                        <!-- Tab 2: URL Inputs -->
                        <div class="tab-pane fade" id="tab-url" role="tabpanel">
                            <div id="galleryUrlInputs" class="d-flex flex-column gap-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i data-lucide="image" style="width:14px;height:14px;"></i></span>
                                    <input type="url" name="gallery_urls[]" class="form-control" placeholder="https://images.unsplash.com/photo-...">
                                    <button type="button" class="btn btn-outline-danger" onclick="removeUrlInput(this)"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2 d-inline-flex align-items-center gap-1 text-xs" onclick="addUrlInput()">
                                <i data-lucide="plus" style="width:13px;height:13px;"></i> Add Another Image URL
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Dynamic Packages Builder -->
        <div class="col-12 border-bottom pt-4 pb-2 mb-2">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i data-lucide="layers" class="text-primary" style="width:18px;height:18px;"></i> Event Ticket Packages & Pricing Tiers
                    </h6>
                    <p class="text-muted text-xs mb-0 mt-0.5" style="font-size:12px;">Add multiple packages (e.g. Standard Pass, VIP Experience, Early Bird, Table Booking) with custom prices and perks.</p>
                </div>
                <button type="button" id="btnAddPackage" class="btn btn-outline-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1.5 shadow-xs">
                    <i data-lucide="plus" style="width:15px;height:15px;"></i> Add Another Package
                </button>
            </div>
        </div>

        <div class="col-12">
            <div id="packagesContainer" class="d-flex flex-column gap-3">
                <!-- Package Item 1 -->
                <div class="package-item card p-3 border bg-light bg-opacity-50 position-relative rounded-3 shadow-xs" data-index="0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary text-white text-xs px-2 py-1 package-number-badge">Package #1</span>
                        <button type="button" class="btn btn-link text-danger p-0 text-decoration-none btn-remove-pkg" style="font-size:12px;" onclick="removePackage(this)">
                            <i data-lucide="trash-2" style="width:15px;height:15px;"></i> Remove
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Package Name <span class="text-danger">*</span></label>
                            <input type="text" name="packages[0][name]" class="form-control form-control-sm" placeholder="e.g. Standard Entry Pass" value="Standard Pass" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="packages[0][price]" class="form-control form-control-sm pkg-price-input" min="0" step="0.01" value="499" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="packages[0][capacity]" class="form-control form-control-sm pkg-cap-input" min="1" value="500" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Tag / Badge (Optional)</label>
                            <input type="text" name="packages[0][badge]" class="form-control form-control-sm" placeholder="e.g. Most Popular, Early Bird" value="Most Popular">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Perks & Inclusions Description</label>
                            <input type="text" name="packages[0][description]" class="form-control form-control-sm" placeholder="e.g. General admission to the main event arena + 1 complimentary beverage" value="General admission to main celebration ground & stage performance">
                        </div>
                    </div>
                </div>

                <!-- Package Item 2 (Sample VIP) -->
                <div class="package-item card p-3 border bg-light bg-opacity-50 position-relative rounded-3 shadow-xs" data-index="1">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning text-dark text-xs px-2 py-1 package-number-badge">Package #2</span>
                        <button type="button" class="btn btn-link text-danger p-0 text-decoration-none btn-remove-pkg" style="font-size:12px;" onclick="removePackage(this)">
                            <i data-lucide="trash-2" style="width:15px;height:15px;"></i> Remove
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Package Name <span class="text-danger">*</span></label>
                            <input type="text" name="packages[1][name]" class="form-control form-control-sm" placeholder="e.g. VIP Gold Pass" value="VIP Gold Pass" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="packages[1][price]" class="form-control form-control-sm pkg-price-input" min="0" step="0.01" value="1299" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="packages[1][capacity]" class="form-control form-control-sm pkg-cap-input" min="1" value="150" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Tag / Badge (Optional)</label>
                            <input type="text" name="packages[1][badge]" class="form-control form-control-sm" placeholder="e.g. VIP Access" value="VIP Access">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-xs fw-semibold text-secondary mb-1">Perks & Inclusions Description</label>
                            <input type="text" name="packages[1][description]" class="form-control form-control-sm" placeholder="e.g. Front-row lounge seating + Buffet dinner voucher + Fast-track entry" value="Front-row reserved lounge seating + Buffet dinner voucher + Fast-track entry">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Package Summary Banner -->
            <div class="d-flex justify-content-between align-items-center bg-white border rounded-3 p-2.5 mt-2 text-xs">
                <span class="text-muted"><i data-lucide="info" style="width:14px;height:14px;vertical-align:-2px;" class="me-1"></i> Total Packages Configured: <strong id="packageCountDisplay" class="text-dark">2</strong></span>
                <span class="text-muted">Total Pass Allocation: <strong id="packageCapacityDisplay" class="text-primary">650 passes</strong></span>
            </div>
        </div>

        <!-- 5. Overall Capacity & Registration Settings -->
        <div class="col-12 border-top pt-3 mt-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Total Event Capacity (All Passes Combined) <span class="text-danger">*</span></label>
                    <input type="number" name="max_capacity" id="max_capacity" class="form-control" value="650" min="1" required>
                    <div class="text-muted text-xs mt-1">Automatically aligned with package capacities or set to venue limit.</div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch pt-sm-3">
                        <input class="form-check-input" type="checkbox" name="booking_open" id="bookingOpenSwitch" checked>
                        <label class="form-check-label fw-semibold text-dark small" for="bookingOpenSwitch">Accept Public Registrations Immediately</label>
                        <div class="text-muted text-xs">Guests can reserve their passes immediately once published.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button Row -->
        <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
            <a href="/admin/events" class="btn btn-light border btn-sm">Cancel</a>
            <button type="submit" class="btn btn-primary fw-semibold px-4 shadow-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="check" style="width:16px;height:16px;"></i> Publish Event & Packages
            </button>
        </div>
    </div>
</form>

<!-- CKEditor 5 Classic Build -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.3.1/classic/ckeditor.js"></script>

<style>
    /* Styling for CKEditor inside super admin card */
    .ck-editor__editable_inline {
        min-height: 240px;
        max-height: 550px;
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
    .package-item {
        transition: all 0.2s ease-in-out;
    }
    .package-item:hover {
        border-color: #93c5fd !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08) !important;
    }
</style>

<script>
let editorInstance = null;

// Initialize CKEditor 5
ClassicEditor
    .create(document.querySelector('#full_description'), {
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
    })
    .then(editor => {
        editorInstance = editor;
        editor.model.document.on('change:data', () => {
            document.querySelector('#full_description').value = editor.getData();
        });
    })
    .catch(error => {
        console.error('CKEditor error:', error);
    });

// Sync on form submit
document.getElementById('eventForm').addEventListener('submit', function() {
    if (editorInstance) {
        document.querySelector('#full_description').value = editorInstance.getData();
    }
});

// Dynamic Package Builder Manager
let packageIndexCounter = 2;

function updatePackageSummary() {
    const items = document.querySelectorAll('.package-item');
    document.getElementById('packageCountDisplay').textContent = items.length;
    
    let totalCap = 0;
    items.forEach((item, idx) => {
        const badge = item.querySelector('.package-number-badge');
        if (badge) {
            badge.textContent = `Package #${idx + 1}`;
            if (idx === 0) {
                badge.className = 'badge bg-primary text-white text-xs px-2 py-1 package-number-badge';
            } else if (idx === 1) {
                badge.className = 'badge bg-warning text-dark text-xs px-2 py-1 package-number-badge';
            } else {
                badge.className = 'badge bg-secondary text-white text-xs px-2 py-1 package-number-badge';
            }
        }
        const capInput = item.querySelector('.pkg-cap-input');
        if (capInput) {
            totalCap += parseInt(capInput.value) || 0;
        }
    });
    
    document.getElementById('packageCapacityDisplay').textContent = `${totalCap} passes`;
    const maxCapInput = document.getElementById('max_capacity');
    if (maxCapInput && totalCap > 0) {
        maxCapInput.value = totalCap;
    }
    
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

document.getElementById('btnAddPackage').addEventListener('click', function() {
    const container = document.getElementById('packagesContainer');
    const newIdx = packageIndexCounter++;
    
    const div = document.createElement('div');
    div.className = 'package-item card p-3 border bg-light bg-opacity-50 position-relative rounded-3 shadow-xs';
    div.setAttribute('data-index', newIdx);
    
    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-secondary text-white text-xs px-2 py-1 package-number-badge">Package #${container.children.length + 1}</span>
            <button type="button" class="btn btn-link text-danger p-0 text-decoration-none btn-remove-pkg" style="font-size:12px;" onclick="removePackage(this)">
                <i data-lucide="trash-2" style="width:15px;height:15px;"></i> Remove
            </button>
        </div>
        <div class="row g-2">
            <div class="col-md-5">
                <label class="form-label text-xs fw-semibold text-secondary mb-1">Package Name <span class="text-danger">*</span></label>
                <input type="text" name="packages[${newIdx}][name]" class="form-control form-control-sm" placeholder="e.g. Couples Pass, Early Bird" value="Pass Tier ${container.children.length + 1}" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label text-xs fw-semibold text-secondary mb-1">Price (₹) <span class="text-danger">*</span></label>
                <input type="number" name="packages[${newIdx}][price]" class="form-control form-control-sm pkg-price-input" min="0" step="0.01" value="799" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label text-xs fw-semibold text-secondary mb-1">Capacity <span class="text-danger">*</span></label>
                <input type="number" name="packages[${newIdx}][capacity]" class="form-control form-control-sm pkg-cap-input" min="1" value="100" required>
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs fw-semibold text-secondary mb-1">Tag / Badge (Optional)</label>
                <input type="text" name="packages[${newIdx}][badge]" class="form-control form-control-sm" placeholder="e.g. Limited, Premium" value="">
            </div>
            <div class="col-12">
                <label class="form-label text-xs fw-semibold text-secondary mb-1">Perks & Inclusions Description</label>
                <input type="text" name="packages[${newIdx}][description]" class="form-control form-control-sm" placeholder="e.g. Dedicated entry lane + snacks coupon" value="Entry pass with designated seating zone">
            </div>
        </div>
    `;
    
    container.appendChild(div);
    
    // Attach listener for capacity change
    div.querySelector('.pkg-cap-input').addEventListener('input', updatePackageSummary);
    
    updatePackageSummary();
});

function removePackage(btn) {
    const items = document.querySelectorAll('.package-item');
    if (items.length <= 1) {
        alert("At least one package is required for this event.");
        return;
    }
    const card = btn.closest('.package-item');
    card.remove();
    updatePackageSummary();
}

// Attach input listeners to initial items
document.querySelectorAll('.pkg-cap-input').forEach(input => {
    input.addEventListener('input', updatePackageSummary);
});

// Gallery Functions
function toggleGalleryDisplay(checked) {
    const area = document.getElementById('galleryContentArea');
    if (area) {
        area.style.display = checked ? 'block' : 'none';
    }
}

function previewGalleryUploads(input) {
    const container = document.getElementById('galleryPreviewContainer');
    container.innerHTML = '';
    if (!input.files || input.files.length === 0) return;

    Array.from(input.files).forEach((file, i) => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-4 col-md-3 col-lg-2';
            col.innerHTML = `
                <div class="card h-100 border rounded-3 overflow-hidden shadow-xs position-relative">
                    <img src="${e.target.result}" class="img-fluid" style="height:85px; width:100%; object-fit:cover;">
                    <div class="p-1 text-center bg-white">
                        <span class="text-truncate text-muted d-block" style="font-size:10px;">${file.name}</span>
                    </div>
                </div>
            `;
            container.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
}

function addUrlInput() {
    const container = document.getElementById('galleryUrlInputs');
    const div = document.createElement('div');
    div.className = 'input-group input-group-sm mt-1';
    div.innerHTML = `
        <span class="input-group-text"><i data-lucide="image" style="width:14px;height:14px;"></i></span>
        <input type="url" name="gallery_urls[]" class="form-control" placeholder="https://images.unsplash.com/photo-...">
        <button type="button" class="btn btn-outline-danger" onclick="removeUrlInput(this)"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
    `;
    container.appendChild(div);
    if (window.lucide) window.lucide.createIcons();
}

function removeUrlInput(btn) {
    const group = btn.closest('.input-group');
    if (document.querySelectorAll('#galleryUrlInputs .input-group').length > 1) {
        group.remove();
    } else {
        group.querySelector('input').value = '';
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
