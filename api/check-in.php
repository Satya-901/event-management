<?php
/**
 * Utsavam - Turnstile Check-In API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/BookingService.php';

if (!isLoggedIn('client') && !isLoggedIn('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required for check-in.']);
    exit;
}

$currentUser = getCurrentUser();
$clientId = getCurrentClientId();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$bookingId = trim($data['booking_id'] ?? '');

if (empty($bookingId)) {
    http_response_code(422);
    echo json_encode(['error' => 'Booking ID is required.']);
    exit;
}

try {
    $updatedBooking = BookingService::checkIn($bookingId, $clientId, $currentUser);
    echo json_encode([
        'success' => true,
        'message' => 'Attendee admitted successfully!',
        'booking' => $updatedBooking
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
