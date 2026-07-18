<?php
session_start();
require 'db.php';

// Check if this is an admin logout
$is_admin_logout = false;
$admin_info = null;

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    $is_admin_logout = true;
    
    // Get admin info before destroying session
    $admin_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $admin_stmt->bind_param("i", $_SESSION['admin_id']);
    $admin_stmt->execute();
    $admin_info = $admin_stmt->get_result()->fetch_assoc();
}

// Check if regular user is logged in
$is_user_logout = false;
$user_info = null;

if (isset($_SESSION['user_id'])) {
    $is_user_logout = true;
    
    // Get user info before destroying session
    $user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_info = $user_stmt->get_result()->fetch_assoc();
}

// Function to log admin actions
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
                INDEX(action_type)
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

// Log the logout actions
if ($is_admin_logout && $admin_info) {
    // Calculate session duration
    $session_start_time = $_SESSION['login_time'] ?? time();
    $session_duration = time() - $session_start_time;
    $duration_formatted = gmdate("H:i:s", $session_duration);
    
    $logout_details = json_encode([
        'logout_method' => 'manual',
        'session_duration' => $duration_formatted,
        'logout_time' => date('Y-m-d H:i:s'),
        'admin_email' => $admin_info['email']
    ]);
    
    logAdminAction(
        $conn, 
        $_SESSION['admin_id'], 
        $admin_info['name'], 
        'ADMIN_LOGOUT', 
        'Admin logged out of system - Session duration: ' . $duration_formatted,
        'auth',
        $_SESSION['admin_id'],
        $logout_details
    );
    
    // Update last_logout time in users table
    $logout_stmt = $conn->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
    $logout_stmt->bind_param("i", $_SESSION['admin_id']);
    $logout_stmt->execute();
}

if ($is_user_logout && $user_info) {
    // Log regular user logout (optional - you can create a separate user_activity_log table)
    $user_logout_details = json_encode([
        'logout_method' => 'manual',
        'logout_time' => date('Y-m-d H:i:s'),
        'user_email' => $user_info['email']
    ]);
    
    // You can log user activities too if needed
    // For now, just update last_logout
    $logout_stmt = $conn->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
    $logout_stmt->bind_param("i", $_SESSION['user_id']);
    $logout_stmt->execute();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect based on who logged out
if ($is_admin_logout) {
    header('Location: admin.php?logout=success');
} else {
    header('Location: login.php?logout=success');
}
exit;
?>