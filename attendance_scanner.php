<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/permissions.php';

// Check if user is logged in and has proper role
checkPageAccess(['admin', 'advisor', 'teacher']);

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$page_title = 'QR Scanner - Attendance';

// Get user's classrooms/subjects based on role
$classrooms = [];
if ($role === 'admin') {
    $stmt = $conn->prepare("SELECT DISTINCT c.id, c.classroom_name, c.section, c.year_level 
                           FROM classrooms c 
                           ORDER BY c.year_level, c.section");
    $stmt->execute();
    $classrooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} elseif ($role === 'advisor') {
    $stmt = $conn->prepare("SELECT id, classroom_name, section, year_level 
                           FROM classrooms 
                           WHERE advisor_id = ? 
                           ORDER BY year_level, section");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $classrooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} elseif ($role === 'teacher') {
    $stmt = $conn->prepare("SELECT DISTINCT c.id, c.classroom_name, c.section, c.year_level 
                           FROM classrooms c
                           JOIN classroom_subjects cs ON c.id = cs.classroom_id
                           WHERE cs.teacher_id = ?
                           ORDER BY c.year_level, c.section");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $classrooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get selected classroom
$selected_classroom = $_GET['classroom_id'] ?? ($classrooms[0]['id'] ?? null);

// Include appropriate header
if ($role === 'admin') {
    include 'includes/admin_header.php';
} elseif ($role === 'advisor') {
    include 'includes/advisor_header.php';
} else {
    include 'includes/teacher_header.php';
}
?>

<style>
    .scanner-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .scanner-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #6B7280;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s;
    }

    .back-btn:hover {
        background: #4B5563;
        transform: translateX(-5px);
    }

    .export-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #10B981;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }

    .export-btn:hover {
        background: #059669;
    }

    .classroom-selector {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .classroom-selector select {
        width: 100%;
        padding: 12px;
        border: 2px solid #E5E7EB;
        border-radius: 8px;
        font-size: 16px;
    }

    .time-display {
        background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
        padding: 30px;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 30px;
    }

    .time-display .time {
        font-size: 48px;
        font-weight: 700;
        color: #1E40AF;
        margin-bottom: 10px;
    }

    .time-display .date {
        font-size: 18px;
        color: #6B7280;
        margin-bottom: 5px;
    }

    .time-display .timezone {
        font-size: 14px;
        color: #9CA3AF;
    }

    .scan-modes {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .mode-card {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        border: 3px solid transparent;
    }

    .mode-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .mode-card.active {
        border-color: #10B981;
        background: #F0FDF4;
    }

    .mode-card.time-in {
        border-left: 4px solid #10B981;
    }

    .mode-card.time-out {
        border-left: 4px solid #6B7280;
    }

    .mode-card .icon {
        font-size: 48px;
        margin-bottom: 15px;
    }

    .mode-card.time-in .icon {
        color: #10B981;
    }

    .mode-card.time-out .icon {
        color: #6B7280;
    }

    .mode-card .title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .mode-card .subtitle {
        color: #6B7280;
        font-size: 14px;
    }

    .scanner-section {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .scanner-title {
        font-size: 24px;
        font-weight: 700;
        color: #1F2937;
        margin-bottom: 20px;
    }

    .scan-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #E5E7EB;
    }

    .scan-tab {
        padding: 12px 24px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        color: #6B7280;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .scan-tab:hover {
        color: #1F2937;
    }

    .scan-tab.active {
        color: #1561AD;
        border-bottom-color: #1561AD;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .camera-container {
        position: relative;
        background: #F3F4F6;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        min-height: 400px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .camera-placeholder {
        color: #9CA3AF;
    }

    .camera-placeholder i {
        font-size: 80px;
        margin-bottom: 20px;
    }

    .camera-placeholder p {
        font-size: 18px;
        margin-bottom: 30px;
    }

    #video {
        width: 100%;
        max-width: 640px;
        border-radius: 8px;
        display: none;
    }

    #canvas {
        display: none;
    }

    .start-camera-btn {
        padding: 15px 40px;
        background: #10B981;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
    }

    .start-camera-btn:hover {
        background: #059669;
    }

    .upload-container {
        border: 3px dashed #D1D5DB;
        border-radius: 12px;
        padding: 60px 40px;
        text-align: center;
        background: #F9FAFB;
        transition: all 0.3s;
    }

    .upload-container:hover {
        border-color: #1561AD;
        background: #EFF6FF;
    }

    .upload-container.dragover {
        border-color: #10B981;
        background: #F0FDF4;
    }

    .upload-icon {
        font-size: 60px;
        color: #9CA3AF;
        margin-bottom: 20px;
    }

    .upload-text {
        font-size: 18px;
        color: #6B7280;
        margin-bottom: 10px;
    }

    .upload-subtext {
        font-size: 14px;
        color: #9CA3AF;
    }

    .choose-file-btn {
        margin-top: 20px;
        padding: 12px 30px;
        background: white;
        border: 2px solid #D1D5DB;
        border-radius: 8px;
        color: #374151;
        font-size: 16px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }

    .choose-file-btn:hover {
        border-color: #1561AD;
        color: #1561AD;
    }

    .manual-input {
        max-width: 500px;
        margin: 0 auto;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 2px solid #E5E7EB;
        border-radius: 8px;
        font-size: 16px;
    }

    .form-group input:focus {
        outline: none;
        border-color: #1561AD;
    }

    .submit-btn {
        width: 100%;
        padding: 15px;
        background: #1561AD;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .submit-btn:hover {
        background: #0d4a8a;
    }

    .tips-box {
        background: #FFFBEB;
        border-left: 4px solid #F59E0B;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .tips-box h4 {
        color: #92400E;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tips-box ul {
        color: #78350F;
        margin-left: 20px;
    }

    .tips-box li {
        margin-bottom: 5px;
    }

    .attendance-log {
        margin-top: 30px;
        background: #F9FAFB;
        padding: 20px;
        border-radius: 12px;
    }

    .attendance-log h3 {
        margin-bottom: 15px;
        color: #1F2937;
    }

    .log-item {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .log-item .student-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .log-item .student-photo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .log-item .status {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
    }

    .log-item .status.time-in {
        background: #D1FAE5;
        color: #065F46;
    }

    .log-item .status.time-out {
        background: #E5E7EB;
        color: #374151;
    }

    @media (max-width: 768px) {
        .scan-modes {
            grid-template-columns: 1fr;
        }

        .scanner-header {
            flex-direction: column;
            gap: 15px;
        }
    }
</style>

<!-- Export Button -->
<div class="content-card" style="margin-bottom: 20px;">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-qrcode"></i>
            QR Scanner - Attendance
        </div>
        <div class="card-actions">
            <button class="btn btn-success" onclick="exportTodayAttendance()">
                <i class="fas fa-download"></i> Export Today
            </button>
        </div>
    </div>
</div>

<?php if (count($classrooms) > 0): ?>
<div class="content-card" style="margin-bottom: 20px;">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-door-open"></i>
            Select Classroom
        </div>
    </div>
    <div style="padding: 20px;">
        <div class="classroom-selector">
            <select id="classroom-select" onchange="changeClassroom(this.value)">
                <?php foreach ($classrooms as $classroom): ?>
                    <option value="<?php echo $classroom['id']; ?>" <?php echo $selected_classroom == $classroom['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($classroom['classroom_name']); ?> -
                        Grade <?php echo $classroom['year_level']; ?>
                        Section <?php echo htmlspecialchars($classroom['section']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="content-card" style="margin-bottom: 20px;">
    <div class="time-display">
        <div class="time" id="current-time">8:49:40 PM</div>
        <div class="date" id="current-date">Monday, November 3, 2025</div>
        <div class="timezone">Manila Time (UTC+8)</div>
    </div>
</div>

<div class="content-card" style="margin-bottom: 20px;">
    <div class="scan-modes">
        <div class="mode-card time-in active" onclick="setMode('time-in')">
            <div class="icon">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="title">TIME IN</div>
            <div class="subtitle">Check in students</div>
        </div>
        <div class="mode-card time-out" onclick="setMode('time-out')">
            <div class="icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="title">TIME OUT</div>
            <div class="subtitle">Check out students</div>
        </div>
    </div>
</div>

<div class="content-card" style="margin-bottom: 20px;">
    <div class="scanner-section">
        <h2 class="scanner-title">Scan Student QR Code</h2>

        <div class="scan-tabs">
            <button class="scan-tab active" onclick="switchTab('camera')">
                <i class="fas fa-camera"></i> Camera
            </button>
            <button class="scan-tab" onclick="switchTab('upload')">
                <i class="fas fa-upload"></i> Upload
            </button>
            <button class="scan-tab" onclick="switchTab('manual')">
                <i class="fas fa-keyboard"></i> Manual
            </button>
        </div>

        <div id="camera-tab" class="tab-content active">
            <div class="camera-container">
                <div class="camera-placeholder" id="camera-placeholder">
                    <i class="fas fa-camera"></i>
                    <p>Camera not active</p>
                    <button class="start-camera-btn" onclick="startCamera()">
                        <i class="fas fa-video"></i> Start Camera
                    </button>
                </div>
                <video id="video" autoplay></video>
                <canvas id="canvas"></canvas>
            </div>
            <p style="text-align: center; color: #6B7280; margin-top: 15px;">
                Position the QR code within the frame to scan<br>
                Make sure there's good lighting for best results
            </p>
        </div>

        <div id="upload-tab" class="tab-content">
            <div class="upload-container" id="upload-area">
                <div class="upload-icon">
                    <i class="fas fa-image"></i>
                </div>
                <div class="upload-text">Drop QR code image here</div>
                <div class="upload-subtext">or click to select from your device</div>
                <button class="choose-file-btn" onclick="document.getElementById('qr-file-input').click()">
                    <i class="fas fa-folder-open"></i> Choose Image File
                </button>
                <input type="file" id="qr-file-input" accept="image/*" style="display: none;" onchange="handleFileUpload(this)">
            </div>

            <div class="tips-box">
                <h4><i class="fas fa-lightbulb"></i> Tips for best results:</h4>
                <ul>
                    <li>Ensure the QR code is clearly visible and not blurry</li>
                    <li>Good lighting helps with recognition</li>
                    <li>The system will automatically send email notifications to parents</li>
                </ul>
            </div>
        </div>

        <div id="manual-tab" class="tab-content">
            <div class="manual-input">
                <form onsubmit="submitManualEntry(event)">
                    <div class="form-group">
                        <label for="student-id">Student ID Number</label>
                        <input type="text" id="student-id" placeholder="Enter student ID" required>
                    </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-check"></i> Submit Attendance
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="attendance-log">
        <h3><i class="fas fa-list"></i> Today's Attendance Log</h3>
        <div id="attendance-list">
            <!-- Attendance records will be loaded here -->
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
    let currentMode = 'time-in';
    let classroomId = <?php echo $selected_classroom ?? 'null'; ?>;
    let cameraStream = null;
    let scanning = false;

    // Update time display
    function updateTime() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        const dateStr = now.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        document.getElementById('current-time').textContent = timeStr;
        document.getElementById('current-date').textContent = dateStr;
    }

    setInterval(updateTime, 1000);
    updateTime();

    function setMode(mode) {
        currentMode = mode;
        document.querySelectorAll('.mode-card').forEach(card => {
            card.classList.remove('active');
        });
        document.querySelector(`.mode-card.${mode}`).classList.add('active');
    }

    function switchTab(tab) {
        document.querySelectorAll('.scan-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        event.target.classList.add('active');
        document.getElementById(`${tab}-tab`).classList.add('active');

        if (tab !== 'camera' && cameraStream) {
            stopCamera();
        }
    }

    function changeClassroom(id) {
        window.location.href = `?classroom_id=${id}`;
    }

    async function startCamera() {
        try {
            const video = document.getElementById('video');
            const placeholder = document.getElementById('camera-placeholder');

            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment'
                }
            });

            video.srcObject = cameraStream;
            video.style.display = 'block';
            placeholder.style.display = 'none';

            scanning = true;
            scanQRCode();
        } catch (err) {
            alert('Unable to access camera: ' + err.message);
        }
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
            scanning = false;
            document.getElementById('video').style.display = 'none';
            document.getElementById('camera-placeholder').style.display = 'flex';
        }
    }

    function scanQRCode() {
        if (!scanning) return;

        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');

        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height);

            if (code) {
                processQRCode(code.data);
                return;
            }
        }

        requestAnimationFrame(scanQRCode);
    }

    function handleFileUpload(input) {
        const file = input.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.getElementById('canvas');
                const context = canvas.getContext('2d');
                canvas.width = img.width;
                canvas.height = img.height;
                context.drawImage(img, 0, 0);

                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);

                if (code) {
                    processQRCode(code.data);
                } else {
                    alert('No QR code found in image');
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function processQRCode(data) {
        recordAttendance(data);
    }

    function submitManualEntry(event) {
        event.preventDefault();
        const studentId = document.getElementById('student-id').value;
        recordAttendance(studentId);
    }

    function recordAttendance(studentId) {
        fetch('attendance_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    student_id: studentId,
                    mode: currentMode,
                    classroom_id: classroomId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✓ ${data.message}`);
                    loadTodayAttendance();
                    document.getElementById('student-id').value = '';
                } else {
                    alert(`✗ ${data.message}`);
                }
            })
            .catch(error => {
                alert('Error recording attendance');
                console.error(error);
            });
    }

    function loadTodayAttendance() {
        if (!classroomId) return;

        fetch(`get_attendance.php?classroom_id=${classroomId}&date=today`)
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('attendance-list');
                if (data.length === 0) {
                    list.innerHTML = '<p style="text-align: center; color: #6B7280;">No attendance records yet</p>';
                    return;
                }

                list.innerHTML = data.map(record => `
                <div class="log-item">
                    <div class="student-info">
                        <img src="${record.photo || 'assets/images/default-avatar.png'}" 
                             alt="${record.name}" class="student-photo">
                        <div>
                            <strong>${record.name}</strong><br>
                            <small>${record.student_id}</small>
                        </div>
                    </div>
                    <div>
                        <span class="status ${record.type}">${record.type.toUpperCase().replace('-', ' ')}</span>
                        <small style="display: block; color: #6B7280; margin-top: 5px;">
                            ${record.time}
                        </small>
                    </div>
                </div>
            `).join('');
            });
    }

    function exportTodayAttendance() {
        if (!classroomId) {
            alert('Please select a classroom first');
            return;
        }
        window.location.href = `export_attendance.php?classroom_id=${classroomId}&date=today`;
    }

    // Drag and drop for upload
    const uploadArea = document.getElementById('upload-area');
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const input = document.getElementById('qr-file-input');
            input.files = e.dataTransfer.files;
            handleFileUpload(input);
        }
    });

    // Load attendance on page load
    loadTodayAttendance();
    setInterval(loadTodayAttendance, 30000); // Refresh every 30 seconds
</script>

<?php
if ($role === 'admin') {
    include 'includes/admin_footer.php';
} elseif ($role === 'advisor') {
    include 'includes/advisor_footer.php';
} else {
    include 'includes/teacher_footer.php';
}
?>