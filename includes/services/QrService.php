<?php
/**
 * Utsavam QrService
 * Secure QR token verification, check-in, and scan logging.
 */

require_once __DIR__ . '/../storage/DataStoreFactory.php';
require_once __DIR__ . '/../helpers.php';

class QrService {
    public static function generateToken(): string {
        return generateSecureQrToken();
    }

    public static function getVerificationUrl(string $token): string {
        return appUrl('/verify/' . $token);
    }

    /**
     * Alias for verifyQr used by scanning endpoints
     */
    public static function verifyQr(string $token, ?string $clientId = null, ?string $targetEventId = null, $currentUser = null): array {
        return self::verifyToken($token, $targetEventId, $clientId);
    }

    /**
     * Verify QR token for scanning
     * @param string $token
     * @param string|null $expectedEventId Optional: constrain check to a specific event
     * @param string|null $expectedClientId Optional: constrain to client's tenant
     * @return array Result containing status and booking details
     */
    public static function verifyToken(string $token, ?string $expectedEventId = null, ?string $expectedClientId = null): array {
        $store = getDataStore();
        $token = trim($token);

        // If a full verification URL was scanned, extract token
        if (str_contains($token, '/verify/')) {
            $parts = explode('/verify/', $token);
            $token = end($parts);
            $token = explode('?', $token)[0];
            $token = trim($token, '/');
        }

        $booking = $store->getBookingByQrToken($token);

        if (!$booking) {
            $store->recordQrScan([
                'booking_id' => '',
                'client_id' => $expectedClientId ?? '',
                'event_id' => $expectedEventId ?? '',
                'qr_token' => $token,
                'status' => 'invalid',
                'scanned_by' => 'Scanner'
            ]);

            return [
                'success' => false,
                'status' => 'INVALID',
                'message' => 'This QR code is not valid or does not exist in the system.'
            ];
        }

        $event = $store->getEventById($booking['event_id']);
        $eventName = $event['name'] ?? 'Unknown Event';

        // Check if token belongs to another client (Tenant security)
        if ($expectedClientId !== null && ($booking['client_id'] ?? '') !== $expectedClientId) {
            $store->recordQrScan([
                'booking_id' => $booking['id'],
                'client_id' => $expectedClientId,
                'event_id' => $expectedEventId ?? $booking['event_id'],
                'qr_token' => $token,
                'status' => 'unauthorized_tenant',
                'scanned_by' => 'Scanner'
            ]);

            return [
                'success' => false,
                'status' => 'INVALID',
                'message' => 'This pass does not belong to your organization.'
            ];
        }

        // Check if token belongs to another event
        if ($expectedEventId !== null && !empty($expectedEventId) && ($booking['event_id'] ?? '') !== $expectedEventId) {
            $store->recordQrScan([
                'booking_id' => $booking['id'],
                'client_id' => $booking['client_id'],
                'event_id' => $expectedEventId,
                'qr_token' => $token,
                'status' => 'wrong_event',
                'scanned_by' => 'Scanner'
            ]);

            return [
                'success' => false,
                'status' => 'WRONG_EVENT',
                'message' => "This pass belongs to another event: \"{$eventName}\".",
                'booking' => $booking,
                'event' => $event
            ];
        }

        // Check if already checked in
        if (strtolower($booking['status']) === 'checked_in') {
            $store->recordQrScan([
                'booking_id' => $booking['id'],
                'client_id' => $booking['client_id'],
                'event_id' => $booking['event_id'],
                'qr_token' => $token,
                'status' => 'already_checked_in',
                'scanned_by' => 'Scanner'
            ]);

            return [
                'success' => false,
                'status' => 'ALREADY_CHECKED_IN',
                'message' => 'Attendee is already checked in.',
                'booking' => $booking,
                'event' => $event,
                'checked_in_at' => $booking['checked_in_at'] ?? 'Previously',
                'checked_in_by' => $booking['checked_in_by'] ?? 'Staff'
            ];
        }

        // Check if cancelled or rejected
        if (in_array(strtolower($booking['status']), ['cancelled', 'rejected', 'expired'])) {
            return [
                'success' => false,
                'status' => 'INVALID_STATUS',
                'message' => "This pass is " . strtoupper($booking['status']) . " and cannot be used for entry.",
                'booking' => $booking,
                'event' => $event
            ];
        }

        // Valid entry
        $store->recordQrScan([
            'booking_id' => $booking['id'],
            'client_id' => $booking['client_id'],
            'event_id' => $booking['event_id'],
            'qr_token' => $token,
            'status' => 'valid',
            'scanned_by' => 'Scanner'
        ]);

        return [
            'success' => true,
            'status' => 'VALID',
            'message' => 'Valid entry pass.',
            'booking' => $booking,
            'event' => $event
        ];
    }

    /**
     * Check-in attendee
     */
    public static function checkIn(string $bookingId, string $actorName, ?string $clientId = null): array {
        $store = getDataStore();
        $booking = $store->getBookingById($bookingId);

        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found.'];
        }

        // Tenant ownership check
        if ($clientId !== null && ($booking['client_id'] ?? '') !== $clientId) {
            return ['success' => false, 'error' => 'Unauthorized tenant.'];
        }

        // Check duplicate check-in
        if (strtolower($booking['status']) === 'checked_in') {
            return [
                'success' => false,
                'error' => 'Attendee was already checked in at ' . formatDateTime($booking['checked_in_at']) . ' by ' . ($booking['checked_in_by'] ?? 'Staff')
            ];
        }

        $now = date('Y-m-d H:i:s');
        $updated = $store->updateBooking($bookingId, [
            'status' => 'checked_in',
            'checked_in_at' => $now,
            'checked_in_by' => $actorName
        ]);

        if ($updated) {
            // Record check-in activity
            $store->logActivity([
                'actor_id' => $actorName,
                'actor_role' => 'client_staff',
                'client_id' => $booking['client_id'],
                'action' => 'attendee_checked_in',
                'entity_type' => 'booking',
                'entity_id' => $bookingId,
                'description' => "Attendee {$booking['customer_name']} (Booking #{$booking['booking_number']}) checked in by {$actorName}"
            ]);

            return [
                'success' => true,
                'message' => 'Attendee checked in successfully!',
                'checked_in_at' => $now,
                'checked_in_by' => $actorName
            ];
        }

        return ['success' => false, 'error' => 'Failed to update check-in status.'];
    }

    public static function getScanHistory(?string $clientId = null, ?string $eventId = null): array {
        return getDataStore()->getQrScans($clientId, $eventId);
    }
}
