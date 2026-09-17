-- ==========================================================
-- Utsavam - Prepared Queries Reference for MySQL
-- ==========================================================

-- 1. Create Client
INSERT INTO `clients` (
    `id`, `code`, `slug`, `name`, `company_name`, `email`, `mobile`, `address`, `logo`, `status`, `created_at`, `updated_at`
) VALUES (
    :id, :code, :slug, :name, :company_name, :email, :mobile, :address, :logo, 'active', NOW(), NOW()
);

-- 2. Create Event
INSERT INTO `events` (
    `id`, `client_id`, `name`, `slug`, `category`, `short_description`, `full_description`,
    `start_date`, `start_time`, `end_date`, `end_time`, `venue_name`, `address`, `city`, `state`,
    `pincode`, `google_maps_url`, `booking_open`, `max_capacity`, `available_seats`, `confirmation_mode`,
    `price_label`, `price_amount`, `contact_email`, `contact_phone`, `contact_whatsapp`, `created_at`, `updated_at`
) VALUES (
    :id, :client_id, :name, :slug, :category, :short_description, :full_description,
    :start_date, :start_time, :end_date, :end_time, :venue_name, :address, :city, :state,
    :pincode, :google_maps_url, 1, :max_capacity, :available_seats, :confirmation_mode,
    :price_label, :price_amount, :contact_email, :contact_phone, :contact_whatsapp, NOW(), NOW()
);

-- 3. Get Client Events (Strict Multi-Tenant Filter)
SELECT * FROM `events`
WHERE `client_id` = :client_id
ORDER BY `start_date` DESC;

-- 4. Get Client Bookings with Event Title and Search Filter
SELECT 
    b.*,
    e.name AS event_name,
    e.start_date AS event_date,
    e.venue_name
FROM `bookings` b
INNER JOIN `events` e ON b.event_id = e.id
WHERE b.client_id = :client_id
  AND (:event_id IS NULL OR b.event_id = :event_id)
  AND (:status IS NULL OR b.status = :status)
  AND (:search IS NULL OR (b.booking_number LIKE CONCAT('%', :search, '%') OR b.customer_name LIKE CONCAT('%', :search, '%') OR b.email LIKE CONCAT('%', :search, '%') OR b.phone LIKE CONCAT('%', :search, '%')))
ORDER BY b.created_at DESC;

-- 5. QR Verification Query
SELECT 
    b.id AS booking_id,
    b.client_id,
    b.event_id,
    b.booking_number,
    b.customer_name,
    b.pass_count,
    b.status AS booking_status,
    b.checked_in_at,
    b.checked_in_by,
    e.name AS event_name,
    e.start_date,
    e.venue_name,
    c.name AS client_name
FROM `bookings` b
INNER JOIN `events` e ON b.event_id = e.id
INNER JOIN `clients` c ON b.client_id = c.id
WHERE b.qr_token = :qr_token
LIMIT 1;

-- 6. Check-In Query (Prevents Duplicate Check-In)
UPDATE `bookings`
SET 
    `status` = 'checked_in',
    `checked_in_at` = NOW(),
    `checked_in_by` = :actor_name,
    `updated_at` = NOW()
WHERE `id` = :booking_id
  AND `client_id` = :client_id
  AND `status` != 'checked_in';

-- Record Check-in Scan Log
INSERT INTO `qr_scans` (
    `id`, `booking_id`, `client_id`, `event_id`, `qr_token`, `status`, `scanned_by`, `device_info`, `ip_address`, `scanned_at`
) VALUES (
    :scan_id, :booking_id, :client_id, :event_id, :qr_token, :status, :scanned_by, :device_info, :ip_address, NOW()
);

-- 7. Event-Wise Analytical Report
SELECT 
    e.id AS event_id,
    e.name AS event_name,
    e.max_capacity,
    e.available_seats,
    COUNT(b.id) AS total_bookings,
    SUM(b.pass_count) AS total_attendees,
    SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
    SUM(CASE WHEN b.status = 'checked_in' THEN 1 ELSE 0 END) AS checked_in_count,
    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
FROM `events` e
LEFT JOIN `bookings` b ON e.id = b.event_id
WHERE e.client_id = :client_id
GROUP BY e.id, e.name, e.max_capacity, e.available_seats;

-- 8. Date-Wise Trend Report
SELECT 
    b.booking_date,
    COUNT(b.id) AS total_bookings_on_date,
    SUM(b.pass_count) AS total_tickets_on_date,
    SUM(CASE WHEN b.status = 'checked_in' THEN 1 ELSE 0 END) AS checked_ins_on_date
FROM `bookings` b
WHERE b.client_id = :client_id
  AND b.booking_date BETWEEN :start_date AND :end_date
GROUP BY b.booking_date
ORDER BY b.booking_date ASC;
