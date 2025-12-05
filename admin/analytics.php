<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'advisor'])) {
    header('Location: ../login.php');
    exit;
}

// Get filter parameters
$view_period = $_GET['period'] ?? 'daily';
$selected_section = $_GET['section'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$today = date('Y-m-d');
$today_present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'Present'")->fetch_assoc()['count'];
$today_late = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'Late'")->fetch_assoc()['count'];

// Get attendance trends based on period
$attendance_trends = [];
if ($view_period === 'daily') {
    // Last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$date' AND status = 'Present'")->fetch_assoc()['count'];
        $late = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$date' AND status = 'Late'")->fetch_assoc()['count'];
        $attendance_trends[] = [
            'label' => date('M d', strtotime($date)),
            'present' => $present,
            'late' => $late
        ];
    }
} elseif ($view_period === 'weekly') {
    // Last 4 weeks
    for ($i = 3; $i >= 0; $i--) {
        $week_start = date('Y-m-d', strtotime("-$i weeks monday"));
        $week_end = date('Y-m-d', strtotime("-$i weeks sunday"));
        $present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date BETWEEN '$week_start' AND '$week_end' AND status = 'Present'")->fetch_assoc()['count'];
        $late = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date BETWEEN '$week_start' AND '$week_end' AND status = 'Late'")->fetch_assoc()['count'];
        $attendance_trends[] = [
            'label' => 'Week ' . date('W', strtotime($week_start)),
            'present' => $present,
            'late' => $late
        ];
    }
} elseif ($view_period === 'monthly') {
    // Last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE DATE_FORMAT(date, '%Y-%m') = '$month' AND status = 'Present'")->fetch_assoc()['count'];
        $late = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE DATE_FORMAT(date, '%Y-%m') = '$month' AND status = 'Late'")->fetch_assoc()['count'];
        $attendance_trends[] = [
            'label' => date('M Y', strtotime($month . '-01')),
            'present' => $present,
            'late' => $late
        ];
    }
}

// Get grade distribution by subject
$grade_distribution_query = "
    SELECT 
        subject,
        AVG(grade) as avg_grade,
        COUNT(*) as student_count,
        MIN(grade) as min_grade,
        MAX(grade) as max_grade
    FROM grades
    WHERE 1=1
";
if ($selected_section !== 'all') {
    $grade_distribution_query .= " AND student_id IN (SELECT student_id FROM students WHERE section = '" . $conn->real_escape_string($selected_section) . "')";
}
$grade_distribution_query .= " GROUP BY subject ORDER BY subject";
$grade_distribution_result = $conn->query($grade_distribution_query);
$grade_distribution = [];
while ($row = $grade_distribution_result->fetch_assoc()) {
    $grade_distribution[] = $row;
}

// Get section performance comparison
$section_performance_query = "
    SELECT 
        s.section,
        COUNT(DISTINCT a.student_id) as attendance_count,
        COUNT(DISTINCT s.student_id) as total_students,
        ROUND((COUNT(DISTINCT a.student_id) / COUNT(DISTINCT s.student_id)) * 100, 2) as attendance_rate,
        COALESCE(AVG(g.grade), 0) as avg_grade
    FROM students s
    LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date BETWEEN '$date_from' AND '$date_to'
    LEFT JOIN grades g ON s.student_id = g.student_id
    GROUP BY s.section
    ORDER BY s.section
";
$section_performance_result = $conn->query($section_performance_query);
$section_performance = [];
while ($row = $section_performance_result->fetch_assoc()) {
    $section_performance[] = $row;
}

// Get all sections for filter
$sections_result = $conn->query("SELECT DISTINCT section FROM students ORDER BY section");
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
    $sections[] = $row['section'];
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

    <div class="container-fluid mt-4">
        <!-- Filter Controls -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-filter"></i> Chart Filters
            </div>
            <div class="card-body">
                <form method="GET" action="analytics.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="period" class="form-label">Time Period</label>
                        <select name="period" id="period" class="form-select">
                            <option value="daily" <?php echo $view_period === 'daily' ? 'selected' : ''; ?>>Daily (Last 7 Days)</option>
                            <option value="weekly" <?php echo $view_period === 'weekly' ? 'selected' : ''; ?>>Weekly (Last 4 Weeks)</option>
                            <option value="monthly" <?php echo $view_period === 'monthly' ? 'selected' : ''; ?>>Monthly (Last 6 Months)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="section" class="form-label">Section</label>
                        <select name="section" id="section" class="form-select">
                            <option value="all" <?php echo $selected_section === 'all' ? 'selected' : ''; ?>>All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section); ?>" <?php echo $selected_section === $section ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($section); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sync-alt"></i> Update Charts
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Total Students</h5>
                        <h2><?php echo $total_students; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-check-circle"></i> Today Present</h5>
                        <h2><?php echo $today_present; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clock"></i> Today Late</h5>
                        <h2><?php echo $today_late; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: Attendance Trends -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i> Attendance Trends - <?php echo ucfirst($view_period); ?> View
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceTrendsChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Grade Distribution and Section Performance -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar"></i> Grade Distribution by Subject
                    </div>
                    <div class="card-body">
                        <canvas id="gradeDistributionChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i> Section Performance Comparison
                    </div>
                    <div class="card-body">
                        <canvas id="sectionPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 3: Attendance Rate by Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-area"></i> Attendance Rate by Section
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceRateChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Absentees Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-exclamation-triangle"></i> Top Absentees (Last 30 Days)
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
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
                                    <td><span class="badge bg-danger"><?php echo $row['absent_count']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script>
        // DepEd Color Scheme
        const depedColors = {
            blue: '#0038A8',
            red: '#CE1126',
            yellow: '#FCD116',
            green: '#28a745',
            orange: '#fd7e14',
            purple: '#6f42c1',
            teal: '#20c997',
            cyan: '#17a2b8'
        };

        // Chart 1: Attendance Trends (Line Chart)
        const attendanceTrendsCtx = document.getElementById('attendanceTrendsChart').getContext('2d');
        const attendanceTrendsChart = new Chart(attendanceTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($attendance_trends, 'label')); ?>,
                datasets: [
                    {
                        label: 'Present',
                        data: <?php echo json_encode(array_column($attendance_trends, 'present')); ?>,
                        borderColor: depedColors.green,
                        backgroundColor: depedColors.green + '33',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Late',
                        data: <?php echo json_encode(array_column($attendance_trends, 'late')); ?>,
                        borderColor: depedColors.orange,
                        backgroundColor: depedColors.orange + '33',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Chart 2: Grade Distribution by Subject (Bar Chart)
        const gradeDistributionCtx = document.getElementById('gradeDistributionChart').getContext('2d');
        const gradeDistributionChart = new Chart(gradeDistributionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($grade_distribution, 'subject')); ?>,
                datasets: [
                    {
                        label: 'Average Grade',
                        data: <?php echo json_encode(array_map(function($item) { return round($item['avg_grade'], 2); }, $grade_distribution)); ?>,
                        backgroundColor: depedColors.blue,
                        borderColor: depedColors.blue,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const data = <?php echo json_encode($grade_distribution); ?>;
                                return [
                                    'Students: ' + data[index].student_count,
                                    'Min: ' + data[index].min_grade,
                                    'Max: ' + data[index].max_grade
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 10
                        }
                    }
                }
            }
        });

        // Chart 3: Section Performance (Radar Chart)
        const sectionPerformanceCtx = document.getElementById('sectionPerformanceChart').getContext('2d');
        const sectionPerformanceChart = new Chart(sectionPerformanceCtx, {
            type: 'radar',
            data: {
                labels: <?php echo json_encode(array_column($section_performance, 'section')); ?>,
                datasets: [
                    {
                        label: 'Average Grade',
                        data: <?php echo json_encode(array_map(function($item) { return round($item['avg_grade'], 2); }, $section_performance)); ?>,
                        borderColor: depedColors.blue,
                        backgroundColor: depedColors.blue + '33',
                        pointBackgroundColor: depedColors.blue,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: depedColors.blue
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });

        // Chart 4: Attendance Rate by Section (Horizontal Bar Chart)
        const attendanceRateCtx = document.getElementById('attendanceRateChart').getContext('2d');
        const attendanceRateChart = new Chart(attendanceRateCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($section_performance, 'section')); ?>,
                datasets: [
                    {
                        label: 'Attendance Rate (%)',
                        data: <?php echo json_encode(array_column($section_performance, 'attendance_rate')); ?>,
                        backgroundColor: function(context) {
                            const value = context.parsed.y;
                            if (value >= 90) return depedColors.green;
                            if (value >= 75) return depedColors.yellow;
                            if (value >= 60) return depedColors.orange;
                            return depedColors.red;
                        },
                        borderWidth: 1
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const data = <?php echo json_encode($section_performance); ?>;
                                return [
                                    'Present: ' + data[index].attendance_count + ' / ' + data[index].total_students,
                                    'Avg Grade: ' + Math.round(data[index].avg_grade * 100) / 100
                                ];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
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
