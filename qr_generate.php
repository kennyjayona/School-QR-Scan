<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/permissions.php';

checkPageAccess(['admin', 'advisor', 'teacher']);

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$page_title = 'Generate QR Code';
$current_page = 'qr_generate';

// Get all students for selection
$students = $conn->query("SELECT id, student_id, first_name, last_name, section, year_level, photo FROM students ORDER BY last_name, first_name");

$selected_student = null;
$student_id = $_GET['student_id'] ?? '';

if (!empty($student_id)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $selected_student = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        error_log("QR generation error: " . $e->getMessage());
    }
}

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
@media print {
    body * { visibility: hidden; }
    .id-card-print, .id-card-print * { visibility: visible; }
    .id-card-print { position: absolute; left: 0; top: 0; }
    .no-print { display: none !important; }
}

.form-row {
    display: flex;
    gap: 15px;
    align-items: end;
    margin-bottom: 0;
}

.form-group {
    flex: 1;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
    font-size: 13px;
}

.form-group select.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--main-bg);
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-group select.form-control:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 56, 168, 0.1);
}

.btn-generate {
    padding: 10px 24px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-generate:hover {
    background: #002d8a;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 56, 168, 0.3);
}

.card-body {
    padding: 25px;
}

.id-card-container {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 30px;
}

.id-card {
    width: 350px;
    height: 550px;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    position: relative;
}

.id-card-front, .id-card-back {
    width: 100%;
    height: 100%;
    position: relative;
    padding: 30px;
    color: white;
}

.id-card-front {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #CE1126 100%);
}

.id-card-front::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        linear-gradient(45deg, transparent 30%, rgba(206, 17, 38, 0.1) 50%, transparent 70%),
        linear-gradient(-45deg, transparent 30%, rgba(0, 56, 168, 0.1) 50%, transparent 70%);
    pointer-events: none;
}

.card-header-logo {
    text-align: center;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
}

.logo-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid #CE1126;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(206, 17, 38, 0.2);
    overflow: hidden;
}

.logo-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.logo-placeholder {
    font-size: 40px;
    color: #CE1126;
}

.qr-code-section {
    background: white;
    padding: 15px;
    border-radius: 12px;
    margin: 20px 0;
    position: relative;
    z-index: 1;
}

#qrcode-front {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
}

.student-id-info {
    text-align: center;
    margin-top: 15px;
    position: relative;
    z-index: 1;
}

.expire-date {
    font-size: 14px;
    color: #FCD116;
    margin-top: 10px;
}

.id-number {
    font-size: 12px;
    color: rgba(255,255,255,0.7);
}

.id-card-back {
    background: linear-gradient(135deg, #CE1126 0%, #1a1a1a 50%, #0038A8 100%);
}

.student-details {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    padding: 20px;
    border-radius: 12px;
    margin-top: 30px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.detail-label {
    color: #FCD116;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
}

.detail-value {
    color: white;
    font-size: 16px;
    font-weight: 700;
}

.barcode-section {
    margin-top: 30px;
    text-align: center;
}

#barcode {
    background: white;
    padding: 10px;
    border-radius: 8px;
    display: inline-block;
}

.valid-thru {
    text-align: center;
    margin-top: 20px;
    font-size: 18px;
    font-weight: 700;
    color: white;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-custom {
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.btn-download {
    background: var(--success);
    color: white;
}

.btn-download:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-print {
    background: var(--info);
    color: white;
}

.btn-print:hover {
    background: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-export-all {
    background: var(--warning);
    color: white;
}

.btn-export-all:hover {
    background: #d97706;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}
</style>

<!-- Student Selector -->
<div class="content-card no-print">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-qrcode"></i>
            Generate Student QR Code
        </div>
    </div>
    <div class="card-body">
        <form method="GET">
            <div class="form-row">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Choose a student --</option>
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                    <?php echo ($student_id === $student['student_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?> 
                                (<?php echo htmlspecialchars($student['student_id']); ?>) - 
                                Grade <?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-generate">
                    <i class="fas fa-search"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_student): ?>
    <div class="id-card-container id-card-print">
        <div class="id-card">
            <div class="id-card-front">
                <div class="card-header-logo">
                    <div class="logo-circle">
                        <?php if (!empty($selected_student['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($selected_student['photo']); ?>" alt="Student Photo">
                        <?php else: ?>
                            <i class="fas fa-graduation-cap logo-placeholder"></i>
                        <?php endif; ?>
                    </div>
                    <h4 style="margin: 0; font-size: 14px; color: #FCD116;">SMART CLASSROOM</h4>
                    <p style="margin: 5px 0 0 0; font-size: 11px; opacity: 0.8;">ATTENDANCE SYSTEM</p>
                </div>

                <div class="qr-code-section">
                    <div id="qrcode-front"></div>
                </div>

                <div class="student-id-info">
                    <div class="expire-date">EXPIRE: <?php echo date('m-d-y', strtotime('+1 year')); ?></div>
                    <div class="id-number">ID NO: <?php echo htmlspecialchars($selected_student['student_id']); ?></div>
                </div>
            </div>
        </div>

        <div class="id-card">
            <div class="id-card-back">
                <div class="card-header-logo">
                    <h2 style="margin: 0; font-size: 28px; color: #FCD116; text-transform: uppercase;">
                        <?php echo htmlspecialchars($selected_student['first_name'] . ' ' . $selected_student['last_name']); ?>
                    </h2>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: rgba(255,255,255,0.8);">STUDENT</p>
                </div>

                <div class="student-details">
                    <div class="detail-row">
                        <span class="detail-label">ID Number</span>
                        <span class="detail-value"><?php echo htmlspecialchars($selected_student['student_id']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Year Level</span>
                        <span class="detail-value">Grade <?php echo htmlspecialchars($selected_student['year_level'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Section</span>
                        <span class="detail-value"><?php echo htmlspecialchars($selected_student['section'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row" style="border-bottom: none;">
                        <span class="detail-label">Contact</span>
                        <span class="detail-value"><?php echo htmlspecialchars($selected_student['contact_number'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="valid-thru">
                    VALID THRU: <?php echo date('m/y', strtotime('+1 year')); ?>
                </div>

                <div class="barcode-section">
                    <svg id="barcode"></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="action-buttons no-print">
        <button onclick="downloadFrontCard()" class="btn-custom btn-download">
            <i class="fas fa-download"></i> Download Front
        </button>
        <button onclick="downloadBackCard()" class="btn-custom btn-download">
            <i class="fas fa-download"></i> Download Back
        </button>
        <button onclick="printCards()" class="btn-custom btn-print">
            <i class="fas fa-print"></i> Print Both Cards
        </button>
        <button onclick="downloadBothCards()" class="btn-custom btn-export-all">
            <i class="fas fa-file-export"></i> Export Both as Images
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
    const studentData = {
        name: <?php echo json_encode($selected_student['first_name'] . ' ' . $selected_student['last_name']); ?>,
        studentId: <?php echo json_encode($selected_student['student_id']); ?>,
        qrCode: <?php echo json_encode($selected_student['qr_code'] ?? $selected_student['student_id']); ?>
    };

    new QRCode(document.getElementById("qrcode-front"), {
        text: studentData.qrCode,
        width: 200,
        height: 200,
        colorDark: "#0038A8",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });

    JsBarcode("#barcode", studentData.studentId, {
        format: "CODE128",
        width: 2,
        height: 50,
        displayValue: false,
        background: "#ffffff",
        lineColor: "#000000"
    });

    function downloadFrontCard() {
        const frontCard = document.querySelector('.id-card-front').parentElement;
        html2canvas(frontCard, { scale: 3, backgroundColor: null }).then(canvas => {
            const link = document.createElement('a');
            link.download = `ID_Front_${studentData.studentId}.png`;
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    function downloadBackCard() {
        const backCard = document.querySelectorAll('.id-card')[1];
        html2canvas(backCard, { scale: 3, backgroundColor: null }).then(canvas => {
            const link = document.createElement('a');
            link.download = `ID_Back_${studentData.studentId}.png`;
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    async function downloadBothCards() {
        const cards = document.querySelectorAll('.id-card');
        for (let i = 0; i < cards.length; i++) {
            const canvas = await html2canvas(cards[i], { scale: 3, backgroundColor: null });
            const link = document.createElement('a');
            link.download = `ID_${i === 0 ? 'Front' : 'Back'}_${studentData.studentId}.png`;
            link.href = canvas.toDataURL();
            link.click();
            await new Promise(resolve => setTimeout(resolve, 500));
        }
    }

    function printCards() {
        window.print();
    }
    </script>
<?php endif; ?>

<?php
if ($role === 'admin') {
    include 'includes/admin_footer.php';
} elseif ($role === 'advisor') {
    include 'includes/advisor_footer.php';
} else {
    include 'includes/teacher_footer.php';
}
?>
