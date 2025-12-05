<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'advisor'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Grade Reports';
$current_page = 'grades_report';
$base_url = '../';

// Get filter parameters
$section = $_GET['section'] ?? '';
$term = $_GET['term'] ?? '';
$subject = $_GET['subject'] ?? '';

// Build query
$query = "SELECT g.*, s.student_id, u.name, s.section, s.year_level 
          FROM grades g
          JOIN students s ON g.student_id = s.student_id
          JOIN users u ON s.user_id = u.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($section)) {
    $query .= " AND s.section = ?";
    $params[] = $section;
    $types .= "s";
}

if (!empty($term)) {
    $query .= " AND g.term = ?";
    $params[] = $term;
    $types .= "s";
}

if (!empty($subject)) {
    $query .= " AND g.subject = ?";
    $params[] = $subject;
    $types .= "s";
}

$query .= " ORDER BY s.section, u.name, g.subject";

try {
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    $result = $conn->query("SELECT * FROM grades LIMIT 0");
}

// Get sections for filter
try {
    $sections = $conn->query("SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section != '' ORDER BY section");
} catch (Exception $e) {
    $sections = $conn->query("SELECT * FROM students LIMIT 0");
}

// Get subjects for filter
try {
    $subjects = $conn->query("SELECT DISTINCT subject FROM grades ORDER BY subject");
} catch (Exception $e) {
    $subjects = $conn->query("SELECT * FROM grades LIMIT 0");
}

// Calculate summary stats
$total_grades = 0;
$avg_grade = 0;
$min_grade = 0;
$max_grade = 0;
$total_students = 0;

try {
    $summary_query = "SELECT 
        COUNT(*) as total_grades,
        AVG(grade) as avg_grade,
        MIN(grade) as min_grade,
        MAX(grade) as max_grade,
        COUNT(DISTINCT student_id) as total_students
        FROM grades g
        JOIN students s ON g.student_id = s.student_id
        WHERE 1=1";
    
    $summary_params = [];
    $summary_types = "";
    
    if (!empty($section)) {
        $summary_query .= " AND s.section = ?";
        $summary_params[] = $section;
        $summary_types .= "s";
    }
    
    if (!empty($term)) {
        $summary_query .= " AND g.term = ?";
        $summary_params[] = $term;
        $summary_types .= "s";
    }
    
    if (!empty($subject)) {
        $summary_query .= " AND g.subject = ?";
        $summary_params[] = $subject;
        $summary_types .= "s";
    }

    $summary_stmt = $conn->prepare($summary_query);
    if (!empty($summary_params)) {
        $summary_stmt->bind_param($summary_types, ...$summary_params);
    }
    $summary_stmt->execute();
    $summary = $summary_stmt->get_result()->fetch_assoc();

    $total_grades = $summary['total_grades'] ?? 0;
    $avg_grade = $summary['avg_grade'] ?? 0;
    $min_grade = $summary['min_grade'] ?? 0;
    $max_grade = $summary['max_grade'] ?? 0;
    $total_students = $summary['total_students'] ?? 0;
} catch (Exception $e) {
    // Keep defaults
}

// Include appropriate header based on role
if ($_SESSION['role'] === 'advisor') {
    include '../includes/advisor_header.php';
} else {
    include '../includes/admin_header.php';
}
?>

<!-- Summary Cards -->
<div class="metrics-row">
    <div class="metric-card info">
        <div class="metric-header">
            <div class="metric-icon blue">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="metric-body">
            <h3><?php echo $total_students; ?></h3>
            <div class="metric-label">Total Students</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-graduation-cap"></i> With grades recorded
        </div>
    </div>

    <div class="metric-card success">
        <div class="metric-header">
            <div class="metric-icon green">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="metric-body">
            <h3><?php echo number_format($avg_grade, 2); ?></h3>
            <div class="metric-label">Average Grade</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-calculator"></i> Overall performance
        </div>
    </div>

    <div class="metric-card warning">
        <div class="metric-header">
            <div class="metric-icon yellow">
                <i class="fas fa-trophy"></i>
            </div>
        </div>
        <div class="metric-body">
            <h3><?php echo number_format($max_grade, 2); ?></h3>
            <div class="metric-label">Highest Grade</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-star"></i> Top performance
        </div>
    </div>

    <div class="metric-card danger">
        <div class="metric-header">
            <div class="metric-icon red">
                <i class="fas fa-chart-bar"></i>
            </div>
        </div>
        <div class="metric-body">
            <h3><?php echo $total_grades; ?></h3>
            <div class="metric-label">Total Entries</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-clipboard-list"></i> Grade records
        </div>
    </div>
</div>

<!-- Filters -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-filter"></i>
            Filter Reports
        </div>
    </div>
    <form method="GET">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Section</label>
                <select name="section" class="form-control" style="padding: 10px; border: 2px solid var(--border-color); border-radius: 8px;">
                    <option value="">All Sections</option>
                    <?php while ($sec = $sections->fetch_assoc()): ?>
                        <option value="<?php echo $sec['section']; ?>" <?php echo $section === $sec['section'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sec['section']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Term</label>
                <select name="term" class="form-control" style="padding: 10px; border: 2px solid var(--border-color); border-radius: 8px;">
                    <option value="">All Terms</option>
                    <option value="1st Quarter" <?php echo $term === '1st Quarter' ? 'selected' : ''; ?>>1st Quarter</option>
                    <option value="2nd Quarter" <?php echo $term === '2nd Quarter' ? 'selected' : ''; ?>>2nd Quarter</option>
                    <option value="3rd Quarter" <?php echo $term === '3rd Quarter' ? 'selected' : ''; ?>>3rd Quarter</option>
                    <option value="4th Quarter" <?php echo $term === '4th Quarter' ? 'selected' : ''; ?>>4th Quarter</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Subject</label>
                <select name="subject" class="form-control" style="padding: 10px; border: 2px solid var(--border-color); border-radius: 8px;">
                    <option value="">All Subjects</option>
                    <?php while ($subj = $subjects->fetch_assoc()): ?>
                        <option value="<?php echo $subj['subject']; ?>" <?php echo $subject === $subj['subject'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subj['subject']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Apply Filters
            </button>
            <button type="button" onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Report
            </button>
            <a href="../export_grades_pdf.php?section=<?php echo urlencode($section); ?>&term=<?php echo urlencode($term); ?>&subject=<?php echo urlencode($subject); ?>" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
        </div>
    </form>
</div>

<!-- Report Table -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-table"></i>
            Grade Records
        </div>
        <div class="card-actions">
            <span style="color: var(--text-secondary); font-size: 14px;">
                Showing <?php echo $result->num_rows; ?> records
            </span>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Student ID</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Name</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Section</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Subject</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Grade</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Term</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['section'] ?? 'N/A'); ?></td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td style="padding: 12px;">
                                <?php
                                $grade = $row['grade'];
                                $grade_color = 'var(--text-secondary)';
                                $grade_bg = 'rgba(128, 128, 128, 0.1)';

                                if ($grade >= 90) {
                                    $grade_color = 'var(--success)';
                                    $grade_bg = 'rgba(16, 185, 129, 0.1)';
                                } elseif ($grade >= 75) {
                                    $grade_color = 'var(--primary-blue)';
                                    $grade_bg = 'rgba(0, 56, 168, 0.1)';
                                } else {
                                    $grade_color = 'var(--danger)';
                                    $grade_bg = 'rgba(239, 68, 68, 0.1)';
                                }
                                ?>
                                <span style="padding: 4px 12px; border-radius: 6px; font-size: 14px; font-weight: 700; background: <?php echo $grade_bg; ?>; color: <?php echo $grade_color; ?>;">
                                    <?php echo number_format($grade, 2); ?>
                                </span>
                            </td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['term']); ?></td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['remarks'] ?? '-'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p style="margin: 0;">No grade records found for the selected filters.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    @media print {
        .sidebar,
        .header-actions,
        .btn,
        .card-actions {
            display: none !important;
        }

        .main-content {
            margin-left: 0 !important;
            padding: 20px !important;
        }

        .content-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>

<?php include '../includes/admin_footer.php'; ?>
