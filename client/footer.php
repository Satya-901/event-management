</div> <!-- /.main-content -->

<!-- Mobile App Bottom Navigation Bar -->
<nav class="client-bottom-nav d-lg-none" id="clientBottomNav" aria-label="Mobile Navigation">
    <a href="/client/dashboard" class="bottom-nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>" id="clientBottomNavOverview">
        <div class="bottom-nav-icon">
            <i data-lucide="layout-dashboard"></i>
        </div>
        <span class="bottom-nav-label">Dashboard</span>
    </a>
    <a href="/client/events" class="bottom-nav-item <?= in_array($currentPage, ['events', 'event_create', 'event_edit', 'event_builder']) ? 'active' : '' ?>" id="clientBottomNavEvents">
        <div class="bottom-nav-icon">
            <i data-lucide="calendar"></i>
        </div>
        <span class="bottom-nav-label">Events</span>
    </a>
    <a href="/client/scanner" class="bottom-nav-item scanner-highlight <?= $currentPage === 'scanner' ? 'active' : '' ?>" id="clientBottomNavScanner">
        <div class="bottom-nav-icon">
            <i data-lucide="scan-line"></i>
        </div>
        <span class="bottom-nav-label">Scan QR</span>
    </a>
    <a href="/client/bookings" class="bottom-nav-item <?= $currentPage === 'bookings' ? 'active' : '' ?>" id="clientBottomNavBookings">
        <div class="bottom-nav-icon">
            <i data-lucide="ticket"></i>
        </div>
        <span class="bottom-nav-label">Bookings</span>
    </a>
    <button type="button" class="bottom-nav-item border-0 bg-transparent <?= in_array($currentPage, ['sheets', 'reports', 'profile']) ? 'active' : '' ?>" data-bs-toggle="offcanvas" data-bs-target="#clientMobileDrawer" aria-label="Open full organizer menu" id="clientBottomNavMore">
        <div class="bottom-nav-icon">
            <i data-lucide="grid-2x2"></i>
        </div>
        <span class="bottom-nav-label">More</span>
    </button>
</nav>

<!-- Mobile App Offcanvas Drawer -->
<div class="offcanvas offcanvas-start client-drawer d-lg-none" tabindex="-1" id="clientMobileDrawer" aria-labelledby="clientMobileDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-2.5">
            <span class="badge bg-warning text-white p-2 rounded-3 shadow-sm" style="background-color: #ea580c !important;">
                <i data-lucide="sparkles" style="width:18px;height:18px;"></i>
            </span>
            <div>
                <h6 class="offcanvas-title fw-bold text-dark mb-0" id="clientMobileDrawerLabel"><?= e($currentClient['name'] ?? APP_NAME) ?></h6>
                <span class="text-uppercase" style="font-size:10px; font-weight:700; letter-spacing:0.5px; color:#ea580c;">Organizer Portal</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <!-- User Info Card -->
        <div class="card p-3 mb-3 border bg-light shadow-xs">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-xs" style="background-color:#ea580c; width:34px; height:34px; font-size:13px;">
                        <?= strtoupper(substr($currentUser['name'] ?? 'OR', 0, 2)) ?>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark small text-truncate" style="max-width:140px;"><?= e($currentUser['name'] ?? 'Organizer Staff') ?></div>
                        <span class="badge bg-light text-secondary border font-monospace" style="font-size:10px;">ID: <?= e($currentClient['code']) ?></span>
                    </div>
                </div>
            </div>
            <a href="/<?= e($currentClient['slug']) ?>/" target="_blank" class="btn btn-outline-secondary btn-sm py-1 d-flex align-items-center justify-content-center gap-1.5 text-xs">
                <i data-lucide="external-link" style="width:13px;height:13px;"></i> View Public Space Page
            </a>
        </div>

        <!-- Navigation Menu in Drawer -->
        <div class="list-group list-group-flush mb-auto">
            <a href="/client/dashboard" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 mb-1 border-0 <?= $currentPage === 'dashboard' ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-dark' ?>">
                <i data-lucide="layout-dashboard" style="width:18px;height:18px;"></i> Dashboard Overview
            </a>
            <a href="/client/scanner" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 mb-1 border-0 <?= $currentPage === 'scanner' ? 'bg-warning bg-opacity-15 text-warning fw-semibold' : 'text-dark' ?>">
                <i data-lucide="scan-line" style="width:18px;height:18px; color:#ea580c;"></i> Live QR Gate Scanner
            </a>
            <a href="/client/sheets" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 mb-1 border-0 <?= $currentPage === 'sheets' ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-dark' ?>">
                <i data-lucide="table" style="width:18px;height:18px;"></i> Live Booking Sheet
            </a>
            <a href="/client/events" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 mb-1 border-0 <?= in_array($currentPage, ['events', 'event_create', 'event_edit', 'event_builder']) ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-dark' ?>">
                <i data-lucide="calendar" style="width:18px;height:18px;"></i> My Events Portfolio
            </a>
            <a href="/client/bookings" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 mb-1 border-0 <?= $currentPage === 'bookings' ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-dark' ?>">
                <i data-lucide="ticket" style="width:18px;height:18px;"></i> All Attendee Bookings
            </a>
            <a href="/client/reports" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 mb-1 border-0 <?= $currentPage === 'reports' ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-dark' ?>">
                <i data-lucide="bar-chart-3" style="width:18px;height:18px;"></i> Analytics & Reports
            </a>
            <a href="/client/profile" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 mb-1 border-0 <?= $currentPage === 'profile' ? 'bg-primary bg-opacity-10 text-primary fw-semibold' : 'text-dark' ?>">
                <i data-lucide="settings" style="width:18px;height:18px;"></i> Space Settings
            </a>
        </div>

        <!-- Drawer Footer -->
        <div class="pt-3 border-top mt-3">
            <a href="/client/logout" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2 py-2 text-sm fw-medium">
                <i data-lucide="log-out" style="width:16px;height:16px;"></i> Sign Out of Space
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
<script>
    const notyf = new Notyf({
        duration: 3500,
        position: { x: 'right', y: 'top' }
    });
    lucide.createIcons();

    // Re-render Lucide icons on offcanvas drawer opening
    const drawerEl = document.getElementById('clientMobileDrawer');
    if (drawerEl) {
        drawerEl.addEventListener('shown.bs.offcanvas', function () {
            lucide.createIcons();
        });
    }
</script>
</body>
</html>
