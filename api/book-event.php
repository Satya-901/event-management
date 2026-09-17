<?php
/**
 * Utsavam - Public Event Booking API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/EventService.php';
require_once __DIR__ . '/../includes/services/BookingService.php';
require_once __DIR__ . '/../includes/services/MailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Support both Form POST and JSON POST
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

$csrfToken = $input['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF verification failed. Please refresh the page and try again.']);
    exit;
}

$eventId = $input['event_id'] ?? '';
$customerName = trim($input['customer_name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$passCount = max(1, (int)($input['pass_count'] ?? 1));

if (empty($eventId) || empty($customerName) || empty($email) || empty($phone)) {
    http_response_code(422);
    echo json_encode(['error' => 'All required contact details must be filled.']);
    exit;
}

$event = EventService::getEvent($eventId);
if (!$event) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found.']);
    exit;
}

if (empty($event['booking_open']) || ($event['status'] ?? '') !== 'published') {
    http_response_code(400);
    echo json_encode(['error' => 'Bookings for this event are currently closed.']);
    exit;
}

if (($event['available_seats'] ?? 0) < $passCount) {
    http_response_code(400);
    echo json_encode(['error' => 'Sorry, only ' . ($event['available_seats'] ?? 0) . ' seats remaining.']);
    exit;
}

// Extract dynamic form field answers
$formFields = EventService::getFormFields($eventId);
$customData = [];
foreach ($formFields as $f) {
    $val = $input['custom_' . $f['field_key']] ?? ($input[$f['field_key']] ?? null);
    if (!empty($f['required']) && (empty($val) && $val !== '0')) {
        http_response_code(422);
        echo json_encode(['error' => 'Please provide: ' . $f['field_label']]);
        exit;
    }
    if ($val !== null) {
        $customData[$f['field_key']] = $val;
    }
}

try {
    $booking = BookingService::createBooking([
        'client_id' => $event['client_id'],
        'event_id' => $eventId,
        'customer_name' => $customerName,
        'email' => $email,
        'phone' => $phone,
        'pass_count' => $passCount,
        'custom_fields' => $customData,
        'status' => 'confirmed'
    ]);

    // Send email confirmation
    MailService::sendBookingConfirmation($booking, $event);

    echo json_encode([
        'success' => true,
        'booking_id' => $booking['id'],
        'booking_number' => $booking['booking_number'],
        'qr_token' => $booking['qr_token'],
        'verify_url' => APP_URL . '/verify/' . $booking['qr_token'],
        'message' => 'Your pass reservation is confirmed!'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
