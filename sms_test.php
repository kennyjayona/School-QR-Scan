<?php
/**
 * SMS Gateway Test Page
 * Use this page to test your Android SMS Gateway connection
 */

require_once 'config.php';
require_once 'db_connect.php';
require_once 'includes/sms_gateway.php';
require_once 'sms_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = 'SMS Gateway Test';
$current_page = 'sms_test';
$result = null;

// Handle test SMS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($phone) && !empty($message)) {
        $result = SMSGateway::sendSMS($phone, $message);
    }
}

include 'includes/admin_header.php';
?>

<style>
.test-card {
    max-width: 800px;
    margin: 0 auto;
}

.config-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.config-info code {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 4px;
    color: #d63384;
}

.result-box {
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}

.result-success {
    background: #d4edda;
    border: 2px solid #28a745;
    color: #155724;
}

.result-error {
    background: #f8d7da;
    border: 2px solid #dc3545;
    color: #721c24;
}
</style>

<div class="test-card">
    <!-- Configuration Info -->
    <div class="content-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-cog"></i>
                SMS Gateway Configuration
            </div>
        </div>
        <div class="config-info">
            <h5><i class="fas fa-server"></i> Current Configuration</h5>
            <p><strong>Gateway URL:</strong> <code><?php echo htmlspecialchars(SMSGateway::getGatewayURL()); ?></code></p>
            <p><strong>Status:</strong> 
                <?php if (SMS_ENABLED): ?>
                    <span class="badge bg-success">Enabled</span>
                <?php else: ?>
                    <span class="badge bg-warning">Disabled (Test Mode)</span>
                <?php endif; ?>
            </p>
            <p><strong>Timeout:</strong> <?php echo SMS_TIMEOUT; ?> seconds</p>
            
            <hr>
            
            <h6><i class="fas fa-info-circle"></i> Setup Instructions:</h6>
            <ol>
                <li>Install <strong>SMS Gateway Ultimate</strong> or similar app on your Android device</li>
                <li>Connect your Android device to the same Wi-Fi network as this server</li>
                <li>Open the SMS Gateway app and start the HTTP server</li>
                <li>Note the IP address and port (e.g., 192.168.1.5:8080)</li>
                <li>Update <code>sms_config.php</code> with your device's IP address</li>
                <li>Use this page to test the connection</li>
            </ol>
        </div>
    </div>

    <!-- Test Form -->
    <div class="content-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-paper-plane"></i>
                Send Test SMS
            </div>
        </div>
        <div style="padding: 25px;">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" 
                           placeholder="+639388043855 or 09388043855" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           required>
                    <small class="text-muted">Enter phone number with country code or starting with 0</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" 
                              placeholder="Enter your test message here..."
                              required><?php echo htmlspecialchars($_POST['message'] ?? 'This is a test message from Smart Classroom SMS Gateway.'); ?></textarea>
                    <small class="text-muted">Maximum 160 characters for single SMS</small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Test SMS
                </button>
            </form>
            
            <?php if ($result): ?>
            <div class="result-box <?php echo $result['success'] ? 'result-success' : 'result-error'; ?>">
                <h5>
                    <i class="fas fa-<?php echo $result['success'] ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo $result['success'] ? 'Success!' : 'Failed'; ?>
                </h5>
                <p><strong>Message:</strong> <?php echo htmlspecialchars($result['message']); ?></p>
                <?php if ($result['response']): ?>
                <p><strong>Gateway Response:</strong></p>
                <pre style="background: #fff; padding: 10px; border-radius: 5px; overflow-x: auto;"><?php echo htmlspecialchars($result['response']); ?></pre>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SMS Logs -->
    <div class="content-card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-history"></i>
                Recent SMS Logs
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Student</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logs = $conn->query("
                        SELECT sl.*, s.name as student_name 
                        FROM sms_logs sl
                        LEFT JOIN students s ON sl.student_id = s.id
                        ORDER BY sl.created_at DESC
                        LIMIT 10
                    ");
                    
                    if ($logs && $logs->num_rows > 0):
                        while ($log = $logs->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo date('M j, Y h:i A', strtotime($log['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['student_name'] ?? 'Test'); ?></td>
                        <td><?php echo htmlspecialchars($log['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars(substr($log['message'], 0, 50)) . (strlen($log['message']) > 50 ? '...' : ''); ?></td>
                        <td>
                            <?php if ($log['status'] === 'Success'): ?>
                                <span class="badge bg-success">Success</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Failed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No SMS logs yet</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
