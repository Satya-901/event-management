<?php
/**
 * Utsavam - Event Listing Inquiry API Endpoint
 * Handles lead submissions from prospective event organizers.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/LeadService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// Support both Form POST and JSON POST
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

// CSRF check (optional fallback for frictionless submission)
$csrfToken = $input['csrf_token'] ?? '';
if (!empty($csrfToken) && !validateCsrfToken($csrfToken)) {
    // If invalid token sent, return error
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security validation expired. Please refresh the page and try again.']);
    exit;
}

$organizerName = trim($input['organizer_name'] ?? '');
$phone = trim($input['phone'] ?? '');
$eventTitle = trim($input['event_title'] ?? '');
$email = trim($input['email'] ?? '');
$city = trim($input['venue_city'] ?? '');

if (empty($organizerName) || empty($phone)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Please provide your Name and Phone/WhatsApp Number so we can call you.'
    ]);
    exit;
}

// Basic phone length check
$cleanPhone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($cleanPhone) < 8) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Please enter a valid mobile or WhatsApp number.'
    ]);
    exit;
}

// If event title is not specified, use a sensible default
if (empty($eventTitle)) {
    $eventTitle = 'Upcoming Event / Listing Inquiry';
}

// Email format check only if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Please enter a valid email address, or leave it empty.'
    ]);
    exit;
}

try {
    $lead = LeadService::createLead([
        'organizer_name' => $organizerName,
        'organization_name' => trim($input['organization_name'] ?? $organizerName),
        'email' => $email,
        'phone' => $phone,
        'event_title' => $eventTitle,
        'event_category' => trim($input['event_category'] ?? 'Event'),
        'expected_attendees' => trim($input['expected_attendees'] ?? 'To be discussed on call'),
        'event_date' => trim($input['event_date'] ?? ''),
        'venue_city' => $city,
        'ticketing_type' => trim($input['ticketing_type'] ?? 'To be discussed on call'),
        'requirements' => trim($input['requirements'] ?? 'Requested callback for event details')
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! We have received your request. Our team will call you shortly to discuss your event details.',
        'inquiry_number' => $lead['inquiry_number'],
        'lead' => [
            'id' => $lead['id'],
            'inquiry_number' => $lead['inquiry_number'],
            'organizer_name' => $lead['organizer_name'],
            'phone' => $lead['phone'],
            'event_title' => $lead['event_title']
        ]
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Could not submit your listing request: ' . $e->getMessage()
    ]);
    exit;
}
