<?php
$pageTitle = "Manage Events";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/services/EventService.php';

$clientId = getCurrentClientId();
$events = EventService::getEvents($clientId);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Events Portfolio</h4>
        <p class="text-muted small mb-0">Create, customize, and oversee celebrations hosted by your organization.</p>
    </div>
    <a href="/client/events/create" class="btn btn-warning btn-sm fw-semibold text-white d-inline-flex align-items-center gap-1.5 shadow-xs" style="background-color:#ea580c !important; border-color:#ea580c !important;">
        <i data-lucide="plus-circle" style="width:16px;height:16px;"></i> Create New Event
    </a>
</div>

<div class="row g-3 g-md-4">
    <?php if (empty($events)): ?>
        <div class="col-12">
            <div class="card p-5 text-center shadow-xs">
                <i data-lucide="calendar-x" style="width:48px;height:48px;" class="mx-auto text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold" style="color:#0f172a;">No Events Yet</h5>
                <p class="text-muted small max-w-sm mx-auto mb-3">Start by creating your first celebration or event landing page.</p>
                <div>
                    <a href="/client/events/create" class="btn btn-warning btn-sm fw-semibold text-white shadow-xs" style="background-color:#ea580c !important;">Create First Event</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($events as $ev): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 overflow-hidden shadow-xs">
                    <div style="height:150px; background-color:#f1f5f9; position:relative; overflow:hidden;">
                        <img src="<?= e($ev['banner'] ?: 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80') ?>" alt="<?= e($ev['name']) ?>" style="height:100%; object-fit:cover; width:100%;">
                        <div style="position:absolute; top:10px; right:10px;">
                            <span class="badge bg-<?= ($ev['status'] ?? '') === 'published' ? 'success' : 'secondary' ?> bg-opacity-90 text-white shadow-xs">
                                <?= strtoupper($ev['status'] ?? 'published') ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                            <span class="badge bg-warning bg-opacity-10 text-uppercase fw-semibold" style="color:#ea580c !important; font-size:10px;"><?= e($ev['category']) ?></span>
                            <span class="text-muted text-xs" style="font-size:11px;">
                                Seats: <strong><?= (int)($ev['available_seats'] ?? 0) ?></strong> / <?= (int)($ev['max_capacity'] ?? 0) ?>
                            </span>
                        </div>
                        <h5 class="fw-bold mb-1.5 fs-6" style="color:#0f172a;">
                            <?= e($ev['name']) ?>
                        </h5>
                        <div class="text-secondary small mb-3">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <i data-lucide="calendar" style="width:13px;height:13px;color:#64748b;"></i>
                                <span><?= formatDate($ev['start_date']) ?> at <?= formatTime($ev['start_time']) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 text-truncate">
                                <i data-lucide="map-pin" style="width:13px;height:13px;color:#64748b;"></i>
                                <span class="text-truncate"><?= e($ev['venue_name']) ?>, <?= e($ev['city']) ?></span>
                            </div>
                        </div>

                        <div class="border-top pt-2.5 mt-auto">
                            <div class="d-flex gap-1.5 flex-wrap">
                                <a href="/<?= e($currentClient['slug']) ?>/<?= e($ev['slug']) ?>/" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2 text-xs flex-grow-1 text-center" title="View Public Landing Page">
                                    <i data-lucide="eye" style="width:13px;height:13px;vertical-align:-1px;"></i> Public
                                </a>
                                <a href="/client/sheets?event_id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-xs text-dark flex-grow-1 text-center" title="Booking Sheet">
                                    <i data-lucide="table" style="width:13px;height:13px;vertical-align:-1px;"></i> Sheet
                                </a>
                                <a href="/client/events/builder?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-xs text-dark flex-grow-1 text-center" title="Dynamic Booking Form Builder">
                                    <i data-lucide="sliders" style="width:13px;height:13px;vertical-align:-1px;"></i> Form
                                </a>
                                <a href="/client/events/edit?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light border py-1 px-2 text-xs text-dark flex-grow-1 text-center" title="Edit Event Details">
                                    <i data-lucide="edit" style="width:13px;height:13px;vertical-align:-1px;"></i> Edit
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
