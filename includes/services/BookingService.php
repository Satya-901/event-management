<?php
/**
 * Utsavam BookingService
 */

require_once __DIR__ . '/../storage/DataStoreFactory.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/MailService.php';

class BookingService {
    public static function getBookings(?string $clientId = null, ?string $eventId = null, array $filters = []): array {
        $bookings = getDataStore()->getBookings($clientId, $eventId);

        // Apply filters if provided
        if (!empty($filters['status'])) {
            $status = strtolower($filters['status']);
            $bookings = array_filter($bookings, fn($b) => strtolower($b['status']) === $status);
        }

        if (!empty($filters['search'])) {
            $q = strtolower($filters['search']);
            $bookings = array_filter($bookings, function($b) use ($q) {
                return str_contains(strtolower($b['booking_number'] ?? ''), $q) ||
                       str_contains(strtolower($b['customer_name'] ?? ''), $q) ||
                       str_contains(strtolower($b['email'] ?? ''), $q) ||
                       str_contains(strtolower($b['phone'] ?? ''), $q);
            });
        }

        if (!empty($filters['from_date'])) {
            $from = $filters['from_date'];
            $bookings = array_filter($bookings, fn($b) => ($b['booking_date'] ?? '') >= $from);
        }

        if (!empty($filters['to_date'])) {
            $to = $filters['to_date'];
            $bookings = array_filter($bookings, fn($b) => ($b['booking_date'] ?? '') <= $to);
        }

        return array_values($bookings);
    }

    public static function getBooking(string $id, ?string $clientId = null): ?array {
        $booking = getDataStore()->getBookingById($id);
        if (!$booking) return null;

        // Tenant ownership check
        if ($clientId !== null && ($booking['client_id'] ?? '') !== $clientId) {
            return null; // Tenant security boundary
        }

        return $booking;
    }

    public static function getBookingByNumber(string $bookingNumber, ?string $clientId = null): ?array {
        $booking = getDataStore()->getBookingByNumber($bookingNumber);
        if (!$booking) return null;

        if ($clientId !== null && ($booking['client_id'] ?? '') !== $clientId) {
            return null;
        }

        return $booking;
    }

    public static function getBookingByQrToken(string $token): ?array {
        return getDataStore()->getBookingByQrToken($token);
    }

    public static function createBooking(array $data, array $answers = []): array {
        $store = getDataStore();
        $event = $store->getEventById($data['event_id'] ?? '');

        if (!$event) {
            throw new Exception("Event not found.");
        }

        if (empty($event['booking_open'])) {
            throw new Exception("Bookings are currently closed for this event.");
        }

        $passCount = max(1, (int)($data['pass_count'] ?? 1));
        if ($event['available_seats'] < $passCount) {
            throw new Exception("Only {$event['available_seats']} passes remaining.");
        }

        $bookingData = [
            'client_id' => $event['client_id'],
            'event_id' => $event['id'],
            'booking_number' => generateBookingNumber(),
            'customer_name' => trim($data['customer_name'] ?? ''),
            'email' => strtolower(trim($data['email'] ?? '')),
            'phone' => trim($data['phone'] ?? ''),
            'pass_count' => $passCount,
            'booking_date' => date('Y-m-d'),
            'status' => ($event['confirmation_mode'] === 'manual') ? 'pending' : 'confirmed',
            'qr_token' => generateSecureQrToken()
        ];

        $booking = $store->createBooking($bookingData, $answers);

        // Activity log
        $store->logActivity([
            'actor_id' => 'visitor',
            'actor_role' => 'public',
            'client_id' => $event['client_id'],
            'action' => 'booking_created',
            'entity_type' => 'booking',
            'entity_id' => $booking['id'],
            'description' => "New booking #{$booking['booking_number']} by {$booking['customer_name']} for {$event['name']}"
        ]);

        // Trigger confirmation email via MailService (asynchronous or non-blocking)
        try {
            MailService::sendBookingConfirmation($booking, $event);
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
        }

        return $booking;
    }

    public static function updateStatus(string $id, string $status, ?string $clientId = null, ?array $actor = null): bool {
        $booking = self::getBooking($id, $clientId);
        if (!$booking) return false;

        $store = getDataStore();
        $oldStatus = $booking['status'];
        $success = $store->updateBookingStatus($id, $status);

        if ($success) {
            $store->logActivity([
                'actor_id' => $actor['id'] ?? 'system',
                'actor_role' => $actor['role'] ?? 'system',
                'client_id' => $booking['client_id'],
                'action' => "booking_status_{$status}",
                'entity_type' => 'booking',
                'entity_id' => $id,
                'description' => "Changed booking #{$booking['booking_number']} status from {$oldStatus} to {$status}"
            ]);
        }

        return $success;
    }

    public static function cancelBooking(string $id, ?string $clientId = null, ?array $actor = null): bool {
        return self::updateStatus($id, 'cancelled', $clientId, $actor);
    }

    public static function confirmBooking(string $id, ?string $clientId = null, ?array $actor = null): bool {
        return self::updateStatus($id, 'confirmed', $clientId, $actor);
    }

    public static function getBookingAnswers(string $bookingId): array {
        return getDataStore()->getBookingAnswers($bookingId);
    }
}
