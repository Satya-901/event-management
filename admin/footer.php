</div> <!-- /.admin-content -->

<!-- Mobile App Bottom Navigation Bar -->
<nav class="admin-bottom-nav d-lg-none" id="adminBottomNav" aria-label="Mobile Navigation">
    <a href="/admin/dashboard" class="bottom-nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>" id="bottomNavOverview">
        <div class="bottom-nav-icon">
            <i data-lucide="layout-dashboard"></i>
        </div>
        <span class="bottom-nav-label">Overview</span>
    </a>
    <a href="/admin/clients" class="bottom-nav-item <?= in_array($currentPage, ['clients', 'client_create']) ? 'active' : '' ?>" id="bottomNavTenants">
        <div class="bottom-nav-icon">
            <i data-lucide="building"></i>
        </div>
        <span class="bottom-nav-label">Tenants</span>
    </a>
    <a href="/admin/events" class="bottom-nav-item <?= $currentPage === 'events' ? 'active' : '' ?>" id="bottomNavEvents">
        <div class="bottom-nav-icon">
            <i data-lucide="calendar"></i>
        </div>
        <span class="bottom-nav-label">Events</span>
    </a>
    <a href="/admin/bookings" class="bottom-nav-item <?= $currentPage === 'bookings' ? 'active' : '' ?>" id="bottomNavBookings">
        <div class="bottom-nav-icon">
            <i data-lucide="ticket"></i>
        </div>
        <span class="bottom-nav-label">Bookings</span>
    </a>
    <button type="button" class="bottom-nav-item border-0 bg-transparent <?= in_array($currentPage, ['reports', 'activity', 'settings']) ? 'active' : '' ?>" data-bs-toggle="offcanvas" data-bs-target="#adminMobileDrawer" aria-label="Open full admin menu" id="bottomNavMore">
        <div class="bottom-nav-icon">
            <i data-lucide="grid-2x2"></i>
        </div>
        <span class="bottom-nav-label">More</span>
    </button>
</nav>

<!-- Mobile App Offcanvas Drawer -->
<div class="offcanvas offcanvas-start admin-drawer d-lg-none" tabindex="-1" id="adminMobileDrawer" aria-labelledby="adminMobileDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-2.5">
            <span class="badge bg-primary text-white p-2 rounded-3 shadow-sm">
                <i data-lucide="shield" style="width:18px;height:18px;"></i>
            </span>
            <div>
                <h6 class="offcanvas-title fw-bold text-dark mb-0" id="adminMobileDrawerLabel"><?= e(APP_NAME) ?></h6>
                <span class="text-primary text-uppercase" style="font-size:10px; font-weight:700; letter-spacing:0.5px;">Super Admin Control</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <!-- User Info Card -->
        <div class="card p-3 mb-3 border bg-light shadow-xs">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-xs" style="width:34px;height:34px;font-size:13px;">
                        <?= strtoupper(substr($currentUser['name'] ?? 'SA', 0, 2)) ?>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark small text-truncate" style="max-width:140px;"><?= e($currentUser['name'] ?? 'Super Admin') ?></div>
                        <div class="text-muted" style="font-size:11px;">Master Operator</div>
                    </div>
                </div>
                <span class="badge bg-white border text-dark font-monospace" style="font-size:10px;">
                    <?= strtoupper(DATA_DRIVER) ?>
                </span>
            </div>
            <a href="/admin/clients/create" class="btn btn-primary btn-sm fw-semibold w-100 d-flex align-items-center justify-content-center gap-1.5 py-1.5 mt-1 shadow-xs">
                <i data-lucide="plus" style="width:15px;height:15px;"></i> Provision New Client
            </a>
        </div>

        <div class="text-uppercase text-muted fw-bold px-2 mb-2" style="font-size:11px; letter-spacing:0.5px;">Core Navigation</div>
        <div class="mb-3">
            <a href="/admin/dashboard" class="drawer-nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" style="width:18px;height:18px;"></i>
                <span>Platform Overview</span>
            </a>
            <a href="/admin/leads" class="drawer-nav-item <?= $currentPage === 'leads' ? 'active' : '' ?>">
                <i data-lucide="inbox" style="width:18px;height:18px;"></i>
                <span>Event Inquiries (Leads)</span>
            </a>
            <a href="/admin/clients" class="drawer-nav-item <?= in_array($currentPage, ['clients', 'client_create']) ? 'active' : '' ?>">
                <i data-lucide="building" style="width:18px;height:18px;"></i>
                <span>Client Tenants</span>
            </a>
            <a href="/admin/events" class="drawer-nav-item <?= $currentPage === 'events' ? 'active' : '' ?>">
                <i data-lucide="calendar" style="width:18px;height:18px;"></i>
                <span>Global Events</span>
            </a>
            <a href="/admin/bookings" class="drawer-nav-item <?= $currentPage === 'bookings' ? 'active' : '' ?>">
                <i data-lucide="ticket" style="width:18px;height:18px;"></i>
                <span>All Bookings Ledger</span>
            </a>
        </div>

        <div class="text-uppercase text-muted fw-bold px-2 mb-2" style="font-size:11px; letter-spacing:0.5px;">Management & Analytics</div>
        <div class="mb-3">
            <a href="/admin/reports" class="drawer-nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i data-lucide="bar-chart-2" style="width:18px;height:18px;"></i>
                <span>Platform Reports</span>
            </a>
            <a href="/admin/activity" class="drawer-nav-item <?= $currentPage === 'activity' ? 'active' : '' ?>">
                <i data-lucide="activity" style="width:18px;height:18px;"></i>
                <span>Audit Activity Logs</span>
            </a>
            <a href="/admin/settings" class="drawer-nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                <i data-lucide="sliders" style="width:18px;height:18px;"></i>
                <span>System Config & DB</span>
            </a>
            <a href="/royal-events/" target="_blank" class="drawer-nav-item text-secondary">
                <i data-lucide="external-link" style="width:18px;height:18px;"></i>
                <span>Preview Demo Space</span>
            </a>
        </div>

        <div class="mt-auto pt-3 border-top border-secondary border-opacity-25">
            <a href="/admin/logout" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2 py-2">
                <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                <span>Sign Out of Console</span>
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
</script>
</body>
</html>
