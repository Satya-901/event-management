<?php
/**
 * Realtime Booking & Payment Verification Status API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/storage/DataStoreFactory.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing verification token.']);
    exit;
}

$store = getDataStore();
$booking = $store->getBookingByQrToken($token);

if (!$booking) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Pass token not found.']);
    exit;
}

echo json_encode([
    'success' => true,
    'id' => $booking['id'],
    'booking_number' => $booking['booking_number'],
    'status' => $booking['status'],
    'payment_status' => $booking['payment_status'] ?? 'pending_verification',
    'is_confirmed' => in_array($booking['status'], ['confirmed', 'checked_in']) && in_array($booking['payment_status'], ['verified', 'free'])
]);
exit;
