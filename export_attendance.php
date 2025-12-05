<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/permissions.php';

checkPageAccess(['admin', 'advisor', 'teacher']);

$classroom_id = $_GET['classroom_id'] ?? null;
$date = $_GET['date'] ?? 'today';

if (!$classroom_id) {
    die('Classroom ID required');
}

// Get classroom info
$stmt = $conn->prepare("SELECT name, section, grade_level FROM classrooms WHERE id = ?");
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$classroom = $stmt->get_result()->fetch_assoc();

$date_condition = $date === 'today' ? 'DATE(a.time_in) = CURDATE()' : "DATE(a.time_in) = '$date'";
$filename = "Attendance_" . str_replace(' ', '_', $classroom['name']) . "_" . date('Y-m-d') . ".csv";

// Get attendance records
$sql = "SELECT 
            s.student_id,
            s.first_name,
            s.last_name,
            s.section,
            s.year_level,
            DATE_FORMAT(a.time_in, '%h:%i %p') as time_in,
            DATE_FORMAT(a.time_out, '%h:%i %p') as time_out,
            CASE 
                WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 'Complete'
                WHEN a.time_in IS NOT NULL THEN 'Time In Only'
                ELSE 'Absent'
            END as status
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id AND $date_condition
        WHERE s.id IN (
            SELECT student_id FROM subject_students ss
            JOIN classroom_subjects cs ON ss.subject_id = cs.subject_id
            WHERE cs.classroom_id = ?
        )
        ORDER BY s.last_name, s.first_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Write header row
fputcsv($output, [
    'Classroom: ' . $classroom['name'],
    'Grade: ' . $classroom['grade_level'],
    'Section: ' . $classroom['section'],
    'Date: ' . date('F d, Y')
]);
fputcsv($output, []); // Empty row

// Write column headers
fputcsv($output, [
    'Student ID',
    'Last Name',
    'First Name',
    'Year Level',
    'Section',
    'Time In',
    'Time Out',
    'Status'
]);

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['student_id'],
        $row['last_name'],
        $row['first_name'],
        $row['year_level'],
        $row['section'],
        $row['time_in'] ?? 'N/A',
        $row['time_out'] ?? 'N/A',
        $row['status']
    ]);
}

fclose($output);
exit;
?>
