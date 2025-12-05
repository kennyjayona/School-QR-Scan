<?php
session_start();
require_once 'db_connect.php';
require_once 'vendor/autoload.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'advisor', 'teacher'])) {
    header('Location: login.php');
    exit;
}

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

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary stats
$summary_query = "SELECT 
    COUNT(*) as total_grades,
    AVG(grade) as avg_grade,
    MIN(grade) as min_grade,
    MAX(grade) as max_grade,
    COUNT(DISTINCT student_id) as total_students
    FROM grades WHERE 1=1";

if (!empty($section)) {
    $summary_query .= " AND student_id IN (SELECT student_id FROM students WHERE section = ?)";
}

$summary_stmt = $conn->prepare($summary_query);
if (!empty($section)) {
    $summary_stmt->bind_param("s", $section);
}
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Create PDF
class GradesPDF extends FPDF
{
    private $reportTitle;
    private $filters;
    
    function __construct($title, $filters) {
        parent::__construct();
        $this->reportTitle = $title;
        $this->filters = $filters;
    }
    
    function Header() {
        // DepEd Header
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
        $this->Cell(0, 6, $this->filters, 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Generated on ' . date('F d, Y h:i A'), 0, 0, 'C');
    }
}

$filterText = 'All Records';
if (!empty($section)) $filterText = 'Section: ' . $section;
if (!empty($term)) $filterText .= ' | Term: ' . $term;
if (!empty($subject)) $filterText .= ' | Subject: ' . $subject;

$pdf = new GradesPDF('Grade Report', $filterText);
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

$pdf->Cell(50, 8, 'Total Students:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 8, $summary['total_students'], 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 8, 'Total Grade Entries:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 8, $summary['total_grades'], 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 8, 'Average Grade:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0, 56, 168);
$pdf->Cell(40, 8, number_format($summary['avg_grade'], 2), 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 8, 'Highest Grade:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(40, 167, 69);
$pdf->Cell(40, 8, number_format($summary['max_grade'], 2), 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 8, 'Lowest Grade:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(220, 53, 69);
$pdf->Cell(40, 8, number_format($summary['min_grade'], 2), 0, 1);
$pdf->SetTextColor(0, 0, 0);

$pdf->Ln(8);

// Grade Records Table
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(0, 56, 168);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'Grade Records', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

// Table Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(30, 8, 'Student ID', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Name', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Section', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Subject', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Grade', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Term', 1, 1, 'C', true);

// Table Data
$pdf->SetFont('Arial', '', 8);
$fill = false;
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(30, 7, $row['student_id'], 1, 0, 'C', $fill);
    $pdf->Cell(50, 7, substr($row['name'], 0, 30), 1, 0, 'L', $fill);
    $pdf->Cell(25, 7, $row['section'] ?? 'N/A', 1, 0, 'C', $fill);
    $pdf->Cell(40, 7, substr($row['subject'], 0, 25), 1, 0, 'L', $fill);
    
    // Color code grades
    $grade = $row['grade'];
    if ($grade >= 90) {
        $pdf->SetTextColor(40, 167, 69); // Green
    } elseif ($grade >= 75) {
        $pdf->SetTextColor(0, 56, 168); // Blue
    } else {
        $pdf->SetTextColor(220, 53, 69); // Red
    }
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 7, number_format($grade, 2), 1, 0, 'C', $fill);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
    
    $pdf->Cell(25, 7, $row['term'], 1, 1, 'C', $fill);
    $fill = !$fill;
}

// Add grading scale reference
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, 'Grading Scale Reference:', 0, 1);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(50, 6, '90-100: Outstanding', 0, 1);
$pdf->Cell(50, 6, '85-89: Very Satisfactory', 0, 1);
$pdf->Cell(50, 6, '80-84: Satisfactory', 0, 1);
$pdf->Cell(50, 6, '75-79: Fairly Satisfactory', 0, 1);
$pdf->Cell(50, 6, 'Below 75: Did Not Meet Expectations', 0, 1);

// Output PDF
$filename = 'Grade_Report_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
?>
