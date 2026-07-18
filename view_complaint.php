<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Check for complaint ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid complaint ID.');
}

$complaint_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !empty(trim($_POST['message']))) {
    $message = trim($_POST['message']);
    
    // Insert message into the database
    $stmt = $conn->prepare("INSERT INTO complaint_messages (complaint_id, user_id, sender_type, message) VALUES (?, ?, 'user', ?)");
    $stmt->bind_param("iis", $complaint_id, $user_id, $message);
    $stmt->execute();

    // Set complaint status to 'in_progress' if it was 'pending'
    $stmt = $conn->prepare("UPDATE complaints SET status = 'in_progress', updated_at = NOW() WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    
    // Refresh to show the new message
    header('Location: view_complaint.php?id=' . $complaint_id);
    exit;
}

try {
    // Fetch complaint details, ensuring the user owns it
    $stmt = $conn->prepare("SELECT c.*, u.name as user_name, u.email as user_email 
                            FROM complaints c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.id = ? AND c.user_id = ?");
    $stmt->bind_param("ii", $complaint_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $complaint = $result->fetch_assoc();

    if (!$complaint) {
        throw new Exception("Complaint not found or you don't have permission to view it.");
    }

    // Fetch chat messages for this complaint
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
    <title>View Complaint #<?php echo $complaint['id']; ?> - KV Bhandup Portal</title>
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
        .message-bubble.user { background: var(--primary-color); color: var(--white); border-bottom-left-radius: 4px; align-self: flex-start; }
        .message-bubble.admin { background: var(--white); color: var(--text-primary); border: 1px solid var(--border-color); border-bottom-right-radius: 4px; align-self: flex-end; }
        .message-bubble.system { background: #f0f9ff; color: #1e40af; border: 1px solid #bfdbfe; border-bottom-right-radius: 4px; align-self: flex-end; position: relative; }
        .message-bubble.system::before { content: "🤖"; position: absolute; top: -10px; right: -5px; background: #3b82f6; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 10px; }
        .message-meta { font-size: 0.75rem; margin-top: 0.5rem; display: block; opacity: 0.7; }
        .chat-form { display: flex; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
        .chat-form input { flex: 1; padding: 0.875rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; }
        .chat-form button { padding: 0.875rem 1.5rem; border: none; background: var(--primary-color); color: var(--white); border-radius: 8px; cursor: pointer; font-weight: 600; }
        .back-link { display: inline-block; margin-bottom: 2rem; color: var(--primary-color); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="my_complaints.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Complaints</a>
        
        <!-- Complaint Details Card -->
        <div class="card">
            <div class="card-header">
                <div style="display:flex; justify-content: space-between; align-items: center;">
                    <h1 class="card-title">Complaint #<?php echo htmlspecialchars($complaint['id']); ?>: <?php echo htmlspecialchars($complaint['title']); ?></h1>
                    <?php $status = $status_config[strtolower($complaint['status'])]; ?>
                    <span class="badge" style="background:<?php echo $status['bg']; ?>; color:<?php echo $status['color']; ?>;"><?php echo $status['icon']; ?> <?php echo $status['label']; ?></span>
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
                <h2 class="card-title">Conversation with Admin</h2>
            </div>
            <div class="card-body">
                <div class="chat-container">
                    <div class="chat-messages" id="chat-box">
                        <!-- System Welcome Message -->
                        <div style="display: flex; flex-direction: column;">
                            <div class="message-bubble system">
                                <strong>Welcome to KV Support!</strong><br><br>
                                📝 To help us assist you better, please provide the following information in your first message:<br>
                                • Your full name<br>
                                • Your class (e.g., 10th, 12th)<br>
                                • Your section (e.g., A, B, C)<br><br>
                                ⏰ Our support team will respond to your complaint within 24-48 hours during working days.<br><br>
                                Thank you for your patience!
                                <span class="message-meta">
                                    <strong>System Message</strong> - Auto-generated
                                </span>
                            </div>
                        </div>

                        <?php if (empty($messages)): ?>
                            <p style="text-align:center; color: var(--text-secondary); margin-top: 1rem;">
                                <i class="fas fa-comments"></i> Send a message to start the conversation with our support team.
                            </p>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <div style="display: flex; flex-direction: column;">
                                    <div class="message-bubble <?php echo $msg['sender_type']; ?>">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        <span class="message-meta">
                                            <strong><?php echo $msg['sender_type'] === 'admin' ? 'Admin' : 'You'; ?></strong> - <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="chat-form">
                        <input type="text" name="message" placeholder="Type your message (include name, class & section in first message)..." required autocomplete="off" maxlength="500">
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

        // Add character counter to message input
        const messageInput = document.querySelector('input[name="message"]');
        const form = document.querySelector('.chat-form');
        
        // Create character counter element
        const charCounter = document.createElement('div');
        charCounter.style.cssText = 'font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem; text-align: right;';
        form.appendChild(charCounter);
        
        function updateCharCounter() {
            const remaining = 500 - messageInput.value.length;
            charCounter.textContent = `${messageInput.value.length}/500`;
            if (remaining < 50) {
                charCounter.style.color = '#ef4444';
            } else {
                charCounter.style.color = 'var(--text-secondary)';
            }
        }
        
        messageInput.addEventListener('input', updateCharCounter);
        updateCharCounter(); // Initialize counter

        // Auto-focus on message input
        messageInput.focus();

        // Add timestamp update every minute
        setInterval(() => {
            const metaElements = document.querySelectorAll('.message-meta');
            metaElements.forEach(meta => {
                const text = meta.textContent;
                if (text.includes('just now') || text.includes('minute ago')) {
                    // Update relative timestamps if needed
                }
            });
        }, 60000);

        console.log('💬 Chat interface enhanced and ready!');
    </script>
</body>
</html>