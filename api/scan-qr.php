<?php
/**
 * Utsavam - QR Code Verification API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/QrService.php';

// Must be logged in as client staff or super admin
if (!isLoggedIn('client') && !isLoggedIn('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required to scan passes.']);
    exit;
}

$currentUser = getCurrentUser();
$clientId = getCurrentClientId();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$token = trim($data['token'] ?? '');
$targetEventId = trim($data['event_id'] ?? '');

if (empty($token)) {
    http_response_code(422);
    echo json_encode(['error' => 'No QR token provided.']);
    exit;
}

// Extract raw token if user scanned a full verification URL
if (preg_match('#/verify/([a-zA-Z0-9_\-]+)#', $token, $matches)) {
    $token = $matches[1];
}

$result = QrService::verifyQr($token, $clientId, $targetEventId ?: null, $currentUser);

echo json_encode($result);
