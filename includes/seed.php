<?php
/**
 * Utsavam Production Initializer
 * Seeds the initial Super Admin account for platform administration.
 * Strictly avoids demo events, sample clients, or fake bookings in production.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage/DataStoreFactory.php';

function seedProductionAdmin(): void {
    $store = getDataStore();
    
    // Check if super admin already exists
    $admin = $store->getUserByUsername('admin');
    if ($admin) {
        return; // Super admin already initialized
    }

    // Provision the single Super Administrator account
    $store->createUser([
        'name' => 'System Administrator',
        'username' => 'admin',
        'email' => 'admin@utsavam.digitechitsolution.com',
        'password' => 'admin123',
        'role' => 'super_admin',
        'client_id' => null,
        'status' => 'active'
    ]);
}

function seedUtsavamDemoData(): void {
    seedProductionAdmin();
}

function seedDatabase(): void {
    seedProductionAdmin();
}

// Auto-run if executed from CLI directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    seedProductionAdmin();
    echo "Utsavam production administrator initialized successfully.\n";
}

