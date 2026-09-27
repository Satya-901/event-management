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
    if (!$admin) {
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

    // Seed Dandiya Night Event if no clients exist
    seedDandiyaEvent();
}

function seedDandiyaEvent(): void {
    try {
        $store = getDataStore();
        $existingClient = $store->getClientBySlug('ak-events');
        if (!$existingClient) {
            $existingClient = $store->createClient([
                'name' => 'AK Events',
                'company_name' => 'AK Events & Entertainment Pvt Ltd',
                'email' => 'support@akevents.com',
                'mobile' => '+91 7003624933',
                'address' => 'Medical College Road, Gorakhpur, Uttar Pradesh',
                'code' => 'AKE-7003',
                'slug' => 'ak-events',
                'terms_and_conditions' => '<h3>1. Admission & Entry Policy</h3><p>Every attendee must present a valid digital QR pass at the entrance gate before entry is permitted.</p><h3>2. Dress Code & Conduct</h3><p>Traditional festive attire is encouraged for Garba and Dandiya. Disruptive behavior will result in immediate removal without refund.</p><h3>3. Dandiya Sticks</h3><p>Dandiya sticks are available at designated stalls inside the venue.</p>',
                'cancellation_policy' => '<h3>1. Non-Refundable Passes</h3><p>All ticket purchases are final. In case of complete organizer cancellation, 100% refund will be processed within 7 business days.</p><h3>2. Ticket Transfers</h3><p>Pass name transfers are allowed up to 24 hours prior to the event.</p>',
                'status' => 'active'
            ]);

            // Create client organizer user
            $store->createUser([
                'name' => 'AK Events Admin',
                'username' => 'akevents',
                'email' => 'admin@akevents.com',
                'password' => 'akevents123',
                'role' => 'client',
                'client_id' => $existingClient['id'],
                'status' => 'active'
            ]);
        }

        $existingEvent = $store->getEventBySlug($existingClient['id'], 'dandiya-night-2026');
        if (!$existingEvent) {
            $store->createEvent([
                'client_id' => $existingClient['id'],
                'name' => 'Gorakhpur’s Biggest Dandiya Night 2026',
                'slug' => 'dandiya-night-2026',
                'category' => 'ENTERTAINMENT',
                'short_description' => 'Get ready for Gorakhpur’s most vibrant festive celebration! Dandiya Night 2026 brings together the perfect mix of Garba, Dandiya, DJ music, live entertainment, shopping, food, fashion and non-stop festive masti. Put on your traditional best and get ready to dance, celebrate and create unforgettable memories with your family and friends.',
                'full_description' => 'Get ready for Gorakhpur’s most vibrant festive celebration! Dandiya Night 2026 brings together the perfect mix of Garba, Dandiya, DJ music, live entertainment, shopping, food, fashion and non-stop festive masti. Put on your traditional best and get ready to dance, celebrate and create unforgettable memories with your family and friends. Experience high-energy live percussion, grand selfie zones, luxury food stalls, and prizes for Best Dressed Couple, Best Dancer, and lucky draw gifts all night including a Manali Gift Voucher!',
                'banner' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1600&q=85',
                'start_date' => '2026-10-18',
                'start_time' => '18:00',
                'end_date' => '2026-10-18',
                'end_time' => '23:00',
                'venue_name' => 'Parinaya Wala Lawn',
                'address' => 'Medical College Road, Opposite City Hospital, Moghla',
                'city' => 'Gorakhpur',
                'state' => 'Uttar Pradesh',
                'pincode' => '273013',
                'google_maps_url' => 'https://maps.google.com/?q=Parinaya+Wala+Lawn+Medical+College+Road+Gorakhpur',
                'booking_open' => 1,
                'max_capacity' => 1500,
                'available_seats' => 1420,
                'confirmation_mode' => 'instant',
                'price_label' => 'Starting from ₹ 399 Onwards',
                'price_amount' => 399,
                'packages' => [
                    [
                        'id' => 'pkg_female_stag',
                        'name' => 'Female Stag Entry',
                        'price' => 399,
                        'capacity' => 400,
                        'badge' => 'Early Bird',
                        'description' => 'Single female admission with access to Dandiya Arena and complimentary soft drink'
                    ],
                    [
                        'id' => 'pkg_male_stag',
                        'name' => 'Male Stag Entry',
                        'price' => 499,
                        'capacity' => 400,
                        'badge' => '',
                        'description' => 'Single male admission with access to Dandiya Arena and DJ dance floor'
                    ],
                    [
                        'id' => 'pkg_couple',
                        'name' => 'Couple Pass',
                        'price' => 799,
                        'capacity' => 300,
                        'badge' => 'Most Popular',
                        'description' => 'Entry for one couple (1 Female + 1 Male) with complimentary Dandiya sticks'
                    ],
                    [
                        'id' => 'pkg_group_4',
                        'name' => 'Group of 4 Pass',
                        'price' => 1399,
                        'capacity' => 200,
                        'badge' => 'Great Value',
                        'description' => 'Group pass for 4 attendees with 2 sets of Dandiya sticks included'
                    ],
                    [
                        'id' => 'pkg_group_6',
                        'name' => 'Group of 6 Pass',
                        'price' => 1699,
                        'capacity' => 100,
                        'badge' => 'Best Value',
                        'description' => 'VIP group admission for 6 attendees with 3 sets of Dandiya sticks and reserved lounge seating'
                    ]
                ],
                'contact_email' => 'support@akevents.com',
                'contact_phone' => '+91 7003624933',
                'contact_whatsapp' => '+91 7003624933',
                'status' => 'published'
            ]);
        }
    } catch (Throwable $e) {
        error_log("Seed notice: " . $e->getMessage());
    }
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

