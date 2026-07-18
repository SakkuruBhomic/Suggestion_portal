<?php
// audit_logger.php - Comprehensive audit logging system

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

function logAuthAction($conn, $admin_id, $admin_name, $action, $details = '') {
    $action_type = $action === 'login' ? 'ADMIN_LOGIN' : 'ADMIN_LOGOUT';
    $description = $action === 'login' ? 'Admin logged into system' : 'Admin logged out of system';
    
    if ($details) {
        $description .= " - $details";
    }
    
    logAdminAction($conn, $admin_id, $admin_name, $action_type, $description, 'auth', $admin_id, $details);
}

function logPageAccess($conn, $admin_id, $admin_name, $page_name, $page_description = '') {
    $description = "Accessed admin page: $page_name";
    if ($page_description) {
        $description .= " - $page_description";
    }
    
    logAdminAction($conn, $admin_id, $admin_name, 'PAGE_ACCESS', $description, 'page', null, $page_name);
}

function logUserAction($conn, $admin_id, $admin_name, $action, $target_user_id, $target_user_name, $target_user_email, $details = '') {
    $action_types = [
        'create' => 'USER_CREATED',
        'update' => 'USER_UPDATED', 
        'delete' => 'USER_DELETED',
        'terminate' => 'ACCOUNT_TERMINATION',
        'view' => 'USER_VIEWED'
    ];
    
    $action_type = $action_types[$action] ?? 'USER_ACTION';
    
    $descriptions = [
        'create' => "Created new user account: $target_user_name",
        'update' => "Updated user account: $target_user_name",
        'delete' => "Deleted user account: $target_user_name",
        'terminate' => "Terminated user account: $target_user_name",
        'view' => "Viewed user details: $target_user_name"
    ];
    
    $description = $descriptions[$action] ?? "Performed action on user: $target_user_name";
    
    if ($details) {
        $description .= " - $details";
    }
    
    $target_details = json_encode([
        'user_name' => $target_user_name,
        'user_email' => $target_user_email,
        'action_details' => $details
    ]);
    
    logAdminAction($conn, $admin_id, $admin_name, $action_type, $description, 'user', $target_user_id, $target_details);
}

function logMessageAction($conn, $admin_id, $admin_name, $action, $message_id, $message_subject, $sender_email, $details = '') {
    $action_types = [
        'view' => 'MESSAGE_VIEWED',
        'reply' => 'MESSAGE_REPLIED',
        'mark_read' => 'MESSAGE_MARKED_READ',
        'mark_unread' => 'MESSAGE_MARKED_UNREAD',
        'archive' => 'MESSAGE_ARCHIVED',
        'delete' => 'MESSAGE_DELETED'
    ];
    
    $action_type = $action_types[$action] ?? 'MESSAGE_ACTION';
    
    $descriptions = [
        'view' => "Viewed message: $message_subject",
        'reply' => "Replied to message from $sender_email",
        'mark_read' => "Marked message as read: $message_subject",
        'mark_unread' => "Marked message as unread: $message_subject", 
        'archive' => "Archived message: $message_subject",
        'delete' => "Deleted message: $message_subject"
    ];
    
    $description = $descriptions[$action] ?? "Performed action on message: $message_subject";
    
    if ($details) {
        $description .= " - $details";
    }
    
    $target_details = json_encode([
        'message_subject' => $message_subject,
        'sender_email' => $sender_email,
        'action_details' => $details
    ]);
    
    logAdminAction($conn, $admin_id, $admin_name, $action_type, $description, 'message', $message_id, $target_details);
}

function logLoginAttempt($conn, $email, $attempt_type = 'user', $success = false, $failure_reason = '') {
    try {
        // Create login_attempts table if it doesn't exist
        $conn->query("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255),
                ip_address VARCHAR(45),
                user_agent TEXT,
                attempt_type ENUM('admin', 'user') DEFAULT 'user',
                success BOOLEAN DEFAULT FALSE,
                failure_reason VARCHAR(255),
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(email),
                INDEX(ip_address),
                INDEX(attempted_at)
            )
        ");

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $conn->prepare("
            INSERT INTO login_attempts 
            (email, ip_address, user_agent, attempt_type, success, failure_reason) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("ssssbs", $email, $ip_address, $user_agent, $attempt_type, $success, $failure_reason);
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log("Login attempt logging failed: " . $e->getMessage());
        return false;
    }
}
?>