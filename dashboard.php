<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if database connection file exists
if (!file_exists('db.php')) {
    die('Database connection file (db.php) not found!');
}

require 'db.php';

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize variables to prevent undefined variable errors
$user = null;
$stats = [
    'total_complaints' => 0,
    'pending_complaints' => 0,
    'in_progress_complaints' => 0,
    'resolved_complaints' => 0,
    'closed_complaints' => 0
];
$recent_complaints = [];

try {
    // Fetch user info and stats
    $user_id = $_SESSION['user_id'];
    
    // Check if users table exists and get user info
    $stmt = $conn->prepare("SELECT name, email, created_at, last_login FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare user query: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception('User not found in database');
    }

    // Check if complaints table exists before querying
    $table_check = $conn->query("SHOW TABLES LIKE 'complaints'");
    if ($table_check->num_rows > 0) {
        // Get complaint statistics
        $stmt = $conn->prepare("SELECT 
            COUNT(*) as total_complaints,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_complaints,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_complaints,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_complaints,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_complaints
            FROM complaints WHERE user_id = ?");
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats_result = $result->fetch_assoc();
            if ($stats_result) {
                $stats = $stats_result;
            }
        }

        // Get recent complaints
        $stmt = $conn->prepare("SELECT id, title, status, created_at FROM complaints WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $recent_complaints = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

} catch (Exception $e) {
    // Log error and continue with default values
    error_log('Dashboard Error: ' . $e->getMessage());
    
    // Set default user if not found
    if (!$user) {
        $user = [
            'name' => 'SakkuruBhomic',
            'email' => 'user@example.com',
            'created_at' => '2025-07-22 15:41:14',
            'last_login' => null
        ];
    }
}

// Get announcement messages from database
$announcement_messages = [];
$announcements_enabled = false;

try {
    // Check if system_settings table exists
    $settings_check = $conn->query("SHOW TABLES LIKE 'system_settings'");
    if ($settings_check && $settings_check->num_rows > 0) {
        // Check if announcements are enabled
        $enabled_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'announcements_enabled'");
        if ($enabled_result && $enabled_result->num_rows > 0) {
            $enabled_data = $enabled_result->fetch_assoc();
            $announcements_enabled = $enabled_data['setting_value'] === '1';
        }

        // Get announcement messages if enabled
        if ($announcements_enabled) {
            $messages_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'announcement_messages'");
            if ($messages_result && $messages_result->num_rows > 0) {
                $messages_data = $messages_result->fetch_assoc();
                $decoded_messages = json_decode($messages_data['setting_value'], true);
                if (is_array($decoded_messages)) {
                    $announcement_messages = array_filter($decoded_messages); // Remove empty messages
                }
            }
        }
    }
} catch (Exception $e) {
    error_log('Announcement fetch error: ' . $e->getMessage());
}

// Default fallback messages if none configured and announcements are enabled
if (empty($announcement_messages) && $announcements_enabled) {
    $announcement_messages = [
        "Welcome to the PM Shri KV Bhandup Portal!",
        "New features added: Enhanced complaint tracking and notifications!"
    ];
}

// Get system announcements (static)
$announcements = [
    [
        'title' => 'Welcome to the Portal',
        'message' => 'Submit and track your complaints easily through our new and improved system.',
        'type' => 'success',
        'date' => '2025-07-20'
    ],
    [
        'title' => 'Portal Guidelines',
        'message' => 'Please provide detailed information when submitting complaints for faster resolution.',
        'type' => 'info',
        'date' => '2025-07-19'
    ]
];

// Ensure stats are integers
foreach ($stats as $key => $value) {
    $stats[$key] = (int)$value;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PM Shri KV Bhandup Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #059669; --secondary-color: #047857; --accent-color: #10b981;
            --success-color: #22c55e; --warning-color: #f59e0b; --danger-color: #ef4444; --info-color: #3b82f6;
            --text-primary: #111827; --text-secondary: #6b7280; --border-color: #e5e7eb;
            --gray-50: #f9fafb; --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--gray-50); color: var(--text-primary); line-height: 1.6; }

        /* Header */
        .header { background: var(--white); box-shadow: var(--shadow-sm); position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--border-color); }
        .header-content { max-width: 1280px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; }
        .logo { font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 600; color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 0.75rem; }
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--white); font-size: 16px; }
        .logout-btn { background: var(--gray-100); color: var(--text-secondary); padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.2s ease; display: flex; align-items: center; gap: 0.5rem; }
        .logout-btn:hover { background: var(--gray-200); color: var(--text-primary); }

        /* Main Content */
        .main-container { max-width: 1280px; margin: 0 auto; padding: 2rem; }
        .section { background: var(--white); border-radius: 16px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow); border: 1px solid var(--border-color); }
        .section-title { font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        
        /* Announcement Banner */
        .announcement-banner { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: var(--white); padding: 1rem 1.5rem; border-radius: 12px; display: flex; align-items: center; gap: 1rem; overflow: hidden; }
        .announcement-icon { font-size: 1.25rem; }
        .announcement-content { flex: 1; overflow: hidden; position: relative; height: 1.5rem; }
        .announcement-slider { display: flex; position: absolute; animation: slide 30s linear infinite; white-space: nowrap; }
        .announcement-text { padding-right: 4rem; font-weight: 500; }
        @keyframes slide { 0% { transform: translateX(0%); } 100% { transform: translateX(-100%); } }

        /* Welcome Section */
        .welcome-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .welcome-title { font-family: 'Poppins', sans-serif; font-size: 2rem; font-weight: 700; }
        .current-time { background: var(--gray-100); color: var(--text-secondary); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; }
        .user-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .detail-card { background: var(--gray-50); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); }
        .detail-label { color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.25rem; }
        .detail-value { font-weight: 600; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        .stat-card { background: var(--gray-50); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; position: relative; overflow: hidden; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--accent-color); }
        .stat-card.total::before { background: var(--info-color); }
        .stat-card.pending::before { background: var(--warning-color); }
        .stat-card.progress::before { background: #3b82f6; }
        .stat-card.resolved::before { background: var(--success-color); }
        .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .stat-title { font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; }
        .stat-icon { font-size: 1.25rem; color: var(--accent-color); }
        .stat-icon.total { color: var(--info-color); }
        .stat-icon.pending { color: var(--warning-color); }
        .stat-icon.progress { color: #3b82f6; }
        .stat-icon.resolved { color: var(--success-color); }
        .stat-number { font-size: 2rem; font-weight: 700; color: var(--text-primary); }

        /* Action Cards */
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .action-card { background: var(--gray-50); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; text-decoration: none; color: inherit; transition: all 0.3s ease; display: flex; flex-direction: column; }
        .action-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary-color); }
        .action-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .action-icon { width: 3rem; height: 3rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: var(--white); }
        .action-title { font-size: 1.125rem; font-weight: 600; color: var(--text-primary); }
        .action-description { color: var(--text-secondary); font-size: 0.875rem; flex-grow: 1; }
        .notification-dot { position: absolute; top: -5px; right: -5px; width: 10px; height: 10px; background: var(--danger-color); border-radius: 50%; border: 2px solid var(--white); }

        /* Recent Activity & Announcements */
        .activity-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .recent-item, .announcement-item { background: var(--gray-50); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fffbeb; color: #b45309; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #dcfce7; color: #166534; }
        .recent-title { font-weight: 600; }
        .recent-date { font-size: 0.875rem; color: var(--text-secondary); }
        .announcement-type { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .type-success { background: #dcfce7; color: #166534; }
        .type-info { background: #dbeafe; color: #1e40af; }

        @media (max-width: 768px) {
            .header-content { padding: 1rem; }
            .main-container { padding: 1rem; }
            .welcome-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .activity-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="#" class="logo"><i class="fas fa-graduation-cap"></i> PM Shri KV Bhandup</a>
            <div class="user-menu">
                <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user['name']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <main class="main-container">
        <!-- Announcement Banner -->
        <?php if ($announcements_enabled && !empty($announcement_messages)): ?>
        <div class="announcement-banner section">
            <i class="fas fa-bullhorn announcement-icon"></i>
            <div class="announcement-content">
                <div class="announcement-slider">
                    <?php foreach ($announcement_messages as $message): ?>
                        <span class="announcement-text"><?php echo htmlspecialchars($message); ?></span>
                    <?php endforeach; ?>
                    <!-- Duplicate for seamless loop -->
                    <?php foreach ($announcement_messages as $message): ?>
                        <span class="announcement-text"><?php echo htmlspecialchars($message); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <section class="section">
            <div class="welcome-header">
                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
                <div class="current-time" id="currentTime">📅 Loading...</div>
            </div>
            <div class="user-details">
                <div class="detail-card">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-label">Member Since</div>
                    <div class="detail-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                </div>
                <div class="detail-card">
                    <div class="detail-label">Last Login</div>
                    <div class="detail-value"><?php echo $user['last_login'] ? date('M j, Y - g:i A', strtotime($user['last_login'])) : 'First time login'; ?></div>
                </div>
            </div>
        </section>

        <!-- Statistics Cards -->
        <section class="section">
            <h2 class="section-title"><i class="fas fa-chart-pie"></i> Your Complaint Summary</h2>
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-header"><div class="stat-title">Total Complaints</div><i class="fas fa-list-ol stat-icon total"></i></div>
                    <div class="stat-number"><?php echo $stats['total_complaints']; ?></div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-header"><div class="stat-title">Pending Review</div><i class="fas fa-hourglass-half stat-icon pending"></i></div>
                    <div class="stat-number"><?php echo $stats['pending_complaints']; ?></div>
                </div>
                <div class="stat-card progress">
                    <div class="stat-header"><div class="stat-title">In Progress</div><i class="fas fa-sync-alt stat-icon progress"></i></div>
                    <div class="stat-number"><?php echo $stats['in_progress_complaints']; ?></div>
                </div>
                <div class="stat-card resolved">
                    <div class="stat-header"><div class="stat-title">Resolved</div><i class="fas fa-check-circle stat-icon resolved"></i></div>
                    <div class="stat-number"><?php echo $stats['resolved_complaints']; ?></div>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="section">
            <h2 class="section-title"><i class="fas fa-rocket"></i> Quick Actions</h2>
            <div class="actions-grid">
                <a href="submit_complaint.php" class="action-card">
                    <div class="action-header"><div class="action-icon"><i class="fas fa-plus"></i></div><h3 class="action-title">Submit New Complaint</h3></div>
                    <p class="action-description">Report a new issue or concern that needs attention.</p>
                </a>
                <a href="my_complaints.php" class="action-card">
                    <div class="action-header"><div class="action-icon"><i class="fas fa-list-check"></i></div><h3 class="action-title">View My Complaints</h3></div>
                    <p class="action-description">Track the status and progress of all your submitted complaints.</p>
                </a>
                <a href="profile.php" class="action-card">
                    <div class="action-header"><div class="action-icon"><i class="fas fa-user-edit"></i></div><h3 class="action-title">Edit Profile</h3></div>
                    <p class="action-description">Update your personal information and account settings.</p>
                </a>
            </div>
        </section>

        <div class="activity-grid">
            <!-- Recent Activity -->
            <section class="section">
                <h2 class="section-title"><i class="fas fa-history"></i> Recent Activity</h2>
                <?php if (!empty($recent_complaints)): ?>
                    <?php foreach ($recent_complaints as $complaint): ?>
                    <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" style="text-decoration:none; color:inherit;">
                        <div class="recent-item">
                            <span class="status-badge status-<?php echo $complaint['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?></span>
                            <div>
                                <div class="recent-title"><?php echo htmlspecialchars($complaint['title']); ?></div>
                                <div class="recent-date">Submitted on <?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-secondary">No recent complaints to show. <a href="submit_complaint.php">Submit one now!</a></p>
                <?php endif; ?>
            </section>

            <!-- System Announcements -->
            <section class="section">
                <h2 class="section-title"><i class="fas fa-bullhorn"></i> System Announcements</h2>
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-item">
                    <span class="announcement-type type-<?php echo $announcement['type']; ?>"><?php echo $announcement['type']; ?></span>
                    <div>
                        <div class="recent-title"><?php echo $announcement['title']; ?></div>
                        <div class="recent-date"><?php echo date('M j, Y', strtotime($announcement['date'])); ?></div>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem;"><?php echo $announcement['message']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
        </div>
    </main>

    <script>
        function updateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            document.getElementById('currentTime').textContent = '📅 ' + now.toLocaleDateString('en-US', options);
        }
        updateTime();
        setInterval(updateTime, 1000);

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.section, .action-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>