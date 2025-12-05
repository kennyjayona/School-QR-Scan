<?php
/**
 * Activity Logger - Audit Trail System
 * Tracks all user actions for security and transparency
 */

/**
 * Log user activity
 */
function log_activity($action, $table_name = null, $record_id = null, $old_value = null, $new_value = null) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Convert arrays/objects to JSON
    if (is_array($old_value) || is_object($old_value)) {
        $old_value = json_encode($old_value);
    }
    if (is_array($new_value) || is_object($new_value)) {
        $new_value = json_encode($new_value);
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssisss', $user_id, $action, $table_name, $record_id, $old_value, $new_value, $ip_address, $user_agent);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get recent activities
 */
function get_recent_activities($limit = 50, $user_id = null) {
    global $conn;
    
    $sql = "SELECT al.*, u.username, u.name 
            FROM activity_logs al 
            INNER JOIN users u ON al.user_id = u.id ";
    
    if ($user_id) {
        $sql .= "WHERE al.user_id = ? ";
    }
    
    $sql .= "ORDER BY al.created_at DESC LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($user_id) {
        $stmt->bind_param('ii', $user_id, $limit);
    } else {
        $stmt->bind_param('i', $limit);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get activities by table
 */
function get_activities_by_table($table_name, $record_id = null, $limit = 20) {
    global $conn;
    
    $sql = "SELECT al.*, u.username, u.name 
            FROM activity_logs al 
            INNER JOIN users u ON al.user_id = u.id 
            WHERE al.table_name = ?";
    
    if ($record_id) {
        $sql .= " AND al.record_id = ?";
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($record_id) {
        $stmt->bind_param('sii', $table_name, $record_id, $limit);
    } else {
        $stmt->bind_param('si', $table_name, $limit);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Format activity for display
 */
function format_activity($activity) {
    $user = $activity['name'] ?? $activity['username'];
    $action = htmlspecialchars($activity['action']);
    $time = date('M d, Y h:i A', strtotime($activity['created_at']));
    
    $html = "<div class='activity-item'>";
    $html .= "<strong>{$user}</strong> {$action}";
    
    if ($activity['table_name']) {
        $html .= " in <em>" . htmlspecialchars($activity['table_name']) . "</em>";
    }
    
    if ($activity['record_id']) {
        $html .= " (ID: {$activity['record_id']})";
    }
    
    $html .= "<br><small class='text-muted'>{$time} from {$activity['ip_address']}</small>";
    $html .= "</div>";
    
    return $html;
}

// Common activity actions
define('ACTIVITY_LOGIN', 'Logged in');
define('ACTIVITY_LOGOUT', 'Logged out');
define('ACTIVITY_CREATE', 'Created record');
define('ACTIVITY_UPDATE', 'Updated record');
define('ACTIVITY_DELETE', 'Deleted record');
define('ACTIVITY_RESTORE', 'Restored record');
define('ACTIVITY_VIEW', 'Viewed record');
define('ACTIVITY_EXPORT', 'Exported data');
?>
