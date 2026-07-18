<?php
session_start();
require 'db.php';

// Only allow admins
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Check for complaint ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid complaint ID.');
}

$complaint_id = (int)$_GET['id'];

// Handle message submission from admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !empty(trim($_POST['message']))) {
    $message = trim($_POST['message']);
    
    // Insert admin message into the database
    $stmt = $conn->prepare("INSERT INTO complaint_messages (complaint_id, user_id, sender_type, message) VALUES (?, ?, 'admin', ?)");
    $stmt->bind_param("iis", $complaint_id, $admin_id, $message);
    $stmt->execute();
    
    // Set complaint status to 'in_progress' if it was 'pending'
    $stmt = $conn->prepare("UPDATE complaints SET status = 'in_progress', updated_at = NOW() WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();

    // Refresh to show the new message
    header('Location: admin_view_complaint.php?id=' . $complaint_id);
    exit;
}

try {
    // Fetch complaint details
    $stmt = $conn->prepare("SELECT c.*, u.name as user_name, u.email as user_email 
                            FROM complaints c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.id = ?");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $complaint = $result->fetch_assoc();

    if (!$complaint) {
        throw new Exception("Complaint not found.");
    }

    // Fetch chat messages for this complaint, along with sender names
    $stmt = $conn->prepare("SELECT m.*, u.name as sender_name 
                            FROM complaint_messages m
                            JOIN users u ON m.user_id = u.id
                            WHERE m.complaint_id = ? 
                            ORDER BY m.created_at ASC");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Configuration for badges
$status_config = [
    'pending' => ['bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳', 'label' => 'Pending'],
    'in_progress' => ['bg' => '#e3f2fd', 'color' => '#1976d2', 'icon' => '🔄', 'label' => 'In Progress'],
    'resolved' => ['bg' => '#e8f5e8', 'color' => '#388e3c', 'icon' => '✅', 'label' => 'Resolved'],
    'closed' => ['bg' => '#f3e5f5', 'color' => '#7b1fa2', 'icon' => '🔒', 'label' => 'Closed']
];
$priority_config = [
    'Low' => ['bg' => '#e8f5e8', 'color' => '#388e3c', 'icon' => '🟢'],
    'Medium' => ['bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '🟡'],
    'High' => ['bg' => '#ffeaa7', 'color' => '#e67e22', 'icon' => '🟠'],
    'Critical' => ['bg' => '#ffebee', 'color' => '#e74c3c', 'icon' => '🔴']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin View Complaint #<?php echo $complaint['id']; ?> - KV Bhandup Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #059669; --secondary-color: #047857; --accent-color: #10b981;
            --text-primary: #111827; --text-secondary: #6b7280; --border-color: #e5e7eb;
            --gray-50: #f9fafb; --white: #ffffff;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        body { font-family: 'Inter', sans-serif; background: var(--gray-50); color: var(--text-primary); }
        .main-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: var(--white); border-radius: 16px; box-shadow: var(--shadow-md); border: 1px solid var(--border-color); margin-bottom: 2rem; }
        .card-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .card-body { padding: 1.5rem; }
        .card-title { font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 600; color: var(--text-primary); }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; }
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        .detail-item label { font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem; display: block; }
        .detail-item span { font-size: 1rem; font-weight: 500; }
        .chat-container { display: flex; flex-direction: column; height: 400px; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 1rem; background: var(--gray-50); border-radius: 12px; }
        .message-bubble { max-width: 75%; padding: 0.75rem 1rem; border-radius: 18px; line-height: 1.5; margin-bottom: 1rem; }
        .message-bubble.user { background: var(--white); color: var(--text-primary); border: 1px solid var(--border-color); border-bottom-left-radius: 4px; align-self: flex-start; }
        .message-bubble.admin { background: var(--primary-color); color: var(--white); border-bottom-right-radius: 4px; align-self: flex-end; }
        .message-meta { font-size: 0.75rem; margin-top: 0.5rem; display: block; opacity: 0.7; }
        .chat-form { display: flex; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
        .chat-form input { flex: 1; padding: 0.875rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; }
        .chat-form button { padding: 0.875rem 1.5rem; border: none; background: var(--primary-color); color: var(--white); border-radius: 8px; cursor: pointer; font-weight: 600; }
        .back-link { display: inline-block; margin-bottom: 2rem; color: var(--primary-color); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="admin_complaints.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to All Complaints</a>
        
        <!-- Complaint Details Card -->
        <div class="card">
            <div class="card-header">
                <div style="display:flex; justify-content: space-between; align-items: center;">
                    <h1 class="card-title">Complaint #<?php echo htmlspecialchars($complaint['id']); ?>: <?php echo htmlspecialchars($complaint['title']); ?></h1>
                    <?php $status = $status_config[strtolower($complaint['status'])]; ?>
                    <span class="badge" style="background:<?php echo $status['bg']; ?>; color:<?php echo $status['color']; ?>;"><?php echo $status['icon']; ?> <?php echo $status['label']; ?></span>
                </div>
                 <div class="detail-item" style="margin-top:1rem;">
                    <label>Submitted By</label>
                    <span style="font-weight:600;"><?php echo htmlspecialchars($complaint['user_name']); ?></span> (<?php echo htmlspecialchars($complaint['user_email']); ?>)
                </div>
            </div>
            <div class="card-body">
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Category</label>
                        <span><?php echo htmlspecialchars($complaint['category']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Priority</label>
                        <?php $priority = $priority_config[$complaint['priority']]; ?>
                        <span class="badge" style="background:<?php echo $priority['bg']; ?>; color:<?php echo $priority['color']; ?>;"><?php echo $priority['icon']; ?> <?php echo htmlspecialchars($complaint['priority']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Submitted On</label>
                        <span><?php echo date('M j, Y, g:i A', strtotime($complaint['created_at'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Last Updated</label>
                        <span><?php echo $complaint['updated_at'] ? date('M j, Y, g:i A', strtotime($complaint['updated_at'])) : 'N/A'; ?></span>
                    </div>
                </div>
                <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                <div>
                    <label style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: block;">Description</label>
                    <p style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Chat Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Conversation with <?php echo htmlspecialchars($complaint['user_name']); ?></h2>
            </div>
            <div class="card-body">
                <div class="chat-container">
                    <div class="chat-messages" id="chat-box">
                        <?php if (empty($messages)): ?>
                            <p style="text-align:center; color: var(--text-secondary);">No messages yet. Send a message to start the conversation.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <div style="display: flex; flex-direction: column;">
                                    <div class="message-bubble <?php echo $msg['sender_type']; ?>">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        <span class="message-meta">
                                            <strong><?php echo $msg['sender_type'] === 'admin' ? 'Admin (' . htmlspecialchars($msg['sender_name']) . ')' : htmlspecialchars($complaint['user_name']); ?></strong> - <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="chat-form">
                        <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
                        <button type="submit"><i class="fas fa-paper-plane"></i> Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Auto-scroll to the bottom of the chat box
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</body>
</html>