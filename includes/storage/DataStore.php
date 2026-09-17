<?php
/**
 * Utsavam DataStore Interface
 * Common data-access abstraction layer supporting both JSON and future SQL drivers.
 */

interface DataStore {
    // Client Methods
    public function getClients(): array;
    public function getClientById(string $id): ?array;
    public function getClientBySlug(string $slug): ?array;
    public function getClientByCode(string $code): ?array;
    public function createClient(array $data): array;
    public function updateClient(string $id, array $data): bool;
    public function deleteClient(string $id): bool;

    // User Methods
    public function getUsers(): array;
    public function getUserById(string $id): ?array;
    public function getUserByUsername(string $username): ?array;
    public function getUsersByClientId(string $clientId): array;
    public function createUser(array $data): array;
    public function updateUser(string $id, array $data): bool;
    public function deleteUser(string $id): bool;

    // Event Methods
    public function getEvents(?string $clientId = null): array;
    public function getEventById(string $id): ?array;
    public function getEventBySlug(string $clientId, string $slug): ?array;
    public function createEvent(array $data): array;
    public function updateEvent(string $id, array $data): bool;
    public function deleteEvent(string $id): bool;

    // Dynamic Form Fields Methods
    public function getFormFieldsByEventId(string $eventId): array;
    public function saveFormFields(string $eventId, array $fields): bool;

    // Booking Methods
    public function getBookings(?string $clientId = null, ?string $eventId = null): array;
    public function getBookingById(string $id): ?array;
    public function getBookingByNumber(string $bookingNumber): ?array;
    public function getBookingByQrToken(string $token): ?array;
    public function createBooking(array $bookingData, array $answers = []): array;
    public function updateBooking(string $id, array $data): bool;
    public function updateBookingStatus(string $id, string $status): bool;
    public function getBookingAnswers(string $bookingId): array;

    // QR Verification & Scans
    public function recordQrScan(array $data): array;
    public function getQrScans(?string $clientId = null, ?string $eventId = null): array;

    // Activity Logs
    public function logActivity(array $data): array;
    public function getActivityLogs(?string $clientId = null, int $limit = 100): array;

    // Settings
    public function getSettings(): array;
    public function updateSettings(array $settings): bool;
}
