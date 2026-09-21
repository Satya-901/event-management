<?php
/**
 * Utsavam SqlDataStore
 * Prepared statements PDO SQL DataStore for future MySQL migration.
 */

require_once __DIR__ . '/DataStore.php';
require_once __DIR__ . '/../helpers.php';

class SqlDataStore implements DataStore {
    protected ?PDO $pdo = null;

    public function __construct() {
        // Lazy PDO connection with prepared statements
        $this->getPdo();
    }

    protected function getPdo(): PDO {
        if ($this->pdo === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            try {
                $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $this->ensureSchema();
            } catch (Throwable $e) {
                // In local or json mode, handle gracefully
                throw new Exception("SQL Database connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }

    protected function ensureSchema(): void {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'clients'")->fetch();
            if (!$check) {
                $schemaFile = __DIR__ . '/../../database/schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    // Remove CREATE DATABASE and USE statements so it executes inside connected database
                    $sql = preg_replace('/CREATE\s+DATABASE[^\;]+;/i', '', $sql);
                    $sql = preg_replace('/USE\s+[`\w]+;/i', '', $sql);
                    $this->pdo->exec($sql);

                    // Seed initial administrative user
                    require_once __DIR__ . '/../seed.php';
                    if (function_exists('seedProductionAdmin')) {
                        seedProductionAdmin();
                    }
                }
            }
        } catch (Throwable $t) {
            error_log("Schema auto-provision notice: " . $t->getMessage());
        }
    }

    public function getClients(): array {
        $stmt = $this->getPdo()->query("SELECT * FROM clients ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getClientById(string $id): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM clients WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getClientBySlug(string $slug): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM clients WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getClientByCode(string $code): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM clients WHERE code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createClient(array $data): array {
        $id = generateId('client');
        $code = !empty($data['code']) ? $data['code'] : generateClientCode();
        $slug = !empty($data['slug']) ? slugify($data['slug']) : slugify($data['name'] ?? 'client');

        $sql = "INSERT INTO clients (id, name, company_name, email, mobile, address, logo, status, code, slug, created_at, updated_at)
                VALUES (:id, :name, :company_name, :email, :mobile, :address, :logo, :status, :code, :slug, NOW(), NOW())";
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'] ?? '',
            'company_name' => $data['company_name'] ?? ($data['name'] ?? ''),
            'email' => $data['email'] ?? '',
            'mobile' => $data['mobile'] ?? '',
            'address' => $data['address'] ?? '',
            'logo' => $data['logo'] ?? '',
            'status' => $data['status'] ?? 'active',
            'code' => $code,
            'slug' => $slug
        ]);

        return $this->getClientById($id);
    }

    public function updateClient(string $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if ($key === 'id') continue;
            $fields[] = "`{$key}` = :{$key}";
            $params[$key] = $value;
        }

        if (empty($fields)) return false;

        $fields[] = "`updated_at` = NOW()";
        $sql = "UPDATE clients SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteClient(string $id): bool {
        $stmt = $this->getPdo()->prepare("DELETE FROM clients WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getUsers(): array {
        $stmt = $this->getPdo()->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getUserById(string $id): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function getUserByUsername(string $username): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => strtolower($username)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function getUsersByClientId(string $clientId): array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM users WHERE client_id = :client_id");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function createUser(array $data): array {
        $id = generateId('usr');
        $hash = !empty($data['password']) ? (str_starts_with($data['password'], '$2y$') ? $data['password'] : password_hash($data['password'], PASSWORD_DEFAULT)) : password_hash('password123', PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (id, client_id, name, username, email, password, role, status, created_at, updated_at)
                VALUES (:id, :client_id, :name, :username, :email, :password, :role, :status, NOW(), NOW())";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'client_id' => $data['client_id'] ?? null,
            'name' => $data['name'] ?? '',
            'username' => strtolower(trim($data['username'] ?? '')),
            'email' => strtolower(trim($data['email'] ?? '')),
            'password' => $hash,
            'role' => $data['role'] ?? 'client',
            'status' => $data['status'] ?? 'active'
        ]);

        return $this->getUserById($id);
    }

    public function updateUser(string $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $k => $v) {
            if ($k === 'id') continue;
            if ($k === 'password') {
                $v = str_starts_with($v, '$2y$') ? $v : password_hash($v, PASSWORD_DEFAULT);
            }
            $fields[] = "`{$k}` = :{$k}";
            $params[$k] = $v;
        }

        if (empty($fields)) return false;
        $fields[] = "`updated_at` = NOW()";

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->getPdo()->prepare($sql)->execute($params);
    }

    public function deleteUser(string $id): bool {
        $stmt = $this->getPdo()->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getEvents(?string $clientId = null): array {
        if ($clientId !== null) {
            $stmt = $this->getPdo()->prepare("SELECT * FROM events WHERE client_id = :client_id ORDER BY start_date DESC");
            $stmt->execute(['client_id' => $clientId]);
            return $stmt->fetchAll();
        }
        $stmt = $this->getPdo()->query("SELECT * FROM events ORDER BY start_date DESC");
        return $stmt->fetchAll();
    }

    public function getEventById(string $id): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM events WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function getEventBySlug(string $clientId, string $slug): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM events WHERE client_id = :client_id AND slug = :slug LIMIT 1");
        $stmt->execute(['client_id' => $clientId, 'slug' => $slug]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function createEvent(array $data): array {
        $id = generateId('evt');
        $slug = !empty($data['slug']) ? slugify($data['slug']) : slugify($data['name'] ?? 'event');

        $sql = "INSERT INTO events (id, client_id, name, slug, short_description, full_description, category, status, banner, logo, gallery, start_date, start_time, end_date, end_time, venue_name, address, city, state, pincode, google_maps_url, booking_open, max_capacity, available_seats, confirmation_mode, price_label, price_amount, contact_email, contact_phone, contact_whatsapp, social_links, highlights, faqs, meta_title, meta_description, og_image, keywords, created_at, updated_at)
                VALUES (:id, :client_id, :name, :slug, :short_description, :full_description, :category, :status, :banner, :logo, :gallery, :start_date, :start_time, :end_date, :end_time, :venue_name, :address, :city, :state, :pincode, :google_maps_url, :booking_open, :max_capacity, :available_seats, :confirmation_mode, :price_label, :price_amount, :contact_email, :contact_phone, :contact_whatsapp, :social_links, :highlights, :faqs, :meta_title, :meta_description, :og_image, :keywords, NOW(), NOW())";
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'client_id' => $data['client_id'] ?? '',
            'name' => $data['name'] ?? '',
            'slug' => $slug,
            'short_description' => $data['short_description'] ?? '',
            'full_description' => $data['full_description'] ?? '',
            'category' => $data['category'] ?? 'Festival & Cultural',
            'status' => $data['status'] ?? 'published',
            'banner' => $data['banner'] ?? '',
            'logo' => $data['logo'] ?? '',
            'gallery' => json_encode($data['gallery'] ?? []),
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
            'booking_open' => (int)($data['booking_open'] ?? 1),
            'max_capacity' => (int)($data['max_capacity'] ?? 500),
            'available_seats' => (int)($data['available_seats'] ?? ($data['max_capacity'] ?? 500)),
            'confirmation_mode' => $data['confirmation_mode'] ?? 'instant',
            'price_label' => $data['price_label'] ?? 'Free Registration',
            'price_amount' => (float)($data['price_amount'] ?? 0),
            'contact_email' => $data['contact_email'] ?? '',
            'contact_phone' => $data['contact_phone'] ?? '',
            'contact_whatsapp' => $data['contact_whatsapp'] ?? '',
            'social_links' => json_encode($data['social_links'] ?? []),
            'highlights' => json_encode($data['highlights'] ?? []),
            'faqs' => json_encode($data['faqs'] ?? []),
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'og_image' => $data['og_image'] ?? '',
            'keywords' => $data['keywords'] ?? ''
        ]);

        return $this->getEventById($id);
    }

    public function updateEvent(string $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $k => $v) {
            if ($k === 'id') continue;
            if (is_array($v)) $v = json_encode($v);
            $fields[] = "`{$k}` = :{$k}";
            $params[$k] = $v;
        }

        if (empty($fields)) return false;
        $fields[] = "`updated_at` = NOW()";

        $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->getPdo()->prepare($sql)->execute($params);
    }

    public function deleteEvent(string $id): bool {
        $stmt = $this->getPdo()->prepare("DELETE FROM events WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getFormFieldsByEventId(string $eventId): array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM event_form_fields WHERE event_id = :event_id ORDER BY `order` ASC");
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function saveFormFields(string $eventId, array $fields): bool {
        $pdo = $this->getPdo();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM event_form_fields WHERE event_id = :event_id");
            $del->execute(['event_id' => $eventId]);

            $ins = $pdo->prepare("INSERT INTO event_form_fields (id, event_id, field_key, field_label, field_type, placeholder, required, options, `order`)
                                  VALUES (:id, :event_id, :field_key, :field_label, :field_type, :placeholder, :required, :options, :order)");
            $order = 1;
            foreach ($fields as $f) {
                $ins->execute([
                    'id' => $f['id'] ?? generateId('fld'),
                    'event_id' => $eventId,
                    'field_key' => $f['field_key'] ?? '',
                    'field_label' => $f['field_label'] ?? '',
                    'field_type' => $f['field_type'] ?? 'text',
                    'placeholder' => $f['placeholder'] ?? '',
                    'required' => (int)($f['required'] ?? 0),
                    'options' => json_encode($f['options'] ?? []),
                    'order' => $order++
                ]);
            }
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function getBookings(?string $clientId = null, ?string $eventId = null): array {
        $sql = "SELECT * FROM bookings WHERE 1=1";
        $params = [];
        if ($clientId !== null) {
            $sql .= " AND client_id = :client_id";
            $params['client_id'] = $clientId;
        }
        if ($eventId !== null) {
            $sql .= " AND event_id = :event_id";
            $params['event_id'] = $eventId;
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getBookingById(string $id): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM bookings WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getBookingByNumber(string $bookingNumber): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM bookings WHERE booking_number = :booking_number LIMIT 1");
        $stmt->execute(['booking_number' => $bookingNumber]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getBookingByQrToken(string $token): ?array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM bookings WHERE qr_token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createBooking(array $bookingData, array $answers = []): array {
        $id = generateId('bk');
        $bookingNumber = $bookingData['booking_number'] ?? generateBookingNumber();
        $qrToken = $bookingData['qr_token'] ?? generateSecureQrToken();

        $sql = "INSERT INTO bookings (id, client_id, event_id, booking_number, customer_name, email, phone, pass_count, booking_date, status, qr_token, ip_address, created_at, updated_at)
                VALUES (:id, :client_id, :event_id, :booking_number, :customer_name, :email, :phone, :pass_count, :booking_date, :status, :qr_token, :ip_address, NOW(), NOW())";
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'client_id' => $bookingData['client_id'] ?? '',
            'event_id' => $bookingData['event_id'] ?? '',
            'booking_number' => $bookingNumber,
            'customer_name' => $bookingData['customer_name'] ?? '',
            'email' => $bookingData['email'] ?? '',
            'phone' => $bookingData['phone'] ?? '',
            'pass_count' => (int)($bookingData['pass_count'] ?? 1),
            'booking_date' => $bookingData['booking_date'] ?? date('Y-m-d'),
            'status' => $bookingData['status'] ?? 'confirmed',
            'qr_token' => $qrToken,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        if (!empty($answers)) {
            $ansStmt = $this->getPdo()->prepare("INSERT INTO booking_answers (id, booking_id, field_key, field_value, created_at) VALUES (:id, :booking_id, :field_key, :field_value, NOW())");
            foreach ($answers as $k => $v) {
                $ansStmt->execute([
                    'id' => generateId('ans'),
                    'booking_id' => $id,
                    'field_key' => $k,
                    'field_value' => is_array($v) ? json_encode($v) : (string)$v
                ]);
            }
        }

        return $this->getBookingById($id);
    }

    public function updateBooking(string $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $k => $v) {
            if ($k === 'id') continue;
            $fields[] = "`{$k}` = :{$k}";
            $params[$k] = $v;
        }
        if (empty($fields)) return false;
        $fields[] = "`updated_at` = NOW()";
        $sql = "UPDATE bookings SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->getPdo()->prepare($sql)->execute($params);
    }

    public function updateBookingStatus(string $id, string $status): bool {
        return $this->updateBooking($id, ['status' => $status]);
    }

    public function getBookingAnswers(string $bookingId): array {
        $stmt = $this->getPdo()->prepare("SELECT * FROM booking_answers WHERE booking_id = :booking_id");
        $stmt->execute(['booking_id' => $bookingId]);
        return $stmt->fetchAll();
    }

    public function recordQrScan(array $data): array {
        $id = generateId('scan');
        $sql = "INSERT INTO qr_scans (id, booking_id, client_id, event_id, qr_token, status, scanned_by, scanned_at, device_info, ip_address)
                VALUES (:id, :booking_id, :client_id, :event_id, :qr_token, :status, :scanned_by, NOW(), :device_info, :ip_address)";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'booking_id' => $data['booking_id'] ?? '',
            'client_id' => $data['client_id'] ?? '',
            'event_id' => $data['event_id'] ?? '',
            'qr_token' => $data['qr_token'] ?? '',
            'status' => $data['status'] ?? 'valid',
            'scanned_by' => $data['scanned_by'] ?? 'System',
            'device_info' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        return array_merge($data, ['id' => $id, 'scanned_at' => date('Y-m-d H:i:s')]);
    }

    public function getQrScans(?string $clientId = null, ?string $eventId = null): array {
        $sql = "SELECT * FROM qr_scans WHERE 1=1";
        $params = [];
        if ($clientId !== null) {
            $sql .= " AND client_id = :client_id";
            $params['client_id'] = $clientId;
        }
        if ($eventId !== null) {
            $sql .= " AND event_id = :event_id";
            $params['event_id'] = $eventId;
        }
        $sql .= " ORDER BY scanned_at DESC";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function logActivity(array $data): array {
        $id = generateId('act');
        $sql = "INSERT INTO activity_logs (id, actor_id, actor_role, client_id, action, entity_type, entity_id, description, ip_address, created_at)
                VALUES (:id, :actor_id, :actor_role, :client_id, :action, :entity_type, :entity_id, :description, :ip_address, NOW())";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'actor_id' => $data['actor_id'] ?? 'system',
            'actor_role' => $data['actor_role'] ?? 'system',
            'client_id' => $data['client_id'] ?? null,
            'action' => $data['action'] ?? '',
            'entity_type' => $data['entity_type'] ?? '',
            'entity_id' => $data['entity_id'] ?? '',
            'description' => $data['description'] ?? '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        return array_merge($data, ['id' => $id, 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function getActivityLogs(?string $clientId = null, int $limit = 100): array {
        $sql = "SELECT * FROM activity_logs WHERE 1=1";
        $params = [];
        if ($clientId !== null) {
            $sql .= " AND (client_id IS NULL OR client_id = :client_id)";
            $params['client_id'] = $clientId;
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSettings(): array {
        $stmt = $this->getPdo()->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = json_decode($row['setting_value'], true) ?? $row['setting_value'];
        }
        return $settings;
    }

    public function updateSettings(array $settings): bool {
        $stmt = $this->getPdo()->prepare("INSERT INTO settings (setting_key, setting_value, updated_at)
                                          VALUES (:key, :value, NOW())
                                          ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = NOW()");
        foreach ($settings as $k => $v) {
            $val = is_array($v) ? json_encode($v) : (string)$v;
            $stmt->execute(['key' => $k, 'value' => $val]);
        }
        return true;
    }
}
