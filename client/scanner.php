<?php
$pageTitle = "Live Gate QR Scanner";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/services/EventService.php';

$clientId = getCurrentClientId();
$events = EventService::getEvents($clientId);
$selectedEventId = $_GET['event_id'] ?? '';
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#0f172a;">Gate Pass Scanner & Turnstile Check-In</h4>
        <p class="text-muted small mb-0">Point your smartphone camera at attendee digital passes or enter tokens manually.</p>
    </div>
    <div class="d-flex align-items-center gap-2 w-100 w-sm-auto">
        <label class="small text-muted mb-0 text-nowrap fw-medium">Target Event:</label>
        <select id="eventFilterSelect" class="form-select form-select-sm" style="max-width: 260px;">
            <option value="">All Active Events (Any Pass)</option>
            <?php foreach ($events as $ev): ?>
                <option value="<?= e($ev['id']) ?>" <?= $selectedEventId === $ev['id'] ? 'selected' : '' ?>>
                    <?= e($ev['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="row g-4">
    <!-- Camera Viewport & Manual Token Entry -->
    <div class="col-lg-6">
        <div class="card p-3 bg-white mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold text-dark small d-flex align-items-center gap-1">
                    <i data-lucide="camera" style="width:16px;height:16px;"></i> Optical Camera Viewport
                </span>
                <div class="form-check form-switch small mb-0">
                    <input class="form-check-label form-check-input" type="checkbox" id="soundToggle" checked>
                    <label class="form-check-label text-muted" for="soundToggle">Audio Chime</label>
                </div>
            </div>

            <!-- HTML5 QR Code Container -->
            <div id="reader" style="width: 100%; min-height: 280px; border-radius: 8px; overflow: hidden; background: #000;"></div>

            <div class="mt-3 d-flex gap-2">
                <button id="startScanBtn" class="btn btn-warning btn-sm fw-bold flex-grow-1">
                    <i data-lucide="play" style="width:14px;height:14px;"></i> Start Camera
                </button>
                <button id="stopScanBtn" class="btn btn-outline-secondary btn-sm flex-grow-1" disabled>
                    <i data-lucide="square" style="width:14px;height:14px;"></i> Stop Camera
                </button>
            </div>
        </div>

        <!-- Manual Token Entry Card -->
        <div class="card p-3 bg-white">
            <h6 class="fw-semibold text-dark mb-2">Manual Token / Booking Code Fallback</h6>
            <p class="text-muted small mb-2">In case attendee phone screen is cracked or scanner camera is unavailable:</p>
            <form id="manualVerifyForm" class="input-group">
                <input type="text" id="manualTokenInput" class="form-control font-monospace" placeholder="Enter or paste QR token / booking reference" required>
                <button type="submit" class="btn btn-dark fw-medium">
                    <i data-lucide="search" style="width:16px;height:16px;"></i> Verify
                </button>
            </form>
            <div class="mt-2 text-xs text-muted" style="font-size:11px;">
                <i data-lucide="info" style="width:12px;height:12px;display:inline-block;vertical-align:middle;"></i>
                <span>Enter the unique token string printed below the QR code or booking reference.</span>
            </div>
        </div>
    </div>

    <!-- Verification Result & Check-In Action Card -->
    <div class="col-lg-6">
        <div class="card p-4 bg-white h-100" id="scanResultContainer">
            <div class="text-center py-5 text-muted" id="idleState">
                <i data-lucide="scan" style="width:48px;height:48px;" class="text-secondary mb-3 opacity-50"></i>
                <h5 class="fw-bold text-dark">Ready for Next Pass</h5>
                <p class="small text-muted max-w-sm mx-auto">Point the gate camera at the attendee's digital pass or enter token to instantly inspect validity.</p>
            </div>

            <!-- Dynamic Verification Result Card (Populated via JS) -->
            <div id="resultState" style="display: none;">
                <div id="statusAlert" class="alert p-3 rounded-3 mb-3 d-flex align-items-center gap-3">
                    <div id="statusIcon"></div>
                    <div>
                        <h5 class="fw-bold mb-0" id="statusTitle"></h5>
                        <div class="small" id="statusMessage"></div>
                    </div>
                </div>

                <div class="card border bg-light p-3 rounded-3 mb-3" id="bookingDetailsCard">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small">Booking Number:</span>
                        <span class="fw-bold font-monospace text-dark" id="resBookingNumber"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small">Attendee Name:</span>
                        <span class="fw-bold text-dark" id="resCustomerName"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small">Event Name:</span>
                        <span class="fw-semibold text-dark text-end" id="resEventName"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small">Passes / Seats:</span>
                        <span class="badge bg-dark fs-6" id="resPassCount"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start" id="checkinTimeRow" style="display:none;">
                        <span class="text-muted small">Checked In At:</span>
                        <span class="text-dark fw-semibold" id="resCheckedInAt"></span>
                    </div>
                </div>

                <div id="actionArea">
                    <button id="confirmCheckInBtn" class="btn btn-success w-100 py-3 fw-bold fs-5 shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i data-lucide="check-circle-2" style="width:22px;height:22px;"></i> CONFIRM CHECK-IN & ADMIT
                    </button>
                    <button id="resetScannerBtn" class="btn btn-outline-secondary w-100 mt-2 py-2 small">
                        Scan Next Pass
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HTML5 QR Code Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let html5QrCode = null;
let currentBookingId = null;
let isScanning = false;

// Audio Synthesizers for gate staff audio feedback
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

function playAudioChime(isSuccess) {
    if (!document.getElementById('soundToggle').checked) return;
    try {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);

        if (isSuccess) {
            // Pleasant double beep
            osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
            osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1); // A5
            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.3);
        } else {
            // Error buzz
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(160, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.4);
        }
    } catch(e) {}
}

function setToken(token) {
    document.getElementById('manualTokenInput').value = token;
    verifyToken(token);
}

// Verification function
function verifyToken(token) {
    const eventId = document.getElementById('eventFilterSelect').value;

    fetch('/api/scan-qr', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            token: token,
            event_id: eventId
        })
    })
    .then(r => r.json())
    .then(res => {
        displayScanResult(res);
    })
    .catch(err => {
        notyf.error("Network error verifying pass.");
    });
}

function displayScanResult(res) {
    document.getElementById('idleState').style.display = 'none';
    document.getElementById('resultState').style.display = 'block';

    const statusAlert = document.getElementById('statusAlert');
    const statusTitle = document.getElementById('statusTitle');
    const statusMessage = document.getElementById('statusMessage');
    const statusIcon = document.getElementById('statusIcon');
    const confirmBtn = document.getElementById('confirmCheckInBtn');
    const checkinTimeRow = document.getElementById('checkinTimeRow');

    currentBookingId = (res.booking && res.booking.id) ? res.booking.id : null;

    if (res.booking) {
        document.getElementById('resBookingNumber').textContent = res.booking.booking_number;
        document.getElementById('resCustomerName').textContent = res.booking.customer_name;
        document.getElementById('resEventName').textContent = (res.event ? res.event.name : 'Selected Event');
        document.getElementById('resPassCount').textContent = (res.booking.pass_count || 1) + ' Pass(es)';
    }

    if (res.status === 'VALID') {
        playAudioChime(true);
        statusAlert.className = 'alert alert-success p-3 rounded-3 mb-3 d-flex align-items-center gap-3';
        statusIcon.innerHTML = '<i data-lucide="check-circle" class="text-success" style="width:36px;height:36px;"></i>';
        statusTitle.textContent = 'VALID PASS - ADMIT ATTENDEE';
        statusMessage.textContent = 'This digital pass is authentic, confirmed, and ready for entry.';
        confirmBtn.style.display = 'flex';
        checkinTimeRow.style.display = 'none';
    } else if (res.status === 'ALREADY_CHECKED_IN') {
        playAudioChime(false);
        statusAlert.className = 'alert alert-warning p-3 rounded-3 mb-3 d-flex align-items-center gap-3';
        statusIcon.innerHTML = '<i data-lucide="alert-triangle" class="text-warning" style="width:36px;height:36px;"></i>';
        statusTitle.textContent = 'ALREADY CHECKED IN';
        statusMessage.textContent = 'This pass was already used for entry earlier!';
        confirmBtn.style.display = 'none';
        checkinTimeRow.style.display = 'flex';
        document.getElementById('resCheckedInAt').textContent = res.checked_in_at || 'Previously';
    } else if (res.status === 'WRONG_EVENT') {
        playAudioChime(false);
        statusAlert.className = 'alert alert-danger p-3 rounded-3 mb-3 d-flex align-items-center gap-3';
        statusIcon.innerHTML = '<i data-lucide="x-circle" class="text-danger" style="width:36px;height:36px;"></i>';
        statusTitle.textContent = 'WRONG EVENT';
        statusMessage.textContent = res.message || 'Pass belongs to a different event.';
        confirmBtn.style.display = 'none';
        checkinTimeRow.style.display = 'none';
    } else {
        playAudioChime(false);
        statusAlert.className = 'alert alert-danger p-3 rounded-3 mb-3 d-flex align-items-center gap-3';
        statusIcon.innerHTML = '<i data-lucide="shield-alert" class="text-danger" style="width:36px;height:36px;"></i>';
        statusTitle.textContent = 'INVALID OR FAKE PASS';
        statusMessage.textContent = res.message || 'QR code token does not match any booking.';
        confirmBtn.style.display = 'none';
        checkinTimeRow.style.display = 'none';
    }

    lucide.createIcons();
}

// Check In Button Listener
document.getElementById('confirmCheckInBtn').addEventListener('click', function() {
    if (!currentBookingId) return;

    this.disabled = true;
    this.innerHTML = 'Admitting Attendee...';

    fetch('/api/check-in', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            booking_id: currentBookingId
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playAudioChime(true);
            Swal.fire({
                title: 'Checked In Successfully!',
                text: 'Attendee has been admitted to the venue.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            setTimeout(resetScanner, 2100);
        } else {
            Swal.fire('Error', data.error || 'Failed to check in.', 'error');
            document.getElementById('confirmCheckInBtn').disabled = false;
        }
    })
    .catch(e => {
        notyf.error('Network failure during check-in.');
        document.getElementById('confirmCheckInBtn').disabled = false;
    });
});

function resetScanner() {
    document.getElementById('idleState').style.display = 'block';
    document.getElementById('resultState').style.display = 'none';
    document.getElementById('manualTokenInput').value = '';
    currentBookingId = null;
    document.getElementById('confirmCheckInBtn').disabled = false;
    document.getElementById('confirmCheckInBtn').innerHTML = '<i data-lucide="check-circle-2" style="width:22px;height:22px;"></i> CONFIRM CHECK-IN & ADMIT';
    lucide.createIcons();
}

document.getElementById('resetScannerBtn').addEventListener('click', resetScanner);

// Manual submit form
document.getElementById('manualVerifyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const token = document.getElementById('manualTokenInput').value.trim();
    if (token) verifyToken(token);
});

// Camera Scanner Controls
const startBtn = document.getElementById('startScanBtn');
const stopBtn = document.getElementById('stopScanBtn');

startBtn.addEventListener('click', function() {
    html5QrCode = new Html5Qrcode("reader");
    startBtn.disabled = true;
    stopBtn.disabled = false;

    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText, decodedResult) => {
            // QR Scanned!
            verifyToken(decodedText);
        },
        (errorMessage) => {
            // Scanning frame parse - ignore
        }
    ).catch((err) => {
        notyf.error("Camera access failed or denied: " + err);
        startBtn.disabled = false;
        stopBtn.disabled = true;
    });
});

stopBtn.addEventListener('click', function() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            startBtn.disabled = false;
            stopBtn.disabled = true;
        }).catch(err => console.error(err));
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
