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
    .print-area { 
        position: absolute; 
        left: 0; 
        top: 0; 
        width: 100%; 
    }
    .no-print { display: none !important; }
    
    /* Print-ready layout: 2 cards per page */
    .id-card-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 20px !important;
        padding: 20px !important;
    }
    
    .mini-card {
        page-break-inside: avoid;
        break-inside: avoid;
        margin-bottom: 20px;
    }
    
    /* Force page break after every 2 cards */
    .mini-card:nth-child(2n) {
        page-break-after: always;
        break-after: always;
    }
    
    /* Remove hover effects in print */
    .mini-card:hover {
        transform: none !important;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2) !important;
    }
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
    color: var(--text-primary);
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group label i {
    margin-right: 5px;
    color: var(--primary-blue);
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    background: var(--main-bg);
    color: var(--text-primary);
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 56, 168, 0.1);
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
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
    margin-top: 15px;
    display: none;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--success), #059669);
    width: 0%;
    transition: width 0.3s ease;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
}

.info-message {
    background: #EFF6FF;
    border-left: 4px solid var(--info);
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    color: #1E40AF;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-message i {
    font-size: 18px;
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
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    padding: 20px 25px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.stat-item {
    text-align: center;
    padding: 0 20px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary-blue);
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 13px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
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
        
        <div class="info-message">
            <i class="fas fa-info-circle"></i>
            <span>Use the filters below to select specific students by year level and section. You can print all visible cards or download them as a ZIP file organized by grade and section.</span>
        </div>
        
        <div class="filter-section">
            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Filter by Year Level</label>
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
                <label><i class="fas fa-users"></i> Filter by Section</label>
                <select id="filterSection" class="form-control">
                    <option value="">All Sections</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-search"></i> Search Student</label>
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
                <i class="fas fa-redo"></i> Clear Filters
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
    
    // Generate QR codes for all cards
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
    
    // Populate section dropdown dynamically
    populateSectionDropdown();
    
    updateStats();
});

// Populate section dropdown with unique sections from students
function populateSectionDropdown() {
    const cards = document.querySelectorAll('.mini-card');
    const sections = new Set();
    
    cards.forEach(card => {
        const section = card.dataset.section;
        if (section && section.trim() !== '') {
            sections.add(section);
        }
    });
    
    const sectionSelect = document.getElementById('filterSection');
    const sortedSections = Array.from(sections).sort();
    
    sortedSections.forEach(section => {
        const option = document.createElement('option');
        option.value = section;
        option.textContent = `Section ${section}`;
        sectionSelect.appendChild(option);
    });
}

document.getElementById('filterYear').addEventListener('change', filterCards);
document.getElementById('filterSection').addEventListener('change', filterCards);
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
        const sectionMatch = !sectionFilter || section === sectionFilter;
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
    const visibleCards = Array.from(document.querySelectorAll('.mini-card')).filter(card => card.style.display !== 'none');
    
    if (visibleCards.length === 0) {
        alert('No cards to print. Please adjust your filters.');
        return;
    }
    
    window.print();
}

async function downloadAllCards() {
    const cards = Array.from(document.querySelectorAll('.mini-card')).filter(card => card.style.display !== 'none');
    
    if (cards.length === 0) {
        alert('No cards to download. Please adjust your filters.');
        return;
    }
    
    // Show confirmation for large batches
    if (cards.length > 50) {
        const confirmed = confirm(`You are about to download ${cards.length} QR codes. This may take a few minutes. Continue?`);
        if (!confirmed) return;
    }
    
    const zip = new JSZip();
    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    
    progressBar.style.display = 'block';
    progressFill.style.width = '0%';
    
    // Create folders in ZIP based on year level and section
    const yearFilter = document.getElementById('filterYear').value;
    const sectionFilter = document.getElementById('filterSection').value;
    
    for (let i = 0; i < cards.length; i++) {
        const card = cards[i];
        const studentId = card.dataset.id;
        const studentName = card.dataset.name;
        const year = card.dataset.year;
        const section = card.dataset.section;
        
        try {
            const canvas = await html2canvas(card, {
                scale: 3,
                backgroundColor: null,
                logging: false
            });
            
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            
            // Organize files in folders by year and section
            const folderPath = `Grade_${year}/Section_${section}`;
            const fileName = `${studentId}_${studentName.replace(/\s+/g, '_')}.png`;
            
            zip.file(`${folderPath}/${fileName}`, blob);
            
            const progress = ((i + 1) / cards.length) * 100;
            progressFill.style.width = progress + '%';
            
            // Small delay to prevent browser freezing
            if (i % 10 === 0) {
                await new Promise(resolve => setTimeout(resolve, 10));
            }
        } catch (error) {
            console.error('Error generating card for student:', studentId, error);
        }
    }
    
    // Generate ZIP file name based on filters
    let zipFileName = 'QR_Codes';
    if (yearFilter) zipFileName += `_Grade${yearFilter}`;
    if (sectionFilter) zipFileName += `_Section${sectionFilter}`;
    zipFileName += `_${new Date().toISOString().split('T')[0]}.zip`;
    
    try {
        const content = await zip.generateAsync({
            type: 'blob',
            compression: 'DEFLATE',
            compressionOptions: { level: 6 }
        });
        
        saveAs(content, zipFileName);
        
        // Show success message
        alert(`Successfully generated ${cards.length} QR codes!`);
    } catch (error) {
        console.error('Error generating ZIP:', error);
        alert('Error creating ZIP file. Please try with fewer cards or contact support.');
    }
    
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
