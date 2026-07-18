<?php
session_start();
require 'db.php';

// Check admin authentication
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

// Handle user actions
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    // Prevent admin from terminating their own account
    if ($user_id == $_SESSION['admin_id']) {
        $message = "❌ You cannot terminate your own admin account!";
        $message_type = 'error';
    } else {
        if ($action === 'terminate') {
            // Get user info before termination
            $user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_info = $user_stmt->get_result()->fetch_assoc();
            
            if ($user_info) {
                // Log the termination action
                $log_stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, admin_name, action_type, target_user_id, target_user_name, target_user_email, action_timestamp, details) VALUES (?, ?, 'ACCOUNT_TERMINATION', ?, ?, ?, NOW(), ?)");
                $details = "Account terminated by admin";
                $log_stmt->bind_param("isisss", $_SESSION['admin_id'], $admin_name, $user_id, $user_info['name'], $user_info['email'], $details);
                $log_stmt->execute();
                
                // Delete the user account
                $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $delete_stmt->bind_param("i", $user_id);
                
                if ($delete_stmt->execute()) {
                    $message = "✅ User account '" . htmlspecialchars($user_info['name']) . "' has been permanently terminated.";
                    $message_type = 'success';
                } else {
                    $message = "❌ Failed to terminate user account. Please try again.";
                    $message_type = 'error';
                }
            } else {
                $message = "❌ User not found.";
                $message_type = 'error';
            }
        }
    }
}

// Get all users with statistics
$res = $conn->query("
    SELECT 
        id, 
        name, 
        email, 
        created_at, 
        last_login,
        CASE 
            WHEN last_login IS NULL THEN 'Never logged in'
            WHEN last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Inactive (30+ days)'
            WHEN last_login < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Inactive (7+ days)'
            ELSE 'Active'
        END as status
    FROM users 
    ORDER BY created_at DESC
");
$users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN last_login IS NULL THEN 1 END) as never_logged,
        COUNT(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as inactive_30,
        COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_7
    FROM users
")->fetch_assoc();

// Create admin_actions table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS admin_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        admin_name VARCHAR(100) NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        target_user_id INT,
        target_user_name VARCHAR(100),
        target_user_email VARCHAR(100),
        action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        details TEXT,
        INDEX(admin_id),
        INDEX(action_timestamp)
    )
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - User Management</title>
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

        .container { 
            max-width: 1200px;
            margin: 2rem auto;
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .admin-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            display: inline-block;
            margin-top: 1rem;
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
            background: #f8fafc;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }

        .stat-label {
            color: var(--secondary);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .content {
            padding: 2rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert.success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert.error {
            background: #fff5f5;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        .table-container {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        table { 
            width: 100%; 
            border-collapse: collapse;
        }

        th, td { 
            padding: 1rem; 
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        th { 
            background: #f8fafc; 
            color: var(--primary); 
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tbody tr:hover {
            background: #f8fafc;
            transition: background 0.2s ease;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-active {
            background: #f0fff4;
            color: #22543d;
        }

        .status-inactive {
            background: #fffaf0;
            color: #744210;
        }

        .status-never {
            background: #fff5f5;
            color: #742a2a;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #e53e3e;
            transform: translateY(-1px);
        }

        .btn-danger:disabled {
            background: #cbd5e0;
            color: #a0aec0;
            cursor: not-allowed;
            transform: none;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: var(--white);
            margin: 10% auto;
            padding: 0;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: var(--danger);
            color: var(--white);
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: var(--secondary);
            color: var(--white);
        }

        .btn-secondary:hover {
            background: #4a5568;
        }

        .warning-box {
            background: #fffaf0;
            border: 1px solid #feb2b2;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .warning-box strong {
            color: var(--danger);
        }

        .navigation {
            padding: 1rem 2rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .nav-btn {
            background: var(--primary);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 0.5rem;
        }

        .nav-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                padding: 1rem;
            }

            .content {
                padding: 1rem;
            }

            table {
                font-size: 0.875rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p>Manage user accounts and monitor activity</p>
            <div class="admin-info">
                <i class="fas fa-user-shield"></i> Logged in as: <strong><?php echo htmlspecialchars($admin_name); ?></strong>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total_users']; ?></span>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['active_7']; ?></span>
                <div class="stat-label">Active (7 days)</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['inactive_30']; ?></span>
                <div class="stat-label">Inactive (30+ days)</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['never_logged']; ?></span>
                <div class="stat-label">Never Logged In</div>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> Name</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-calendar"></i> Joined</th>
                            <th><i class="fas fa-clock"></i> Last Login</th>
                            <th><i class="fas fa-flag"></i> Status</th>
                            <th><i class="fas fa-cog"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--secondary);">
                                <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                                No users found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong>#<?php echo $u['id']; ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <?php if ($u['id'] == $_SESSION['admin_id']): ?>
                                            <i class="fas fa-crown" style="color: var(--warning);" title="Admin"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($u['name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ($u['last_login']): ?>
                                        <?php echo date('M j, Y H:i', strtotime($u['last_login'])); ?>
                                    <?php else: ?>
                                        <span style="color: var(--secondary);">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = 'status-active';
                                    if (strpos($u['status'], 'Never') !== false) {
                                        $statusClass = 'status-never';
                                    } elseif (strpos($u['status'], 'Inactive') !== false) {
                                        $statusClass = 'status-inactive';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $u['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['id'] == $_SESSION['admin_id']): ?>
                                        <button class="btn btn-danger" disabled>
                                            <i class="fas fa-shield-alt"></i> Protected
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-danger" onclick="confirmTermination(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>', '<?php echo htmlspecialchars(addslashes($u['email'])); ?>')">
                                            <i class="fas fa-user-times"></i> Terminate
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="navigation">
            <a href="admin_dashboard.php" class="nav-btn">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
            <a href="admin_messages.php" class="nav-btn">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="logout.php" class="nav-btn" style="background: var(--secondary);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Termination Confirmation Modal -->
    <div id="terminationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Account Termination
                </h3>
            </div>
            <div class="modal-body">
                <div class="warning-box">
                    <strong><i class="fas fa-warning"></i> WARNING:</strong> This action is irreversible and will permanently delete the user account.
                </div>
                
                <p>Are you sure you want to terminate the following user account?</p>
                
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                    <p><strong>Name:</strong> <span id="terminate-name"></span></p>
                    <p><strong>Email:</strong> <span id="terminate-email"></span></p>
                </div>
                
                <p><strong>This will:</strong></p>
                <ul style="margin: 1rem 0; padding-left: 2rem; color: var(--danger);">
                    <li>Permanently delete the user account</li>
                    <li>Remove all associated data</li>
                    <li>Force the user to register again</li>
                    <li>Log this action for audit purposes</li>
                </ul>
                
                <p style="color: var(--secondary); font-size: 0.875rem; margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i> This action will be logged with your admin account for security purposes.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="terminate">
                    <input type="hidden" name="user_id" id="terminate-user-id">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-user-times"></i> Terminate Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('terminationModal');

        function confirmTermination(userId, userName, userEmail) {
            document.getElementById('terminate-user-id').value = userId;
            document.getElementById('terminate-name').textContent = userName;
            document.getElementById('terminate-email').textContent = userEmail;
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>