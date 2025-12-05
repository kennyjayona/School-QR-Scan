<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/permissions.php';

checkPageAccess(['admin', 'advisor', 'teacher']);

$classroom_id = $_GET['classroom_id'] ?? null;
$date = $_GET['date'] ?? 'today';

if (!$classroom_id) {
    echo json_encode([]);
    exit;
}

$date_condition = $date === 'today' ? 'DATE(a.time_in) = CURDATE()' : "DATE(a.time_in) = '$date'";

$sql = "SELECT 
            s.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as name,
            s.photo,
            a.time_in,
            a.time_out,
            CASE 
                WHEN a.time_out IS NOT NULL THEN 'time-out'
                ELSE 'time-in'
            END as type,
            CASE 
                WHEN a.time_out IS NOT NULL THEN TIME_FORMAT(a.time_out, '%h:%i %p')
                ELSE TIME_FORMAT(a.time_in, '%h:%i %p')
            END as time
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE $date_condition
        AND s.id IN (
            SELECT student_id FROM subject_students ss
            JOIN classroom_subjects cs ON ss.subject_id = cs.subject_id
            WHERE cs.classroom_id = ?
        )
        ORDER BY a.time_in DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$result = $stmt->get_result();

$attendance = [];
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}

header('Content-Type: application/json');
echo json_encode($attendance);
?>
