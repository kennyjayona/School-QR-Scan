<?php
session_start();
require_once '../db_connect.php';

$page_title = 'My QR Code';
$current_page = 'qr';

$user_id = $_SESSION['user_id'];

// Get student record by matching username with qr_code
$username = $_SESSION['username'];
$student_query = $conn->prepare("SELECT s.* FROM students s WHERE s.qr_code = ? LIMIT 1");
$student_query->bind_param("s", $username);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();

include '../includes/student_header.php';
?>

<!-- QR Code Display -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-qrcode"></i>
            My QR Code
        </div>
    </div>
    
    <?php if ($student): ?>
    <div style="text-align: center; padding: 40px;">
        <div style="margin-bottom: 20px;">
            <h3><?php echo htmlspecialchars($student['username']); ?></h3>
            <p style="color: var(--text-secondary);">Student ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
        </div>
        
        <div id="qrcode" style="display: inline-block; padding: 20px; background: white; border-radius: 12px; box-shadow: var(--shadow-md);"></div>
        
        <div style="margin-top: 30px;">
            <button onclick="downloadQR()" class="btn btn-primary" style="margin-right: 10px;">
                <i class="fas fa-download"></i> Download QR Code
            </button>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print QR Code
            </button>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: rgba(59, 130, 246, 0.1); border-radius: 8px; max-width: 500px; margin-left: auto; margin-right: auto;">
            <i class="fas fa-info-circle"></i> Use this QR code for attendance scanning
        </div>
    </div>
    <?php else: ?>
    <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
        <i class="fas fa-exclamation-triangle" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
        Student record not found
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// Generate QR Code
const qrcode = new QRCode(document.getElementById("qrcode"), {
    text: "<?php echo htmlspecialchars($student['username'] ?? ''); ?>",
    width: 256,
    height: 256,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});

// Download QR Code
function downloadQR() {
    const canvas = document.querySelector('#qrcode canvas');
    const url = canvas.toDataURL('image/png');
    const a = document.createElement('a');
    a.href = url;
    a.download = 'my-qr-code.png';
    a.click();
}
</script>

<?php include '../includes/student_footer.php'; ?>
