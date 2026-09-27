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
            $bookings = array_filter($bookings, fn($b) => strtolower($b['status'] ?? '') === $status);
        }

        if (!empty($filters['payment_status'])) {
            $pStatus = strtolower($filters['payment_status']);
            $bookings = array_filter($bookings, fn($b) => strtolower($b['payment_status'] ?? '') === $pStatus);
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

        $totalAmount = isset($data['total_amount']) ? (float)$data['total_amount'] : 0;
        $utrNumber = trim($data['utr_number'] ?? '');
        $paymentMethod = $data['payment_method'] ?? 'upi';

        // Payment status resolution:
        // If event requires payment (totalAmount > 0), booking starts in pending status awaiting verification!
        $isPaidEvent = ($totalAmount > 0);
        $initialStatus = $isPaidEvent ? 'pending' : (($event['confirmation_mode'] === 'manual') ? 'pending' : 'confirmed');
        $initialPaymentStatus = $isPaidEvent ? 'pending_verification' : 'free';

        $bookingData = [
            'client_id' => $event['client_id'],
            'event_id' => $event['id'],
            'booking_number' => generateBookingNumber(),
            'customer_name' => trim($data['customer_name'] ?? ''),
            'email' => strtolower(trim($data['email'] ?? '')),
            'phone' => trim($data['phone'] ?? ''),
            'pass_count' => $passCount,
            'package_id' => $data['package_id'] ?? null,
            'package_name' => $data['package_name'] ?? null,
            'package_price' => isset($data['package_price']) ? (float)$data['package_price'] : 0,
            'total_amount' => $totalAmount,
            'payment_status' => $data['payment_status'] ?? $initialPaymentStatus,
            'utr_number' => $utrNumber ?: null,
            'payment_method' => $paymentMethod,
            'booking_date' => date('Y-m-d'),
            'status' => $data['status'] ?? $initialStatus,
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
            'description' => "New booking #{$booking['booking_number']} by {$booking['customer_name']} (UTR: " . ($utrNumber ?: 'N/A') . ") for {$event['name']}"
        ]);

        // If free booking and confirmed, send email confirmation immediately
        if ($booking['status'] === 'confirmed' && !empty($booking['email'])) {
            try {
                MailService::sendBookingConfirmation($booking, $event);
            } catch (Exception $e) {
                error_log("Email sending error: " . $e->getMessage());
            }
        }

        return $booking;
    }

    public static function getPendingPaymentsCount(?string $clientId = null): int {
        $bookings = getDataStore()->getBookings($clientId, null);
        $count = 0;
        foreach ($bookings as $b) {
            $pStatus = strtolower($b['payment_status'] ?? '');
            $bStatus = strtolower($b['status'] ?? '');
            if ($pStatus === 'pending_verification' && $bStatus !== 'cancelled' && $bStatus !== 'rejected') {
                $count++;
            }
        }
        return $count;
    }

    public static function verifyPayment(string $id, ?string $clientId = null, ?array $actor = null): bool {
        $booking = self::getBooking($id, $clientId);
        if (!$booking) return false;

        $store = getDataStore();
        $actorName = $actor['name'] ?? $actor['username'] ?? 'Organizer Admin';

        $updated = $store->updateBooking($id, [
            'status' => 'confirmed',
            'payment_status' => 'verified',
            'payment_verified_at' => date('Y-m-d H:i:s'),
            'payment_verified_by' => $actorName
        ]);

        if ($updated) {
            $store->logActivity([
                'actor_id' => $actor['id'] ?? 'system',
                'actor_role' => $actor['role'] ?? 'admin',
                'client_id' => $booking['client_id'],
                'action' => "payment_verified",
                'entity_type' => 'booking',
                'entity_id' => $id,
                'description' => "Payment verified & ticket approved for booking #{$booking['booking_number']} (UTR: {$booking['utr_number']}) by {$actorName}"
            ]);

            // Dispatch confirmation email to customer
            if (!empty($booking['email'])) {
                try {
                    $event = $store->getEventById($booking['event_id']);
                    if ($event) {
                        $refreshedBooking = self::getBooking($id, $clientId);
                        MailService::sendBookingConfirmation($refreshedBooking ?: $booking, $event);
                    }
                } catch (Exception $e) {
                    error_log("Verification email error: " . $e->getMessage());
                }
            }
        }

        return $updated;
    }

    public static function rejectPayment(string $id, ?string $reason = null, ?string $clientId = null, ?array $actor = null): bool {
        $booking = self::getBooking($id, $clientId);
        if (!$booking) return false;

        $store = getDataStore();
        $actorName = $actor['name'] ?? $actor['username'] ?? 'Organizer Admin';

        $updated = $store->updateBooking($id, [
            'status' => 'rejected',
            'payment_status' => 'rejected',
            'payment_verified_at' => date('Y-m-d H:i:s'),
            'payment_verified_by' => $actorName
        ]);

        if ($updated) {
            $store->logActivity([
                'actor_id' => $actor['id'] ?? 'system',
                'actor_role' => $actor['role'] ?? 'admin',
                'client_id' => $booking['client_id'],
                'action' => "payment_rejected",
                'entity_type' => 'booking',
                'entity_id' => $id,
                'description' => "Payment rejected for booking #{$booking['booking_number']}" . ($reason ? " Reason: {$reason}" : "")
            ]);
        }

        return $updated;
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

    public static function checkIn(string $bookingId, ?string $clientId = null, ?array $actor = null): array {
        require_once __DIR__ . '/QrService.php';
        $actorName = $actor['name'] ?? $actor['username'] ?? 'Staff';
        $result = QrService::checkIn($bookingId, $actorName, $clientId);
        if (empty($result['success'])) {
            throw new Exception($result['error'] ?? 'Check-in failed.');
        }
        $booking = self::getBooking($bookingId, $clientId);
        return $booking ?? [];
    }
}
