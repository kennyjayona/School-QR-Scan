<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'advisor'])) {
    header('Location: ../login.php');
    exit;
}

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$today = date('Y-m-d');
$today_present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'Present'")->fetch_assoc()['count'];
$today_late = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'Late'")->fetch_assoc()['count'];

// Get last 7 days attendance
$last_7_days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$date'")->fetch_assoc()['count'];
    $last_7_days[] = ['date' => $date, 'count' => $count];
}

// Top absentees (students with most absences in last 30 days)
$top_absentees = $conn->query("
    SELECT s.name, s.student_id, COUNT(*) as absent_count
    FROM students s
    LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE a.id IS NULL OR a.status = 'Absent'
    GROUP BY s.student_id
    ORDER BY absent_count DESC
    LIMIT 10
");
$page_title = 'Analytics';
$current_page = 'analytics';

// Include appropriate header based on role
if ($_SESSION['role'] === 'advisor') {
    include '../includes/advisor_header.php';
} else {
    include '../includes/admin_header.php';
}
?>

    <div class="container mt-4">
        <h2>Analytics Dashboard</h2>

        <!-- Statistics Cards -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Students</h5>
                        <h2><?php echo $total_students; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Today Present</h5>
                        <h2><?php echo $today_present; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Today Late</h5>
                        <h2><?php echo $today_late; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Chart -->
        <div class="card mb-4">
            <div class="card-header">Last 7 Days Attendance</div>
            <div class="card-body">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <!-- Top Absentees -->
        <div class="card">
            <div class="card-header">Top Absentees (Last 30 Days)</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Absent Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $top_absentees->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['absent_count']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($last_7_days, 'date')); ?>,
                datasets: [{
                    label: 'Attendance Count',
                    data: <?php echo json_encode(array_column($last_7_days, 'count')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

<?php 
// Include appropriate footer based on role
if ($_SESSION['role'] === 'advisor') {
    include '../includes/advisor_footer.php';
} else {
    include '../includes/admin_footer.php';
}
?>
