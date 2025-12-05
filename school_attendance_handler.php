<?php
/**
 * School Attendance Handler - TIME IN / TIME OUT
 * Handles student arrival and dismissal
 */
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

require_once 'config.php';
require_once 'db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Log incoming request for debugging
error_log("Attendance Handler - POST data: " . print_r($_POST, true));

$student_id = trim($_POST['student_id'] ?? '');
$action = trim($_POST['action'] ?? 'time_in'); // 'time_in' or 'time_out'
$send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] == '1';

if (empty($student_id)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'QR code not detected',
        'debug' => 'No student_id in POST data'
    ]);
    exit;
}

try {
    // Find student by student_id - check multiple possible formats
    $stmt = $conn->prepare("
        SELECT s.id, s.student_id, s.first_name, s.last_name, 
               CONCAT(s.first_name, ' ', s.last_name) as name, 
               s.parent_contact 
        FROM students s 
        WHERE s.student_id = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        error_log("Student not found: " . $student_id);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Student not found in database',
            'debug' => 'Scanned ID: ' . $student_id,
            'student_id' => $student_id
        ]);
        exit;
    }
    
    $student = $student_result->fetch_assoc();
    $student_db_id = $student['id']; // Database ID (INT)
    $student_id_value = $student['student_id']; // Student ID (VARCHAR) - used for school_attendance
    $student_name = $student['name'];
    $date = date('Y-m-d');
    $time = date('H:i:s');
    
    error_log("Student found: DB_ID={$student_db_id}, Student_ID={$student_id_value}, Name={$student_name}, Action={$action}");
    
    if ($action === 'time_in') {
        // TIME IN - School Arrival
        
        // Check if already timed in today
        $check = $conn->prepare("SELECT * FROM school_attendance WHERE student_id = ? AND date = ? AND time_in IS NOT NULL");
        $check->bind_param("ss", $student_id_value, $date);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            echo json_encode([
                'status' => 'warning', 
                'message' => 'Already timed in today',
                'time' => date('h:i A', strtotime($existing['time_in'])),
                'student' => $student_name
            ]);
            exit;
        }
        
        // Determine status based on time (Late if after 7:30 AM)
        $status = (strtotime($time) > strtotime('07:30:00')) ? 'Late' : 'On Time';
        
        // Insert TIME IN record
        $insert = $conn->prepare("INSERT INTO school_attendance (student_id, date, time_in, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insert->bind_param("ssss", $student_id_value, $date, $time, $status);
        
        if ($insert->execute()) {
            error_log("TIME IN successful for student: {$student_name}");
            
            // Send SMS if enabled
            $sms_sent = false;
            if ($send_sms && !empty($student['parent_contact'])) {
                try {
                    require_once 'includes/sms_gateway.php';
                    $message = "Good morning! {$student_name} has arrived at school at " . date('h:i A', strtotime($time)) . ". Status: {$status}";
                    $sms_sent = sendSMS($student['parent_contact'], $message);
                } catch (Exception $sms_error) {
                    error_log("SMS error: " . $sms_error->getMessage());
                }
            }
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'TIME IN recorded successfully',
                'time' => date('h:i A', strtotime($time)),
                'student' => $student_name,
                'student_id' => $student_id_value,
                'attendance_status' => $status,
                'sms_sent' => $sms_sent
            ]);
        } else {
            throw new Exception("Failed to insert TIME IN record: " . $insert->error);
        }
        
    } elseif ($action === 'time_out') {
        // TIME OUT - School Dismissal
        
        // Check if timed in today
        $check = $conn->prepare("SELECT * FROM school_attendance WHERE student_id = ? AND date = ? AND time_in IS NOT NULL");
        $check->bind_param("ss", $student_id_value, $date);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'No TIME IN record found for today',
                'student' => $student_name
            ]);
            exit;
        }
        
        $attendance_record = $result->fetch_assoc();
        
        // Check if already timed out
        if (!empty($attendance_record['time_out'])) {
            echo json_encode([
                'status' => 'warning', 
                'message' => 'Already timed out today',
                'time' => date('h:i A', strtotime($attendance_record['time_out'])),
                'student' => $student_name
            ]);
            exit;
        }
        
        // Update with TIME OUT
        $update = $conn->prepare("UPDATE school_attendance SET time_out = ?, updated_at = NOW() WHERE id = ?");
        $update->bind_param("si", $time, $attendance_record['id']);
        
        if ($update->execute()) {
            error_log("TIME OUT successful for student: {$student_name}");
            
            // Send SMS if enabled
            $sms_sent = false;
            if ($send_sms && !empty($student['parent_contact'])) {
                try {
                    require_once 'includes/sms_gateway.php';
                    $message = "Good afternoon! {$student_name} has left school at " . date('h:i A', strtotime($time)) . ". Time in was " . date('h:i A', strtotime($attendance_record['time_in'])) . ".";
                    $sms_sent = sendSMS($student['parent_contact'], $message);
                } catch (Exception $sms_error) {
                    error_log("SMS error: " . $sms_error->getMessage());
                }
            }
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'TIME OUT recorded successfully',
                'time' => date('h:i A', strtotime($time)),
                'student' => $student_name,
                'student_id' => $student_id_value,
                'time_in' => date('h:i A', strtotime($attendance_record['time_in'])),
                'sms_sent' => $sms_sent
            ]);
        } else {
            throw new Exception("Failed to update TIME OUT record: " . $update->error);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("School attendance handler error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error occurred',
        'debug' => $e->getMessage(),
        'student_id' => $student_id ?? 'unknown'
    ]);
}
?>
