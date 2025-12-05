<?php
session_start();
require_once 'db_connect.php';
require_once 'vendor/autoload.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

// Get student record
$username = $_SESSION['username'];
$student_query = $conn->prepare("SELECT s.*, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.qr_code = ? LIMIT 1");
$student_query->bind_param("s", $username);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    die('Student record not found');
}

$student_id = $student['student_id'];
$student_name = $student['name'];

// Get grades
$grades_query = $conn->prepare("SELECT * FROM grades WHERE student_id = ? ORDER BY term, subject");
$grades_query->bind_param("s", $student_id);
$grades_query->execute();
$result = $grades_query->get_result();

// Calculate summary
$summary_query = $conn->prepare("SELECT 
    COUNT(*) as total_grades,
    AVG(grade) as avg_grade,
    MIN(grade) as min_grade,
    MAX(grade) as max_grade
    FROM grades WHERE student_id = ?");
$summary_query->bind_param("s", $student_id);
$summary_query->execute();
$summary = $summary_query->get_result()->fetch_assoc();

// Create PDF
class StudentGradesPDF extends FPDF
{
    private $studentName;
    private $studentId;
    
    function __construct($name, $id) {
        parent::__construct();
        $this->studentName = $name;
        $this->studentId = $id;
    }
    
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 56, 168);
        $this->Cell(0, 10, 'Smart Classroom System', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 8, 'Department of Education', 0, 1, 'C');
        
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'Student Grade Report', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Student: ' . $this->studentName . ' (' . $this->studentId . ')', 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Generated on ' . date('F d, Y h:i A'), 0, 0, 'C');
    }
}

$pdf = new StudentGradesPDF($student_name, $student_id);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Student Information
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(0, 56, 168);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'Student Information', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Ln(2);

$pdf->Cell(50, 8, 'Student ID:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, $student_id, 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 8, 'Name:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, $student_name, 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 8, 'Section:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, $student['section'] ?? 'N/A', 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 8, 'Year Level:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, $student['year_level'] ?? 'N/A', 0, 1);

$pdf->Ln(8);

// Summary Section
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(0, 56, 168);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'Grade Summary', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Ln(2);

$pdf->Cell(50, 8, 'Total Subjects:', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 8, $summary['total_grades'], 0, 1);
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(50, 8, 'Overall Average:', 0, 0);
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
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(70, 8, 'Subject', 1, 0, 'L', true);
$pdf->Cell(30, 8, 'Grade', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Term', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Remarks', 1, 1, 'L', true);

// Table Data
$pdf->SetFont('Arial', '', 9);
$fill = false;
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(70, 7, substr($row['subject'], 0, 35), 1, 0, 'L', $fill);
    
    // Color code grades
    $grade = $row['grade'];
    if ($grade >= 90) {
        $pdf->SetTextColor(40, 167, 69); // Green
    } elseif ($grade >= 75) {
        $pdf->SetTextColor(0, 56, 168); // Blue
    } else {
        $pdf->SetTextColor(220, 53, 69); // Red
    }
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(30, 7, number_format($grade, 2), 1, 0, 'C', $fill);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 9);
    
    $pdf->Cell(40, 7, $row['term'], 1, 0, 'C', $fill);
    $pdf->Cell(50, 7, substr($row['remarks'] ?? '', 0, 25), 1, 1, 'L', $fill);
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
$filename = 'Grade_Report_' . $student_id . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
?>
