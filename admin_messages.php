<?php
// --- SETUP AND DEBUGGING ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

// --- PHPMailer Setup ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

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

function logMessageAction($conn, $admin_id, $admin_name, $action, $message_id, $message_subject, $sender_email, $details = '') {
    $action_types = [
        'view' => 'MESSAGE_VIEWED',
        'reply' => 'MESSAGE_REPLIED',
        'mark_read' => 'MESSAGE_MARKED_READ',
        'mark_unread' => 'MESSAGE_MARKED_UNREAD',
        'archive' => 'MESSAGE_ARCHIVED',
        'delete' => 'MESSAGE_DELETED',
        'reset_table' => 'MESSAGES_TABLE_RESET'
    ];
    
    $action_type = $action_types[$action] ?? 'MESSAGE_ACTION';
    
    $descriptions = [
        'view' => "Viewed message: $message_subject",
        'reply' => "Replied to message from $sender_email",
        'mark_read' => "Marked message as read: $message_subject",
        'mark_unread' => "Marked message as unread: $message_subject", 
        'archive' => "Archived message: $message_subject",
        'delete' => "Deleted message: $message_subject",
        'reset_table' => "Reset all contact messages table data"
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

// --- ADMIN SESSION CHECK ---
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

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
$admin_name = $admin['name'];

// Log page access
logPageAccess($conn, $admin_id, $admin_name, 'admin_messages.php', 'Contact messages management');

// --- HANDLE RESET ACTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_table') {
    // Get count of messages before reset for audit
    $count_result = $conn->query("SELECT COUNT(*) as total FROM contact_messages");
    $message_count = $count_result ? $count_result->fetch_assoc()['total'] : 0;
    
    // Reset the table
    $conn->query("TRUNCATE TABLE contact_messages");
    
    // Log the reset action
    logMessageAction($conn, $admin_id, $admin_name, 'reset_table', 0, 'All Messages', 'System', "Deleted $message_count messages from database");
    
    $_SESSION['alert_success'] = "🗑️ Contact messages table has been reset successfully. $message_count messages were deleted.";
    header('Location: admin_messages.php');
    exit;
}

// --- FORM ACTION HANDLING (WITH PHPMailer) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['message_id'])) {
    $message_id = (int)$_POST['message_id'];
    $action = $_POST['action'];
    $sql = '';

    if ($action === 'reply') {
        $reply_text = trim($_POST['reply_text'] ?? '');
        $user_email = trim($_POST['user_email'] ?? '');
        $user_name = trim($_POST['user_name'] ?? '');
        $original_subject = trim($_POST['original_subject'] ?? '');
        $original_message = trim($_POST['original_message'] ?? '');

        if (empty($reply_text) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['alert_error'] = "Reply text was empty or user email was invalid.";
        } else {
            // --- DYNAMIC "FROM" ADDRESS LOGIC ---
            $from_email = '';
            $from_name = '';
            $smtp_user = '';
            $smtp_pass = '';

            // Since 2FA is OFF, we use the regular login passwords.
            if (strcasecmp($admin_name, 'Bhomic') == 0 || strcasecmp($admin_name, 'SakkuruBhomic') == 0) {
                $from_email = 'bhomic@kvbalumni.me';
                $from_name = 'Bhomic';
                $smtp_user = 'bhomic@kvbalumni.me';
                $smtp_pass = 'Bhomic#2008'; 
            } elseif (strcasecmp($admin_name, 'Sanchay') == 0) {
                $from_email = 'sanchay@kvbalumni.me';
                $from_name = 'Sanchay';
                $smtp_user = 'sanchay@kvbalumni.me';
                $smtp_pass = 'Sanchay@29';
            }

            if(empty($smtp_user)) {
                 $_SESSION['alert_error'] = "Could not determine sender credentials.";
                 header('Location: admin_messages.php');
                 exit;
            }

            $mail = new PHPMailer(true);
            try {
                // --- Server settings ---
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host       = 'smtp.zoho.in'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                $mail->SMTPSecure = 'ssl'; 
                $mail->Port       = 465;

                // --- Recipients ---
                $mail->setFrom($from_email, $from_name . ' - KV Bhandup Alumni');
                $mail->addAddress($user_email, $user_name);
                $mail->addReplyTo($from_email, $from_name);

                // --- Enhanced Email Content ---
                $mail->isHTML(true);
                $mail->Subject = 'Re: ' . $original_subject;
                
                // Professional HTML email template
                $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Reply from KV Bhandup Alumni</title>
                </head>
                <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f8f9fa;'>
                    <div style='max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;'>
                        <!-- Header -->
                        <div style='background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); padding: 30px 20px; text-align: center;'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;'>
                                <span style='background: rgba(255,255,255,0.1); padding: 8px 16px; border-radius: 25px; display: inline-block;'>
                                    🎓 KV Bhandup Alumni
                                </span>
                            </h1>
                            <p style='color: #ffffff; margin: 10px 0 0 0; opacity: 0.9; font-size: 14px;'>
                                Response to your inquiry
                            </p>
                        </div>
                        
                        <!-- Content -->
                        <div style='padding: 30px 25px;'>
                            <p style='color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                                Dear <strong>" . htmlspecialchars($user_name) . "</strong>,
                            </p>
                            
                            <p style='color: #666666; font-size: 14px; line-height: 1.5; margin: 0 0 25px 0;'>
                                Thank you for reaching out to us. We have reviewed your message and are pleased to provide you with the following response:
                            </p>
                            
                            <!-- Original Message Reference -->
                            <div style='background-color: #f8f9fa; border-left: 4px solid #0d6efd; padding: 15px 20px; margin: 20px 0; border-radius: 0 8px 8px 0;'>
                                <p style='color: #666666; font-size: 12px; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;'>
                                    📋 Your Original Message
                                </p>
                                <p style='color: #333333; font-size: 14px; line-height: 1.5; margin: 0; font-style: italic;'>
                                    \"" . htmlspecialchars(strlen($original_message) > 150 ? substr($original_message, 0, 150) . '...' : $original_message) . "\"
                                </p>
                            </div>
                            
                            <!-- Reply Content -->
                            <div style='background-color: #ffffff; border: 2px solid #e9ecef; padding: 20px; margin: 25px 0; border-radius: 8px;'>
                                <p style='color: #666666; font-size: 12px; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;'>
                                    💬 Our Response
                                </p>
                                <div style='color: #333333; font-size: 15px; line-height: 1.7; margin: 0;'>
                                    " . nl2br(htmlspecialchars($reply_text)) . "
                                </div>
                            </div>
                            
                            <div style='margin: 30px 0 20px 0; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; text-align: center;'>
                                <p style='color: #666666; font-size: 14px; margin: 0 0 10px 0;'>
                                    Need further assistance? Feel free to reply to this email.
                                </p>
                                <p style='color: #0d6efd; font-size: 13px; margin: 0; font-weight: 600;'>
                                    We're here to help! 🤝
                                </p>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div style='background-color: #f8f9fa; padding: 25px; border-top: 1px solid #e9ecef;'>
                            <div style='text-align: center; margin-bottom: 15px;'>
                                <p style='color: #333333; font-size: 16px; margin: 0; font-weight: 600;'>
                                    Best regards,
                                </p>
                                <p style='color: #0d6efd; font-size: 15px; margin: 5px 0 0 0; font-weight: 600;'>
                                    " . htmlspecialchars($from_name) . "
                                </p>
                                <p style='color: #666666; font-size: 13px; margin: 2px 0 0 0;'>
                                    KV Bhandup Alumni Support Team
                                </p>
                            </div>
                            
                            <div style='border-top: 1px solid #dee2e6; padding-top: 15px; text-align: center;'>
                                <p style='color: #999999; font-size: 11px; margin: 0; line-height: 1.4;'>
                                    This email was sent from KV Bhandup Alumni Support System.<br>
                                    If you have any concerns, please contact us directly.
                                </p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>";
                
                // Plain text alternative
                $mail->AltBody = "Dear " . $user_name . ",\n\n" .
                                "Thank you for your message regarding: \"" . $original_subject . "\"\n\n" .
                                "Your original message:\n" . 
                                "\"" . (strlen($original_message) > 200 ? substr($original_message, 0, 200) . '...' : $original_message) . "\"\n\n" .
                                "Our response:\n" . $reply_text . "\n\n" .
                                "Best regards,\n" . $from_name . "\n" .
                                "KV Bhandup Alumni Support Team\n\n" .
                                "---\n" .
                                "This email was sent from KV Bhandup Alumni Support System.";

                $mail->send();
                
                $sql = "UPDATE contact_messages SET status = 'replied', updated_at = NOW() WHERE id = ?";
                
                // Log the successful reply
                $reply_details = "Reply sent to $user_name ($user_email) regarding '$original_subject'. Reply length: " . strlen($reply_text) . " characters.";
                logMessageAction($conn, $admin_id, $admin_name, 'reply', $message_id, $original_subject, $user_email, $reply_details);
                
                $_SESSION['alert_success'] = "✅ Professional reply sent successfully to " . htmlspecialchars($user_email);

            } catch (Exception $e) {
                // Log the failed reply attempt
                $error_details = "Failed to send reply to $user_name ($user_email). Error: " . $e->getMessage();
                logMessageAction($conn, $admin_id, $admin_name, 'reply', $message_id, $original_subject, $user_email, "FAILED - $error_details");
                
                $_SESSION['alert_error'] = "❌ Message could not be sent. Error: {$mail->ErrorInfo}";
            }
        }
    }
    
    // Handle other message actions
    if ($action === 'mark_read' || $action === 'mark_unread' || $action === 'archive' || $action === 'delete') {
        // Get message details for logging
        $msg_stmt = $conn->prepare("SELECT subject, email, name FROM contact_messages WHERE id = ?");
        $msg_stmt->bind_param("i", $message_id);
        $msg_stmt->execute();
        $msg_info = $msg_stmt->get_result()->fetch_assoc();
        
        if ($msg_info) {
            if ($action === 'mark_read') {
                $sql = "UPDATE contact_messages SET status = 'read', updated_at = NOW() WHERE id = ?";
                logMessageAction($conn, $admin_id, $admin_name, 'mark_read', $message_id, $msg_info['subject'], $msg_info['email']);
            } elseif ($action === 'mark_unread') {
                $sql = "UPDATE contact_messages SET status = 'unread', updated_at = NOW() WHERE id = ?";
                logMessageAction($conn, $admin_id, $admin_name, 'mark_unread', $message_id, $msg_info['subject'], $msg_info['email']);
            } elseif ($action === 'archive') {
                $sql = "UPDATE contact_messages SET status = 'archived', updated_at = NOW() WHERE id = ?";
                logMessageAction($conn, $admin_id, $admin_name, 'archive', $message_id, $msg_info['subject'], $msg_info['email']);
            } elseif ($action === 'delete') {
                $sql = "DELETE FROM contact_messages WHERE id = ?";
                logMessageAction($conn, $admin_id, $admin_name, 'delete', $message_id, $msg_info['subject'], $msg_info['email'], "Message permanently deleted");
            }
        }
    }
    
    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
    }
    header('Location: admin_messages.php');
    exit;
}

// --- HANDLE VIEW MESSAGE ACTION FOR AUDIT ---
if (isset($_GET['view_message_id'])) {
    $view_message_id = (int)$_GET['view_message_id'];
    
    // Get message details for logging
    $view_stmt = $conn->prepare("SELECT subject, email, name FROM contact_messages WHERE id = ?");
    $view_stmt->bind_param("i", $view_message_id);
    $view_stmt->execute();
    $view_info = $view_stmt->get_result()->fetch_assoc();
    
    if ($view_info) {
        logMessageAction($conn, $admin_id, $admin_name, 'view', $view_message_id, $view_info['subject'], $view_info['email'], "Opened message in modal view");
    }
}

// --- HTML AND DATA DISPLAY ---
$alert_success = $_SESSION['alert_success'] ?? null;
$alert_error = $_SESSION['alert_error'] ?? null;
unset($_SESSION['alert_success'], $_SESSION['alert_error']);

// Get message statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_messages,
        COUNT(CASE WHEN status = 'unread' THEN 1 END) as unread_messages,
        COUNT(CASE WHEN status = 'replied' THEN 1 END) as replied_messages,
        COUNT(CASE WHEN status = 'archived' THEN 1 END) as archived_messages
    FROM contact_messages
")->fetch_assoc();

$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Contact Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d6efd; --secondary: #6c757d; --success: #198754; --danger: #dc3545;
            --warning: #ffc107; --light: #f8f9fa; --dark: #212529; --info: #0dcaf0;
            --border-color: #dee2e6; --shadow: 0 .5rem 1rem rgba(0,0,0,.075);
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--light); color: var(--dark); margin: 0; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 1.5rem; }
        .card { background-color: white; border-radius: .75rem; box-shadow: var(--shadow); margin-bottom: 1.5rem; }
        .card-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); background: linear-gradient(135deg, var(--primary) 0%, #0b5ed7 100%); color: white; border-radius: .75rem .75rem 0 0; }
        .card-body { padding: 1.5rem; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: .75rem;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            color: var(--secondary);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .admin-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .admin-info {
            background: linear-gradient(135deg, var(--info) 0%, var(--primary) 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .table th { font-weight: 600; color: var(--secondary); background-color: #f8f9fa; }
        .table tbody tr:hover { background-color: #f8f9fa; transition: background-color 0.2s; }
        .badge { padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; border-radius: .25rem; }
        .badge-unread { color: #000; background-color: var(--warning); }
        .badge-replied { color: #fff; background-color: var(--success); }
        .badge-archived { color: #fff; background-color: var(--secondary); }
        .btn { padding: .5rem 1rem; font-size: .875rem; border-radius: .375rem; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: .5rem; margin: 0 .25rem; }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { background-color: #0b5ed7; transform: translateY(-1px); }
        .btn-info { background-color: var(--info); color: white; }
        .btn-info:hover { background-color: #31d2f2; transform: translateY(-1px); }
        .btn-secondary { background-color: var(--secondary); color: white; }
        .btn-secondary:hover { background-color: #5c636a; }
        .btn-danger { background-color: var(--danger); color: white; }
        .btn-danger:hover { background-color: #bb2d3b; }
        .btn-warning { background-color: var(--warning); color: black; }
        .btn-warning:hover { background-color: #ffca2c; }
        .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow-y: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { position: relative; margin: 3% auto; padding: 0; width: 90%; max-width: 700px; background-color: white; border-radius: .75rem; animation: fadeIn .3s; box-shadow: 0 1rem 3rem rgba(0,0,0,.175); }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); background: linear-gradient(135deg, var(--primary) 0%, #0b5ed7 100%); color: white; border-radius: .75rem .75rem 0 0; }
        .modal-body { padding: 1.5rem; max-height: 70vh; overflow-y: auto; }
        .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); text-align: right; background-color: #f8f9fa; border-radius: 0 0 .75rem .75rem; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-30px); } }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border: 1px solid transparent; border-radius: .375rem; }
        .alert-success { color: #0f5132; background-color: #d1e7dd; border-color: #badbcc; }
        .alert-danger { color: #842029; background-color: #f8d7da; border-color: #f5c2c7; }
        .message-details { background-color: #f8f9fa; padding: 1rem; border-radius: .5rem; margin: 1rem 0; border-left: 4px solid var(--primary); }
        .message-content { background-color: white; padding: 1rem; border-radius: .5rem; border: 1px solid var(--border-color); white-space: pre-wrap; line-height: 1.6; max-height: 200px; overflow-y: auto; }
        .close-btn { float: right; font-size: 1.5rem; cursor: pointer; color: white; opacity: 0.8; transition: opacity 0.2s; }
        .close-btn:hover { opacity: 1; }
        textarea { width: 100%; padding: .75rem; border: 1px solid var(--border-color); border-radius: .375rem; font-family: inherit; resize: vertical; }
        textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 .2rem rgba(13,110,253,.25); }
        
        .reset-section {
            background: linear-gradient(135deg, #fff5f5 0%, #fee2e2 100%);
            border: 2px solid var(--danger);
            border-radius: .75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .reset-warning {
            color: var(--danger);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_messages'] ?? 0); ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['unread_messages'] ?? 0); ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['replied_messages'] ?? 0); ?></div>
                <div class="stat-label">Replied Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['archived_messages'] ?? 0); ?></div>
                <div class="stat-label">Archived Messages</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="admin-controls">
                    <div>
                        <h1 style="margin:0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-envelope"></i> Contact Messages Management
                        </h1>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Manage and respond to contact inquiries</p>
                    </div>
                    <div class="admin-info">
                        <i class="fas fa-user-shield"></i> Logged in as: <?php echo htmlspecialchars($admin_name); ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($alert_success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $alert_success; ?></div>
                <?php endif; ?>
                <?php if ($alert_error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $alert_error; ?></div>
                <?php endif; ?>

                <!-- Reset Section -->
                <div class="reset-section">
                    <div class="reset-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Danger Zone: Reset Contact Messages Table
                    </div>
                    <p style="margin-bottom: 1rem; color: var(--dark);">
                        This action will permanently delete ALL contact messages from the database. This cannot be undone!
                    </p>
                    <button class="btn btn-danger" onclick="confirmReset()">
                        <i class="fas fa-trash-alt"></i> Reset Messages Table
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Sender</th>
                                <th><i class="fas fa-tag"></i> Subject</th>
                                <th><i class="fas fa-comment"></i> Message Snippet</th>
                                <th><i class="fas fa-flag"></i> Status</th>
                                <th><i class="fas fa-clock"></i> Received</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem; color: var(--secondary);">
                                        <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                                        No messages found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($msg['name']); ?></strong>
                                                <br><small style="color: var(--secondary);"><?php echo htmlspecialchars($msg['email']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                        <td style="max-width: 200px;"><?php echo htmlspecialchars(substr($msg['message'], 0, 80)); ?><?php echo strlen($msg['message']) > 80 ? '...' : ''; ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $msg['status'] === 'replied' ? 'badge-replied' : 
                                                    ($msg['status'] === 'archived' ? 'badge-archived' : 'badge-unread'); 
                                            ?>">
                                                <?php echo ucfirst($msg['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y, g:i a', strtotime($msg['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-info" onclick="viewMessage(<?php echo htmlspecialchars(json_encode($msg), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-primary" onclick="openReplyModal(<?php echo htmlspecialchars(json_encode($msg), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fas fa-reply"></i> Reply
                                            </button>
                                            <?php if ($msg['status'] !== 'archived'): ?>
                                                <button class="btn btn-warning" onclick="archiveMessage(<?php echo $msg['id']; ?>)">
                                                    <i class="fas fa-archive"></i> Archive
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-danger" onclick="deleteMessage(<?php echo $msg['id']; ?>, '<?php echo htmlspecialchars(addslashes($msg['subject'])); ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Message Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-envelope-open"></i> View Message
                </h2>
                <span onclick="closeViewModal()" class="close-btn">&times;</span>
            </div>
            <div class="modal-body">
                <div class="message-details">
                    <p><strong><i class="fas fa-user"></i> From:</strong> <span id="view_sender_info"></span></p>
                    <p><strong><i class="fas fa-tag"></i> Subject:</strong> <span id="view_subject_info"></span></p>
                    <p><strong><i class="fas fa-clock"></i> Received:</strong> <span id="view_date_info"></span></p>
                    <p><strong><i class="fas fa-flag"></i> Status:</strong> <span id="view_status_info"></span></p>
                </div>
                <div>
                    <h4 style="margin-bottom: 0.5rem;"><i class="fas fa-comment"></i> Full Message:</h4>
                    <div class="message-content" id="view_message_content"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="replyFromView()">
                    <i class="fas fa-reply"></i> Reply to this Message
                </button>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-reply"></i> Reply to Message
                </h2>
                <span onclick="closeReplyModal()" class="close-btn">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="message_id" id="reply_message_id">
                    <input type="hidden" name="user_email" id="reply_user_email">
                    <input type="hidden" name="user_name" id="reply_user_name">
                    <input type="hidden" name="original_subject" id="reply_original_subject">
                    <input type="hidden" name="original_message" id="reply_original_message">
                    
                    <div class="message-details">
                        <p><strong><i class="fas fa-user"></i> To:</strong> <span id="modal_user_info"></span></p>
                        <p><strong><i class="fas fa-tag"></i> Subject:</strong> Re: <span id="modal_subject_info"></span></p>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <label for="reply_text" style="font-weight:600; display: block; margin-bottom: 0.5rem;">
                            <i class="fas fa-edit"></i> Your Reply:
                        </label>
                        <textarea name="reply_text" id="reply_text" rows="8" placeholder="Type your professional response here..." required></textarea>
                        <small style="color: var(--secondary); margin-top: 0.5rem; display: block;">
                            💡 Tip: Your reply will be sent in a professional email format with KV Bhandup Alumni branding.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeReplyModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Professional Reply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--danger) 0%, #bb2d3b 100%);">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Reset
                </h2>
                <span onclick="closeResetModal()" class="close-btn">&times;</span>
            </div>
            <div class="modal-body">
                <div style="background: #fff5f5; border: 1px solid var(--danger); border-radius: .5rem; padding: 1rem; margin-bottom: 1rem;">
                    <p style="color: var(--danger); font-weight: 600; margin: 0;">
                        ⚠️ WARNING: This action is irreversible!
                    </p>
                </div>
                <p>You are about to permanently delete <strong>ALL</strong> contact messages from the database.</p>
                <p>This will:</p>
                <ul style="margin: 1rem 0; color: var(--danger);">
                    <li>Delete all contact messages permanently</li>
                    <li>Clear the entire messages table</li>
                    <li>Cannot be undone</li>
                    <li>Log this action in the audit trail</li>
                </ul>
                <p><strong>Are you absolutely sure you want to proceed?</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeResetModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="reset_table">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Yes, Reset All Messages
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const replyModal = document.getElementById('replyModal');
        const viewModal = document.getElementById('viewModal');
        const resetModal = document.getElementById('resetModal');
        let currentMessage = null;

        function viewMessage(message) {
            currentMessage = message;
            
            // Log the view action by making a request
            fetch(`admin_messages.php?view_message_id=${message.id}`);
            
            document.getElementById('view_sender_info').innerText = `${message.name} <${message.email}>`;
            document.getElementById('view_subject_info').innerText = message.subject;
            document.getElementById('view_date_info').innerText = new Date(message.created_at).toLocaleString();
            document.getElementById('view_status_info').innerHTML = `<span class="badge ${message.status === 'replied' ? 'badge-replied' : (message.status === 'archived' ? 'badge-archived' : 'badge-unread')}">${message.status.charAt(0).toUpperCase() + message.status.slice(1)}</span>`;
            document.getElementById('view_message_content').innerText = message.message;
            viewModal.style.display = 'block';
        }

        function openReplyModal(message) {
            currentMessage = message;
            document.getElementById('reply_message_id').value = message.id;
            document.getElementById('reply_user_email').value = message.email;
            document.getElementById('reply_user_name').value = message.name;
            document.getElementById('reply_original_subject').value = message.subject;
            document.getElementById('reply_original_message').value = message.message;
            document.getElementById('modal_user_info').innerText = `${message.name} <${message.email}>`;
            document.getElementById('modal_subject_info').innerText = message.subject;
            document.getElementById('reply_text').value = ''; // Clear previous content
            replyModal.style.display = 'block';
        }

        function replyFromView() {
            if (currentMessage) {
                closeViewModal();
                openReplyModal(currentMessage);
            }
        }

        function archiveMessage(messageId) {
            if (confirm('Archive this message?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="archive">
                    <input type="hidden" name="message_id" value="${messageId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteMessage(messageId, subject) {
            if (confirm(`Are you sure you want to permanently delete the message "${subject}"?\n\nThis action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="message_id" value="${messageId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function confirmReset() {
            resetModal.style.display = 'block';
        }

        function closeReplyModal() { 
            replyModal.style.display = 'none'; 
        }

        function closeViewModal() { 
            viewModal.style.display = 'none'; 
        }

        function closeResetModal() {
            resetModal.style.display = 'none';
        }

        window.onclick = function(event) { 
            if (event.target == replyModal) closeReplyModal(); 
            if (event.target == viewModal) closeViewModal(); 
            if (event.target == resetModal) closeResetModal();
        }
    </script>
</body>
</html>
</html>