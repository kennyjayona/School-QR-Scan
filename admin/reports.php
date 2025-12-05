<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'advisor'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Attendance Reports';
$current_page = 'reports';
$base_url = '../';

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$section = $_GET['section'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Try school_attendance table first, fallback to attendance
$table_to_use = 'school_attendance';
try {
    $conn->query("SELECT 1 FROM school_attendance LIMIT 1");
} catch (Exception $e) {
    $table_to_use = 'attendance';
}

// Build query
if ($table_to_use === 'school_attendance') {
    $query = "SELECT sa.*, s.student_id, u.name, s.section, s.year_level 
              FROM school_attendance sa
              JOIN students s ON sa.student_id = s.id 
              JOIN users u ON s.user_id = u.id
              WHERE sa.date BETWEEN ? AND ?";
} else {
    $query = "SELECT a.*, s.student_id, u.name, s.section, s.year_level 
              FROM attendance a 
              JOIN students s ON a.student_id = s.id
              JOIN users u ON s.user_id = u.id
              WHERE a.date BETWEEN ? AND ?";
}

$params = [$date_from, $date_to];
$types = "ss";

if (!empty($section)) {
    $query .= " AND s.section = ?";
    $params[] = $section;
    $types .= "s";
}

if (!empty($status_filter)) {
    $query .= " AND " . ($table_to_use === 'school_attendance' ? 'sa' : 'a') . ".status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY " . ($table_to_use === 'school_attendance' ? 'sa' : 'a') . ".date DESC, " . ($table_to_use === 'school_attendance' ? 'sa' : 'a') . ".time_in DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    $result = $conn->query("SELECT * FROM students LIMIT 0");
}

// Get sections for filter
try {
    $sections = $conn->query("SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section != '' ORDER BY section");
} catch (Exception $e) {
    $sections = $conn->query("SELECT * FROM students LIMIT 0");
}

// Calculate summary stats
$total_records = 0;
$on_time_count = 0;
$late_count = 0;
$absent_count = 0;

try {
    $summary_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'On Time' OR status = 'Present' THEN 1 ELSE 0 END) as on_time,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
        FROM $table_to_use " . ($table_to_use === 'school_attendance' ? 'sa' : 'a') . "
        WHERE " . ($table_to_use === 'school_attendance' ? 'sa' : 'a') . ".date BETWEEN ? AND ?";

    $summary_stmt = $conn->prepare($summary_query);
    $summary_stmt->bind_param("ss", $date_from, $date_to);
    $summary_stmt->execute();
    $summary = $summary_stmt->get_result()->fetch_assoc();

    $total_records = $summary['total'] ?? 0;
    $on_time_count = $summary['on_time'] ?? 0;
    $late_count = $summary['late'] ?? 0;
    $absent_count = $summary['absent'] ?? 0;
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
                <i class="fas fa-clipboard-list"></i>
            </div>
        </div>
        <div class="metric-body">
            <h3><?php echo $total_records; ?></h3>
            <div class="metric-label">Total Records</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-calendar"></i> <?php echo date('M d', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
        </div>
    </div>

    <div class="metric-card success">
        <div class="metric-header">
            <div class="metric-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="metric-body">
            <h3><?php echo $on_time_count; ?></h3>
            <div class="metric-label">On Time</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-percentage"></i> <?php echo $total_records > 0 ? round(($on_time_count / $total_records) * 100, 1) : 0; ?>% of total
        </div>
    </div>

    <div class="metric-card warning">
        <div class="metric-header">
            <div class="metric-icon yellow">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="metric-body">
            <h3><?php echo $late_count; ?></h3>
            <div class="metric-label">Late</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-percentage"></i> <?php echo $total_records > 0 ? round(($late_count / $total_records) * 100, 1) : 0; ?>% of total
        </div>
    </div>

    <div class="metric-card danger">
        <div class="metric-header">
            <div class="metric-icon red">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
        <div class="metric-body">
            <h3><?php echo $absent_count; ?></h3>
            <div class="metric-label">Absent</div>
        </div>
        <div class="metric-footer">
            <i class="fas fa-percentage"></i> <?php echo $total_records > 0 ? round(($absent_count / $total_records) * 100, 1) : 0; ?>% of total
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
            <div class="col-md-3 mb-3">
                <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" style="padding: 10px; border: 2px solid var(--border-color); border-radius: 8px;">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" style="padding: 10px; border: 2px solid var(--border-color); border-radius: 8px;">
            </div>
            <div class="col-md-3 mb-3">
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
            <div class="col-md-3 mb-3">
                <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">Status</label>
                <select name="status" class="form-control" style="padding: 10px; border: 2px solid var(--border-color); border-radius: 8px;">
                    <option value="">All Status</option>
                    <option value="On Time" <?php echo $status_filter === 'On Time' ? 'selected' : ''; ?>>On Time</option>
                    <option value="Present" <?php echo $status_filter === 'Present' ? 'selected' : ''; ?>>Present</option>
                    <option value="Late" <?php echo $status_filter === 'Late' ? 'selected' : ''; ?>>Late</option>
                    <option value="Absent" <?php echo $status_filter === 'Absent' ? 'selected' : ''; ?>>Absent</option>
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
            <a href="analytics.php?export=excel&type=attendance" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
        </div>
    </form>
</div>

<!-- Report Table -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-table"></i>
            Attendance Records
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
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Date</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Student ID</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Name</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Section</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Time In</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Time Out</th>
                    <th style="padding: 12px; text-align: left; font-size: 13px; color: var(--text-secondary);">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px; font-size: 14px;"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td style="padding: 12px; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($row['section'] ?? 'N/A'); ?></td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-'; ?></td>
                            <td style="padding: 12px; font-size: 14px;"><?php echo !empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '-'; ?></td>
                            <td style="padding: 12px;">
                                <?php
                                $status = $row['status'];
                                $status_color = 'var(--text-secondary)';
                                $status_bg = 'rgba(128, 128, 128, 0.1)';

                                if ($status === 'On Time' || $status === 'Present') {
                                    $status_color = 'var(--success)';
                                    $status_bg = 'rgba(16, 185, 129, 0.1)';
                                } elseif ($status === 'Late') {
                                    $status_color = 'var(--warning)';
                                    $status_bg = 'rgba(245, 158, 11, 0.1)';
                                } elseif ($status === 'Absent') {
                                    $status_color = 'var(--danger)';
                                    $status_bg = 'rgba(239, 68, 68, 0.1)';
                                }
                                ?>
                                <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>;">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p style="margin: 0;">No attendance records found for the selected filters.</p>
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