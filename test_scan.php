<?php
/**
 * Quick Scan Test
 * Simulates a QR scan to test the attendance handler
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>QR Scan Test</h2>";
echo "<hr>";

// Simulate POST data - using the actual student ID from database
$_POST['student_id'] = 'mark'; // This matches the student_id from test_connection.php
$_POST['action'] = 'time_in'; // or 'time_out'
$_POST['send_sms'] = '0'; // Disable SMS for testing

echo "<h3>Testing with:</h3>";
echo "Student ID: " . $_POST['student_id'] . "<br>";
echo "Action: " . $_POST['action'] . "<br>";
echo "<hr>";

// Include the handler
ob_start();
include 'school_attendance_handler.php';
$response = ob_get_clean();

echo "<h3>Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Decode and display nicely
$data = json_decode($response, true);
if ($data) {
    echo "<h3>Parsed Response:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    foreach ($data as $key => $value) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($data['status'] === 'success') {
        echo "<h3 style='color: green;'>✅ SUCCESS! Attendance recorded.</h3>";
    } elseif ($data['status'] === 'warning') {
        echo "<h3 style='color: orange;'>⚠️ WARNING: " . $data['message'] . "</h3>";
    } else {
        echo "<h3 style='color: red;'>❌ ERROR: " . $data['message'] . "</h3>";
    }
}

echo "<hr>";
echo "<p><a href='test_connection.php'>← Back to Connection Test</a></p>";
echo "<p><a href='qr_scan_time_in.html'>→ Go to TIME IN Scanner</a></p>";
?>
