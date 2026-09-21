<?php
/**
 * Utsavam - Official B2B Event Ticketing & Pass Verification SaaS Landing Page
 * 
 * Marketing page exclusively for Event Organizers, Festival Directors, and Event Managers.
 * Strictly no event directory on this page — all events open directly on their unique URLs.
 * 100% English copywriting, responsive layout, modern design with Lucide icons.
 */

$pageTitle = "Utsavam - The Modern Event Management, Ticketing & QR Verification Platform";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - Host, Manage & Verify Your Events with Digital QR Passes</title>
    <meta name="description" content="List your events on Utsavam. Get a dedicated organizer dashboard, custom registration forms, instant verifiable QR digital passes, and camera turnstile gate scanning.">
    <meta property="og:title" content="<?= e(APP_NAME) ?> - Event Ticketing & QR Turnstile Verification Platform">
    <meta property="og:description" content="The all-in-one platform for event organizers: dedicated white-label portal, dynamic form builder, instant digital passes, and camera gate scanner.">
    <meta property="og:image" content="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=85">

    <!-- Bootstrap 5 CSS & Lucide Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --uts-maroon: #781d42;
            --uts-maroon-dark: #4a0e26;
            --uts-gold: #c2410c;
            --uts-amber: #d97706;
            --uts-gold-light: #fef3c7;
            --uts-dark: #1c1917;
            --uts-bg-light: #fafaf9;
            --uts-border: #e7e5e4;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--uts-bg-light);
            color: #292524;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Top Announcement Ribbon */
        .top-announcement-bar {
            background: linear-gradient(90deg, #3b0764 0%, #781d42 50%, #431407 100%);
            color: #fef3c7;
            font-size: 0.85rem;
            padding: 8px 0;
            letter-spacing: 0.2px;
        }

        /* Navbar */
        .main-navbar {
            background: #ffffff;
            border-bottom: 1px solid #f0eee9;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }

        /* Hero Banner */
        .uts-hero {
            background: radial-gradient(circle at 80% 20%, rgba(217, 119, 6, 0.22) 0%, transparent 50%),
                        radial-gradient(circle at 15% 85%, rgba(120, 29, 66, 0.35) 0%, transparent 60%),
                        linear-gradient(135deg, #1c1917 0%, #31111d 40%, #581432 75%, #2a0845 100%);
            color: #ffffff;
            padding: 90px 0 85px 0;
            position: relative;
            overflow: hidden;
        }

        .uts-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 26px 26px;
            pointer-events: none;
            opacity: 0.45;
        }

        .badge-pill-uts {
            background: rgba(254, 243, 199, 0.15);
            border: 1px solid rgba(254, 243, 199, 0.35);
            color: #fef08a;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Feature & Value Cards */
        .feature-card {
            background: #ffffff;
            border: 1px solid var(--uts-border);
            border-radius: 16px;
            padding: 30px 26px;
            height: 100%;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            display: flex;
            flex-direction: column;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.07);
            border-color: #f59e0b;
        }
        .feature-icon-wrapper {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        /* Step Card */
        .step-card {
            background: #ffffff;
            border: 1px solid var(--uts-border);
            border-radius: 18px;
            padding: 32px 24px;
            text-align: center;
            height: 100%;
            position: relative;
            transition: transform 0.2s ease;
        }
        .step-card:hover {
            transform: translateY(-4px);
        }
        .step-bubble {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #781d42, #c2410c);
            color: #ffffff;
            font-weight: 800;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px auto;
            box-shadow: 0 8px 18px rgba(120, 29, 66, 0.25);
        }

        /* Interactive Dashboard Preview Box */
        .dashboard-preview-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
            overflow: hidden;
            color: #1c1917;
        }

        .preview-header-bar {
            background: #1c1917;
            color: #ffffff;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Comparison Table */
        .comparison-table {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid var(--uts-border);
            overflow: hidden;
        }
        .comparison-table th {
            padding: 18px 22px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 700;
        }
        .comparison-table td {
            padding: 16px 22px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        /* CTA Section */
        .cta-banner-box {
            background: radial-gradient(circle at 10% 40%, rgba(217, 119, 6, 0.3) 0%, transparent 60%),
                        linear-gradient(135deg, #431407 0%, #781d42 55%, #1e1b4b 100%);
            border-radius: 24px;
            padding: 70px 48px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        /* Accordion */
        .uts-accordion .accordion-item {
            border: 1px solid var(--uts-border);
            border-radius: 14px !important;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .uts-accordion .accordion-button {
            font-weight: 600;
            color: #1c1917;
            padding: 18px 22px;
            background-color: #ffffff;
            box-shadow: none !important;
        }
        .uts-accordion .accordion-button:not(.collapsed) {
            background-color: #fffaf5;
            color: #781d42;
        }

        @media (max-width: 767px) {
            .uts-hero {
                padding: 60px 0 50px 0;
            }
            .cta-banner-box {
                padding: 40px 24px;
            }
        }
    </style>
</head>
<body>

<!-- Top Announcement Ribbon -->
<div class="top-announcement-bar text-center">
    <div class="container d-flex align-items-center justify-content-center gap-2 flex-wrap">
        <span>✨ <strong>The Modern Event Platform:</strong> Dynamic registration forms, instant QR passes, and camera turnstiles.</span>
    </div>
</div>

<!-- Main Navbar -->
<nav class="navbar main-navbar py-3">
    <div class="container d-flex align-items-center justify-content-between">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold m-0" href="/" style="color: #781d42;">
            <div class="p-2 rounded-3 d-flex align-items-center justify-content-center text-white" style="background: linear-gradient(135deg, #781d42, #c2410c);">
                <i data-lucide="sparkles" style="width:20px;height:20px;"></i>
            </div>
            <div>
                <span class="fs-4 fw-black tracking-tight" style="letter-spacing: -0.5px; font-weight: 800;"><?= e(APP_NAME) ?></span>
                <span class="badge bg-warning text-dark ms-1 px-2 py-0" style="font-size: 10px;">ORGANIZER PLATFORM</span>
            </div>
        </a>

        <!-- Header CTA for Event Listing -->
        <div class="d-flex align-items-center gap-2">
            <a href="#list-your-event" class="btn btn-warning fw-bold text-dark px-3.5 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size: 14px;">
                <i data-lucide="plus-circle" style="width:16px;height:16px;"></i> List Your Event
            </a>
        </div>
    </div>
</nav>

<!-- Hero Section (Strictly Marketing to Organizers) -->
<header class="uts-hero">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="badge-pill-uts mb-3">
                    <i data-lucide="sparkles" style="width:15px;height:15px;"></i> The Complete Event Ticketing & Pass Verification Engine
                </div>
                <h1 class="display-4 fw-black text-white mb-3" style="font-weight: 800; line-height: 1.15; letter-spacing: -0.8px;">
                    List Your Events, Issue Digital Passes, and Turn Any Phone into a Gate Scanner.
                </h1>
                <p class="lead text-white text-opacity-90 mb-4 fs-5" style="max-width: 630px; line-height: 1.6;">
                    Utsavam empowers event organizers with a dedicated white-label portal. Collect attendee registrations with custom dynamic forms, issue instant tamper-proof digital QR passes, and verify entry at the venue gates with sub-second camera scanning.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="#list-your-event" class="btn btn-warning btn-lg fw-bold text-dark px-4 py-3 rounded-3 shadow d-flex align-items-center gap-2">
                        <i data-lucide="calendar-plus" style="width:20px;height:20px;"></i>
                        <span>List Your Event With Us</span> &rarr;
                    </a>
                    <a href="#what-you-get" class="btn btn-outline-light btn-lg px-4 py-3 rounded-3 fw-semibold d-flex align-items-center gap-2">
                        <span>See How It Works</span> &darr;
                    </a>
                </div>

                <!-- Trust Points -->
                <div class="row g-3 text-white text-opacity-90 pt-3 border-top border-white border-opacity-10">
                    <div class="col-6 col-sm-3">
                        <div class="fw-bold fs-4 text-warning">100%</div>
                        <div class="small text-white text-opacity-75">Dedicated Brand Portal</div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="fw-bold fs-4 text-warning">&lt;0.5s</div>
                        <div class="small text-white text-opacity-75">Camera QR Scan Time</div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="fw-bold fs-4 text-warning">Zero</div>
                        <div class="small text-white text-opacity-75">Extra Hardware Needed</div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="fw-bold fs-4 text-warning">1-Click</div>
                        <div class="small text-white text-opacity-75">Excel Guest List Export</div>
                    </div>
                </div>
            </div>

            <!-- Hero Organizer Panel Visual Showcase -->
            <div class="col-lg-5">
                <div class="dashboard-preview-card">
                    <div class="preview-header-bar">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle bg-danger d-inline-block" style="width:10px;height:10px;"></span>
                            <span class="rounded-circle bg-warning d-inline-block" style="width:10px;height:10px;"></span>
                            <span class="rounded-circle bg-success d-inline-block" style="width:10px;height:10px;"></span>
                            <span class="small font-monospace ms-2 text-white text-opacity-75">utsavam.digitechitsolution.com/portal</span>
                        </div>
                        <span class="badge bg-warning text-dark fw-bold font-monospace" style="font-size:10px;">LIVE PANEL</span>
                    </div>

                    <div class="p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Apex Event Management</h6>
                                <span class="text-muted small" style="font-size: 12px;">Portal URL: <code>/apex-events/</code></span>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill fw-bold" style="font-size: 11px;">
                                ACTIVE ORGANIZER
                            </span>
                        </div>

                        <!-- Mini Stats Grid -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2 bg-light rounded border">
                                    <div class="text-muted small" style="font-size: 11px;">TOTAL PASSES ISSUED</div>
                                    <div class="fw-black text-dark fs-5">1,458</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded border">
                                    <div class="text-muted small" style="font-size: 11px;">GATE CHECK-IN RATE</div>
                                    <div class="fw-black text-success fs-5">89.4%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Feature Badges -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between small text-muted mb-2">
                                <span>Turnstile Camera Scanner:</span>
                                <span class="badge bg-success text-white">Online & Ready</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-muted mb-2">
                                <span>Anti-Duplicate Ticket Guard:</span>
                                <span class="badge bg-primary text-white">Enabled</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <span>Realtime Guest List Export:</span>
                                <span class="text-dark fw-semibold">CSV / XLSX</span>
                            </div>
                        </div>

                        <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-25 mb-3 text-start">
                            <div class="d-flex gap-2">
                                <i data-lucide="shield-check" class="text-warning flex-shrink-0" style="width:18px;height:18px;"></i>
                                <div class="small text-dark">
                                    <strong>Sub-Second Gate Turnstiles:</strong> Hand your volunteers the mobile scanner link. No hardware to rent, no paper tickets to tear.
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="#list-your-event" class="btn btn-dark btn-sm fw-bold py-2">
                                Host Your Event With Utsavam &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- SECTION 1: "WHAT YOU GET AS AN ORGANIZER" (CORE VALUE PROPOSITIONS) -->
<section id="what-you-get" class="py-5" style="background-color: #ffffff;">
    <div class="container py-4">
        <div class="text-center max-w-75 mx-auto mb-5">
            <div class="text-uppercase text-danger fw-bold small tracking-wide mb-1" style="color: #c2410c !important; letter-spacing: 1px;">
                <i data-lucide="gift" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> COMPLETE ORGANIZER TOOLKIT
            </div>
            <h2 class="display-6 fw-black text-dark mb-2" style="font-weight: 800;">
                Everything You Get When You Host on Utsavam
            </h2>
            <p class="text-muted lead fs-6" style="max-width: 680px; margin: 0 auto;">
                We provide end-to-end event infrastructure so you can focus on building an unforgettable experience. Here is the full suite of tools included in your organizer portal:
            </p>
        </div>

        <div class="row g-4">
            <!-- Feature 1: Dedicated Portal -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper bg-warning bg-opacity-10 text-warning">
                        <i data-lucide="globe" style="width:26px;height:26px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Dedicated White-Label Portal Space</h5>
                    <p class="text-muted small mb-0">
                        Get your own branded space (e.g. <code>utsavam.in/your-brand</code>) displaying only your events, banners, brand colors, contact numbers, and social links. No competitor ads, no platform clutter.
                    </p>
                </div>
            </div>

            <!-- Feature 2: Dynamic Form Builder -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper bg-danger bg-opacity-10 text-danger">
                        <i data-lucide="form-input" style="width:26px;height:26px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Custom Dynamic Form Builder</h5>
                    <p class="text-muted small mb-0">
                        Collect any attendee data you require. Add custom form questions such as T-shirt size, dietary choices, corporate designation, college roll number, or ID verification files with ease.
                    </p>
                </div>
            </div>

            <!-- Feature 3: Digital QR Gate Passes -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i data-lucide="ticket" style="width:26px;height:26px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Instant Digital QR Gate Passes</h5>
                    <p class="text-muted small mb-0">
                        Upon booking, attendees instantly receive a clean, wallet-styled digital pass featuring high-resolution QR codes, security tokens, attendee info, and event access details directly on their phone screen.
                    </p>
                </div>
            </div>

            <!-- Feature 4: Mobile Camera Turnstile Scanner -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i data-lucide="camera" style="width:26px;height:26px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">In-Browser Camera Turnstile Scanner</h5>
                    <p class="text-muted small mb-0">
                        Turn any volunteer or gate steward’s mobile phone into an optical gate scanner in seconds. Scans QR codes in &lt;0.5 seconds with clear green/red verification feedback and audio confirmation chimes.
                    </p>
                </div>
            </div>

            <!-- Feature 5: Anti-Duplicate Ticket Protection -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper bg-info bg-opacity-10 text-info">
                        <i data-lucide="shield-alert" style="width:26px;height:26px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Zero Fraud & Anti-Passback Guard</h5>
                    <p class="text-muted small mb-0">
                        Every pass is secured with a cryptographically hashed single-use token. If an attendee attempts to reuse a ticket or share screenshots, the gate scanner flags it immediately as <em>"Already Checked In"</em>.
                    </p>
                </div>
            </div>

            <!-- Feature 6: Live Analytics & Excel Export -->
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper bg-secondary bg-opacity-10 text-dark">
                        <i data-lucide="file-spreadsheet" style="width:26px;height:26px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Live Turnout & 1-Click Excel Export</h5>
                    <p class="text-muted small mb-0">
                        Monitor real-time gate arrivals, check-in percentages, and total registrations. Download complete attendee lists, contact information, and custom question answers into CSV / Excel spreadsheets anytime.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 2: PLATFORM WORKFLOW ("HOW IT WORKS FOR ORGANIZERS") -->
<section id="how-it-works" class="py-5" style="background-color: #fafaf9;">
    <div class="container py-4">
        <div class="text-center max-w-75 mx-auto mb-5">
            <div class="text-uppercase text-danger fw-bold small tracking-wide mb-1" style="color: #c2410c !important; letter-spacing: 1px;">
                <i data-lucide="play-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> SIMPLE 4-STEP SETUP
            </div>
            <h2 class="display-6 fw-black text-dark mb-2" style="font-weight: 800;">
                How Easy It Is to Launch & Run Your Event
            </h2>
            <p class="text-muted lead fs-6">From publishing your event page to welcoming guests at the gate.</p>
        </div>

        <div class="row g-4">
            <!-- Step 1 -->
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-bubble">1</div>
                    <h5 class="fw-bold text-dark mb-2">Get Your Brand Portal</h5>
                    <p class="text-muted small mb-0">
                        Log in to your organizer dashboard and receive your exclusive branded URL (e.g. <code>utsavam.in/your-brand/</code>).
                    </p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-bubble">2</div>
                    <h5 class="fw-bold text-dark mb-2">Publish Event & Form</h5>
                    <p class="text-muted small mb-0">
                        Set your venue details, banners, ticket quotas, and add custom registration questions in under 5 minutes.
                    </p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-bubble">3</div>
                    <h5 class="fw-bold text-dark mb-2">Distribute Direct Links</h5>
                    <p class="text-muted small mb-0">
                        Share your dedicated event link on social media or WhatsApp. Attendees register in 60 seconds and receive instant QR passes.
                    </p>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-bubble">4</div>
                    <h5 class="fw-bold text-dark mb-2">Scan & Verify at the Gate</h5>
                    <p class="text-muted small mb-0">
                        Open the built-in camera scanner on any phone at your venue entrance to verify passes in &lt;0.5 seconds with zero lines.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 3: QR VERIFICATION & GATE SCANNER DEEP-DIVE -->
<section id="verification-scanner" class="py-5" style="background-color: #ffffff;">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="text-uppercase text-danger fw-bold small tracking-wide mb-1" style="color: #c2410c !important; letter-spacing: 1px;">
                    <i data-lucide="scan" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> TURNSTILE GATE TECHNOLOGY
                </div>
                <h2 class="display-6 fw-black text-dark mb-3" style="font-weight: 800;">
                    Optical Camera Scanning: Faster Gates, Zero Counterfeiting.
                </h2>
                <p class="text-muted fs-6 mb-4" style="line-height: 1.7;">
                    Forget bulky laser scanner rentals or tedious manual paper guest lists. Utsavam’s web-based gate scanner operates right inside the mobile browser of your entry staff.
                </p>

                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex gap-3">
                        <div class="p-2 bg-success bg-opacity-10 text-success rounded-3 flex-shrink-0" style="height:fit-content;">
                            <i data-lucide="zap" style="width:20px;height:20px;"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Sub-Second Scanning (&lt;500ms)</h6>
                            <p class="text-muted small mb-0">Instant QR detection even on dimmed phone screens or through screen protectors.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-3 flex-shrink-0" style="height:fit-content;">
                            <i data-lucide="shield-check" style="width:20px;height:20px;"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Double-Entry Prevention</h6>
                            <p class="text-muted small mb-0">Instantly marks tokens as used. If scanned again, emits an audible alert and prevents unauthorized second entry.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 flex-shrink-0" style="height:fit-content;">
                            <i data-lucide="users" style="width:20px;height:20px;"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Multi-Gate Concurrent Scanning</h6>
                            <p class="text-muted small mb-0">Deploy 10 or 50 volunteers simultaneously across multiple gates with real-time central synchronization.</p>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-3 flex-wrap">
                    <a href="#list-your-event" class="btn btn-dark px-4 py-2 fw-semibold rounded-3 shadow-sm">
                        <i data-lucide="camera" style="width:16px;height:16px;"></i> Request Gate Scanner Access
                    </a>
                    <a href="#what-you-get" class="btn btn-outline-dark px-4 py-2 rounded-3">
                        <i data-lucide="sparkles" style="width:16px;height:16px;"></i> Explore Platform Features
                    </a>
                </div>
            </div>

            <!-- Scanner Visual Card -->
            <div class="col-lg-6">
                <div class="p-4 bg-light rounded-4 border shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="camera" class="text-danger" style="width:18px;height:18px;"></i>
                            <span class="fw-bold text-dark">Mobile Gate Scanner View</span>
                        </div>
                        <span class="badge bg-success font-monospace">READY FOR SCAN</span>
                    </div>

                    <!-- Visual Viewfinder Mockup -->
                    <div class="p-4 rounded-3 text-center position-relative mb-3" style="background:#1c1917; color:white; min-height: 220px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                        <div style="border: 2px dashed #f59e0b; width: 140px; height: 140px; border-radius: 12px; display: flex; align-items: center; justify-content: center; position: relative;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=https%3A%2F%2Futsavam.digitechitsolution.com" alt="QR" style="opacity: 0.85; width: 100px; height: 100px;">
                            <div style="position: absolute; top:0; left:0; right:0; height: 2px; background: #22c55e; box-shadow: 0 0 8px #22c55e;"></div>
                        </div>
                        <div class="small text-warning mt-2 font-monospace">ALIGN QR CODE WITHIN TARGET</div>
                    </div>

                    <!-- Result Box Simulation -->
                    <div class="p-3 bg-white rounded-3 border">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success p-2 rounded-circle"><i data-lucide="check" style="width:14px;height:14px;"></i></span>
                                <div>
                                    <div class="fw-bold text-dark small">Attendee Check-In (Pass Verified)</div>
                                    <div class="text-muted" style="font-size: 11px;">Verified Entry • Turnstile Gate #2</div>
                                </div>
                            </div>
                            <span class="badge bg-success text-white fw-bold">ACCESS GRANTED</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 4: COMPARISON TABLE (UTSAVAM VS TRADITIONAL PLATFORMS) -->
<section class="py-5" style="background-color: #fafaf9;">
    <div class="container py-4">
        <div class="text-center max-w-75 mx-auto mb-5">
            <div class="text-uppercase text-danger fw-bold small tracking-wide mb-1" style="color: #c2410c !important; letter-spacing: 1px;">
                <i data-lucide="trending-up" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> PLATFORM COMPARISON
            </div>
            <h2 class="display-6 fw-black text-dark mb-2" style="font-weight: 800;">
                Why Event Organizers Choose Utsavam
            </h2>
            <p class="text-muted lead fs-6">Experience modern flexibility without predatory platform fees.</p>
        </div>

        <div class="table-responsive comparison-table shadow-sm">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 40%;">Capability / Feature</th>
                        <th style="width: 30%; color: #781d42;" class="fs-6">Utsavam Platform</th>
                        <th style="width: 30%; color: #64748b;">Traditional Ticketing Giants</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold">Dedicated White-Label URL Space</td>
                        <td class="text-success fw-bold"><i data-lucide="check-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> Yes (Your Own Branded Portal)</td>
                        <td class="text-muted"><i data-lucide="x-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> No (Competitors listed alongside)</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Attendee Data Ownership</td>
                        <td class="text-success fw-bold"><i data-lucide="check-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> 100% Yours (1-Click Excel Export)</td>
                        <td class="text-muted"><i data-lucide="x-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> Locked / Masked by platform</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Gate Verification Hardware</td>
                        <td class="text-success fw-bold"><i data-lucide="check-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> Zero (Any Smartphone Camera)</td>
                        <td class="text-muted"><i data-lucide="x-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> Expensive scanner rentals needed</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Dynamic Custom Registration Questions</td>
                        <td class="text-success fw-bold"><i data-lucide="check-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> Fully Flexible Form Builder</td>
                        <td class="text-muted"><i data-lucide="x-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> Rigid or charged as premium</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Pass Verification Speed</td>
                        <td class="text-success fw-bold"><i data-lucide="check-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> &lt;0.5 Seconds Sub-second Turnstile</td>
                        <td class="text-muted"><i data-lucide="x-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> 2–5 Seconds per badge</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Anti-Duplicate Ticket Guard</td>
                        <td class="text-success fw-bold"><i data-lucide="check-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> Cryptographic Real-Time Sync</td>
                        <td class="text-muted">Varies / Prone to sync lag</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- SECTION 5: LIST YOUR EVENT ON UTSAVAM (RAPID ONBOARDING & CALLBACK REQUEST) -->
<section id="list-your-event" class="py-5" style="background-color: #fafaf9;">
    <div class="container py-4">
        <div class="text-center max-w-75 mx-auto mb-4">
            <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger fw-bold text-xs mb-2" style="font-size: 11.5px; letter-spacing: 0.5px;">
                <i data-lucide="phone-call" style="width:14px;height:14px;"></i> RAPID EVENT ONBOARDING &bull; DIRECT CONSULTATION
            </div>
            <h2 class="display-5 fw-black text-dark mb-2" style="font-weight: 800;">
                List Your Event on Utsavam
            </h2>
            <p class="text-muted lead fs-6" style="max-width: 680px; margin: 0 auto;">
                Provide your basic contact information and event concept. Our onboarding team connects with you directly to configure your ticketing tiers, custom attendee forms, and gate scanners.
            </p>
        </div>

        <div class="row g-4 align-items-center justify-content-center">
            <!-- Left Column: How It Works & Onboarding Steps -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-4 mb-3" style="background: linear-gradient(145deg, #781d42, #4c0519); color: #ffffff;">
                    <div class="d-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill bg-warning text-dark fw-bold text-xs mb-3" style="font-size: 11px; width: fit-content;">
                        <i data-lucide="zap" style="width:14px;height:14px;"></i> 3-STEP HASSLE-FREE ONBOARDING
                    </div>
                    <h4 class="fw-black text-white mb-2" style="font-weight: 800;">Fast-Track Consultation</h4>
                    <p class="text-white text-opacity-80 small mb-4">
                        Skip tedious setup questionnaires. Share your basic details below, and our dedicated event operations team will coordinate the technical setup over a brief call:
                    </p>

                    <div class="d-flex flex-column gap-3.5">
                        <div class="d-flex gap-3">
                            <div class="p-2 rounded-circle bg-warning text-dark fw-black flex-shrink-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:13px;">
                                1
                            </div>
                            <div>
                                <h6 class="fw-bold text-white mb-0.5">Submit Your Inquiry</h6>
                                <p class="text-white text-opacity-75 small mb-0">Share your contact number and event type in less than 30 seconds.</p>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div class="p-2 rounded-circle bg-warning text-dark fw-black flex-shrink-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:13px;">
                                2
                            </div>
                            <div>
                                <h6 class="fw-bold text-white mb-0.5">Discovery & Tailored Setup</h6>
                                <p class="text-white text-opacity-75 small mb-0">Our platform specialist discusses your expected turnout, gate turnstiles, and custom registration fields.</p>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div class="p-2 rounded-circle bg-warning text-dark fw-black flex-shrink-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:13px;">
                                3
                            </div>
                            <div>
                                <h6 class="fw-bold text-white mb-0.5">Go Live with Scanners Ready</h6>
                                <p class="text-white text-opacity-75 small mb-0">Your branded registration page and optical camera scanners are provisioned within hours.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top border-white border-opacity-15 d-flex align-items-center justify-content-between">
                        <div class="small text-white text-opacity-85">
                            <i data-lucide="phone-forwarded" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-top:-2px;" class="text-warning"></i> Response Time: <strong>Within 15 Minutes</strong>
                        </div>
                        <span class="badge bg-white bg-opacity-20 text-white font-monospace" style="font-size:11px;">ONBOARDING DESK</span>
                    </div>
                </div>

                <div class="card border border-secondary border-opacity-20 rounded-4 p-3 bg-white shadow-xs">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 rounded-3 bg-success bg-opacity-10 text-success flex-shrink-0">
                            <i data-lucide="check-check" style="width:20px;height:20px;"></i>
                        </div>
                        <div class="small">
                            <span class="fw-bold text-dark">Strict Confidentiality:</span>
                            <span class="text-muted"> Your organizer information is accessed exclusively by Utsavam platform administrators to fulfill your event onboarding.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Short 4-Field Lead Capture Form -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg rounded-4 p-4 p-md-4 bg-white position-relative overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-black text-dark mb-0.5" style="font-weight: 800;">Request Event Listing & Callback</h5>
                            <span class="text-muted small">Enter your contact details to schedule your onboarding call.</span>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success font-monospace fw-bold px-2.5 py-1" style="font-size:11px;">
                            <i data-lucide="phone" style="width:11px;height:11px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> PRIORITY DESK
                        </span>
                    </div>

                    <!-- Alert message container -->
                    <div id="leadFormAlert" class="alert d-none py-2.5 px-3 small rounded-3 mb-3"></div>

                    <!-- Short Lead Submission Form (Only 4 fields) -->
                    <form id="landingLeadForm" onsubmit="submitLeadForm(event, 'landingLeadForm', 'leadFormAlert', 'leadFormSuccess')">
                        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

                        <div class="row g-3">
                            <!-- Field 1: Organizer Name -->
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Organizer / Contact Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i data-lucide="user" style="width:16px;height:16px;"></i></span>
                                    <input type="text" name="organizer_name" class="form-control border-start-0 py-2.5 fs-6" placeholder="Full name of event director or coordinator" required>
                                </div>
                            </div>

                            <!-- Field 2: Phone / WhatsApp Number -->
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Phone / WhatsApp Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i data-lucide="phone" style="width:16px;height:16px;"></i></span>
                                    <input type="tel" name="phone" class="form-control border-start-0 py-2.5 fs-6" placeholder="Direct phone number with country code" required>
                                </div>
                                <span class="text-muted" style="font-size: 11px;">Our operations team will call this number to finalize event parameters.</span>
                            </div>

                            <!-- Field 3: Event Name / Idea -->
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Event Name or Title <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i data-lucide="calendar" style="width:16px;height:16px;"></i></span>
                                    <input type="text" name="event_title" class="form-control border-start-0 py-2" placeholder="Title of your festival, conference, or concert" required>
                                </div>
                            </div>

                            <!-- Field 4: City / Location -->
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Host City / Venue
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i data-lucide="map-pin" style="width:16px;height:16px;"></i></span>
                                    <input type="text" name="venue_city" class="form-control border-start-0 py-2" placeholder="City or venue name">
                                </div>
                            </div>

                            <!-- Submit Action Button -->
                            <div class="col-12 pt-2">
                                <button type="submit" id="landingSubmitBtn" class="btn btn-warning w-100 py-3 fw-bold text-dark shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 16px;">
                                    <i data-lucide="phone-call" style="width:18px;height:18px;"></i>
                                    <span>Request Callback & List Event</span> &rarr;
                                </button>
                                <div class="d-flex flex-wrap align-items-center justify-content-center gap-3 mt-3 text-muted" style="font-size: 11.5px;">
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i data-lucide="check" class="text-success" style="width:13px;height:13px;"></i> Direct Phone Consultation
                                    </span>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i data-lucide="check" class="text-success" style="width:13px;height:13px;"></i> No Form Friction
                                    </span>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i data-lucide="check" class="text-success" style="width:13px;height:13px;"></i> Dedicated Onboarding Specialist
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Success State View (Revealed after submission) -->
                    <div id="leadFormSuccess" class="d-none text-center py-4">
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <i data-lucide="phone-incoming" style="width:48px;height:48px;"></i>
                        </div>
                        <h4 class="fw-black text-dark mb-2" style="font-weight: 800;">Request Received! Callback Scheduled</h4>
                        <p class="text-muted small mb-3" style="max-width: 440px; margin: 0 auto;">
                            Thank you! Your event inquiry has been logged with our onboarding team. A platform manager will reach out shortly to review your ticketing model, digital passes, and gate scanners.
                        </p>
                        <div class="p-3 bg-light rounded-3 border d-inline-block text-start mb-4" style="min-width: 280px;">
                            <div class="text-muted small" style="font-size: 11px;">YOUR INQUIRY REFERENCE:</div>
                            <div class="fw-bold font-monospace fs-5 text-primary mb-1" id="leadInquiryNumber">INQ-REF</div>
                            <div class="text-muted small" style="font-size: 12px;">Status: <span class="badge bg-success text-white">Call Priority Scheduled</span></div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetLeadForm('landingLeadForm', 'leadFormAlert', 'leadFormSuccess')">
                                Submit Another Event Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 6: CALL TO ACTION BANNER FOR EVENT ORGANIZERS -->
<section class="py-5" style="background-color: #ffffff;">
    <div class="container py-3">
        <div class="cta-banner-box shadow-lg text-center text-lg-start">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-3">
                        <i data-lucide="rocket" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> GET STARTED IN MINUTES
                    </span>
                    <h2 class="display-5 fw-black text-white mb-3" style="font-weight: 800;">
                        Ready to Host Your Next Grand Celebration or Event?
                    </h2>
                    <p class="text-light text-opacity-90 lead fs-6 mb-4" style="max-width: 650px;">
                        Join premier festival curators, concert managers, and conclave organizers. Set up your dedicated brand space, publish custom registration forms, and experience flawless gate scanning.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="#list-your-event" class="btn btn-warning btn-lg fw-bold text-dark px-4 shadow-sm d-flex align-items-center gap-2">
                            <i data-lucide="sparkles" style="width:18px;height:18px;"></i> List Your Event With Us
                        </a>
                        <button type="button" class="btn btn-outline-light btn-lg px-4 fw-semibold" onclick="openLeadModal()">
                            Open Quick Application Form &rarr;
                        </button>
                    </div>
                </div>

                <div class="col-lg-4 text-center">
                    <div class="p-4 bg-white bg-opacity-10 backdrop-blur rounded-4 border border-white border-opacity-20 text-start">
                        <div class="d-flex align-items-center gap-2 text-warning fw-bold small mb-2">
                            <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                            <span>ENTERPRISE READY</span>
                        </div>
                        <h6 class="text-white fw-bold mb-2">Dedicated Organizer Portal</h6>
                        <p class="text-light text-opacity-75 small mb-3">
                            Get your own branded custom domain, ticketing forms, and multi-volunteer gate scanner tools.
                        </p>
                        <a href="#list-your-event" class="btn btn-light btn-sm w-100 fw-semibold text-dark">
                            Request Your Organizer Portal &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 7: FREQUENTLY ASKED QUESTIONS (FAQS - ALL IN ENGLISH) -->
<section id="faqs" class="py-5" style="background-color: #fafaf9;">
    <div class="container py-4">
        <div class="text-center max-w-75 mx-auto mb-5">
            <div class="text-uppercase text-danger fw-bold small tracking-wide mb-1" style="color: #c2410c !important; letter-spacing: 1px;">
                <i data-lucide="help-circle" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-top:-2px;"></i> CLEAR ANSWERS
            </div>
            <h2 class="display-6 fw-black text-dark mb-2" style="font-weight: 800;">
                Frequently Asked Questions for Organizers
            </h2>
            <p class="text-muted lead fs-6">Everything you need to know about hosting and gate verification on Utsavam.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion uts-accordion" id="faqAccordion">
                    <!-- Q1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                1. How does the gate verification scanner work without dedicated hardware?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small" style="line-height: 1.7;">
                                Utsavam’s gate scanner runs directly inside modern web browsers (Chrome, Safari, Edge) on any smartphone or tablet. By utilizing device camera streams, our web-assembly optical decoder scans QR codes in less than 500 milliseconds. Your gate team can log in from anywhere without downloading apps or renting expensive barcode hardware.
                            </div>
                        </div>
                    </div>

                    <!-- Q2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                2. How are counterfeit passes and screenshot sharing prevented?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small" style="line-height: 1.7;">
                                Every digital pass is generated with a unique, cryptographically signed token. The moment a pass is scanned at the gate, its status changes to "Checked-In" with a server timestamp and operator identifier. If someone attempts to scan the same QR code again, the scanner immediately sounds a warning chime and displays a red "Already Checked In" alert.
                            </div>
                        </div>
                    </div>

                    <!-- Q3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                3. Can I collect custom attendee information during registration?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small" style="line-height: 1.7;">
                                Yes! Our Dynamic Form Builder lets you add required or optional fields tailored to your event type — including attendee designations, company names, T-shirt sizes, meal preferences, or emergency contact numbers. All custom responses are tracked and exported to Excel.
                            </div>
                        </div>
                    </div>

                    <!-- Q4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                4. How do I access and export attendee records?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small" style="line-height: 1.7;">
                                Your organizer portal includes a live Bookings module and Excel Sheets dashboard. You can filter attendees by confirmation status, check-in state, or booking date, and download full spreadsheets in CSV or Excel format with a single click.
                            </div>
                        </div>
                    </div>

                    <!-- Q5 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                                5. Can I host multiple concurrent events under my organization?
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small" style="line-height: 1.7;">
                                Absolutely. Each organizer receives a dedicated multi-event dashboard. You can create, edit, manage quotas, and view turnstile check-ins for dozens of simultaneous events across different cities or dates.
                            </div>
                        </div>
                    </div>

                    <!-- Q6 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSix">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix">
                                6. How do attendees access my event page?
                            </button>
                        </h2>
                        <div id="collapseSix" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small" style="line-height: 1.7;">
                                Every event has its own clean, shareable URL (e.g. <code>utsavam.in/{your-brand}/{event-name}/</code>). You can share this link on social channels, flyers, email newsletters, or WhatsApp. Attendees open the link directly in their browser without registering on a central marketplace.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="bg-dark text-white pt-5 pb-4 border-top" style="background-color: #1c1917 !important;">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="p-2 rounded-3 text-white" style="background: linear-gradient(135deg, #781d42, #c2410c);">
                        <i data-lucide="sparkles" style="width:20px;height:20px;"></i>
                    </div>
                    <span class="fs-4 fw-black text-white" style="font-weight: 800;"><?= e(APP_NAME) ?></span>
                </div>
                <p class="text-secondary small mb-3" style="line-height: 1.6; max-width: 420px;">
                    Utsavam is the enterprise-grade event management, digital ticketing, and optical pass verification SaaS. Empowering organizers with dedicated brand spaces, custom dynamic forms, and frictionless turnstile gate scanning.
                </p>
                <div class="text-secondary small">
                    <span class="badge bg-secondary bg-opacity-25 text-light me-1">Zero Hardware Scanners</span>
                    <span class="badge bg-secondary bg-opacity-25 text-light me-1">Sub-Second Check-ins</span>
                    <span class="badge bg-secondary bg-opacity-25 text-light">100% Data Ownership</span>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <h6 class="text-white fw-bold mb-3">Organizer Navigation</h6>
                <ul class="list-unstyled text-secondary small mb-0">
                    <li class="mb-2"><a href="#what-you-get" class="text-secondary text-decoration-none hover-white">Platform Features</a></li>
                    <li class="mb-2"><a href="#how-it-works" class="text-secondary text-decoration-none hover-white">How It Works</a></li>
                    <li class="mb-2"><a href="#verification-scanner" class="text-secondary text-decoration-none hover-white">QR Gate Verification</a></li>
                    <li class="mb-2"><a href="#faqs" class="text-secondary text-decoration-none hover-white">Frequently Asked Questions</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-4">
                <h6 class="text-white fw-bold mb-3">Organizer Inquiries</h6>
                <ul class="list-unstyled text-secondary small mb-3">
                    <li class="mb-2"><a href="#list-your-event" class="text-secondary text-decoration-none hover-white">List Your Event &rarr;</a></li>
                    <li class="mb-2"><a href="#what-you-get" class="text-secondary text-decoration-none hover-white">Organizer Toolkit &rarr;</a></li>
                    <li class="mb-2"><a href="#verification-scanner" class="text-secondary text-decoration-none hover-white">Gate Turnstile Tech &rarr;</a></li>
                </ul>
                <div class="p-3 bg-secondary bg-opacity-10 rounded border border-secondary border-opacity-25">
                    <div class="text-white small fw-bold mb-1">Host Your Next Event:</div>
                    <a href="#list-your-event" class="btn btn-outline-warning btn-sm w-100 fw-medium" style="font-size: 11px;">
                        Onboard With Utsavam &rarr;
                    </a>
                </div>
            </div>
        </div>

        <div class="border-top border-secondary border-opacity-25 pt-4 d-flex flex-column flex-md-row align-items-center justify-content-between text-secondary small">
            <div>
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> Platform. All rights reserved. Enterprise event hosting & pass verification made effortless.
            </div>
            <div class="mt-2 mt-md-0">
                <span>Designed for high-throughput gate operations and complete organizer privacy.</span>
            </div>
        </div>
    </div>
</footer>

<!-- QUICK LEAD APPLICATION MODAL -->
<div class="modal fade" id="leadModal" tabindex="-1" aria-labelledby="leadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #781d42, #c2410c);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-3 bg-white bg-opacity-20 text-white">
                        <i data-lucide="sparkles" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="leadModalLabel">List Your Event On Utsavam</h5>
                        <div class="text-white text-opacity-80 small" style="font-size: 12px;">Dedicated Organizer Portal & Verification Scanner Request</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div id="modalLeadAlert" class="alert d-none py-2.5 px-3 small rounded-3 mb-3"></div>

                <!-- Short Modal Form (Only 4 fields) -->
                <form id="modalLeadForm" onsubmit="submitLeadForm(event, 'modalLeadForm', 'modalLeadAlert', 'modalLeadSuccess')">
                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Organizer / Contact Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i data-lucide="user" style="width:15px;height:15px;"></i></span>
                                <input type="text" name="organizer_name" class="form-control border-start-0 py-2" placeholder="Full name of organizer or event manager" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Phone / WhatsApp Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i data-lucide="phone" style="width:15px;height:15px;"></i></span>
                                <input type="tel" name="phone" class="form-control border-start-0 py-2" placeholder="Direct mobile number with country code" required>
                            </div>
                            <span class="text-muted" style="font-size: 11px;">Our operations team will call this number to discuss your event requirements.</span>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Event Name or Title <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i data-lucide="calendar" style="width:15px;height:15px;"></i></span>
                                <input type="text" name="event_title" class="form-control border-start-0 py-2" placeholder="Title of your event or festival" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Host City / Venue
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i data-lucide="map-pin" style="width:15px;height:15px;"></i></span>
                                <input type="text" name="venue_city" class="form-control border-start-0 py-2" placeholder="City or venue location">
                            </div>
                        </div>

                        <div class="col-12 pt-2">
                            <button type="submit" class="btn btn-warning w-100 py-2.5 fw-bold text-dark shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 15px;">
                                <i data-lucide="phone-call" style="width:17px;height:17px;"></i>
                                <span>Request Callback & List Event</span> &rarr;
                            </button>
                            <p class="text-center text-muted small mt-2 mb-0" style="font-size: 11px;">
                                ⚡ Our operations team will call you within 15 minutes to review event specifications.
                            </p>
                        </div>
                    </div>
                </form>

                <!-- Modal Success State -->
                <div id="modalLeadSuccess" class="d-none text-center py-4">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                        <i data-lucide="phone-incoming" style="width:44px;height:44px;"></i>
                    </div>
                    <h4 class="fw-black text-dark mb-2" style="font-weight: 800;">Request Received!</h4>
                    <p class="text-muted small mb-3">
                        Thank you! Your event inquiry has been logged with our onboarding team. A platform manager will call you shortly.
                    </p>
                    <div class="p-3 bg-light rounded-3 border d-inline-block text-start mb-4" style="min-width: 260px;">
                        <div class="text-muted small" style="font-size: 11px;">INQUIRY REFERENCE:</div>
                        <div class="fw-bold font-monospace fs-5 text-primary mb-1" id="modalInquiryNumber">INQ-REF</div>
                        <div class="text-muted small" style="font-size: 12px;">Status: <span class="badge bg-success text-white">Call Priority Scheduled</span></div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Open Quick Lead Modal
    function openLeadModal() {
        const modalEl = document.getElementById('leadModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            setTimeout(() => { lucide.createIcons(); }, 150);
        }
    }

    // Generic AJAX handler for Lead Form Submission
    async function submitLeadForm(e, formId, alertId, successId) {
        e.preventDefault();
        const form = document.getElementById(formId);
        const alertBox = document.getElementById(alertId);
        const successBox = document.getElementById(successId);
        const submitBtn = form.querySelector('button[type="submit"]');

        if (!form) return;

        // Reset alert
        alertBox.className = 'alert d-none py-2.5 px-3 small rounded-3 mb-3';
        alertBox.textContent = '';

        // UI Loading state
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting Application...';

        try {
            const formData = new FormData(form);
            const response = await fetch('/api/submit-lead', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Unable to submit inquiry. Please check the fields and try again.');
            }

            // Success state
            form.classList.add('d-none');
            successBox.classList.remove('d-none');

            // Set Inquiry Reference # in both views
            const inqRef = data.inquiry_number || ('INQ-' + Date.now().toString().slice(-6));
            const inqElem = successBox.querySelector('#leadInquiryNumber') || successBox.querySelector('#modalInquiryNumber');
            if (inqElem) {
                inqElem.textContent = inqRef;
            }

            // Re-render lucide icons inside success box
            setTimeout(() => { lucide.createIcons(); }, 100);

        } catch (err) {
            alertBox.className = 'alert alert-danger py-2.5 px-3 small rounded-3 mb-3 d-block';
            alertBox.textContent = err.message;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            setTimeout(() => { lucide.createIcons(); }, 50);
        }
    }

    // Reset Form to Submit Another Event
    function resetLeadForm(formId, alertId, successId) {
        const form = document.getElementById(formId);
        const alertBox = document.getElementById(alertId);
        const successBox = document.getElementById(successId);

        if (form) {
            form.reset();
            form.classList.remove('d-none');
        }
        if (alertBox) {
            alertBox.className = 'alert d-none py-2.5 px-3 small rounded-3 mb-3';
            alertBox.textContent = '';
        }
        if (successBox) {
            successBox.classList.add('d-none');
        }
        setTimeout(() => { lucide.createIcons(); }, 100);
    }
</script>

</body>
</html>
