<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'TIME IN Scanner';
$current_page = 'time_in';
$base_url = './';
$name = $_SESSION['name'] ?? $_SESSION['username'];

include 'includes/admin_header.php';
?>

<style>
    .scanner-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .scanner-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .scanner-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .scanner-header h2 {
        color: #0038A8;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .scanner-header .badge {
        font-size: 16px;
        padding: 10px 20px;
        background: #10b981;
        color: white;
        border-radius: 8px;
    }

    #reader {
        border-radius: 15px;
        overflow: hidden;
        border: 3px solid #0038A8;
        margin: 20px 0;
    }

    .status-message {
        margin-top: 20px;
        padding: 15px;
        border-radius: 10px;
        display: none;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .status-success {
        background: #d4edda;
        border: 2px solid #28a745;
        color: #155724;
    }

    .status-warning {
        background: #fff3cd;
        border: 2px solid #ffc107;
        color: #856404;
    }

    .status-error {
        background: #f8d7da;
        border: 2px solid #dc3545;
        color: #721c24;
    }

    .scan-icon {
        font-size: 48px;
        color: #0038A8;
        margin-bottom: 15px;
    }
</style>

<div class="scanner-container">
    <div class="scanner-card">
        <div class="scanner-header">
            <i class="fas fa-sign-in-alt scan-icon"></i>
            <h2>TIME IN Scanner</h2>
            <span class="badge">School Arrival</span>
            <p class="text-muted mt-2">Scan student QR code for school entry</p>
        </div>

        <div id="reader"></div>

        <div id="statusMessage" class="status-message"></div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let isScanning = false;

    function onScanSuccess(decodedText, decodedResult) {
        if (isScanning) return;
        isScanning = true;

        console.log('QR Code Scanned:', decodedText);

        // Show processing message
        const statusDiv = document.getElementById('statusMessage');
        statusDiv.style.display = 'block';
        statusDiv.className = 'status-message status-warning';
        statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        // Send to server
        fetch('school_attendance_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'student_id=' + encodeURIComponent(decodedText) + '&action=time_in&send_sms=0'
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Server returned ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                console.log('Parsed data:', data);
                const statusDiv = document.getElementById('statusMessage');
                statusDiv.style.display = 'block';

                if (data.status === 'success') {
                    statusDiv.className = 'status-message status-success';
                    statusDiv.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <strong>TIME IN Successful!</strong><br>
                Student: ${data.student}<br>
                Time: ${data.time}<br>
                Status: ${data.attendance_status}
            `;

                    playSound('success');
                } else if (data.status === 'warning') {
                    statusDiv.className = 'status-message status-warning';
                    statusDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <strong>${data.message}</strong><br>
                Student: ${data.student}<br>
                Already timed in at: ${data.time}
            `;

                    playSound('warning');
                } else {
                    statusDiv.className = 'status-message status-error';
                    statusDiv.innerHTML = `
                <i class="fas fa-times-circle"></i>
                <strong>Error:</strong> ${data.message}<br>
                ${data.debug ? '<small>Debug: ' + data.debug + '</small>' : ''}
                ${data.student_id ? '<br><small>Scanned: ' + data.student_id + '</small>' : ''}
            `;

                    playSound('error');
                }

                // Allow next scan after 3 seconds
                setTimeout(() => {
                    isScanning = false;
                    statusDiv.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                const statusDiv = document.getElementById('statusMessage');
                statusDiv.style.display = 'block';
                statusDiv.className = 'status-message status-error';
                statusDiv.innerHTML = `
            <i class="fas fa-times-circle"></i>
            <strong>Connection Error</strong><br>
            ${error.message}<br>
            <small>Check console for details</small>
        `;

                setTimeout(() => {
                    isScanning = false;
                    statusDiv.style.display = 'none';
                }, 5000);
            });
    }

    function onScanError(errorMessage) {
        // Ignore scan errors (too noisy)
    }

    function playSound(type) {
        const audioContext = new(window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        if (type === 'success') {
            oscillator.frequency.value = 800;
            gainNode.gain.value = 0.3;
            oscillator.start();
            setTimeout(() => oscillator.stop(), 200);
        } else if (type === 'warning') {
            oscillator.frequency.value = 600;
            gainNode.gain.value = 0.3;
            oscillator.start();
            setTimeout(() => oscillator.stop(), 300);
        } else {
            oscillator.frequency.value = 400;
            gainNode.gain.value = 0.3;
            oscillator.start();
            setTimeout(() => oscillator.stop(), 400);
        }
    }

    // Initialize QR Scanner
    const html5QrCode = new Html5Qrcode("reader");
    const config = {
        fps: 10,
        qrbox: {
            width: 250,
            height: 250
        },
        aspectRatio: 1.0
    };

    html5QrCode.start({
            facingMode: "environment"
        },
        config,
        onScanSuccess,
        onScanError
    ).catch(err => {
        alert('Unable to access camera. Please grant camera permissions.');
        console.error('Camera error:', err);
    });
</script>

<?php include 'includes/admin_footer.php'; ?>