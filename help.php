<?php
// --- SETUP AND DEBUGGING ---
// This will show any PHP errors, which is crucial for debugging.
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php'; // Assumes your db.php is now correct.

// --- USER SESSION LOGIC ---
$is_logged_in = isset($_SESSION['user_id']);
$user_name = '';
$user_email = '';

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        if ($user = $user_result->fetch_assoc()) {
            $user_name = $user['name'];
            $user_email = $user['email'];
        }
        $stmt->close();
    }
}

// --- FORM SUBMISSION LOGIC ---
$contact_success = '';
$contact_error = '';
// Pre-fill form data if user is logged in
$form_data = ['name' => $user_name, 'email' => $user_email, 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $contact_name = trim($_POST['contact_name']);
    $contact_email = trim($_POST['contact_email']);
    $contact_subject = trim($_POST['contact_subject']);
    $contact_message = trim($_POST['contact_message']);
    
    // Retain submitted values on error
    $form_data = ['name' => $contact_name, 'email' => $contact_email, 'subject' => $contact_subject, 'message' => $contact_message];
    
    // Server-side validation
    if (empty($contact_name) || empty($contact_email) || empty($contact_subject) || empty($contact_message)) {
        $contact_error = 'All fields are required. Please fill them out.';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = 'The email address you entered is not valid.';
    } elseif (strlen($contact_message) < 10) {
        $contact_error = 'The message must be at least 10 characters long.';
    } else {
        try {
            // Prepare the SQL statement
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            
            // **CRITICAL DEBUGGING STEP**: Check if the prepare statement failed
            if ($stmt === false) {
                // This will catch errors if table/columns are wrong or permissions are denied
                throw new Exception("Database Prepare Error: " . $conn->error);
            }

            $stmt->bind_param("ssss", $contact_name, $contact_email, $contact_subject, $contact_message);
            
            // **CRITICAL DEBUGGING STEP**: Check if the execute statement failed
            if ($stmt->execute()) {
                $message_id = $conn->insert_id;
                $contact_success = "Message sent! Your reference ID is #$message_id. We'll be in touch soon.";
                // Clear form for next submission, keeping logged-in user's details
                $form_data = ['name' => $user_name, 'email' => $user_email, 'subject' => '', 'message' => ''];
            } else {
                throw new Exception("Database Execute Error: " . $stmt->error);
            }
            $stmt->close();

        } catch (Exception $e) {
            // Log the detailed error for your own debugging
            error_log("Help Form Submission Error: " . $e->getMessage()); 
            // Show a user-friendly error message
            $contact_error = 'A system error occurred. Please try again later or contact support directly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - KV Alumni Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d6efd; --primary-hover: #0b5ed7; --bg-main: #f4f7f9;
            --bg-card: #ffffff; --text-dark: #212529; --text-light: #6c757d;
            --border-color: #dee2e6; --success-bg: #d1e7dd; --success-text: #0f5132;
            --error-bg: #f8d7da; --error-text: #842029;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); color: var(--text-dark); }

        .header { background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 1rem 1.5rem; position: sticky; top: 0; z-index: 100; }
        .header-content { max-width: 1280px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 700; color: var(--primary-color); text-decoration: none; }
        .back-btn { background-color: #e9ecef; color: var(--text-dark); padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background-color 0.2s; }
        .back-btn:hover { background-color: #ced4da; }

        .main-container { max-width: 1280px; margin: 3rem auto; padding: 0 1.5rem; }
        .page-header { text-align: center; margin-bottom: 3.5rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.75rem; letter-spacing: -1px; }
        .page-subtitle { font-size: 1.125rem; color: var(--text-light); max-width: 700px; margin: 0 auto; }

        .content-grid { display: grid; grid-template-columns: 1fr; gap: 2.5rem; }
        @media (min-width: 992px) { .content-grid { grid-template-columns: 5fr 7fr; } }
        
        .card { background-color: var(--bg-card); border-radius: 16px; padding: 2rem; box-shadow: var(--shadow); }
        .section-title { font-size: 1.75rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: var(--primary-color); }
        
        .faq-item { border-bottom: 1px solid var(--border-color); }
        .faq-item:last-child { border-bottom: none; }
        .faq-question { padding: 1.25rem 0.5rem; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; }
        .faq-question .icon { transition: transform 0.3s; color: var(--text-light); }
        .faq-question.active .icon { transform: rotate(180deg); color: var(--primary-color); }
        .faq-answer { max-height: 0; overflow: hidden; transition: all 0.4s ease-out; color: var(--text-light); padding-left: 0.5rem; }
        .faq-answer.active { max-height: 200px; padding-bottom: 1.25rem; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-weight: 500; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.875rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); }
        textarea.form-control { min-height: 140px; resize: vertical; }

        .submit-btn { background-color: var(--primary-color); color: var(--bg-card); border: none; padding: 0.875rem; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; width: 100%; }
        .submit-btn:hover:not(:disabled) { background-color: var(--primary-hover); transform: translateY(-2px); box-shadow: var(--shadow); }
        .submit-btn:disabled { background-color: #6ea8ff; cursor: not-allowed; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } }
        .alert-success { background-color: var(--success-bg); color: var(--success-text); border: 1px solid #b6d7c3; }
        .alert-error { background-color: var(--error-bg); color: var(--error-text); border: 1px solid #f5c2c7; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">Support Center</a>
            <a href="<?php echo $is_logged_in ? 'dashboard.php' : 'index.php'; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>&nbsp; Back
            </a>
        </div>
    </header>

    <main class="main-container">
        <div class="page-header">
            <h1 class="page-title">How can we help?</h1>
            <p class="page-subtitle">Find quick answers to common questions or send us a message for direct assistance. We're here for you.</p>
        </div>

        <div class="content-grid">
            <div class="card">
                <h2 class="section-title"><i class="fas fa-question-circle"></i> FAQ</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">How do I submit a complaint? <span class="icon"><i class="fas fa-chevron-down"></i></span></div>
                        <div class="faq-answer"><p>Log in, go to your dashboard, click "Submit Complaint," fill out the form, and submit. You'll get a tracking ID instantly.</p></div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">How can I check my complaint's status? <span class="icon"><i class="fas fa-chevron-down"></i></span></div>
                        <div class="faq-answer"><p>Visit the "My Complaints" section from your dashboard to see the current status and any updates from administrators.</p></div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">What if I forget my password? <span class="icon"><i class="fas fa-chevron-down"></i></span></div>
                        <div class="faq-answer"><p>Use the "Forgot Password" link on the login page. A reset link will be sent to your registered email.</p></div>
                    </div>
                </div>
            </div>

            <div class="card" id="contact">
                <h2 class="section-title"><i class="fas fa-envelope"></i> Send us a Message</h2>
                
                <?php if ($contact_success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($contact_success); ?></div>
                <?php endif; ?>
                <?php if ($contact_error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($contact_error); ?></div>
                <?php endif; ?>

                <form method="POST" action="help.php#contact">
                    <div class="form-group">
                        <label for="contact_name" class="form-label">Full Name</label>
                        <input type="text" name="contact_name" id="contact_name" class="form-control" value="<?php echo htmlspecialchars($form_data['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_email" class="form-label">Email Address</label>
                        <input type="email" name="contact_email" id="contact_email" class="form-control" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_subject" class="form-label">Subject</label>
                        <select name="contact_subject" id="contact_subject" class="form-control" required>
                            <option value="" disabled <?php if(empty($form_data['subject'])) echo 'selected'; ?>>Please select a subject...</option>
                            <option value="Technical Issue" <?php if($form_data['subject'] == 'Technical Issue') echo 'selected'; ?>>Technical Issue</option>
                            <option value="Account Problem" <?php if($form_data['subject'] == 'Account Problem') echo 'selected'; ?>>Account Problem</option>
                            <option value="General Question" <?php if($form_data['subject'] == 'General Question') echo 'selected'; ?>>General Question</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="contact_message" class="form-label">Message</label>
                        <textarea name="contact_message" id="contact_message" class="form-control" placeholder="Please describe your issue in detail..." required minlength="10"><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                    </div>
                    <button type="submit" name="contact_submit" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            element.classList.toggle('active');
            answer.classList.toggle('active');
        }
    </script>
</body>
</html>