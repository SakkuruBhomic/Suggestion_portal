<?php
session_start();
require 'db.php';

// Only allow admins
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

// Fetch admin info
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Initialize arrays to prevent errors
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'closed' => 0
];
$recent_complaints = [];
$messages = [];
$user_stats = ['total_users' => 0, 'new_today' => 0, 'active_users' => 0];
$priority_stats = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];

try {
    // Complaint stats (all users)
    $res = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status='closed' THEN 1 ELSE 0 END) as closed
        FROM complaints");
    if ($res) $stats = $res->fetch_assoc();

    // User statistics
    $res = $conn->query("SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today,
        SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_users
        FROM users WHERE id != $admin_id");
    if ($res) $user_stats = $res->fetch_assoc();

    // Priority statistics
    $res = $conn->query("SELECT 
        SUM(CASE WHEN priority='Low' THEN 1 ELSE 0 END) as low,
        SUM(CASE WHEN priority='Medium' THEN 1 ELSE 0 END) as medium,
        SUM(CASE WHEN priority='High' THEN 1 ELSE 0 END) as high,
        SUM(CASE WHEN priority='Critical' THEN 1 ELSE 0 END) as critical
        FROM complaints");
    if ($res) $priority_stats = $res->fetch_assoc();

    // Recent complaints (all users)
    $res = $conn->query("SELECT c.id, c.title, c.status, c.priority, c.category, c.created_at, u.name as user_name 
        FROM complaints c 
        LEFT JOIN users u ON c.user_id = u.id 
        ORDER BY c.created_at DESC LIMIT 5");
    if ($res) $recent_complaints = $res->fetch_all(MYSQLI_ASSOC);

    // Fetch contact messages for support
    $res = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
    if ($res) $messages = $res->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    // Handle database errors gracefully
    error_log('Admin Dashboard Error: ' . $e->getMessage());
}

// Handle quick actions
if ($_POST && isset($_POST['action'])) {
    $complaint_id = (int)$_POST['complaint_id'];
    $new_status = $_POST['new_status'];
    
    if (in_array($new_status, ['pending', 'in_progress', 'resolved', 'closed'])) {
        $stmt = $conn->prepare("UPDATE complaints SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $new_status, $complaint_id);
        $stmt->execute();
        
        // Refresh page to show updated data
        header('Location: admin_dashboard.php');
        exit;
    }
}

function statusBadge($status) {
    $configs = [
        'pending' => ['bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳'],
        'in_progress' => ['bg' => '#e3f2fd', 'color' => '#1976d2', 'icon' => '🔄'],
        'resolved' => ['bg' => '#e8f5e8', 'color' => '#388e3c', 'icon' => '✅'],
        'closed' => ['bg' => '#f3e5f5', 'color' => '#7b1fa2', 'icon' => '🔒']
    ];
    
    $config = $configs[strtolower($status)] ?? $configs['pending'];
    return "<span style='background:{$config['bg']};color:{$config['color']};padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;text-transform:uppercase;border:1px solid {$config['color']}20;'>{$config['icon']} {$status}</span>";
}

function priorityBadge($priority) {
    $configs = [
        'Low' => ['bg' => '#e8f5e8', 'color' => '#388e3c', 'icon' => '🟢'],
        'Medium' => ['bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '🟡'],
        'High' => ['bg' => '#ffeaa7', 'color' => '#e67e22', 'icon' => '🟠'],
        'Critical' => ['bg' => '#ffebee', 'color' => '#e74c3c', 'icon' => '🔴']
    ];
    
    $config = $configs[$priority] ?? $configs['Medium'];
    return "<span style='background:{$config['bg']};color:{$config['color']};padding:4px 8px;border-radius:12px;font-size:11px;font-weight:600;border:1px solid {$config['color']}20;'>{$config['icon']} {$priority}</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KV Bhandup Portal</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--gray-50);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: var(--white);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }

        .header-content {
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

        .logo i {
            font-size: 1.75rem;
            color: var(--accent-color);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-secondary);
        }

        .admin-avatar {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--white);
            font-size: 1rem;
        }

        .nav-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .nav-btn.logout {
            background: var(--danger-color);
            border-color: var(--danger-color);
            color: var(--white);
        }

        .nav-btn.logout:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Welcome Section */
        .welcome-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color), var(--primary-color));
        }

        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .welcome-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .current-time {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: var(--shadow-md);
        }

        .welcome-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .welcome-stat {
            text-align: center;
            padding: 1.5rem;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .welcome-stat:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: var(--white);
        }

        .welcome-stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .welcome-stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Statistics Container */
        .stats-container {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--gray-50);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-color);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            background: var(--white);
        }

        .stat-card.complaints::before { background: var(--primary-color); }
        .stat-card.pending::before { background: var(--warning-color); }
        .stat-card.progress::before { background: var(--info-color); }
        .stat-card.resolved::before { background: var(--success-color); }
        .stat-card.users::before { background: var(--accent-color); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--white);
            background: var(--primary-color);
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .stat-trend {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .trend-up {
            background: #f0fdf4;
            color: var(--success-color);
        }

        .trend-down {
            background: #fef2f2;
            color: var(--danger-color);
        }

        /* Priority Chart */
        .priority-chart {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            text-align: center;
        }

        .priority-bars {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .priority-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .priority-label {
            width: 80px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .priority-fill {
            flex: 1;
            height: 1.5rem;
            background: var(--gray-200);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .priority-fill-bar {
            height: 100%;
            border-radius: 8px;
            transition: width 1s ease;
        }

        .priority-low { background: var(--success-color); }
        .priority-medium { background: var(--warning-color); }
        .priority-high { background: #f97316; }
        .priority-critical { background: var(--danger-color); }

        .priority-count {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            min-width: 30px;
        }

        /* Quick Actions */
        .quick-actions {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-card {
            background: var(--gray-50);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            display: block;
            text-align: center;
        }

        .action-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-md);
            background: var(--white);
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            display: block;
            color: var(--primary-color);
        }

        .action-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .action-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Data Tables */
        .data-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .data-table th,
        .data-table td {
            padding: 1rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--gray-50);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tr {
            transition: all 0.2s ease;
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        .complaint-title {
            color: var(--text-primary);
            font-weight: 600;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-name {
            color: var(--primary-color);
            font-weight: 500;
        }

        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--primary-color);
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .action-link:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        /* Quick Status Update */
        .status-select {
            padding: 0.25rem 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.875rem;
            background: var(--white);
            cursor: pointer;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-300);
        }

        .empty-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-message {
            font-size: 0.875rem;
            line-height: 1.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .main-container {
                padding: 1rem;
            }

            .welcome-section,
            .stats-container,
            .quick-actions,
            .data-section {
                padding: 1.5rem;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .welcome-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .nav-buttons {
                flex-direction: column;
                width: 100%;
            }

            .data-table {
                font-size: 0.875rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.75rem 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }

            .welcome-stats {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--gray-300);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="admin_dashboard.php" class="logo">
                <i class="fas fa-shield-alt"></i>
                KV Bhandup Admin
            </a>
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($admin['name']); ?></div>
                    <div style="font-size: 0.75rem; opacity: 0.8;">Administrator</div>
                </div>
                <div class="nav-buttons">
                    <a href="admin_complaints.php" class="nav-btn">
                        <i class="fas fa-clipboard-list"></i>
                        Complaints
                    </a>
                    <a href="admin_messages.php" class="nav-btn">
                        <i class="fas fa-envelope"></i>
                        Messages
                    </a>
                    <a href="admin_users.php" class="nav-btn">
                        <i class="fas fa-users"></i>
                        Users
                    </a>
                    <a href="logout.php" class="nav-btn logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-header">
                <h1 class="welcome-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Admin Dashboard
                </h1>
                <div class="current-time" id="currentTime">
                    <i class="fas fa-clock"></i>
                    Loading...
                </div>
            </div>
            
            <div class="welcome-stats">
                <div class="welcome-stat">
                    <div class="welcome-stat-number"><?php echo $user_stats['total_users']; ?></div>
                    <div class="welcome-stat-label">Total Users</div>
                </div>
                <div class="welcome-stat">
                    <div class="welcome-stat-number"><?php echo $user_stats['new_today']; ?></div>
                    <div class="welcome-stat-label">New Today</div>
                </div>
                <div class="welcome-stat">
                    <div class="welcome-stat-number"><?php echo $user_stats['active_users']; ?></div>
                    <div class="welcome-stat-label">Active Users</div>
                </div>
                <div class="welcome-stat">
                    <div class="welcome-stat-number"><?php echo date('H:i'); ?></div>
                    <div class="welcome-stat-label">Current Time</div>
                </div>
            </div>
        </div>

        <!-- Statistics Container -->
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-chart-bar"></i>
                Complaint Statistics
            </h2>
            <div class="stats-grid">
                <div class="stat-card complaints">
                    <div class="stat-header">
                        <div class="stat-title">Total Complaints</div>
                        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                    </div>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-description">All time submissions</div>
                    <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> Active system</div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-header">
                        <div class="stat-title">Pending Review</div>
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    </div>
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-description">Awaiting attention</div>
                    <?php if ($stats['pending'] > 0): ?>
                        <div class="stat-trend trend-up"><i class="fas fa-exclamation"></i> Needs attention</div>
                    <?php else: ?>
                        <div class="stat-trend trend-down"><i class="fas fa-check"></i> All caught up</div>
                    <?php endif; ?>
                </div>

                <div class="stat-card progress">
                    <div class="stat-header">
                        <div class="stat-title">In Progress</div>
                        <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                    </div>
                    <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-description">Being processed</div>
                    <div class="stat-trend trend-up"><i class="fas fa-cog"></i> Active work</div>
                </div>

                <div class="stat-card resolved">
                    <div class="stat-header">
                        <div class="stat-title">Resolved</div>
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <div class="stat-number"><?php echo $stats['resolved']; ?></div>
                    <div class="stat-description">Successfully completed</div>
                    <div class="stat-trend trend-up"><i class="fas fa-trophy"></i> Great work</div>
                </div>

                <div class="stat-card users">
                    <div class="stat-header">
                        <div class="stat-title">Resolution Rate</div>
                        <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    </div>
                    <div class="stat-number">
                        <?php 
                        $total = $stats['total'];
                        $resolved = $stats['resolved'] + $stats['closed'];
                        $rate = $total > 0 ? round(($resolved / $total) * 100) : 0;
                        echo $rate . '%';
                        ?>
                    </div>
                    <div class="stat-description">Overall success rate</div>
                    <div class="stat-trend <?php echo $rate >= 70 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="fas fa-<?php echo $rate >= 70 ? 'thumbs-up' : 'exclamation-triangle'; ?>"></i>
                        <?php echo $rate >= 70 ? 'Excellent' : 'Needs improvement'; ?>
                    </div>
                </div>
            </div>

            <!-- Priority Distribution Chart -->
            <div class="priority-chart">
                <div class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Priority Distribution
                </div>
                <div class="priority-bars">
                    <div class="priority-bar">
                        <div class="priority-label">🟢 Low</div>
                        <div class="priority-fill">
                            <div class="priority-fill-bar priority-low" style="width: <?php echo $stats['total'] > 0 ? ($priority_stats['low'] / $stats['total']) * 100 : 0; ?>%"></div>
                        </div>
                        <div class="priority-count"><?php echo $priority_stats['low']; ?></div>
                    </div>
                    <div class="priority-bar">
                        <div class="priority-label">🟡 Medium</div>
                        <div class="priority-fill">
                            <div class="priority-fill-bar priority-medium" style="width: <?php echo $stats['total'] > 0 ? ($priority_stats['medium'] / $stats['total']) * 100 : 0; ?>%"></div>
                        </div>
                        <div class="priority-count"><?php echo $priority_stats['medium']; ?></div>
                    </div>
                    <div class="priority-bar">
                        <div class="priority-label">🟠 High</div>
                        <div class="priority-fill">
                            <div class="priority-fill-bar priority-high" style="width: <?php echo $stats['total'] > 0 ? ($priority_stats['high'] / $stats['total']) * 100 : 0; ?>%"></div>
                        </div>
                        <div class="priority-count"><?php echo $priority_stats['high']; ?></div>
                    </div>
                    <div class="priority-bar">
                        <div class="priority-label">🔴 Critical</div>
                        <div class="priority-fill">
                            <div class="priority-fill-bar priority-critical" style="width: <?php echo $stats['total'] > 0 ? ($priority_stats['critical'] / $stats['total']) * 100 : 0; ?>%"></div>
                        </div>
                        <div class="priority-count"><?php echo $priority_stats['critical']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h2>
            <div class="actions-grid">
                <a href="admin_complaints.php" class="action-card">
                    <i class="fas fa-clipboard-list action-icon"></i>
                    <div class="action-title">Manage Complaints</div>
                    <div class="action-description">View and process all complaints</div>
                </a>
                <a href="admin_messages.php" class="action-card">
                    <i class="fas fa-envelope action-icon"></i>
                    <div class="action-title">Contact Messages</div>
                    <div class="action-description">Review support inquiries</div>
                </a>
                <a href="admin_users.php" class="action-card">
                    <i class="fas fa-users action-icon"></i>
                    <div class="action-title">User Management</div>
                    <div class="action-description">Manage user accounts</div>
                </a>
                <a href="admin_reports.php" class="action-card">
                    <i class="fas fa-chart-line action-icon"></i>
                    <div class="action-title">Reports & Analytics</div>
                    <div class="action-description">View detailed reports</div>
                </a>
                <a href="admin_settings.php" class="action-card">
                    <i class="fas fa-cog action-icon"></i>
                    <div class="action-title">System Settings</div>
                    <div class="action-description">Configure portal settings</div>
                </a>
            </div>
        </div>

        <!-- Recent Complaints -->
        <div class="data-section">
            <h2 class="section-title">
                <i class="fas fa-file-alt"></i>
                Latest Complaints
            </h2>
            <?php if (count($recent_complaints) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Title</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Category</th>
                                <th>Submitted</th>
                                <th>Quick Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_complaints as $complaint): ?>
                            <tr>
                                <td><strong>#<?php echo $complaint['id']; ?></strong></td>
                                <td class="complaint-title" title="<?php echo htmlspecialchars($complaint['title']); ?>">
                                    <?php echo htmlspecialchars($complaint['title']); ?>
                                </td>
                                <td class="user-name"><?php echo htmlspecialchars($complaint['user_name']); ?></td>
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
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                        <input type="hidden" name="action" value="status_update">
                                        <select name="new_status" class="status-select" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $complaint['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="in_progress" <?php echo $complaint['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="resolved" <?php echo $complaint['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            <option value="closed" <?php echo $complaint['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <a href="admin_view_complaint.php?id=<?php echo $complaint['id']; ?>" class="action-link">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="empty-title">No complaints submitted yet</div>
                    <div class="empty-message">Complaints from users will appear here when they're submitted.</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Contact Messages -->
        <div class="data-section">
            <h2 class="section-title">
                <i class="fas fa-envelope-open"></i>
                Latest Contact Messages
            </h2>
            <?php if (count($messages) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message Preview</th>
                                <th>Received</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><strong>#<?php echo $msg['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color: var(--primary-color); text-decoration: none;">
                                        <?php echo htmlspecialchars($msg['email']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                <td class="complaint-title" title="<?php echo htmlspecialchars($msg['message']); ?>">
                                    <?php echo htmlspecialchars(substr($msg['message'], 0, 50)); ?>
                                    <?php if (strlen($msg['message']) > 50): ?>...<?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        <?php echo date('M j, Y', strtotime($msg['created_at'])); ?><br>
                                        <span style="color: var(--text-secondary);"><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <a href="admin_messages.php#msg-<?php echo $msg['id']; ?>" class="action-link">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-envelope"></i></div>
                    <div class="empty-title">No contact messages yet</div>
                    <div class="empty-message">Contact messages from users will appear here when they're submitted.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Real-time clock
            function updateTime() {
                const now = new Date();
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZoneName: 'short'
                };
                document.getElementById('currentTime').innerHTML = 
                    '<i class="fas fa-clock"></i> ' + now.toLocaleDateString('en-US', options);
            }

            updateTime();
            setInterval(updateTime, 1000);

            // Animate priority bars on load
            setTimeout(() => {
                document.querySelectorAll('.priority-fill-bar').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            }, 500);

            // Add loading states for form submissions
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const select = this.querySelector('select');
                    if (select) {
                        select.disabled = true;
                        select.style.opacity = '0.6';
                    }
                });
            });

            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.borderColor = 'var(--primary-color)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.borderColor = 'var(--border-color)';
                });
            });

            console.log('🛡️ KV Bhandup Admin Dashboard Ready!');
            console.log('📅 Current Date: 2025-08-06 13:41:05 UTC');
            console.log('👤 Current Admin: SakkuruBhomic');
        });
    </script>
</body>
</html>