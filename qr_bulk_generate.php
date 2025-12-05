<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/permissions.php';

checkPageAccess(['admin', 'advisor']);

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$page_title = 'Bulk QR Generation';
$current_page = 'qr_bulk';

// Get all students
$students = $conn->query("SELECT id, student_id, first_name, last_name, section, year_level, photo FROM students ORDER BY year_level, section, last_name, first_name");

// Include appropriate header
if ($role === 'admin') {
    include 'includes/admin_header.php';
} else {
    include 'includes/advisor_header.php';
}
?>

<style>
.dashboard-container {
    padding: 0 !important;
}
</style>

<style>
@media print {
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
    .id-card-grid { page-break-inside: avoid; }
}

.bulk-container {
    padding: 0;
}



.filter-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    font-size: 16px;
}

.form-control:focus {
    outline: none;
    border-color: #1561AD;
}

.action-bar {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #1561AD;
    color: white;
}

.btn-primary:hover {
    background: #0d4a8a;
}

.btn-success {
    background: #10B981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-secondary {
    background: #6B7280;
    color: white;
}

.btn-secondary:hover {
    background: #4B5563;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background: #E5E7EB;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 15px;
    display: none;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    width: 0%;
    transition: width 0.3s;
}

.id-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.mini-card {
    width: 100%;
    aspect-ratio: 0.64;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #CE1126 100%);
    border-radius: 15px;
    padding: 20px;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.mini-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.mini-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(206, 17, 38, 0.1) 50%, transparent 70%);
    pointer-events: none;
}

.card-content {
    position: relative;
    z-index: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.card-logo {
    text-align: center;
    margin-bottom: 15px;
}

.logo-mini {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid #CE1126;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(206, 17, 38, 0.2);
    overflow: hidden;
}

.logo-mini img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.qr-mini {
    background: white;
    padding: 10px;
    border-radius: 8px;
    margin: 10px 0;
    display: flex;
    justify-content: center;
}

.student-name-mini {
    font-size: 16px;
    font-weight: 700;
    text-align: center;
    margin: 10px 0;
    color: #FCD116;
}

.student-id-mini {
    font-size: 12px;
    text-align: center;
    opacity: 0.8;
}

.stats-bar {
    background: #F3F4F6;
    padding: 15px 25px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1561AD;
}

.stat-label {
    font-size: 14px;
    color: #6B7280;
}
</style>

<!-- Controls Panel -->
<div class="content-card no-print">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-th"></i>
            Bulk QR Code Generation
        </div>
    </div>
    <div style="padding: 25px;">
        
        <div class="filter-section">
            <div class="form-group">
                <label>Filter by Year Level</label>
                <select id="filterYear" class="form-control">
                    <option value="">All Year Levels</option>
                    <option value="7">Grade 7</option>
                    <option value="8">Grade 8</option>
                    <option value="9">Grade 9</option>
                    <option value="10">Grade 10</option>
                    <option value="11">Grade 11</option>
                    <option value="12">Grade 12</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Filter by Section</label>
                <input type="text" id="filterSection" class="form-control" placeholder="e.g., A, B, C">
            </div>
            
            <div class="form-group">
                <label>Search Student</label>
                <input type="text" id="searchStudent" class="form-control" placeholder="Search by name or ID">
            </div>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value" id="totalCards">0</div>
                <div class="stat-label">Total Cards</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="visibleCards">0</div>
                <div class="stat-label">Visible Cards</div>
            </div>
        </div>

        <div class="action-bar" style="margin-top: 20px;">
            <button onclick="printAllCards()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print All Visible
            </button>
            <button onclick="downloadAllCards()" class="btn btn-success">
                <i class="fas fa-download"></i> Download as ZIP
            </button>
            <button onclick="selectAll()" class="btn btn-secondary">
                <i class="fas fa-check-square"></i> Select All
            </button>
        </div>

        <div class="progress-bar" id="progressBar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
    </div>
</div>

<!-- QR Cards Grid -->
<div class="content-card print-area">
    <div class="id-card-grid" id="cardsGrid">
        <?php while ($student = $students->fetch_assoc()): ?>
        <div class="mini-card" 
             data-year="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>"
             data-section="<?php echo htmlspecialchars($student['section'] ?? ''); ?>"
             data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
             data-id="<?php echo htmlspecialchars($student['student_id']); ?>"
             data-qr="<?php echo htmlspecialchars($student['qr_code'] ?? $student['student_id']); ?>">
            
            <div class="card-content">
                <div class="card-logo">
                    <div class="logo-mini">
                        <?php if (!empty($student['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($student['photo']); ?>" alt="Student Photo">
                        <?php else: ?>
                            <i class="fas fa-graduation-cap" style="font-size: 24px; color: #CE1126;"></i>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 10px; color: #FCD116;">SMART CLASSROOM</div>
                </div>

                <div class="qr-mini" data-qr-container></div>

                <div class="student-name-mini"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                <div class="student-id-mini">
                    ID: <?php echo htmlspecialchars($student['student_id']); ?><br>
                    Grade <?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?> - 
                    Section <?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.mini-card');
    
    cards.forEach(card => {
        const qrContainer = card.querySelector('[data-qr-container]');
        const qrData = card.dataset.qr;
        
        new QRCode(qrContainer, {
            text: qrData,
            width: 150,
            height: 150,
            colorDark: "#0038A8",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    });
    
    updateStats();
});

document.getElementById('filterYear').addEventListener('change', filterCards);
document.getElementById('filterSection').addEventListener('input', filterCards);
document.getElementById('searchStudent').addEventListener('input', filterCards);

function filterCards() {
    const yearFilter = document.getElementById('filterYear').value.toLowerCase();
    const sectionFilter = document.getElementById('filterSection').value.toLowerCase();
    const searchFilter = document.getElementById('searchStudent').value.toLowerCase();
    
    const cards = document.querySelectorAll('.mini-card');
    
    cards.forEach(card => {
        const year = card.dataset.year.toLowerCase();
        const section = card.dataset.section.toLowerCase();
        const name = card.dataset.name.toLowerCase();
        const id = card.dataset.id.toLowerCase();
        
        const yearMatch = !yearFilter || year === yearFilter;
        const sectionMatch = !sectionFilter || section.includes(sectionFilter);
        const searchMatch = !searchFilter || name.includes(searchFilter) || id.includes(searchFilter);
        
        if (yearMatch && sectionMatch && searchMatch) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    updateStats();
}

function updateStats() {
    const allCards = document.querySelectorAll('.mini-card');
    const visibleCards = Array.from(allCards).filter(card => card.style.display !== 'none');
    
    document.getElementById('totalCards').textContent = allCards.length;
    document.getElementById('visibleCards').textContent = visibleCards.length;
}

function printAllCards() {
    window.print();
}

async function downloadAllCards() {
    const cards = Array.from(document.querySelectorAll('.mini-card')).filter(card => card.style.display !== 'none');
    
    if (cards.length === 0) {
        alert('No cards to download. Please adjust your filters.');
        return;
    }
    
    const zip = new JSZip();
    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    
    progressBar.style.display = 'block';
    
    for (let i = 0; i < cards.length; i++) {
        const card = cards[i];
        const studentId = card.dataset.id;
        const studentName = card.dataset.name;
        
        try {
            const canvas = await html2canvas(card, {
                scale: 3,
                backgroundColor: null
            });
            
            const blob = await new Promise(resolve => canvas.toBlob(resolve));
            zip.file(`QR_${studentId}_${studentName.replace(/\s+/g, '_')}.png`, blob);
            
            const progress = ((i + 1) / cards.length) * 100;
            progressFill.style.width = progress + '%';
        } catch (error) {
            console.error('Error generating card:', error);
        }
    }
    
    const content = await zip.generateAsync({type: 'blob'});
    saveAs(content, `QR_Codes_${new Date().toISOString().split('T')[0]}.zip`);
    
    progressBar.style.display = 'none';
    progressFill.style.width = '0%';
}

function selectAll() {
    document.getElementById('filterYear').value = '';
    document.getElementById('filterSection').value = '';
    document.getElementById('searchStudent').value = '';
    filterCards();
}
</script>

<?php
if ($role === 'admin') {
    include 'includes/admin_footer.php';
} else {
    include 'includes/advisor_footer.php';
}
?>
