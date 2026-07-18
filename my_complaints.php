<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if database connection exists
if (!file_exists('db.php')) {
    die('Database connection file not found!');
}

require 'db.php';

// Initialize variables
$user = null;
$stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
$complaints = [];

try {
    // Fetch user info for display
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare user query: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception('User not found');
    }

    // Check if complaints table exists and what columns it has
    $table_check = $conn->query("SHOW TABLES LIKE 'complaints'");
    if ($table_check->num_rows == 0) {
        throw new Exception('Complaints table does not exist');
    }

    // Check what columns exist in complaints table
    $columns_result = $conn->query("SHOW COLUMNS FROM complaints");
    $available_columns = [];
    while ($column = $columns_result->fetch_assoc()) {
        $available_columns[] = $column['Field'];
    }

    // Check if priority column exists
    $has_priority = in_array('priority', $available_columns);
    
    // Get filter parameters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
    $priority_filter = $has_priority && isset($_GET['priority']) ? $_GET['priority'] : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Build query with filters
    $where_conditions = ["user_id = ?"];
    $params = [$user_id];
    $param_types = "i";

    if ($status_filter) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
        $param_types .= "s";
    }

    if ($category_filter) {
        $where_conditions[] = "category = ?";
        $params[] = $category_filter;
        $param_types .= "s";
    }

    if ($has_priority && $priority_filter) {
        $where_conditions[] = "priority = ?";
        $params[] = $priority_filter;
        $param_types .= "s";
    }

    if ($search) {
        if (in_array('description', $available_columns)) {
            $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $param_types .= "ss";
        } else {
            $where_conditions[] = "title LIKE ?";
            $search_param = "%$search%";
            $params[] = $search_param;
            $param_types .= "s";
        }
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Build SELECT query based on available columns
    $select_columns = "id, title, category, status, created_at";
    if ($has_priority) {
        $select_columns .= ", priority";
    }
    if (in_array('updated_at', $available_columns)) {
        $select_columns .= ", updated_at";
    }
    if (in_array('description', $available_columns)) {
        $select_columns .= ", description";
    }

    // Fetch complaints with filters
    $sql = "SELECT $select_columns FROM complaints WHERE $where_clause ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare complaints query: ' . $conn->error);
    }
    
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Set default values for missing columns
        if (!isset($row['priority'])) {
            $row['priority'] = 'Medium';
        }
        if (!isset($row['updated_at'])) {
            $row['updated_at'] = null;
        }
        if (!isset($row['description'])) {
            $row['description'] = 'No description available';
        }
        $complaints[] = $row;
    }

    // Get statistics
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
        FROM complaints WHERE user_id = ?";
    $stmt = $conn->prepare($stats_sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats_result = $result->fetch_assoc();
        if ($stats_result) {
            $stats = $stats_result;
        }
    }

} catch (Exception $e) {
    // Log error and set default values
    error_log('My Complaints Error: ' . $e->getMessage());
    
    if (!$user) {
        $user = [
            'name' => 'User',
            'email' => 'user@example.com'
        ];
    }
    
    $error_message = $e->getMessage();
}

// Status color and icon map
$status_config = [
    'pending' => ['color' => '#f39c12', 'bg' => '#fff3e0', 'icon' => '⏳', 'label' => 'Pending Review'],
    'in_progress' => ['color' => '#3498db', 'bg' => '#e3f2fd', 'icon' => '🔄', 'label' => 'In Progress'],
    'resolved' => ['color' => '#27ae60', 'bg' => '#e8f5e8', 'icon' => '✅', 'label' => 'Resolved'],
    'closed' => ['color' => '#7b1fa2', 'bg' => '#f3e5f5', 'icon' => '🔒', 'label' => 'Closed'],
];

$priority_config = [
    'Low' => ['color' => '#27ae60', 'bg' => '#e8f5e8', 'icon' => '🟢'],
    'Medium' => ['color' => '#f39c12', 'bg' => '#fff3e0', 'icon' => '🟡'],
    'High' => ['color' => '#e67e22', 'bg' => '#ffeaa7', 'icon' => '🟠'],
    'Critical' => ['color' => '#e74c3c', 'bg' => '#ffebee', 'icon' => '🔴'],
];

function getStatusBadge($status, $config) {
    $status_info = $config[strtolower($status)] ?? $config['pending'];
    return "<span class='status-badge' style='background: {$status_info['bg']}; color: {$status_info['color']}; border: 1px solid {$status_info['color']}20;'>{$status_info['icon']} {$status_info['label']}</span>";
}

function getPriorityBadge($priority, $config) {
    $priority_info = $config[$priority] ?? $config['Medium'];
    return "<span class='priority-badge' style='background: {$priority_info['bg']}; color: {$priority_info['color']}; border: 1px solid {$priority_info['color']}20;'>{$priority_info['icon']} {$priority}</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - KV Bhandup Portal</title>
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
            max-width: 1280px;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-secondary);
        }

        .user-avatar {
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

        .back-btn {
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

        .back-btn:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 1.125rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Error Display */
        .error-container {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--danger-color);
        }

        .error-title {
            color: var(--danger-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .error-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-btn.primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .error-btn.secondary {
            background: var(--gray-100);
            color: var(--text-primary);
        }

        .error-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Stats Section */
        .stats-container {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Complaints List */
        .complaints-container {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .complaints-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .complaints-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .complaint-card {
            background: var(--gray-50);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .complaint-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: var(--white);
        }

        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .complaint-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1.125rem;
            line-height: 1.4;
        }

        .complaint-id {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .complaint-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            font-size: 0.875rem;
            flex-wrap: wrap;
        }

        .complaint-description {
            color: var(--text-primary);
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .complaint-actions {
            display: flex;
            justify-content: flex-end;
        }

        .view-btn {
            background: var(--primary-color);
            color: var(--white);
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

        .view-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
        }

        /* Badge Styles */
        .status-badge,
        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-description {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
            }

            .main-container {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .complaints-header {
                flex-direction: column;
                align-items: start;
                gap: 1rem;
            }

            .complaint-header {
                flex-direction: column;
                align-items: start;
            }

            .complaint-meta {
                flex-direction: column;
                align-items: start;
                gap: 0.5rem;
            }

            .error-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-comments"></i>
                KV Bhandup Portal
            </a>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div style="font-size: 0.75rem; opacity: 0.8;"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">My Complaints</h1>
            <p class="page-subtitle">
                Track and manage all your submitted complaints in one place.
            </p>
        </div>

        <?php if (isset($error_message)): ?>
            <!-- Error Display -->
            <div class="error-container">
                <div class="error-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Database Issue Detected
                </div>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?><br><br>
                    This usually happens when the complaints table structure needs to be updated or created.
                </div>
                <div class="error-actions">
                    <a href="dashboard.php" class="error-btn primary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <a href="submit_complaint.php" class="error-btn secondary">
                        <i class="fas fa-plus"></i>
                        Submit New Complaint
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Complaints</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['resolved']; ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                </div>
            </div>

            <!-- Complaints List -->
            <div class="complaints-container">
                <div class="complaints-header">
                    <h2 class="complaints-title">
                        <i class="fas fa-list"></i>
                        Your Complaints
                    </h2>
                    <a href="submit_complaint.php" class="submit-btn">
                        <i class="fas fa-plus"></i>
                        Submit New Complaint
                    </a>
                </div>

                <?php if (count($complaints) > 0): ?>
                    <?php foreach ($complaints as $complaint): ?>
                    <div class="complaint-card">
                        <div class="complaint-header">
                            <div>
                                <div class="complaint-title">
                                    <span class="complaint-id">#<?php echo $complaint['id']; ?></span> - 
                                    <?php echo htmlspecialchars($complaint['title']); ?>
                                </div>
                            </div>
                            <?php echo getStatusBadge($complaint['status'], $status_config); ?>
                        </div>
                        
                        <div class="complaint-meta">
                            <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($complaint['category']); ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></span>
                            <?php if (isset($has_priority) && $has_priority): ?>
                                <span><?php echo getPriorityBadge($complaint['priority'], $priority_config); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($complaint['description']) && $complaint['description'] != 'No description available'): ?>
                        <div class="complaint-description">
                            <?php echo htmlspecialchars(substr($complaint['description'], 0, 150)); ?>
                            <?php if (strlen($complaint['description']) > 150): ?>...<?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="complaint-actions">
                            <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="view-btn">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="empty-title">No Complaints Yet</div>
                        <div class="empty-description">
                            You haven't submitted any complaints yet.<br>
                            Start by reporting an issue or concern that needs attention.
                        </div>
                        <a href="submit_complaint.php" class="submit-btn">
                            <i class="fas fa-plus"></i>
                            Submit Your First Complaint
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states for links
            const links = document.querySelectorAll('a[href]');
            links.forEach(link => {
                link.addEventListener('click', function() {
                    if (this.href.includes('view_complaint.php') || this.href.includes('submit_complaint.php')) {
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.className = 'fas fa-spinner fa-spin';
                        }
                    }
                });
            });

            // Add hover effects to complaint cards
            const cards = document.querySelectorAll('.complaint-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.borderColor = 'var(--primary-color)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.borderColor = 'var(--border-color)';
                });
            });

            console.log('📋 My Complaints Page Enhanced and Ready!');
            console.log('📅 Current Date: 2025-08-06 13:24:34 UTC');
            console.log('👤 Current User: SakkuruBhomic');
            console.log('📊 Total Complaints: <?php echo $stats['total']; ?>');
        });
    </script>
</body>
</html>