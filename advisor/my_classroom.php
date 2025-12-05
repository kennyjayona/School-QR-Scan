<?php
session_start();
require_once '../db_connect.php';

$page_title = 'My Classroom';
$current_page = 'classroom';

$user_id = $_SESSION['user_id'];

// Get advisor's classroom
$classroom_query = $conn->prepare("SELECT * FROM classrooms WHERE advisor_id = ? LIMIT 1");
$classroom_query->bind_param("i", $user_id);
$classroom_query->execute();
$classroom_result = $classroom_query->get_result();
$classroom = $classroom_result->fetch_assoc();

include '../includes/advisor_header.php';
?>

<!-- Classroom Info -->
<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-door-open"></i>
            My Classroom
        </div>
    </div>

    <?php if ($classroom): ?>
        <div style="padding: 30px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <p style="margin-bottom: 15px;">
                        <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Classroom Name</strong>
                        <span style="font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($classroom['classroom_name'] ?? 'N/A'); ?></span>
                    </p>
                    <p style="margin-bottom: 15px;">
                        <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Year Level</strong>
                        <span style="font-size: 18px;">Grade <?php echo htmlspecialchars($classroom['year_level'] ?? 'N/A'); ?></span>
                    </p>
                </div>
                <div>
                    <p style="margin-bottom: 15px;">
                        <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Section</strong>
                        <span style="font-size: 18px;"><?php echo htmlspecialchars($classroom['section'] ?? 'N/A'); ?></span>
                    </p>
                    <p style="margin-bottom: 15px;">
                        <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Room Number</strong>
                        <span style="font-size: 18px;"><?php echo htmlspecialchars($classroom['room_number'] ?? 'N/A'); ?></span>
                    </p>
                </div>
                <div>
                    <p style="margin-bottom: 15px;">
                        <strong style="color: var(--text-secondary); display: block; font-size: 12px; margin-bottom: 5px;">Capacity</strong>
                        <span style="font-size: 18px; font-weight: 600; color: var(--warning);"><?php echo htmlspecialchars($classroom['capacity'] ?? 'N/A'); ?> seats</span>
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
            No classroom assigned yet
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/advisor_footer.php'; ?>