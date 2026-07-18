<?php
session_start();
require 'db.php';

// Admin authentication check
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

// --- AUDIT LOGGING FUNCTIONS ---
function logAdminAction($conn, $admin_id, $admin_name, $action_type, $description, $target_type = null, $target_id = null, $target_details = null) {
    try {
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

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $conn->prepare("
            INSERT INTO audit_log 
            (admin_id, admin_name, action_type, action_description, target_type, target_id, target_details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("issssisss", $admin_id, $admin_name, $action_type, $description, $target_type, $target_id, $target_details, $ip_address, $user_agent);
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

function logPageAccess($conn, $admin_id, $admin_name, $page_name, $page_description = '') {
    $description = "Accessed admin page: $page_name";
    if ($page_description) {
        $description .= " - $page_description";
    }
    
    logAdminAction($conn, $admin_id, $admin_name, 'PAGE_ACCESS', $description, 'page', null, $page_name);
}

function logSettingChange($conn, $admin_id, $admin_name, $setting_key, $action, $old_value = '', $new_value = '', $details = '') {
    $action_types = [
        'create' => 'SETTING_CREATED',
        'update' => 'SETTING_UPDATED',
        'toggle' => 'SETTING_TOGGLED',
        'delete' => 'SETTING_DELETED'
    ];
    
    $action_type = $action_types[$action] ?? 'SETTING_MODIFIED';
    
    $descriptions = [
        'create' => "Created new setting: $setting_key",
        'update' => "Updated setting: $setting_key",
        'toggle' => "Toggled setting: $setting_key",
        'delete' => "Deleted setting: $setting_key"
    ];
    
    $description = $descriptions[$action] ?? "Modified setting: $setting_key";
    
    if ($details) {
        $description .= " - $details";
    }
    
    $target_details = json_encode([
        'setting_key' => $setting_key,
        'action' => $action,
        'old_value' => $old_value,
        'new_value' => $new_value,
        'change_details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    logAdminAction($conn, $admin_id, $admin_name, $action_type, $description, 'setting', null, $target_details);
}

// Log page access
logPageAccess($conn, $_SESSION['admin_id'], $admin_name, 'admin_settings.php', 'System settings configuration');

// Create settings table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_description TEXT,
        updated_by VARCHAR(100),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(setting_key)
    )
");

// Insert default announcement messages if they don't exist
$default_messages = [
    "🎉 Welcome to KV Alumni Portal - Your gateway to staying connected!",
    "📢 New features added: Enhanced complaint tracking and notifications!",
    "✨ System improvements: Faster response times and better user experience!",
    "🔧 Maintenance scheduled: We're constantly improving your portal experience!",
    "📱 Mobile-friendly updates: Access your portal seamlessly on any device!"
];

$check_messages = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'announcement_messages'");
if ($check_messages->num_rows == 0) {
    $messages_json = json_encode($default_messages);
    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_description, updated_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", 
        $key = 'announcement_messages',
        $messages_json,
        $desc = 'Running announcement messages displayed on dashboard',
        $admin_name
    );
    $stmt->execute();
    
    // Log the creation of default messages
    logSettingChange($conn, $_SESSION['admin_id'], $admin_name, 'announcement_messages', 'create', '', $messages_json, 'Default announcement messages created');
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_messages') {
            // Get old messages for audit
            $old_messages_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'announcement_messages'");
            $old_messages = '';
            if ($old_messages_result && $old_messages_result->num_rows > 0) {
                $old_data = $old_messages_result->fetch_assoc();
                $old_messages = $old_data['setting_value'];
            }
            
            $messages = [];
            for ($i = 1; $i <= 10; $i++) {
                $message = trim($_POST["message_$i"] ?? '');
                if (!empty($message)) {
                    $messages[] = $message;
                }
            }
            
            if (!empty($messages)) {
                $messages_json = json_encode($messages);
                $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = 'announcement_messages'");
                $stmt->bind_param("ss", $messages_json, $admin_name);
                
                if ($stmt->execute()) {
                    // Log the message update
                    $change_details = "Updated " . count($messages) . " announcement messages. New messages: " . implode('; ', array_slice($messages, 0, 3)) . (count($messages) > 3 ? '...' : '');
                    logSettingChange($conn, $_SESSION['admin_id'], $admin_name, 'announcement_messages', 'update', $old_messages, $messages_json, $change_details);
                    
                    $success_message = "✅ Announcement messages updated successfully!";
                } else {
                    $error_message = "❌ Failed to update messages. Please try again.";
                }
            } else {
                $error_message = "❌ Please enter at least one message.";
            }
        }
        
        if ($_POST['action'] === 'toggle_announcements') {
            $enabled = isset($_POST['announcements_enabled']) ? '1' : '0';
            
            // Get old value for audit
            $old_enabled_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'announcements_enabled'");
            $old_enabled = '';
            if ($old_enabled_result && $old_enabled_result->num_rows > 0) {
                $old_data = $old_enabled_result->fetch_assoc();
                $old_enabled = $old_data['setting_value'];
            }
            
            // Check if setting exists
            $check = $conn->query("SELECT id FROM system_settings WHERE setting_key = 'announcements_enabled'");
            if ($check->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = 'announcements_enabled'");
                $stmt->bind_param("ss", $enabled, $admin_name);
                $action = 'update';
            } else {
                $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_description, updated_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", 
                    $key = 'announcements_enabled',
                    $enabled,
                    $desc = 'Enable/disable running announcements on dashboard',
                    $admin_name
                );
                $action = 'create';
            }
            
            if ($stmt->execute()) {
                $status = $enabled ? 'enabled' : 'disabled';
                $status_details = "Running announcements " . ($enabled ? 'enabled' : 'disabled') . " for all users";
                
                // Log the toggle action
                logSettingChange($conn, $_SESSION['admin_id'], $admin_name, 'announcements_enabled', 'toggle', $old_enabled, $enabled, $status_details);
                
                $success_message = "✅ Running announcements $status successfully!";
            } else {
                $error_message = "❌ Failed to update announcement status.";
            }
        }
    }
}

// Get current settings
$current_messages = [];
$announcements_enabled = true;

$messages_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'announcement_messages'");
if ($messages_result && $messages_result->num_rows > 0) {
    $messages_data = $messages_result->fetch_assoc();
    $current_messages = json_decode($messages_data['setting_value'], true) ?: [];
}

$enabled_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'announcements_enabled'");
if ($enabled_result && $enabled_result->num_rows > 0) {
    $enabled_data = $enabled_result->fetch_assoc();
    $announcements_enabled = $enabled_data['setting_value'] === '1';
}

// Ensure we have at least 5 empty slots for editing, but can be more
$message_slots = max(5, count($current_messages) + 1);
while (count($current_messages) < $message_slots) {
    $current_messages[] = '';
}

// Get recent setting changes for audit display
$recent_changes = [];
$recent_result = $conn->query("
    SELECT 
        admin_name,
        action_type,
        action_description,
        target_details,
        created_at
    FROM audit_log 
    WHERE action_type LIKE 'SETTING_%' 
    ORDER BY created_at DESC 
    LIMIT 10
");

if ($recent_result) {
    $recent_changes = $recent_result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Settings - KV Alumni Portal</title>
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
            max-width: 1400px;
            margin: 2rem auto;
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

        .admin-info {
            background: linear-gradient(135deg, var(--info) 0%, var(--primary) 100%);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .current-time {
            background: linear-gradient(135deg, var(--success) 0%, #38a169 100%);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
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

        .alert-success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-danger {
            background: #fff5f5;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        .btn-success {
            background: var(--success);
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-secondary {
            background: var(--secondary);
        }

        .btn-secondary:hover {
            background: #4a5568;
        }

        .message-input {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .message-number {
            background: var(--primary);
            color: var(--white);
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .preview-section {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%);
            border: 2px solid var(--primary);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .preview-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .announcement-preview {
            background: var(--primary);
            color: var(--white);
            padding: 12px 0;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            height: 50px;
            display: flex;
            align-items: center;
        }

        .announcement-content {
            display: flex;
            animation: scroll-left 30s linear infinite;
            white-space: nowrap;
        }

        .announcement-item {
            padding: 0 50px;
            font-weight: 500;
        }

        @keyframes scroll-left {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-100%); }
        }

        .audit-section {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .audit-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .audit-item:last-child {
            border-bottom: none;
        }

        .audit-icon {
            background: linear-gradient(135deg, var(--info) 0%, var(--primary) 100%);
            color: var(--white);
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        .audit-content {
            flex: 1;
        }

        .audit-description {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .audit-details {
            font-size: 0.875rem;
            color: var(--secondary);
        }

        .audit-time {
            font-size: 0.75rem;
            color: var(--secondary);
            text-align: right;
        }

        .navigation {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
            margin-top: 2rem;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }

            .header {
                text-align: center;
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .message-input {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1><i class="fas fa-cogs"></i> System Settings</h1>
                <div class="current-time">
                    <i class="fas fa-clock"></i> 
                    <span id="currentDateTime"><?php echo date('Y-m-d H:i:s'); ?> UTC</span>
                </div>
            </div>
            <div class="admin-info">
                <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Announcement Settings -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bullhorn"></i> Running Announcements Settings
            </div>
            <div class="card-body">
                <!-- Enable/Disable Toggle -->
                <form method="POST" style="margin-bottom: 2rem;">
                    <input type="hidden" name="action" value="toggle_announcements">
                    <div class="form-group">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <label class="toggle-switch">
                                <input type="checkbox" name="announcements_enabled" <?php echo $announcements_enabled ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span class="slider"></span>
                            </label>
                            <span style="font-weight: 600; color: var(--dark);">
                                Enable Running Announcements on Dashboard
                            </span>
                        </div>
                        <small style="color: var(--secondary); margin-top: 0.5rem; display: block;">
                            Toggle this to show/hide the announcement banner on the user dashboard
                        </small>
                    </div>
                </form>

                <!-- Messages Configuration -->
                <form method="POST">
                    <input type="hidden" name="action" value="update_messages">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-edit"></i> Announcement Messages
                        </label>
                        <p style="color: var(--secondary); margin-bottom: 1rem;">
                            Enter up to 10 messages that will rotate in the announcement banner. Leave fields empty to remove messages.
                        </p>
                        
                        <?php for ($i = 0; $i < 10; $i++): ?>
                            <div class="message-input">
                                <div class="message-number"><?php echo $i + 1; ?></div>
                                <input 
                                    type="text" 
                                    name="message_<?php echo $i + 1; ?>" 
                                    class="form-control" 
                                    placeholder="Enter announcement message..." 
                                    value="<?php echo htmlspecialchars($current_messages[$i] ?? ''); ?>"
                                    maxlength="200"
                                >
                            </div>
                        <?php endfor; ?>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Messages
                    </button>
                </form>

                <!-- Preview Section -->
                <?php if (!empty(array_filter($current_messages)) && $announcements_enabled): ?>
                <div class="preview-section">
                    <div class="preview-title">
                        <i class="fas fa-eye"></i> Live Preview
                    </div>
                    <div class="announcement-preview">
                        <div class="announcement-content">
                            <?php foreach (array_filter($current_messages) as $message): ?>
                                <span class="announcement-item"><?php echo htmlspecialchars($message); ?></span>
                            <?php endforeach; ?>
                            <!-- Duplicate for seamless loop -->
                            <?php foreach (array_filter($current_messages) as $message): ?>
                                <span class="announcement-item"><?php echo htmlspecialchars($message); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <small style="color: var(--secondary); margin-top: 0.5rem; display: block;">
                        This is how the announcement banner will appear on the user dashboard
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Setting Changes (Audit Trail) -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Recent Setting Changes (Audit Trail)
            </div>
            <div class="card-body">
                <?php if (!empty($recent_changes)): ?>
                    <div class="audit-section">
                        <?php foreach ($recent_changes as $change): ?>
                            <div class="audit-item">
                                <div class="audit-icon">
                                    <?php
                                    $icon = 'fas fa-cog';
                                    if (strpos($change['action_type'], 'CREATED') !== false) $icon = 'fas fa-plus-circle';
                                    elseif (strpos($change['action_type'], 'UPDATED') !== false) $icon = 'fas fa-edit';
                                    elseif (strpos($change['action_type'], 'TOGGLED') !== false) $icon = 'fas fa-toggle-on';
                                    elseif (strpos($change['action_type'], 'DELETED') !== false) $icon = 'fas fa-trash';
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div class="audit-content">
                                    <div class="audit-description">
                                        <?php echo htmlspecialchars($change['action_description']); ?>
                                    </div>
                                    <div class="audit-details">
                                        By: <strong><?php echo htmlspecialchars($change['admin_name']); ?></strong>
                                        <?php
                                        if ($change['target_details']) {
                                            $details = json_decode($change['target_details'], true);
                                            if ($details && isset($details['change_details'])) {
                                                echo " - " . htmlspecialchars($details['change_details']);
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="audit-time">
                                    <div><?php echo date('M j, Y', strtotime($change['created_at'])); ?></div>
                                    <div><?php echo date('H:i:s', strtotime($change['created_at'])); ?> UTC</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="admin_reports.php" class="btn btn-secondary">
                            <i class="fas fa-chart-line"></i> View Complete Audit Log
                        </a>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: var(--secondary);">
                        <i class="fas fa-history" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                        No recent setting changes found
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation -->
        <div class="navigation">
            <div class="nav-buttons">
                <a href="admin_dashboard.php" class="btn">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="admin_users.php" class="btn">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="admin_messages.php" class="btn">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="admin_reports.php" class="btn">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
                <a href="logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <script>
        // Update current time display
        function updateTime() {
            const now = new Date();
            const utcTime = now.toISOString().slice(0, 19).replace('T', ' ');
            document.getElementById('currentDateTime').textContent = utcTime + ' UTC';
        }

        updateTime();
        setInterval(updateTime, 1000);

        // Auto-save indication
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input[type="text"]');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.style.borderColor = '#ed8936';
                    this.style.backgroundColor = '#fffaf0';
                    
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = setTimeout(() => {
                        this.style.borderColor = '#e2e8f0';
                        this.style.backgroundColor = '#ffffff';
                    }, 2000);
                });
            });
        });

        // Confirm toggle changes
        const toggleInput = document.querySelector('input[name="announcements_enabled"]');
        if (toggleInput) {
            toggleInput.form.addEventListener('submit', function(e) {
                const status = toggleInput.checked ? 'enable' : 'disable';
                if (!confirm(`Are you sure you want to ${status} running announcements for all users?`)) {
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>