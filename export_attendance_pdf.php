<?php
session_start();
require_once 'db_connect.php';
require_once 'vendor/autoload.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'advisor'])) {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$section = $_GET['section'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Determine which table to use
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

$query .= " ORDER BY " . ($table_to_use === 'school_attendance' ? 'sa' : 'a') . ".date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary stats
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

// Create PDF
class AttendancePDF extends FPDF
{
    private $reportTitle;
    private $dateRange;
    
    function __construct($title, $dateRange) {
        parent::__construct();
        $this->reportTitle = $title;
        $this->dateRange = $dateRange;
    }
    
    function Header() {
        // DepEd Logo placeholder (you can add actual logo)
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 56, 168); // DepEd Blue
        $this->Cell(0, 10, 'Smart Classroom System', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 8, 'Department of Education', 0, 1, 'C');
        
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, $this->reportTitle, 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, $this->dateRange, 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Generated on ' . date('F d, Y h:i A'), 0, 0, 'C');
    }
}

$pdf = new AttendancePDF('Attendance Report', 'Period: ' . date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to)));
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Summary Section
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(0, 56, 168);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'Summary Statistics', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Ln(2);

$pdf->Cell(45, 8, 'Total Records:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 8, $summary['total'], 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(45, 8, 'On Time:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(40, 167, 69);
$pdf->Cell(40, 8, $summary['on_time'], 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(45, 8, 'Late:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(255, 193, 7);
$pdf->Cell(40, 8, $summary['late'], 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(45, 8, 'Absent:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(220, 53, 69);
$pdf->Cell(40, 8, $summary['absent'], 0, 1);
$pdf->SetTextColor(0, 0, 0);

$pdf->Ln(8);

// Attendance Records Table
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(0, 56, 168);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'Attendance Records', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

// Table Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(25, 8, 'Date', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Student ID', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Name', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Section', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Time In', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Time Out', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Status', 1, 1, 'C', true);

// Table Data
$pdf->SetFont('Arial', '', 8);
$fill = false;
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(25, 7, date('M d, Y', strtotime($row['date'])), 1, 0, 'C', $fill);
    $pdf->Cell(30, 7, $row['student_id'], 1, 0, 'C', $fill);
    $pdf->Cell(50, 7, substr($row['name'], 0, 30), 1, 0, 'L', $fill);
    $pdf->Cell(25, 7, $row['section'] ?? 'N/A', 1, 0, 'C', $fill);
    $pdf->Cell(25, 7, $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-', 1, 0, 'C', $fill);
    $pdf->Cell(25, 7, !empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '-', 1, 0, 'C', $fill);
    $pdf->Cell(20, 7, $row['status'], 1, 1, 'C', $fill);
    $fill = !$fill;
}

// Output PDF
$filename = 'Attendance_Report_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
?>
