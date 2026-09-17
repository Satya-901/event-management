<?php
/**
 * Utsavam Demo Data Seeder
 * Seeds Royal Events client, Dandiya Night 2026 event, admin and client users,
 * and realistic bookings with valid QR tokens.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage/DataStoreFactory.php';

function seedUtsavamDemoData(): void {
    $store = getDataStore();
    
    // Check if clients already exist
    $clients = $store->getClients();
    if (!empty($clients)) {
        return; // Already seeded
    }

    // 1. Seed Super Admin User
    $adminUser = $store->createUser([
        'name' => 'System Administrator',
        'username' => 'admin',
        'email' => 'admin@utsavam.com',
        'password' => 'admin123',
        'role' => 'super_admin',
        'status' => 'active'
    ]);

    // 2. Seed Client "Royal Events"
    $royalClient = $store->createClient([
        'name' => 'Royal Events',
        'company_name' => 'Royal Celebrations & Event Management Pvt Ltd',
        'email' => 'contact@royalevents.in',
        'mobile' => '+91 98860 12345',
        'address' => '#42, Palace Cross Road, Vasanth Nagar, Bengaluru, Karnataka 560052',
        'code' => 'UTS-8F42K',
        'slug' => 'royal-events',
        'logo' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=200&q=80',
        'status' => 'active'
    ]);

    // 3. Seed Client User
    $clientUser = $store->createUser([
        'client_id' => $royalClient['id'],
        'name' => 'Rajesh Sharma',
        'username' => 'organizer',
        'email' => 'organizer@royalevents.in',
        'password' => 'password123',
        'role' => 'client',
        'status' => 'active'
    ]);

    // 4. Seed Main Demo Event: "Dandiya Night 2026"
    $dandiyaEvent = $store->createEvent([
        'client_id' => $royalClient['id'],
        'name' => 'Dandiya Night 2026',
        'slug' => 'dandiya-night-2026',
        'short_description' => 'An evening of music, dance & celebration in the heart of Bengaluru.',
        'full_description' => "Get ready for Bengaluru's biggest Navratri celebration! Utsavam presents Dandiya Night 2026 hosted by Royal Events. Experience pulsating beats with top traditional Gujarati and Bollywood folk bands, celebrity garba dancers, authentic food stalls, and mesmerizing cultural festivities. Bring your friends and family dressed in your finest traditional attire and dance the night away under the stars!",
        'category' => 'Festival & Cultural',
        'status' => 'published',
        'banner' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=85',
        'logo' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=200&q=80',
        'gallery' => [
            'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&w=800&q=80'
        ],
        'start_date' => '2026-10-24',
        'start_time' => '19:00',
        'end_date' => '2026-10-24',
        'end_time' => '23:30',
        'venue_name' => 'Palace Grounds, Gayatri Vihar',
        'address' => 'Near Mehkri Circle, Bellary Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'pincode' => '560006',
        'google_maps_url' => 'https://maps.google.com/?q=Palace+Grounds+Bengaluru',
        'booking_open' => 1,
        'max_capacity' => 1500,
        'available_seats' => 1472,
        'confirmation_mode' => 'instant',
        'price_label' => 'Standard Entry (Includes Dandiya Sticks)',
        'price_amount' => 499,
        'contact_email' => 'dandiya@royalevents.in',
        'contact_phone' => '+91 98860 12345',
        'contact_whatsapp' => '+91 98860 12345',
        'social_links' => [
            'instagram' => 'https://instagram.com/royalevents',
            'facebook' => 'https://facebook.com/royalevents',
            'youtube' => 'https://youtube.com'
        ],
        'highlights' => [
            'Live Orchestra featuring 12 renowned folk musicians from Ahmedabad',
            'Complimentary authentic wooden Dandiya sticks at the entrance',
            'Multi-cuisine festive food street with 30+ gourmet vegetarian stalls',
            'Professional DJ set fusion with Dhol tasha beats',
            'Best Dressed and Best Dancer prizes worth ₹50,000',
            'Ample free parking space and dedicated air-conditioned family lounges'
        ],
        'faqs' => [
            [
                'question' => 'What is the dress code for Dandiya Night 2026?',
                'answer' => 'Traditional ethnic wear is strongly encouraged! Chaniya Cholis, Kurta Pyjamas, Kediya, and Sarees create the vibrant festive ambiance.'
            ],
            [
                'question' => 'Are dandiya sticks provided or should we bring our own?',
                'answer' => 'Every registered attendee receives one complimentary pair of polished Dandiya sticks upon check-in at the gate!'
            ],
            [
                'question' => 'Is this a family-friendly event?',
                'answer' => 'Yes, absolutely! We have dedicated family areas, seating zones for senior citizens, and tight professional security.'
            ],
            [
                'question' => 'How does the digital QR pass work?',
                'answer' => 'Once you complete your registration, a unique QR code is generated instantly. Simply show this QR code on your phone or print it for contact-free entry at the gates.'
            ]
        ],
        'meta_title' => 'Dandiya Night 2026 | Royal Events Bengaluru',
        'meta_description' => 'Join the grandest Dandiya & Garba celebration in Bengaluru on 24 October 2026 at Palace Grounds.',
        'og_image' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=85',
        'keywords' => 'Dandiya Night 2026, Bengaluru Garba, Navratri celebration, Royal Events'
    ]);

    // 5. Seed a second event for Royal Events to demonstrate multi-event listing logic:
    // "Diwali Gala & Lights Festival 2026"
    $diwaliEvent = $store->createEvent([
        'client_id' => $royalClient['id'],
        'name' => 'Diwali Gala & Lights Festival 2026',
        'slug' => 'diwali-gala-2026',
        'short_description' => 'A dazzling night of fireworks, musical symphony, and festive dining.',
        'full_description' => 'Celebrate the Festival of Lights in majestic style with curated cultural showcases, laser light shows, and gourmet dining.',
        'category' => 'Gala & Celebrations',
        'status' => 'published',
        'banner' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1200&q=85',
        'start_date' => '2026-11-08',
        'start_time' => '18:30',
        'end_date' => '2026-11-08',
        'end_time' => '23:00',
        'venue_name' => 'The Leela Palace Ballroom',
        'address' => 'Old Airport Road, Kodihalli',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'pincode' => '560008',
        'max_capacity' => 800,
        'available_seats' => 785,
        'booking_open' => 1
    ]);

    // 6. Seed Realistic Demo Bookings for Dandiya Night
    // Multiple statuses: Confirmed, Checked-In, Pending, Cancelled
    $demoBookings = [
        [
            'client_id' => $royalClient['id'],
            'event_id' => $dandiyaEvent['id'],
            'booking_number' => 'UTS-2026-000124',
            'customer_name' => 'Priya Patel',
            'email' => 'priya.patel@example.com',
            'phone' => '+91 98450 11223',
            'pass_count' => 2,
            'status' => 'confirmed',
            'qr_token' => 'uts_token_priya_confirmed_demo_2026',
            'booking_date' => date('Y-m-d', strtotime('-2 days'))
        ],
        [
            'client_id' => $royalClient['id'],
            'event_id' => $dandiyaEvent['id'],
            'booking_number' => 'UTS-2026-000125',
            'customer_name' => 'Amitabh Mehta',
            'email' => 'amitabh.mehta@example.com',
            'phone' => '+91 98860 33445',
            'pass_count' => 4,
            'status' => 'checked_in',
            'qr_token' => 'uts_token_amitabh_checkedin_demo_2026',
            'checked_in_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'checked_in_by' => 'Rajesh Sharma (organizer)',
            'booking_date' => date('Y-m-d', strtotime('-5 days'))
        ],
        [
            'client_id' => $royalClient['id'],
            'event_id' => $dandiyaEvent['id'],
            'booking_number' => 'UTS-2026-000126',
            'customer_name' => 'Sneha Kulkarni',
            'email' => 'sneha.kulkarni@example.com',
            'phone' => '+91 97410 55667',
            'pass_count' => 1,
            'status' => 'pending',
            'qr_token' => 'uts_token_sneha_pending_demo_2026',
            'booking_date' => date('Y-m-d', strtotime('-1 day'))
        ],
        [
            'client_id' => $royalClient['id'],
            'event_id' => $dandiyaEvent['id'],
            'booking_number' => 'UTS-2026-000127',
            'customer_name' => 'Vikram Singhania',
            'email' => 'vikram.singhania@example.com',
            'phone' => '+91 99001 77889',
            'pass_count' => 2,
            'status' => 'cancelled',
            'qr_token' => 'uts_token_vikram_cancelled_demo_2026',
            'booking_date' => date('Y-m-d', strtotime('-3 days'))
        ],
        [
            'client_id' => $royalClient['id'],
            'event_id' => $dandiyaEvent['id'],
            'booking_number' => 'UTS-2026-000128',
            'customer_name' => 'Ananya Deshmukh',
            'email' => 'ananya.deshmukh@example.com',
            'phone' => '+91 98452 99001',
            'pass_count' => 3,
            'status' => 'confirmed',
            'qr_token' => 'uts_token_ananya_confirmed_demo_2026',
            'booking_date' => date('Y-m-d')
        ]
    ];

    foreach ($demoBookings as $bData) {
        $answers = [
            'full_name' => $bData['customer_name'],
            'email_address' => $bData['email'],
            'mobile_number' => $bData['phone'],
            'attendees_count' => (string)$bData['pass_count'],
            'city_locality' => 'Bengaluru, Koramangala',
            'special_notes' => 'Looking forward to the folk band performance!'
        ];
        $store->createBooking($bData, $answers);
    }

    // 7. Seed an initial check-in scan record for Amitabh Mehta
    $store->recordQrScan([
        'booking_id' => 'UTS-2026-000125',
        'client_id' => $royalClient['id'],
        'event_id' => $dandiyaEvent['id'],
        'qr_token' => 'uts_token_amitabh_checkedin_demo_2026',
        'status' => 'already_checked_in',
        'scanned_by' => 'Rajesh Sharma (organizer)'
    ]);

    // 8. Activity log for initial setup
    $store->logActivity([
        'actor_id' => 'system',
        'actor_role' => 'system',
        'client_id' => $royalClient['id'],
        'action' => 'system_initialized',
        'entity_type' => 'platform',
        'entity_id' => 'utsavam',
        'description' => 'System seeded with Royal Events organizer and Dandiya Night 2026 demo'
    ]);
}

function seedDatabase(): void {
    seedUtsavamDemoData();
}

// Auto-run if executed from CLI directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    seedUtsavamDemoData();
    echo "Utsavam demo data seeded successfully.\n";
}
