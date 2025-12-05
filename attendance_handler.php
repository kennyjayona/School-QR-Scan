<?php
require_once 'db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$student_id = trim($_POST['student_id'] ?? '');

if (empty($student_id)) {
    echo "❌ QR code not detected!";
    exit;
}

try {
    // Find student by username (QR contains username)
    $stmt = $conn->prepare("SELECT s.id, u.name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE u.username = ? LIMIT 1");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Student not found']);
        exit;
    }
    
    $student = $student_result->fetch_assoc();
    $student_db_id = $student['id'];
    $date = date('Y-m-d');
    $time = date('H:i:s');
    
    // Check for duplicate attendance
    $check = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? AND date = ?");
    $check->bind_param("is", $student_db_id, $date);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'warning', 'message' => 'Already logged today', 'time' => $time]);
    } else {
        // Determine status based on time (Late if after 8:00 AM)
        $status = (strtotime($time) > strtotime('08:00:00')) ? 'late' : 'present';
        
        $insert = $conn->prepare("INSERT INTO attendance (student_id, date, time_in, status) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isss", $student_db_id, $date, $time, $status);
        
        if ($insert->execute()) {
            echo json_encode(['status' => 'ok', 'message' => 'Attendance logged', 'time' => $time, 'student' => $student['name']]);
        } else {
            throw new Exception("Failed to insert attendance record");
        }
    }
} catch (Exception $e) {
    error_log("Attendance handler error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

function send_sms($contact_number, $student_name, $time) {
    // SMS API configuration (Semaphore example)
    $apikey = 'YOUR_SEMAPHORE_API_KEY'; // Replace with actual API key
    
    if ($apikey === 'YOUR_SEMAPHORE_API_KEY') {
        return; // Skip SMS if not configured
    }
    
    $message = "Your child " . $student_name . " has timed in at " . $time . ".";
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'apikey' => $apikey,
            'number' => $contact_number,
            'message' => $message
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);
        
        error_log("SMS sent to " . $contact_number . ": " . $output);
    } catch (Exception $e) {
        error_log("SMS error: " . $e->getMessage());
    }
}
?>
