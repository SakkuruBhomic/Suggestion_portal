<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

// Only allow admins
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Create admin_activity_logs table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS admin_activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        admin_name VARCHAR(100) NOT NULL,
        action_type VARCHAR(100) NOT NULL,
        action_description TEXT NOT NULL,
        target_id INT,
        target_type VARCHAR(50),
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(admin_id),
        INDEX(created_at),
        INDEX(action_type),
        INDEX(admin_name)
    )
");

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['complaint_id'])) {
        $complaint_id = (int)$_POST['complaint_id'];
        $action = $_POST['action'];
        
        // Validate complaint exists
        $check_stmt = $conn->prepare("SELECT id, title, status FROM complaints WHERE id = ?");
        $check_stmt->bind_param("i", $complaint_id);
        $check_stmt->execute();
        $existing_complaint = $check_stmt->get_result()->fetch_assoc();
        
        if (!$existing_complaint) {
            $_SESSION['error_message'] = "Complaint not found.";
            header('Location: admin_complaints.php');
            exit;
        }
        
        if ($action === 'status_update' && isset($_POST['new_status'])) {
            $new_status = $_POST['new_status'];
            
            // Validate status
            if (!in_array($new_status, ['pending', 'in_progress', 'resolved', 'closed'])) {
                $_SESSION['error_message'] = "Invalid status provided.";
                header('Location: admin_complaints.php');
                exit;
            }
            
            try {
                // Update complaint status
                $stmt = $conn->prepare("UPDATE complaints SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $new_status, $complaint_id);
                
                if ($stmt->execute()) {
                    // Log the status update - FIX: Separate variable declarations
                    try {
                        $log_stmt = $conn->prepare("INSERT INTO admin_activity_logs (admin_id, admin_name, action_type, action_description, target_id, target_type, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        
                        // Declare variables separately for proper reference passing
                        $log_admin_id = $admin_id;
                        $log_admin_name = $admin['name'];
                        $log_action_type = 'COMPLAINT_STATUS_UPDATE';
                        $log_description = "Updated complaint #{$complaint_id} status from '{$existing_complaint['status']}' to '{$new_status}'";
                        $log_target_id = $complaint_id;
                        $log_target_type = 'complaint';
                        $log_ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        
                        $log_stmt->bind_param("isssiss", $log_admin_id, $log_admin_name, $log_action_type, $log_description, $log_target_id, $log_target_type, $log_ip_address);
                        $log_stmt->execute();
                    } catch (Exception $e) {
                        error_log("Failed to log activity: " . $e->getMessage());
                    }
                    
                    $_SESSION['success_message'] = "Complaint #{$complaint_id} status updated to " . ucfirst(str_replace('_', ' ', $new_status)) . ".";
                } else {
                    $_SESSION['error_message'] = "Failed to update complaint status. Please try again.";
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Database error: " . $e->getMessage();
                error_log("Status update error: " . $e->getMessage());
            }
            
        } elseif ($action === 'delete') {
            try {
                // Start transaction
                $conn->autocommit(false);
                
                // Get complaint details before deletion for logging
                $stmt = $conn->prepare("SELECT c.*, u.name AS user_name FROM complaints c LEFT JOIN users u ON c.user_id = u.id WHERE c.id = ?");
                $stmt->bind_param("i", $complaint_id);
                $stmt->execute();
                $complaint_to_delete = $stmt->get_result()->fetch_assoc();
                
                if ($complaint_to_delete) {
                    // Delete related messages first
                    $delete_messages_stmt = $conn->prepare("DELETE FROM complaint_messages WHERE complaint_id = ?");
                    $delete_messages_stmt->bind_param("i", $complaint_id);
                    $delete_messages_stmt->execute();
                    
                    // Delete the complaint
                    $delete_stmt = $conn->prepare("DELETE FROM complaints WHERE id = ?");
                    $delete_stmt->bind_param("i", $complaint_id);
                    
                    if ($delete_stmt->execute()) {
                        // Log the deletion - FIX: Separate variable declarations
                        try {
                            $log_stmt = $conn->prepare("INSERT INTO admin_activity_logs (admin_id, admin_name, action_type, action_description, target_id, target_type, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            
                            // Declare variables separately for proper reference passing
                            $log_admin_id = $admin_id;
                            $log_admin_name = $admin['name'];
                            $log_action_type = 'COMPLAINT_DELETION';
                            $log_description = "Deleted complaint #{$complaint_id}: '{$complaint_to_delete['title']}' submitted by {$complaint_to_delete['user_name']}";
                            $log_target_id = $complaint_id;
                            $log_target_type = 'complaint';
                            $log_ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                            
                            $log_stmt->bind_param("isssiss", $log_admin_id, $log_admin_name, $log_action_type, $log_description, $log_target_id, $log_target_type, $log_ip_address);
                            $log_stmt->execute();
                        } catch (Exception $e) {
                            error_log("Failed to log activity: " . $e->getMessage());
                        }
                        
                        // Commit transaction
                        $conn->commit();
                        $_SESSION['success_message'] = "Complaint #{$complaint_id} has been successfully deleted.";
                    } else {
                        $conn->rollback();
                        $_SESSION['error_message'] = "Failed to delete complaint #{$complaint_id}.";
                    }
                } else {
                    $conn->rollback();
                    $_SESSION['error_message'] = "Complaint not found.";
                }
                
                // Reset autocommit
                $conn->autocommit(true);
                
            } catch (Exception $e) {
                $conn->rollback();
                $conn->autocommit(true);
                $_SESSION['error_message'] = "Database error: " . $e->getMessage();
                error_log("Delete error: " . $e->getMessage());
            }
        }
        
        // Redirect to avoid form resubmission
        header('Location: admin_complaints.php');
        exit;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = "";

if ($status_filter) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($priority_filter) {
    $where_conditions[] = "c.priority = ?";
    $params[] = $priority_filter;
    $param_types .= "s";
}

if ($category_filter) {
    $where_conditions[] = "c.category = ?";
    $params[] = $category_filter;
    $param_types .= "s";
}

if ($search) {
    $where_conditions[] = "(c.title LIKE ? OR c.description LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Fetch complaints with filters
$sql = "SELECT c.*, u.name AS user_name, u.email AS user_email 
        FROM complaints c 
        LEFT JOIN users u ON c.user_id = u.id 
        $where_clause 
        ORDER BY c.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $complaints = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching complaints: " . $e->getMessage();
    $complaints = [];
    error_log("Fetch complaints error: " . $e->getMessage());
}

// Get statistics
try {
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
        FROM complaints";
    $stats = $conn->query($stats_sql)->fetch_assoc();
} catch (Exception $e) {
    $stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
    error_log("Stats error: " . $e->getMessage());
}

// Get unique categories and priorities for filters
try {
    $categories = $conn->query("SELECT DISTINCT category FROM complaints WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetch_all(MYSQLI_ASSOC);
    $priorities = $conn->query("SELECT DISTINCT priority FROM complaints WHERE priority IS NOT NULL AND priority != '' ORDER BY priority")->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $categories = [];
    $priorities = [];
    error_log("Filter options error: " . $e->getMessage());
}

function statusBadge($status) {
    $configs = [
        'pending' => ['bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳', 'label' => 'Pending'],
        'in_progress' => ['bg' => '#e3f2fd', 'color' => '#1976d2', 'icon' => '🔄', 'label' => 'In Progress'],
        'resolved' => ['bg' => '#e8f5e8', 'color' => '#388e3c', 'icon' => '✅', 'label' => 'Resolved'],
        'closed' => ['bg' => '#f3e5f5', 'color' => '#7b1fa2', 'icon' => '🔒', 'label' => 'Closed']
    ];
    
    $config = $configs[strtolower($status)] ?? $configs['pending'];
    return "<span class='status-badge' style='background:{$config['bg']};color:{$config['color']};padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid {$config['color']}20;'>{$config['icon']} {$config['label']}</span>";
}

function priorityBadge($priority) {
    $configs = [
        'Low' => ['bg' => '#e8f5e8', 'color' => '#388e3c', 'icon' => '🟢'],
        'Medium' => ['bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '🟡'],
        'High' => ['bg' => '#ffeaa7', 'color' => '#e67e22', 'icon' => '🟠'],
        'Critical' => ['bg' => '#ffebee', 'color' => '#e74c3c', 'icon' => '🔴']
    ];
    
    $config = $configs[$priority] ?? $configs['Medium'];
    return "<span class='priority-badge' style='background:{$config['bg']};color:{$config['color']};padding:4px 8px;border-radius:12px;font-size:11px;font-weight:600;border:1px solid {$config['color']}20;'>{$config['icon']} {$priority}</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - Admin Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #059669;
            --secondary-color: #047857;
            --accent-color: #10b981;
            --success-color: #22c55e;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-color: #111827;
            --light-color: #f9fafb;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Header Navigation */
        .header-nav {
            background: var(--white);
            box-shadow: var(--shadow-sm);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }

        .logo {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
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
            background: var(--primary-color);
            color: var(--white);
        }

        .nav-btn.secondary {
            background: var(--gray-100);
            color: var(--text-secondary);
        }

        .nav-btn.active {
            background: var(--primary-color);
            color: var(--white);
        }

        .nav-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--white);
            font-size: 16px;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert.success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--gray-50);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-color);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            background: var(--white);
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .filter-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: var(--white);
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-btn.primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .filter-btn.secondary {
            background: var(--gray-200);
            color: var(--text-secondary);
        }

        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Complaints Section */
        .complaints-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .complaints-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .complaints-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .complaints-count {
            background: var(--primary-color);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .complaints-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .complaints-table th,
        .complaints-table td {
            padding: 1rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .complaints-table th {
            background: var(--gray-50);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .complaints-table tr {
            transition: all 0.2s ease;
        }

        .complaints-table tbody tr:hover {
            background: var(--gray-50);
        }

        .complaint-title {
            font-weight: 600;
            color: var(--text-primary);
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .user-email {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .action-btn.view {
            background: var(--info-color);
            color: var(--white);
        }

        .action-btn.delete {
            background: var(--danger-color);
            color: var(--white);
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        /* Status Select */
        .status-select {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.75rem;
            background: var(--white);
            cursor: pointer;
            min-width: 130px;
            transition: all 0.2s ease;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-message {
            font-size: 1rem;
            line-height: 1.5;
        }

        /* Responsive Design */
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

            .admin-info {
                width: 100%;
                justify-content: space-between;
            }

            .main-container {
                padding: 1rem;
            }

            .page-header,
            .filter-section,
            .complaints-section {
                padding: 1rem;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                justify-content: stretch;
            }

            .filter-btn {
                flex: 1;
                justify-content: center;
            }

            .complaints-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .complaints-table {
                font-size: 0.875rem;
            }

            .complaints-table th,
            .complaints-table td {
                padding: 0.75rem 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
                <a href="admin_complaints.php" class="nav-btn active">
                    <i class="fas fa-clipboard-list"></i> Complaints
                </a>
                <a href="admin_users.php" class="nav-btn secondary">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="admin_messages.php" class="nav-btn secondary">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="admin_reports.php" class="nav-btn secondary">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($admin['name']); ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Administrator</div>
                </div>
                <a href="logout.php" class="nav-btn primary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <?php
        // Display success/error messages
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert success"><i class="fas fa-check-circle"></i>' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert error"><i class="fas fa-exclamation-circle"></i>' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-clipboard-list"></i>
                Complaint Management
            </h1>
            <p class="page-subtitle">Monitor, filter, and manage all student complaints from a centralized dashboard</p>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Complaints
            </div>
            <form method="GET" id="filterForm">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Search by title, description, or user..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>⏳ Pending</option>
                            <option value="in_progress" <?php echo ($status_filter == 'in_progress') ? 'selected' : ''; ?>>🔄 In Progress</option>
                            <option value="resolved" <?php echo ($status_filter == 'resolved') ? 'selected' : ''; ?>>✅ Resolved</option>
                            <option value="closed" <?php echo ($status_filter == 'closed') ? 'selected' : ''; ?>>🔒 Closed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Priority</label>
                        <select name="priority">
                            <option value="">All Priorities</option>
                            <?php foreach ($priorities as $priority): ?>
                                <option value="<?php echo htmlspecialchars($priority['priority']); ?>" 
                                        <?php echo ($priority_filter == $priority['priority']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($priority['priority']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category']); ?>" 
                                        <?php echo ($category_filter == $category['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <a href="admin_complaints.php" class="filter-btn secondary">
                        <i class="fas fa-undo"></i> Clear Filters
                    </a>
                    <button type="submit" class="filter-btn primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Complaints Section -->
        <div class="complaints-section">
            <div class="complaints-header">
                <h2 class="complaints-title">All Complaints</h2>
                <div class="complaints-count">
                    <?php echo count($complaints); ?> complaint<?php echo count($complaints) != 1 ? 's' : ''; ?> found
                </div>
            </div>

            <?php if (count($complaints) > 0): ?>
                <div class="table-container">
                    <table class="complaints-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Category</th>
                                <th>Submitted</th>
                                <th>Quick Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><strong>#<?php echo $complaint['id']; ?></strong></td>
                                <td class="complaint-title" title="<?php echo htmlspecialchars($complaint['title']); ?>">
                                    <?php echo htmlspecialchars($complaint['title']); ?>
                                </td>
                                <td class="user-info">
                                    <div class="user-name"><?php echo htmlspecialchars($complaint['user_name'] ?: 'Unknown User'); ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($complaint['user_email'] ?: 'No Email'); ?></div>
                                </td>
                                <td><?php echo statusBadge($complaint['status']); ?></td>
                                <td><?php echo priorityBadge($complaint['priority']); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', $complaint['category'])); ?></td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        <?php echo date('M j, Y', strtotime($complaint['created_at'])); ?><br>
                                        <span style="color: var(--text-secondary);"><?php echo date('H:i', strtotime($complaint['created_at'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" class="status-form" onsubmit="return handleStatusSubmit(this)">
                                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                        <input type="hidden" name="action" value="status_update">
                                        <select name="new_status" class="status-select" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $complaint['status'] == 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                            <option value="in_progress" <?php echo $complaint['status'] == 'in_progress' ? 'selected' : ''; ?>>🔄 In Progress</option>
                                            <option value="resolved" <?php echo $complaint['status'] == 'resolved' ? 'selected' : ''; ?>>✅ Resolved</option>
                                            <option value="closed" <?php echo $complaint['status'] == 'closed' ? 'selected' : ''; ?>>🔒 Closed</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="admin_view_complaint.php?id=<?php echo $complaint['id']; ?>" class="action-btn view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button type="button" class="action-btn delete" 
                                                onclick="confirmDelete(<?php echo $complaint['id']; ?>, '<?php echo addslashes(htmlspecialchars($complaint['title'])); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <div class="empty-title">
                        <?php if ($status_filter || $priority_filter || $category_filter || $search): ?>
                            No complaints match your filters
                        <?php else: ?>
                            No complaints found
                        <?php endif; ?>
                    </div>
                    <div class="empty-message">
                        <?php if ($status_filter || $priority_filter || $category_filter || $search): ?>
                            Try adjusting your filter criteria to see more results.
                        <?php else: ?>
                            When students submit complaints, they will appear here for review and management.
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="complaint_id" id="deleteComplaintId">
        <input type="hidden" name="action" value="delete">
    </form>

    <script>
        // Handle status form submission
        function handleStatusSubmit(form) {
            const select = form.querySelector('select');
            select.disabled = true;
            select.style.opacity = '0.6';
            return true;
        }

        // Delete confirmation function
        function confirmDelete(complaintId, complaintTitle) {
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to permanently delete complaint:<br><strong>"${complaintTitle}"</strong><br><br>This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Delete It!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the complaint.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit delete form
                    document.getElementById('deleteComplaintId').value = complaintId;
                    document.getElementById('deleteForm').submit();
                }
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        console.log('📋 Admin Complaints Management - FIXED Version Ready!');
        console.log('📊 Total Complaints:', <?php echo $stats['total']; ?>);
        console.log('⏰ Current Time:', '2025-07-25 16:06:57');
        console.log('👤 Admin:', '<?php echo addslashes($admin['name']); ?>');
        console.log('✅ mysqli_stmt::bind_param() reference issue FIXED!');
    </script>
</body>
</html>