<?php
session_start();
require_once '../db_connect.php';

$page_title = 'Reports';
$current_page = 'reports';

include '../includes/advisor_header.php';
?>

<div class="content-card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-file-alt"></i>
            Reports
        </div>
    </div>
    <div style="padding: 40px; text-align: center;">
        <i class="fas fa-chart-bar" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
        <h3>Reports Coming Soon</h3>
        <p style="color: var(--text-secondary);">This feature is under development</p>
    </div>
</div>

<?php include '../includes/advisor_footer.php'; ?>
