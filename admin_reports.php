<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';
require_once 'audit_logger.php';

if (!isset($_SESSION['admin_id'])) { 
    header('Location: admin.php'); 
    exit; 
}

// Get admin info
$admin_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$admin_stmt->bind_param("i", $_SESSION['admin_id']);
$admin_stmt->execute();
$admin_info = $admin_stmt->get_result()->fetch_assoc();
$admin_name = $admin_info['name'] ?? 'Admin';

// Log page access
logPageAccess($conn, $_SESSION['admin_id'], $admin_name, 'admin_reports.php', 'Reports and audit log page');

// Create audit_log table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        admin_name VARCHAR(100) NOT NULL,
        action_type VARCHAR(100) NOT NULL,
        action_description TEXT NOT NULL,
        target_type VARCHAR(50),
        target_id INT,
        target_details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(admin_id),
        INDEX(created_at),
        INDEX(action_type),
        INDEX(admin_name)
    )
");

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Log export action
    logAdminAction($conn, $_SESSION['admin_id'], $admin_name, 'REPORT_EXPORT', "Exported audit report as $export_type", 'report', null, "Export type: $export_type");
    
    if ($export_type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Timestamp', 'Admin', 'Action Type', 'Description', 'Target Type', 'Target ID', 'IP Address']);
        
        $export_result = $conn->query("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 1000");
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, [
                $row['created_at'],
                $row['admin_name'],
                $row['action_type'],
                $row['action_description'],
                $row['target_type'] ?: '-',
                $row['target_id'] ?: '-',
                $row['ip_address']
            ]);
        }
        fclose($output);
        exit;
    }
}

// Date range handling for audit log
$date_range = $_GET['range'] ?? '7';
$custom_start = $_GET['start_date'] ?? '';
$custom_end = $_GET['end_date'] ?? '';
$action_filter = $_GET['action_type'] ?? '';
$admin_filter = $_GET['admin_name'] ?? '';

// Build date filter
$date_filter = '';
$date_params = [];
$param_types = '';

if ($custom_start && $custom_end) {
    $date_filter = " AND DATE(created_at) BETWEEN ? AND ?";
    $date_params = [$custom_start, $custom_end];
    $param_types = 'ss';
    $period_label = "Custom Period (" . date('M j', strtotime($custom_start)) . " - " . date('M j, Y', strtotime($custom_end)) . ")";
} else {
    switch($date_range) {
        case '1':
            $date_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            $period_label = "Last 24 Hours";
            break;
        case '7':
            $date_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $period_label = "Last 7 Days";
            break;
        case '30':
            $date_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $period_label = "Last 30 Days";
            break;
        case '90':
            $date_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            $period_label = "Last 90 Days";
            break;
        case 'all':
        default:
            $date_filter = "";
            $period_label = "All Time";
            break;
    }
}

// Add action type filter
if ($action_filter) {
    $date_filter .= " AND action_type = ?";
    $date_params[] = $action_filter;
    $param_types .= 's';
}

// Add admin name filter
if ($admin_filter) {
    $date_filter .= " AND admin_name = ?";
    $date_params[] = $admin_filter;
    $param_types .= 's';
}

// Helper function for queries
function executeQuery($conn, $sql, $params = [], $param_types = '') {
    try {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            return $stmt->get_result();
        } else {
            $result = $conn->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $conn->error);
            }
            return $result;
        }
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

// Get audit log statistics
$audit_stats = [
    'total_actions' => 0,
    'today_actions' => 0,
    'week_actions' => 0,
    'unique_admins' => 0,
    'login_events' => 0,
    'logout_events' => 0,
    'page_views' => 0,
    'user_actions' => 0,
    'message_actions' => 0,
    'complaint_deletions' => 0,
    'complaint_status_updates' => 0
];

$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total_actions,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_actions,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_actions,
        COUNT(DISTINCT admin_name) as unique_admins,
        COUNT(CASE WHEN action_type = 'ADMIN_LOGIN' THEN 1 END) as login_events,
        COUNT(CASE WHEN action_type = 'ADMIN_LOGOUT' THEN 1 END) as logout_events,
        COUNT(CASE WHEN action_type = 'PAGE_ACCESS' THEN 1 END) as page_views,
        COUNT(CASE WHEN action_type LIKE '%USER%' THEN 1 END) as user_actions,
        COUNT(CASE WHEN action_type LIKE '%MESSAGE%' THEN 1 END) as message_actions,
        COUNT(CASE WHEN action_type = 'COMPLAINT_DELETION' THEN 1 END) as complaint_deletions,
        COUNT(CASE WHEN action_type = 'COMPLAINT_STATUS_UPDATE' THEN 1 END) as complaint_status_updates
    FROM audit_log
");

if ($stats_result) {
    $audit_stats = $stats_result->fetch_assoc() ?: $audit_stats;
}

// Get system statistics
$system_stats = [
    'total_users' => 0,
    'active_7d' => 0,
    'new_30d' => 0,
    'total_messages' => 0,
    'unread_messages' => 0,
    'replied_messages' => 0,
    'total_complaints' => 0,
    'pending_complaints' => 0
];

// User statistics
$user_result = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_7d,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_30d
    FROM users
");

if ($user_result) {
    $user_data = $user_result->fetch_assoc();
    $system_stats = array_merge($system_stats, $user_data ?: []);
}

// Message statistics
$table_check = $conn->query("SHOW TABLES LIKE 'contact_messages'");
if ($table_check && $table_check->num_rows > 0) {
    $message_result = $conn->query("
        SELECT 
            COUNT(*) as total_messages,
            COUNT(CASE WHEN status = 'unread' THEN 1 END) as unread_messages,
            COUNT(CASE WHEN status = 'replied' THEN 1 END) as replied_messages
        FROM contact_messages
    ");

    if ($message_result) {
        $message_data = $message_result->fetch_assoc();
        $system_stats = array_merge($system_stats, $message_data ?: []);
    }
}

// Complaint statistics
$complaint_check = $conn->query("SHOW TABLES LIKE 'complaints'");
if ($complaint_check && $complaint_check->num_rows > 0) {
    $complaint_result = $conn->query("
        SELECT 
            COUNT(*) as total_complaints,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_complaints
        FROM complaints
    ");

    if ($complaint_result) {
        $complaint_data = $complaint_result->fetch_assoc();
        $system_stats = array_merge($system_stats, $complaint_data ?: []);
    }
}

// Get daily activity for the last 7 days
$daily_activity = $conn->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_actions,
        COUNT(CASE WHEN action_type = 'ADMIN_LOGIN' THEN 1 END) as logins,
        COUNT(CASE WHEN action_type = 'PAGE_ACCESS' THEN 1 END) as page_views,
        COUNT(CASE WHEN action_type LIKE '%USER%' THEN 1 END) as user_actions,
        COUNT(CASE WHEN action_type LIKE '%MESSAGE%' THEN 1 END) as message_actions,
        COUNT(CASE WHEN action_type LIKE '%COMPLAINT%' THEN 1 END) as complaint_actions
    FROM audit_log 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
")->fetch_all(MYSQLI_ASSOC);

// Top active admins
$top_admins = $conn->query("
    SELECT 
        admin_name,
        COUNT(*) as total_actions,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_actions,
        MAX(created_at) as last_activity
    FROM audit_log 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY admin_name, admin_id
    ORDER BY total_actions DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Most common actions
$common_actions = $conn->query("
    SELECT 
        action_type,
        COUNT(*) as count,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_count
    FROM audit_log 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY action_type
    ORDER BY count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Recent login sessions
$recent_sessions = $conn->query("
    SELECT 
        admin_name,
        MIN(CASE WHEN action_type = 'ADMIN_LOGIN' THEN created_at END) as login_time,
        MAX(CASE WHEN action_type = 'ADMIN_LOGOUT' THEN created_at END) as logout_time,
        ip_address
    FROM audit_log 
    WHERE action_type IN ('ADMIN_LOGIN', 'ADMIN_LOGOUT')
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY admin_name, ip_address, DATE(created_at)
    ORDER BY login_time DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Pagination for audit log
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get total audit records for pagination
$count_sql = "SELECT COUNT(*) as total FROM audit_log WHERE 1=1 $date_filter";
$count_result = executeQuery($conn, $count_sql, $date_params, $param_types);
$total_records = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_records / $per_page);

// Get audit log entries
$audit_sql = "
    SELECT 
        id,
        admin_name,
        action_type,
        action_description,
        target_type,
        target_id,
        target_details,
        ip_address,
        created_at
    FROM audit_log 
    WHERE 1=1 $date_filter
    ORDER BY created_at DESC 
    LIMIT $offset, $per_page
";

$audit_result = executeQuery($conn, $audit_sql, $date_params, $param_types);
$audit_logs = $audit_result ? $audit_result->fetch_all(MYSQLI_ASSOC) : [];

// Get unique action types and admin names for filters
$action_types = $conn->query("SELECT DISTINCT action_type FROM audit_log ORDER BY action_type")->fetch_all(MYSQLI_ASSOC);
$admin_names = $conn->query("SELECT DISTINCT admin_name FROM audit_log ORDER BY admin_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Reports & Audit Trail</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #718096;
            --success: #48bb78;
            --danger: #f56565;
            --warning: #ed8936;
            --info: #4299e1;
            --light: #f7fafc;
            --dark: #2d3748;
            --white: #ffffff;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        /* Header Navigation */
        .header-nav {
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-links {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .nav-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn.primary {
            background: var(--primary);
            color: var(--white);
        }

        .nav-btn.secondary {
            background: #f7fafc;
            color: var(--secondary);
        }

        .nav-btn.active {
            background: var(--primary);
            color: var(--white);
        }

        .nav-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--white);
            font-size: 16px;
        }

        .container { 
            max-width: 1600px;
            margin: 1rem auto;
            padding: 0 1rem;
        }

        .header {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-info {
            text-align: right;
            color: var(--secondary);
        }

        .live-indicator {
            background: linear-gradient(135deg, var(--success) 0%, #38a169 100%);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .current-admin {
            background: linear-gradient(135deg, var(--info) 0%, var(--primary) 100%);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--info) 100%);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            color: var(--white);
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }

        .stat-label {
            color: var(--secondary);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .insight-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .insight-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .insight-content {
            padding: 1.5rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .insight-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .insight-item:last-child {
            border-bottom: none;
        }

        .insight-name {
            font-weight: 600;
            color: var(--dark);
        }

        .insight-details {
            font-size: 0.875rem;
            color: var(--secondary);
        }

        .insight-value {
            font-weight: 700;
            color: var(--primary);
        }

        .filters-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .filters-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.875rem;
        }

        select, input[type="date"] {
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            transition: border-color 0.2s;
            background: var(--white);
        }

        select:focus, input[type="date"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--secondary);
        }

        .btn-secondary:hover {
            background: #4a5568;
        }

        .btn-success {
            background: var(--success);
        }

        .btn-success:hover {
            background: #38a169;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .audit-section {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .audit-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .audit-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .audit-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .audit-content {
            padding: 0;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
        }

        .audit-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.875rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .audit-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .audit-table tbody tr:hover {
            background: #f8fafc;
            transition: background 0.2s;
        }

        .action-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-login { background: rgba(72, 187, 120, 0.1); color: #22543d; }
        .action-logout { background: rgba(156, 163, 175, 0.1); color: #4a5568; }
        .action-user { background: rgba(66, 153, 225, 0.1); color: #2c5282; }
        .action-message { background: rgba(237, 137, 54, 0.1); color: #744210; }
        .action-termination { background: rgba(245, 101, 101, 0.1); color: #742a2a; }
        .action-page { background: rgba(102, 126, 234, 0.1); color: #553c9a; }
        .action-report { background: rgba(16, 185, 129, 0.1); color: #047857; }
        .action-complaint { background: rgba(245, 101, 101, 0.1); color: #742a2a; }

        .timestamp {
            font-size: 0.875rem;
            color: var(--secondary);
        }

        .admin-name {
            font-weight: 600;
            color: var(--dark);
        }

        .target-info {
            font-size: 0.875rem;
            color: var(--secondary);
            margin-top: 0.25rem;
        }

        .ip-address {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            background: #f1f5f9;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            color: var(--secondary);
        }

        .pagination {
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .page-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            background: var(--white);
            color: var(--dark);
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .page-btn:hover {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .page-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .page-btn.disabled {
            color: #cbd5e0;
            pointer-events: none;
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--secondary);
        }

        .empty-state i {
            font-size: 4rem;
            color: #e2e8f0;
            margin-bottom: 1rem;
        }

        .session-info {
            font-size: 0.75rem;
            color: var(--secondary);
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .container {
                margin: 0.5rem auto;
                padding: 0 0.5rem;
            }

            .header {
                text-align: center;
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .insights-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .audit-table {
                font-size: 0.875rem;
            }

            .audit-table th,
            .audit-table td {
                padding: 0.75rem 0.5rem;
            }

            .filter-actions {
                justify-content: stretch;
                flex-direction: column;
            }

            .export-buttons {
                justify-content: stretch;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <div class="header-nav">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                KV Bhandup Admin
            </a>
            <div class="nav-links">
                <a href="admin_dashboard.php" class="nav-btn secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="admin_complaints.php" class="nav-btn secondary">
                    <i class="fas fa-clipboard-list"></i> Complaints
                </a>
                <a href="admin_users.php" class="nav-btn secondary">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="admin_messages.php" class="nav-btn secondary">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="admin_reports.php" class="nav-btn active">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div style="font-size: 0.75rem; color: var(--secondary);">Administrator</div>
                </div>
                <a href="logout.php" class="nav-btn primary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1><i class="fas fa-shield-alt"></i> Admin Reports & Audit Trail</h1>
                <p style="color: var(--secondary); margin-top: 0.5rem;">Comprehensive system monitoring and security audit trail</p>
            </div>
            <div class="header-info">
                <div class="live-indicator">
                    <i class="fas fa-circle"></i> Live Monitoring
                </div>
                <div class="current-admin">
                    <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?>
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.875rem;">
                    Last updated: <?php echo date('M j, Y H:i:s'); ?> UTC
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-number"><?php echo number_format($system_stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                </div>
                <div class="stat-number"><?php echo number_format($system_stats['total_complaints']); ?></div>
                <div class="stat-label">Total Complaints</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                </div>
                <div class="stat-number"><?php echo number_format($system_stats['total_messages']); ?></div>
                <div class="stat-label">Total Messages</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-history"></i></div>
                </div>
                <div class="stat-number"><?php echo number_format($audit_stats['total_actions']); ?></div>
                <div class="stat-label">Total Admin Actions</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-number"><?php echo number_format($audit_stats['today_actions']); ?></div>
                <div class="stat-label">Actions Today</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-trash"></i></div>
                </div>
                <div class="stat-number"><?php echo number_format($audit_stats['complaint_deletions']); ?></div>
                <div class="stat-label">Complaints Deleted</div>
            </div>
        </div>

        <!-- Insights Grid -->
        <div class="insights-grid">
            <!-- Top Active Admins -->
            <div class="insight-card">
                <div class="insight-header">
                    <i class="fas fa-crown"></i> Most Active Admins (30 days)
                </div>
                <div class="insight-content">
                    <?php if (!empty($top_admins)): ?>
                        <?php foreach ($top_admins as $admin): ?>
                            <div class="insight-item">
                                <div>
                                    <div class="insight-name"><?php echo htmlspecialchars($admin['admin_name']); ?></div>
                                    <div class="insight-details">
                                        Last activity: <?php echo date('M j, H:i', strtotime($admin['last_activity'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="insight-value"><?php echo number_format($admin['total_actions']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--secondary);">
                                        <?php echo number_format($admin['today_actions']); ?> today
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="insight-item">
                            <div class="insight-details">No admin activity data available</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Common Actions -->
            <div class="insight-card">
                <div class="insight-header">
                    <i class="fas fa-chart-bar"></i> Most Common Actions (30 days)
                </div>
                <div class="insight-content">
                    <?php if (!empty($common_actions)): ?>
                        <?php foreach ($common_actions as $action): ?>
                            <div class="insight-item">
                                <div>
                                    <div class="insight-name"><?php echo htmlspecialchars(str_replace('_', ' ', $action['action_type'])); ?></div>
                                    <div class="insight-details"><?php echo number_format($action['today_count']); ?> today</div>
                                </div>
                                <div class="insight-value"><?php echo number_format($action['count']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="insight-item">
                            <div class="insight-details">No action data available</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Sessions -->
            <div class="insight-card">
                <div class="insight-header">
                    <i class="fas fa-history"></i> Recent Admin Sessions
                </div>
                <div class="insight-content">
                    <?php if (!empty($recent_sessions)): ?>
                        <?php foreach ($recent_sessions as $session): ?>
                            <div class="insight-item">
                                <div>
                                    <div class="insight-name"><?php echo htmlspecialchars($session['admin_name']); ?></div>
                                    <div class="insight-details">
                                        <?php if ($session['login_time']): ?>
                                            Login: <?php echo date('M j, H:i', strtotime($session['login_time'])); ?>
                                        <?php endif; ?>
                                        <?php if ($session['logout_time']): ?>
                                            | Logout: <?php echo date('H:i', strtotime($session['logout_time'])); ?>
                                        <?php else: ?>
                                            | <span style="color: var(--success);">Active</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="session-info">IP: <?php echo htmlspecialchars($session['ip_address']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="insight-item">
                            <div class="insight-details">No recent session data available</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Daily Activity -->
            <div class="insight-card">
                <div class="insight-header">
                    <i class="fas fa-calendar-week"></i> Daily Activity (Last 7 days)
                </div>
                <div class="insight-content">
                    <?php if (!empty($daily_activity)): ?>
                        <?php foreach ($daily_activity as $day): ?>
                            <div class="insight-item">
                                <div>
                                    <div class="insight-name"><?php echo date('M j (D)', strtotime($day['date'])); ?></div>
                                    <div class="insight-details">
                                        <?php echo $day['logins']; ?> logins, <?php echo $day['page_views']; ?> page views, <?php echo $day['complaint_actions']; ?> complaint actions
                                    </div>
                                </div>
                                <div class="insight-value"><?php echo number_format($day['total_actions']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="insight-item">
                            <div class="insight-details">No daily activity data available</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-title">
                <i class="fas fa-filter"></i> Filter & Export Audit Log
            </div>
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Time Period</label>
                        <select name="range" id="range" onchange="toggleCustomDates()">
                            <option value="1" <?php echo $date_range == '1' ? 'selected' : ''; ?>>Last 24 Hours</option>
                            <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                            <option value="all" <?php echo $date_range == 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="custom" <?php echo ($custom_start && $custom_end) ? 'selected' : ''; ?>>Custom Range</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Action Type</label>
                        <select name="action_type">
                            <option value="">All Actions</option>
                            <?php foreach ($action_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['action_type']); ?>" 
                                        <?php echo ($action_filter == $type['action_type']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $type['action_type'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Admin User</label>
                        <select name="admin_name">
                            <option value="">All Admins</option>
                            <?php foreach ($admin_names as $admin): ?>
                                <option value="<?php echo htmlspecialchars($admin['admin_name']); ?>" 
                                        <?php echo ($admin_filter == $admin['admin_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['admin_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group" id="custom-dates" style="display: <?php echo ($custom_start && $custom_end) ? 'block' : 'none'; ?>;">
                        <label>Custom Date Range</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($custom_start); ?>" max="<?php echo date('Y-m-d'); ?>">
                            <span>to</span>
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($custom_end); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <div class="filter-actions">
                    <div>
                        <a href="admin_reports.php" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Clear Filters
                        </a>
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                    </div>
                    
                    <div class="export-buttons">
                        <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <button type="button" class="btn btn-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Audit Log Section -->
        <div class="audit-section">
            <div class="audit-header">
                <div class="audit-title">
                    <i class="fas fa-history"></i> Complete Audit Trail
                </div>
                <div class="audit-count">
                    <?php echo number_format($total_records); ?> record<?php echo $total_records != 1 ? 's' : ''; ?> found
                </div>
            </div>

            <div class="audit-content">
                <?php if (!empty($audit_logs)): ?>
                    <table class="audit-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Target</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--dark);">
                                            <?php echo date('M j, Y', strtotime($log['created_at'])); ?>
                                        </div>
                                        <div class="timestamp">
                                            <?php echo date('H:i:s', strtotime($log['created_at'])); ?> UTC
                                        </div>
                                    </td>
                                    <td>
                                        <div class="admin-name"><?php echo htmlspecialchars($log['admin_name']); ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $action_class = 'action-page';
                                        if (strpos($log['action_type'], 'LOGIN') !== false) $action_class = 'action-login';
                                        elseif (strpos($log['action_type'], 'LOGOUT') !== false) $action_class = 'action-logout';
                                        elseif (strpos($log['action_type'], 'USER') !== false) $action_class = 'action-user';
                                        elseif (strpos($log['action_type'], 'MESSAGE') !== false) $action_class = 'action-message';
                                        elseif (strpos($log['action_type'], 'TERMINATION') !== false) $action_class = 'action-termination';
                                        elseif (strpos($log['action_type'], 'EXPORT') !== false) $action_class = 'action-report';
                                        elseif (strpos($log['action_type'], 'COMPLAINT') !== false) $action_class = 'action-complaint';
                                        ?>
                                        <span class="action-badge <?php echo $action_class; ?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $log['action_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($log['action_description']); ?></div>
                                        <?php if ($log['target_details']): ?>
                                            <div class="target-info"><?php echo htmlspecialchars($log['target_details']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['target_type'] && $log['target_id']): ?>
                                            <div style="font-size: 0.875rem;">
                                                <strong><?php echo htmlspecialchars($log['target_type']); ?></strong> 
                                                #<?php echo htmlspecialchars($log['target_id']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--secondary);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="ip-address"><?php echo htmlspecialchars($log['ip_address'] ?: 'Unknown'); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <a href="?page=1&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }), '', '&'); ?>" 
                               class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?php echo max(1, $page - 1); ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }), '', '&'); ?>" 
                               class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }), '', '&'); ?>" 
                                   class="page-btn <?php echo $page == $i ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <a href="?page=<?php echo min($total_pages, $page + 1); ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }), '', '&'); ?>" 
                               class="page-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $total_pages; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }), '', '&'); ?>" 
                               class="page-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No Audit Records Found</h3>
                        <p>No administrative actions match your current filters.<br>
                        Try adjusting the time period or removing filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleCustomDates() {
            const range = document.getElementById('range').value;
            const customDates = document.getElementById('custom-dates');
            customDates.style.display = range === 'custom' ? 'block' : 'none';
        }

        // Auto-refresh every 30 seconds when visible
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                // Only refresh the stats, not the whole page
                const currentUrl = new URL(window.location);
                if (!currentUrl.searchParams.has('page') || currentUrl.searchParams.get('page') === '1') {
                    // Only auto-refresh if on first page to avoid interrupting user navigation
                    window.location.reload();
                }
            }
        }, 30000);
    </script>
</body>
</html>