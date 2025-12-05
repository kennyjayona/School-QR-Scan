<?php
/**
 * Database Connection Test
 * Use this to verify database setup
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";
echo "<hr>";

// Test 1: Check if config.php exists
echo "<h3>1. Config File Check</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    require_once 'config.php';
} else {
    echo "❌ config.php NOT found<br>";
    die("Please create config.php file");
}

// Test 2: Check database constants
echo "<h3>2. Database Constants</h3>";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : '❌ NOT DEFINED') . "<br>";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : '❌ NOT DEFINED') . "<br>";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : '❌ NOT DEFINED') . "<br>";

// Test 3: Connect to database
echo "<h3>3. Database Connection</h3>";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo "❌ Connection FAILED: " . $conn->connect_error . "<br>";
    die();
} else {
    echo "✅ Connection SUCCESSFUL<br>";
    echo "Server: " . $conn->host_info . "<br>";
}

// Test 4: Check if tables exist
echo "<h3>4. Required Tables Check</h3>";

$required_tables = ['students', 'school_attendance', 'users'];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
        
        // Count records
        $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($count_result) {
            $count = $count_result->fetch_assoc()['count'];
            echo "&nbsp;&nbsp;&nbsp;→ Records: $count<br>";
        }
    } else {
        echo "❌ Table '$table' NOT FOUND<br>";
    }
}

// Test 5: Check school_attendance table structure
echo "<h3>5. school_attendance Table Structure</h3>";
$result = $conn->query("DESCRIBE school_attendance");
if ($result) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Cannot describe table: " . $conn->error . "<br>";
    echo "<br><strong>Creating school_attendance table...</strong><br>";
    
    $create_sql = "CREATE TABLE IF NOT EXISTS school_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        date DATE NOT NULL,
        time_in TIME NULL,
        time_out TIME NULL,
        status ENUM('On Time', 'Late', 'Absent') DEFAULT 'On Time',
        remarks TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        INDEX idx_student_id (student_id),
        INDEX idx_date (date),
        UNIQUE KEY unique_student_date (student_id, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_sql)) {
        echo "✅ Table created successfully!<br>";
    } else {
        echo "❌ Error creating table: " . $conn->error . "<br>";
    }
}

// Test 6: Check students table
echo "<h3>6. Students Table Check</h3>";
$students = $conn->query("SELECT COUNT(*) as count FROM students");
if ($students) {
    $count = $students->fetch_assoc()['count'];
    echo "✅ Students table accessible<br>";
    echo "&nbsp;&nbsp;&nbsp;→ Total students: $count<br>";
    
    if ($count > 0) {
        echo "<br><strong>Sample Students:</strong><br>";
        $sample = $conn->query("SELECT id, student_id, name FROM students LIMIT 5");
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Student ID</th><th>Name</th></tr>";
        while ($row = $sample->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['student_id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No students in database. Add students first!<br>";
    }
} else {
    echo "❌ Cannot access students table: " . $conn->error . "<br>";
}

// Test 7: Test attendance handler
echo "<h3>7. Attendance Handler Test</h3>";
if (file_exists('school_attendance_handler.php')) {
    echo "✅ school_attendance_handler.php exists<br>";
} else {
    echo "❌ school_attendance_handler.php NOT FOUND<br>";
}

// Test 8: Check today's attendance
echo "<h3>8. Today's Attendance</h3>";
$today = date('Y-m-d');
$today_attendance = $conn->query("SELECT COUNT(*) as count FROM school_attendance WHERE date = '$today'");
if ($today_attendance) {
    $count = $today_attendance->fetch_assoc()['count'];
    echo "✅ Today's records: $count<br>";
    
    if ($count > 0) {
        echo "<br><strong>Today's Attendance:</strong><br>";
        $records = $conn->query("
            SELECT sa.*, s.name, s.student_id 
            FROM school_attendance sa 
            JOIN students s ON sa.student_id = s.student_id 
            WHERE sa.date = '$today' 
            ORDER BY sa.time_in DESC 
            LIMIT 10
        ");
        
        if ($records && $records->num_rows > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Student</th><th>TIME IN</th><th>TIME OUT</th><th>Status</th></tr>";
            while ($row = $records->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['name'] . " (" . $row['student_id'] . ")</td>";
                echo "<td>" . ($row['time_in'] ?? '-') . "</td>";
                echo "<td>" . ($row['time_out'] ?? '-') . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

echo "<hr>";
echo "<h3>✅ Test Complete</h3>";
echo "<p><strong>If all checks passed, your system is ready!</strong></p>";
echo "<p><a href='qr_scan_time_in.html'>→ Go to TIME IN Scanner</a></p>";
echo "<p><a href='qr_scan_time_out.html'>→ Go to TIME OUT Scanner</a></p>";

$conn->close();
?>
