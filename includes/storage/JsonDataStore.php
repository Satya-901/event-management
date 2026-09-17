<?php
/**
 * Utsavam JsonDataStore
 * Production-ready JSON file-based database with file locking and atomic transactions.
 */

require_once __DIR__ . '/DataStore.php';
require_once __DIR__ . '/../helpers.php';

class JsonDataStore implements DataStore {
    protected string $storagePath;

    public function __construct(?string $storagePath = null) {
        $this->storagePath = $storagePath ?? JSON_STORAGE_PATH;
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }
    }

    protected function getFilePath(string $filename): string {
        return rtrim($this->storagePath, '/') . '/' . ltrim($filename, '/');
    }

    /**
     * Read JSON file with shared lock
     */
    protected function readJson(string $filename, $default = []): array {
        $file = $this->getFilePath($filename);
        if (!file_exists($file)) {
            $this->writeJson($filename, $default);
            return $default;
        }

        $fp = @fopen($file, 'r');
        if (!$fp) {
            return $default;
        }

        @flock($fp, LOCK_SH);
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 8192);
        }
        @flock($fp, LOCK_UN);
        @fclose($fp);

        if (empty(trim($content))) {
            return $default;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * Write JSON file with exclusive lock and atomic file write
     */
    protected function writeJson(string $filename, array $data): bool {
        $file = $this->getFilePath($filename);
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return false;
        }

        if (@flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $encoded);
            fflush($fp);
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return true;
        }

        @fclose($fp);
        return false;
    }

    // ================= CLIENTS ================= //

    public function getClients(): array {
        $clients = $this->readJson('clients.json');
        usort($clients, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $clients;
    }

    public function getClientById(string $id): ?array {
        $clients = $this->readJson('clients.json');
        foreach ($clients as $c) {
            if (($c['id'] ?? '') === $id) return $c;
        }
        return null;
    }

    public function getClientBySlug(string $slug): ?array {
        $clients = $this->readJson('clients.json');
        foreach ($clients as $c) {
            if (($c['slug'] ?? '') === $slug) return $c;
        }
        return null;
    }

    public function getClientByCode(string $code): ?array {
        $clients = $this->readJson('clients.json');
        foreach ($clients as $c) {
            if (strcasecmp($c['code'] ?? '', $code) === 0) return $c;
        }
        return null;
    }

    public function createClient(array $data): array {
        $clients = $this->readJson('clients.json');
        
        $id = generateId('client');
        $code = !empty($data['code']) ? $data['code'] : generateClientCode();
        $slug = !empty($data['slug']) ? slugify($data['slug']) : slugify($data['name'] ?? 'client');

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while ($this->getClientBySlug($slug) !== null) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $client = [
            'id' => $id,
            'name' => $data['name'] ?? '',
            'company_name' => $data['company_name'] ?? ($data['name'] ?? ''),
            'email' => $data['email'] ?? '',
            'mobile' => $data['mobile'] ?? '',
            'address' => $data['address'] ?? '',
            'logo' => $data['logo'] ?? '',
            'status' => $data['status'] ?? 'active',
            'code' => $code,
            'slug' => $slug,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $clients[] = $client;
        $this->writeJson('clients.json', $clients);
        return $client;
    }

    public function updateClient(string $id, array $data): bool {
        $clients = $this->readJson('clients.json');
        $updated = false;

        foreach ($clients as &$c) {
            if ($c['id'] === $id) {
                if (isset($data['name'])) $c['name'] = $data['name'];
                if (isset($data['company_name'])) $c['company_name'] = $data['company_name'];
                if (isset($data['email'])) $c['email'] = $data['email'];
                if (isset($data['mobile'])) $c['mobile'] = $data['mobile'];
                if (isset($data['address'])) $c['address'] = $data['address'];
                if (isset($data['logo'])) $c['logo'] = $data['logo'];
                if (isset($data['status'])) $c['status'] = $data['status'];
                if (isset($data['slug']) && !empty($data['slug'])) {
                    $newSlug = slugify($data['slug']);
                    $existing = $this->getClientBySlug($newSlug);
                    if (!$existing || $existing['id'] === $id) {
                        $c['slug'] = $newSlug;
                    }
                }
                $c['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return $this->writeJson('clients.json', $clients);
        }
        return false;
    }

    public function deleteClient(string $id): bool {
        $clients = $this->readJson('clients.json');
        $filtered = array_filter($clients, fn($c) => ($c['id'] ?? '') !== $id);
        if (count($filtered) !== count($clients)) {
            return $this->writeJson('clients.json', array_values($filtered));
        }
        return false;
    }

    // ================= USERS ================= //

    public function getUsers(): array {
        return $this->readJson('users.json');
    }

    public function getUserById(string $id): ?array {
        $users = $this->readJson('users.json');
        foreach ($users as $u) {
            if (($u['id'] ?? '') === $id) return $u;
        }
        return null;
    }

    public function getUserByUsername(string $username): ?array {
        $users = $this->readJson('users.json');
        foreach ($users as $u) {
            if (strcasecmp($u['username'] ?? '', $username) === 0) return $u;
        }
        return null;
    }

    public function getUsersByClientId(string $clientId): array {
        $users = $this->readJson('users.json');
        return array_values(array_filter($users, fn($u) => ($u['client_id'] ?? '') === $clientId));
    }

    public function createUser(array $data): array {
        $users = $this->readJson('users.json');
        
        $passwordHash = !empty($data['password']) 
            ? (str_starts_with($data['password'], '$2y$') ? $data['password'] : password_hash($data['password'], PASSWORD_DEFAULT))
            : password_hash('password123', PASSWORD_DEFAULT);

        $user = [
            'id' => generateId('usr'),
            'client_id' => $data['client_id'] ?? null,
            'name' => $data['name'] ?? '',
            'username' => strtolower(trim($data['username'] ?? '')),
            'email' => strtolower(trim($data['email'] ?? '')),
            'password' => $passwordHash,
            'role' => $data['role'] ?? 'client', // 'super_admin' or 'client'
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $users[] = $user;
        $this->writeJson('users.json', $users);
        return $user;
    }

    public function updateUser(string $id, array $data): bool {
        $users = $this->readJson('users.json');
        $updated = false;

        foreach ($users as &$u) {
            if ($u['id'] === $id) {
                if (!empty($data['name'])) $u['name'] = $data['name'];
                if (!empty($data['email'])) $u['email'] = strtolower(trim($data['email']));
                if (!empty($data['username'])) $u['username'] = strtolower(trim($data['username']));
                if (!empty($data['password'])) {
                    $u['password'] = str_starts_with($data['password'], '$2y$') ? $data['password'] : password_hash($data['password'], PASSWORD_DEFAULT);
                }
                if (isset($data['status'])) $u['status'] = $data['status'];
                $u['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return $this->writeJson('users.json', $users);
        }
        return false;
    }

    public function deleteUser(string $id): bool {
        $users = $this->readJson('users.json');
        $filtered = array_filter($users, fn($u) => ($u['id'] ?? '') !== $id);
        if (count($filtered) !== count($users)) {
            return $this->writeJson('users.json', array_values($filtered));
        }
        return false;
    }

    // ================= EVENTS ================= //

    public function getEvents(?string $clientId = null): array {
        $events = $this->readJson('events.json');
        if ($clientId !== null) {
            $events = array_values(array_filter($events, fn($e) => ($e['client_id'] ?? '') === $clientId));
        }
        usort($events, fn($a, $b) => strcmp($b['start_date'] ?? '', $a['start_date'] ?? ''));
        return $events;
    }

    public function getEventById(string $id): ?array {
        $events = $this->readJson('events.json');
        foreach ($events as $e) {
            if (($e['id'] ?? '') === $id) return $e;
        }
        return null;
    }

    public function getEventBySlug(string $clientId, string $slug): ?array {
        $events = $this->readJson('events.json');
        foreach ($events as $e) {
            if (($e['client_id'] ?? '') === $clientId && ($e['slug'] ?? '') === $slug) {
                return $e;
            }
        }
        return null;
    }

    public function createEvent(array $data): array {
        $events = $this->readJson('events.json');

        $id = generateId('evt');
        $slug = !empty($data['slug']) ? slugify($data['slug']) : slugify($data['name'] ?? 'event');

        // Ensure unique slug per client
        $clientId = $data['client_id'] ?? '';
        $originalSlug = $slug;
        $counter = 1;
        while ($this->getEventBySlug($clientId, $slug) !== null) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $capacity = (int)($data['max_capacity'] ?? 500);
        $available = isset($data['available_seats']) ? (int)$data['available_seats'] : $capacity;

        $event = [
            'id' => $id,
            'client_id' => $clientId,
            'name' => $data['name'] ?? '',
            'slug' => $slug,
            'short_description' => $data['short_description'] ?? '',
            'full_description' => $data['full_description'] ?? '',
            'category' => $data['category'] ?? 'Festival & Cultural',
            'status' => $data['status'] ?? 'published', // 'draft', 'published', 'closed'
            'banner' => $data['banner'] ?? '',
            'logo' => $data['logo'] ?? '',
            'gallery' => is_array($data['gallery'] ?? null) ? $data['gallery'] : [],
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'start_time' => $data['start_time'] ?? '19:00',
            'end_date' => $data['end_date'] ?? ($data['start_date'] ?? date('Y-m-d')),
            'end_time' => $data['end_time'] ?? '23:00',
            'venue_name' => $data['venue_name'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'pincode' => $data['pincode'] ?? '',
            'google_maps_url' => $data['google_maps_url'] ?? '',
            'booking_open' => (bool)($data['booking_open'] ?? true),
            'max_capacity' => $capacity,
            'available_seats' => $available,
            'confirmation_mode' => $data['confirmation_mode'] ?? 'instant', // 'instant', 'manual'
            'price_label' => $data['price_label'] ?? 'Free Registration',
            'price_amount' => (float)($data['price_amount'] ?? 0),
            'contact_email' => $data['contact_email'] ?? '',
            'contact_phone' => $data['contact_phone'] ?? '',
            'contact_whatsapp' => $data['contact_whatsapp'] ?? '',
            'social_links' => is_array($data['social_links'] ?? null) ? $data['social_links'] : [],
            'highlights' => is_array($data['highlights'] ?? null) ? $data['highlights'] : [],
            'faqs' => is_array($data['faqs'] ?? null) ? $data['faqs'] : [],
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'og_image' => $data['og_image'] ?? '',
            'keywords' => $data['keywords'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $events[] = $event;
        $this->writeJson('events.json', $events);
        return $event;
    }

    public function updateEvent(string $id, array $data): bool {
        $events = $this->readJson('events.json');
        $updated = false;

        foreach ($events as &$e) {
            if ($e['id'] === $id) {
                foreach ($data as $key => $val) {
                    if ($key === 'id') continue;
                    if ($key === 'slug' && !empty($val)) {
                        $newSlug = slugify($val);
                        $existing = $this->getEventBySlug($e['client_id'], $newSlug);
                        if (!$existing || $existing['id'] === $id) {
                            $e['slug'] = $newSlug;
                        }
                    } else {
                        $e[$key] = $val;
                    }
                }
                $e['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return $this->writeJson('events.json', $events);
        }
        return false;
    }

    public function deleteEvent(string $id): bool {
        $events = $this->readJson('events.json');
        $filtered = array_filter($events, fn($e) => ($e['id'] ?? '') !== $id);
        if (count($filtered) !== count($events)) {
            return $this->writeJson('events.json', array_values($filtered));
        }
        return false;
    }

    // ================= DYNAMIC FORM FIELDS ================= //

    public function getFormFieldsByEventId(string $eventId): array {
        $allFields = $this->readJson('event_form_fields.json');
        $eventFields = array_values(array_filter($allFields, fn($f) => ($f['event_id'] ?? '') === $eventId));
        usort($eventFields, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        return $eventFields;
    }

    public function saveFormFields(string $eventId, array $fields): bool {
        $allFields = $this->readJson('event_form_fields.json');
        // Remove existing fields for this event
        $remaining = array_filter($allFields, fn($f) => ($f['event_id'] ?? '') !== $eventId);
        
        $order = 1;
        foreach ($fields as $field) {
            $field['id'] = $field['id'] ?? generateId('fld');
            $field['event_id'] = $eventId;
            $field['order'] = $order++;
            $remaining[] = $field;
        }

        return $this->writeJson('event_form_fields.json', array_values($remaining));
    }

    // ================= BOOKINGS ================= //

    public function getBookings(?string $clientId = null, ?string $eventId = null): array {
        $bookings = $this->readJson('bookings.json');
        if ($clientId !== null) {
            $bookings = array_filter($bookings, fn($b) => ($b['client_id'] ?? '') === $clientId);
        }
        if ($eventId !== null) {
            $bookings = array_filter($bookings, fn($b) => ($b['event_id'] ?? '') === $eventId);
        }
        $bookings = array_values($bookings);
        usort($bookings, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return $bookings;
    }

    public function getBookingById(string $id): ?array {
        $bookings = $this->readJson('bookings.json');
        foreach ($bookings as $b) {
            if (($b['id'] ?? '') === $id) return $b;
        }
        return null;
    }

    public function getBookingByNumber(string $bookingNumber): ?array {
        $bookings = $this->readJson('bookings.json');
        foreach ($bookings as $b) {
            if (strcasecmp($b['booking_number'] ?? '', $bookingNumber) === 0) return $b;
        }
        return null;
    }

    public function getBookingByQrToken(string $token): ?array {
        $bookings = $this->readJson('bookings.json');
        foreach ($bookings as $b) {
            if (($b['qr_token'] ?? '') === $token) return $b;
        }
        return null;
    }

    public function createBooking(array $bookingData, array $answers = []): array {
        $bookings = $this->readJson('bookings.json');
        $id = generateId('bk');
        $bookingNumber = $bookingData['booking_number'] ?? generateBookingNumber();
        $qrToken = $bookingData['qr_token'] ?? generateSecureQrToken();

        $booking = [
            'id' => $id,
            'client_id' => $bookingData['client_id'] ?? '',
            'event_id' => $bookingData['event_id'] ?? '',
            'booking_number' => $bookingNumber,
            'customer_name' => $bookingData['customer_name'] ?? '',
            'email' => $bookingData['email'] ?? '',
            'phone' => $bookingData['phone'] ?? '',
            'pass_count' => (int)($bookingData['pass_count'] ?? 1),
            'booking_date' => $bookingData['booking_date'] ?? date('Y-m-d'),
            'status' => $bookingData['status'] ?? 'confirmed', // pending, confirmed, cancelled, rejected, checked_in, expired
            'qr_token' => $qrToken,
            'checked_in_at' => $bookingData['checked_in_at'] ?? null,
            'checked_in_by' => $bookingData['checked_in_by'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $bookings[] = $booking;
        $this->writeJson('bookings.json', $bookings);

        // Store dynamic answers
        if (!empty($answers)) {
            $allAnswers = $this->readJson('booking_answers.json');
            foreach ($answers as $fieldKey => $fieldValue) {
                $allAnswers[] = [
                    'id' => generateId('ans'),
                    'booking_id' => $id,
                    'field_key' => $fieldKey,
                    'field_value' => is_array($fieldValue) ? json_encode($fieldValue) : (string)$fieldValue,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            $this->writeJson('booking_answers.json', $allAnswers);
        }

        // Store QR token mapping
        $qrTokens = $this->readJson('qr_tokens.json');
        $qrTokens[] = [
            'token' => $qrToken,
            'booking_id' => $id,
            'client_id' => $booking['client_id'],
            'event_id' => $booking['event_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->writeJson('qr_tokens.json', $qrTokens);

        // Decrement available seats on event
        if (!empty($booking['event_id'])) {
            $event = $this->getEventById($booking['event_id']);
            if ($event && isset($event['available_seats'])) {
                $newAvailable = max(0, $event['available_seats'] - $booking['pass_count']);
                $this->updateEvent($booking['event_id'], ['available_seats' => $newAvailable]);
            }
        }

        return $booking;
    }

    public function updateBooking(string $id, array $data): bool {
        $bookings = $this->readJson('bookings.json');
        $updated = false;

        foreach ($bookings as &$b) {
            if ($b['id'] === $id) {
                foreach ($data as $k => $v) {
                    if ($k === 'id') continue;
                    $b[$k] = $v;
                }
                $b['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return $this->writeJson('bookings.json', $bookings);
        }
        return false;
    }

    public function updateBookingStatus(string $id, string $status): bool {
        return $this->updateBooking($id, ['status' => $status]);
    }

    public function getBookingAnswers(string $bookingId): array {
        $answers = $this->readJson('booking_answers.json');
        return array_values(array_filter($answers, fn($a) => ($a['booking_id'] ?? '') === $bookingId));
    }

    // ================= QR SCANS ================= //

    public function recordQrScan(array $data): array {
        $scans = $this->readJson('qr_scans.json');
        $scan = [
            'id' => generateId('scan'),
            'booking_id' => $data['booking_id'] ?? '',
            'client_id' => $data['client_id'] ?? '',
            'event_id' => $data['event_id'] ?? '',
            'qr_token' => $data['qr_token'] ?? '',
            'status' => $data['status'] ?? 'valid', // valid, already_checked_in, wrong_event, invalid
            'scanned_by' => $data['scanned_by'] ?? 'System',
            'scanned_at' => date('Y-m-d H:i:s'),
            'device_info' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];
        $scans[] = $scan;
        $this->writeJson('qr_scans.json', $scans);
        return $scan;
    }

    public function getQrScans(?string $clientId = null, ?string $eventId = null): array {
        $scans = $this->readJson('qr_scans.json');
        if ($clientId !== null) {
            $scans = array_filter($scans, fn($s) => ($s['client_id'] ?? '') === $clientId);
        }
        if ($eventId !== null) {
            $scans = array_filter($scans, fn($s) => ($s['event_id'] ?? '') === $eventId);
        }
        $scans = array_values($scans);
        usort($scans, fn($a, $b) => strcmp($b['scanned_at'] ?? '', $a['scanned_at'] ?? ''));
        return $scans;
    }

    // ================= ACTIVITY LOGS ================= //

    public function logActivity(array $data): array {
        $logs = $this->readJson('activity_logs.json');
        $log = [
            'id' => generateId('act'),
            'actor_id' => $data['actor_id'] ?? 'system',
            'actor_role' => $data['actor_role'] ?? 'system',
            'client_id' => $data['client_id'] ?? null,
            'action' => $data['action'] ?? '',
            'entity_type' => $data['entity_type'] ?? '',
            'entity_id' => $data['entity_id'] ?? '',
            'description' => $data['description'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];
        $logs[] = $log;
        $this->writeJson('activity_logs.json', $logs);
        return $log;
    }

    public function getActivityLogs(?string $clientId = null, int $limit = 100): array {
        $logs = $this->readJson('activity_logs.json');
        if ($clientId !== null) {
            $logs = array_filter($logs, fn($l) => empty($l['client_id']) || $l['client_id'] === $clientId);
        }
        $logs = array_values($logs);
        usort($logs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return array_slice($logs, 0, $limit);
    }

    // ================= SETTINGS ================= //

    public function getSettings(): array {
        return $this->readJson('settings.json', [
            'platform_name' => 'Utsavam',
            'tagline' => 'A place for celebrations and events.',
            'support_email' => 'support@utsavam.com',
            'contact_phone' => '+91 98765 43210',
            'smtp_enabled' => false,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_encryption' => 'tls',
            'from_address' => 'support@utsavam.com',
            'from_name' => 'Utsavam',
            'allow_client_registration' => false
        ]);
    }

    public function updateSettings(array $settings): bool {
        $current = $this->getSettings();
        $merged = array_merge($current, $settings);
        return $this->writeJson('settings.json', $merged);
    }
}
