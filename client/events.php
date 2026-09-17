<?php
$pageTitle = "Manage Events";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/EventService.php';

$clientId = getCurrentClientId();
$events = EventService::getEvents($clientId);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Events Portfolio</h4>
        <p class="text-muted small mb-0">Create, customize, and oversee celebrations under your organization.</p>
    </div>
    <a href="/client/events/create" class="btn btn-warning btn-sm fw-bold text-dark d-inline-flex align-items-center gap-1">
        <i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Create New Event
    </a>
</div>

<div class="row g-4">
    <?php if (empty($events)): ?>
        <div class="col-12">
            <div class="card p-5 text-center bg-white">
                <i data-lucide="calendar-x" style="width:48px;height:48px;" class="mx-auto text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold">No Events Yet</h5>
                <p class="text-muted small">Start by creating your first celebration or event landing page.</p>
                <div>
                    <a href="/client/events/create" class="btn btn-warning btn-sm fw-semibold text-dark">Create First Event</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($events as $ev): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card bg-white h-100 overflow-hidden shadow-sm">
                    <img src="<?= e($ev['banner'] ?: 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80') ?>" alt="<?= e($ev['name']) ?>" style="height:160px; object-fit:cover; width:100%;">
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-warning text-dark small text-uppercase"><?= e($ev['category']) ?></span>
                            <span class="badge bg-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?>">
                                <?= strtoupper($ev['status'] ?? 'published') ?>
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1 fs-6">
                            <?= e($ev['name']) ?>
                        </h5>
                        <div class="text-muted small mb-3">
                            <div><i data-lucide="calendar" style="width:13px;height:13px;"></i> <?= formatDate($ev['start_date']) ?> at <?= formatTime($ev['start_time']) ?></div>
                            <div><i data-lucide="map-pin" style="width:13px;height:13px;"></i> <?= e($ev['venue_name']) ?>, <?= e($ev['city']) ?></div>
                        </div>

                        <div class="border-top pt-2 mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2 text-xs text-muted" style="font-size:11px;">
                                <span>Capacity: <strong><?= (int)($ev['max_capacity'] ?? 0) ?></strong></span>
                                <span>Seats Left: <strong><?= (int)($ev['available_seats'] ?? 0) ?></strong></span>
                            </div>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="/<?= e($currentClient['slug']) ?>/<?= e($ev['slug']) ?>/" target="_blank" class="btn btn-sm btn-outline-dark py-1 px-2 text-xs" title="View Public Landing Page">
                                    <i data-lucide="eye" style="width:13px;height:13px;"></i> Public
                                </a>
                                <a href="/client/sheets?event_id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-xs" title="Booking Sheet">
                                    <i data-lucide="table" style="width:13px;height:13px;"></i> Sheet
                                </a>
                                <a href="/client/events/builder?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-xs" title="Dynamic Booking Form Builder">
                                    <i data-lucide="sliders" style="width:13px;height:13px;"></i> Form
                                </a>
                                <a href="/client/events/edit?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-xs" title="Edit Event Details">
                                    <i data-lucide="edit" style="width:13px;height:13px;"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
