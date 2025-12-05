<?php
/**
 * TIME OUT Test
 * Tests the TIME OUT functionality after TIME IN
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TIME OUT Test</h2>";
echo "<hr>";

// Simulate POST data for TIME OUT
$_POST['student_id'] = 'mark'; // Same student who timed in
$_POST['action'] = 'time_out';
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
        echo "<h3 style='color: green;'>✅ SUCCESS! TIME OUT recorded.</h3>";
        echo "<p><strong>Student:</strong> " . htmlspecialchars($data['student']) . "</p>";
        echo "<p><strong>Time IN:</strong> " . htmlspecialchars($data['time_in']) . "</p>";
        echo "<p><strong>Time OUT:</strong> " . htmlspecialchars($data['time']) . "</p>";
    } elseif ($data['status'] === 'warning') {
        echo "<h3 style='color: orange;'>⚠️ WARNING: " . htmlspecialchars($data['message']) . "</h3>";
    } elseif ($data['status'] === 'error') {
        echo "<h3 style='color: red;'>❌ ERROR: " . htmlspecialchars($data['message']) . "</h3>";
        if ($data['message'] === 'No TIME IN record found for today') {
            echo "<p style='color: red;'><strong>Note:</strong> You need to TIME IN first before you can TIME OUT!</p>";
            echo "<p><a href='test_scan.php' style='color: blue;'>→ Click here to TIME IN first</a></p>";
        }
    }
}

echo "<hr>";
echo "<h3>Database Check:</h3>";

// Check database for today's record
require_once 'config.php';
require_once 'db_connect.php';

$check_sql = "SELECT sa.*, s.first_name, s.last_name 
              FROM school_attendance sa 
              JOIN students s ON sa.student_id = s.student_id 
              WHERE sa.date = CURDATE() AND s.student_id = 'mark'";
$result = $conn->query($check_sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Student</th><th>Date</th><th>Time IN</th><th>Time OUT</th><th>Status</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
        echo "<td style='color: green; font-weight: bold;'>" . htmlspecialchars($row['time_in'] ?? 'N/A') . "</td>";
        echo "<td style='color: red; font-weight: bold;'>" . htmlspecialchars($row['time_out'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $record = $result->fetch_assoc();
    $result->data_seek(0);
    $record = $result->fetch_assoc();
    
    if (!empty($record['time_in']) && !empty($record['time_out'])) {
        echo "<h3 style='color: green;'>✅ Complete Record: Both TIME IN and TIME OUT recorded!</h3>";
    } elseif (!empty($record['time_in']) && empty($record['time_out'])) {
        echo "<h3 style='color: orange;'>⚠️ Partial Record: TIME IN recorded, waiting for TIME OUT</h3>";
    }
} else {
    echo "<p style='color: red;'>❌ No attendance record found for today.</p>";
    echo "<p><a href='test_scan.php' style='color: blue;'>→ Click here to TIME IN first</a></p>";
}

echo "<hr>";
echo "<h3>Navigation:</h3>";
echo "<p><a href='test_connection.php'>← Back to Connection Test</a></p>";
echo "<p><a href='test_scan.php'>← TIME IN Test</a></p>";
echo "<p><a href='qr_scan_time_out.html'>→ Go to TIME OUT Scanner</a></p>";

echo "<hr>";
echo "<h3>Test Sequence:</h3>";
echo "<ol>";
echo "<li><a href='test_scan.php'>Step 1: TIME IN</a> - Record school arrival</li>";
echo "<li><strong>Step 2: TIME OUT (This page)</strong> - Record school dismissal</li>";
echo "<li><a href='qr_scan_time_out.html'>Step 3: Real Scanner</a> - Test with QR code</li>";
echo "</ol>";
?>
