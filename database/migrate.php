<?php
/**
 * Utsavam Database Schema Migration Utility
 * Ensures all required columns exist in MySQL database (e.g. Hostinger / Cloud SQL / cPanel).
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

function runDatabaseMigrations(): array {
    $results = [];

    if (!extension_loaded('pdo_mysql') || !in_array('mysql', PDO::getAvailableDrivers())) {
        return ['status' => 'skipped', 'message' => 'PDO MySQL driver not available.'];
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $migrations = [
            // Clients table columns
            "ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `terms_and_conditions` LONGTEXT NULL",
            "ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `cancellation_policy` LONGTEXT NULL",
            "ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `upi_id` VARCHAR(191) NULL",
            "ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `upi_name` VARCHAR(191) NULL",
            "ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `upi_qr_code` VARCHAR(500) NULL",
            "ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `payment_instructions` TEXT NULL",
            
            // Events table columns
            "ALTER TABLE `events` ADD COLUMN IF NOT EXISTS `show_gallery` TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE `events` ADD COLUMN IF NOT EXISTS `packages` JSON NULL",

            // Bookings table columns
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `package_id` VARCHAR(64) NULL",
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `package_name` VARCHAR(191) NULL",
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `package_price` DECIMAL(10,2) NULL DEFAULT 0.00",
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `total_amount` DECIMAL(10,2) NULL DEFAULT 0.00",
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `payment_status` VARCHAR(64) NOT NULL DEFAULT 'pending_verification'",
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `utr_number` VARCHAR(100) NULL",
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(64) NULL DEFAULT 'upi'",
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `payment_verified_at` DATETIME NULL",
            "ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `payment_verified_by` VARCHAR(191) NULL",
        ];

        foreach ($migrations as $sql) {
            try {
                $pdo->exec($sql);
                $results[] = ['sql' => $sql, 'status' => 'success'];
            } catch (Throwable $e) {
                // If MySQL version doesn't support IF NOT EXISTS in ADD COLUMN (older than 8.0.29)
                if (strpos($e->getMessage(), 'IF NOT EXISTS') !== false || strpos($e->getMessage(), 'syntax') !== false) {
                    // Fallback to inspection check
                    if (preg_match('/ALTER\s+TABLE\s+`?(\w+)`?\s+ADD\s+COLUMN\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?\s+(.*)/i', $sql, $m)) {
                        $table = $m[1];
                        $col = $m[2];
                        $def = $m[3];
                        try {
                            $chk = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'")->fetch();
                            if (!$chk) {
                                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
                                $results[] = ['sql' => "ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}", 'status' => 'success'];
                            } else {
                                $results[] = ['sql' => "{$table}.{$col}", 'status' => 'already_exists'];
                            }
                        } catch (Throwable $inner) {
                            $results[] = ['sql' => $sql, 'status' => 'error', 'message' => $inner->getMessage()];
                        }
                    }
                } elseif (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    $results[] = ['sql' => $sql, 'status' => 'already_exists'];
                } else {
                    $results[] = ['sql' => $sql, 'status' => 'notice', 'message' => $e->getMessage()];
                }
            }
        }

        return ['status' => 'completed', 'details' => $results];
    } catch (Throwable $e) {
        return ['status' => 'connection_failed', 'message' => $e->getMessage()];
    }
}

// Auto-run if executed from CLI or directly accessed by admin
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "Running database schema migrations for Utsavam...\n";
    $res = runDatabaseMigrations();
    echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
}
