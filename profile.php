<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user profile info
$stmt = $conn->prepare("SELECT name, email, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user activity stats
$stmt = $conn->prepare("SELECT COUNT(*) as complaint_count FROM complaints WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

$error = $success = '';
$form_data = ['name' => $user['name']];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Store form data for repopulation
    $form_data['name'] = $new_name;

    if (!$new_name) {
        $error = "Name cannot be empty.";
    } elseif (strlen($new_name) < 2) {
        $error = "Name must be at least 2 characters long.";
    } elseif (strlen($new_name) > 50) {
        $error = "Name cannot exceed 50 characters.";
    } elseif ($new_password && !$current_password) {
        $error = "Current password is required to set a new password.";
    } elseif ($new_password && strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } elseif ($new_password && $new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Verify current password if changing password
        if ($new_password) {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $current_user = $stmt->get_result()->fetch_assoc();
            
            if (!password_verify($current_password, $current_user['password'])) {
                $error = "Current password is incorrect.";
            }
        }

        if (!$error) {
            $updated = false;
            
            // Update name
            if ($new_name !== $user['name']) {
                $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
                $stmt->bind_param("si", $new_name, $user_id);
                $stmt->execute();
                $_SESSION['user_name'] = $new_name;
                $updated = true;
            }
            
            // Update password
            if ($new_password) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);
                $stmt->execute();
                $updated = true;
            }
            
            if ($updated) {
                $success = "Profile updated successfully!";
                // Refresh user info
                $stmt = $conn->prepare("SELECT name, email, created_at, last_login FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $form_data['name'] = $user['name'];
            } else {
                $success = "No changes were made to your profile.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - KV Bhandup Portal</title>
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
            max-width: 800px;
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

        .profile-container {
            background: var(--white);
            border-radius: 16px;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color), var(--primary-color));
        }

        /* Account Information */
        .account-info {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid var(--success-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .account-info h3 {
            color: var(--success-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .info-item {
            background: var(--white);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .info-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .info-value {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 600;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: var(--danger-color);
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: var(--success-color);
            border: 1px solid #bbf7d0;
        }

        /* Form Sections */
        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            color: var(--text-primary);
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .required {
            color: var(--danger-color);
            font-size: 0.75rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--white);
            color: var(--text-primary);
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .form-group input:disabled {
            background: var(--gray-100);
            color: var(--gray-500);
            cursor: not-allowed;
        }

        .helper-text {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .char-counter {
            position: absolute;
            bottom: -1.25rem;
            right: 0.5rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .char-counter.warning {
            color: var(--warning-color);
        }

        .char-counter.error {
            color: var(--danger-color);
        }

        /* Password Input Group */
        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 1.125rem;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--text-primary);
            background: var(--gray-100);
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 0.5rem;
            display: none;
        }

        .strength-bar {
            width: 100%;
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { background: var(--danger-color); }
        .strength-medium { background: var(--warning-color); }
        .strength-strong { background: var(--success-color); }

        .strength-text {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Form Validation */
        .form-group.error input {
            border-color: var(--danger-color);
            background: #fef2f2;
        }

        .form-group.success input {
            border-color: var(--success-color);
            background: #f0fdf4;
        }

        .validation-message {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .validation-message.error {
            color: var(--danger-color);
        }

        .validation-message.success {
            color: var(--success-color);
        }

        /* Submit Section */
        .submit-section {
            background: var(--gray-50);
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }

        .submit-helper {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-md);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 200px;
            justify-content: center;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Security Notice */
        .security-notice {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid var(--info-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            color: var(--info-color);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .security-notice h4 {
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .security-notice ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .security-notice li {
            margin-bottom: 0.25rem;
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
            }

            .main-container {
                padding: 1rem;
            }

            .profile-container {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .submit-btn {
                width: 100%;
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
            <h1 class="page-title">Edit Profile</h1>
            <p class="page-subtitle">
                Update your personal information and security settings
            </p>
        </div>

        <div class="profile-container">
            <!-- Account Information -->
            <div class="account-info">
                <h3><i class="fas fa-chart-bar"></i> Account Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-calendar-alt"></i> Member Since</div>
                        <div class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-clock"></i> Last Login</div>
                        <div class="info-value"><?php echo $user['last_login'] ? date('M j, Y - g:i A', strtotime($user['last_login'])) : 'First time login'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-clipboard-list"></i> Total Complaints</div>
                        <div class="info-value"><?php echo $activity['complaint_count'] ?: '0'; ?> submitted</div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <form method="POST" id="profileForm" novalidate>
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i>
                        Personal Information
                    </h3>
                    
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-id-card"></i>
                            Full Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               placeholder="Enter your full name"
                               value="<?php echo htmlspecialchars($form_data['name']); ?>"
                               maxlength="50"
                               required>
                        <div class="char-counter" id="nameCounter">0/50</div>
                        <div class="validation-message" id="nameValidation"></div>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                               disabled>
                        <div class="helper-text">
                            <i class="fas fa-lock"></i>
                            Email address cannot be changed for security reasons
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-shield-alt"></i>
                        Security Settings
                    </h3>
                    
                    <div class="form-group">
                        <label for="current_password">
                            <i class="fas fa-key"></i>
                            Current Password
                        </label>
                        <div class="password-input-group">
                            <input type="password" 
                                   name="current_password" 
                                   id="current_password" 
                                   placeholder="Enter current password to change password">
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="helper-text">
                            <i class="fas fa-info-circle"></i>
                            Required only when changing password
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            New Password
                        </label>
                        <div class="password-input-group">
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   placeholder="Enter new password (min 8 characters)">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength will appear here</div>
                        </div>
                        <div class="helper-text">
                            <i class="fas fa-info-circle"></i>
                            Leave empty to keep current password
                        </div>
                        <div class="validation-message" id="passwordValidation"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-check-circle"></i>
                            Confirm New Password
                        </label>
                        <div class="password-input-group">
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password" 
                                   placeholder="Confirm your new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="validation-message" id="confirmPasswordValidation"></div>
                    </div>
                </div>

                <!-- Submit Section -->
                <div class="submit-section">
                    <div class="submit-helper">
                        <i class="fas fa-info-circle"></i>
                        Please review your changes before updating your profile
                    </div>
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Update Profile
                    </button>
                </div>
            </form>

            <!-- Security Notice -->
            <div class="security-notice">
                <h4><i class="fas fa-shield-alt"></i> Security & Privacy</h4>
                <ul>
                    <li>Your password is encrypted and cannot be viewed by administrators</li>
                    <li>Profile changes are logged for security purposes</li>
                    <li>Use a strong, unique password for your account</li>
                    <li>Contact support if you encounter any issues</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Character counter for name field
            function updateCharCounter() {
                const nameField = document.getElementById('name');
                const counter = document.getElementById('nameCounter');
                const currentLength = nameField.value.length;
                counter.textContent = `${currentLength}/50`;
                
                counter.classList.remove('warning', 'error');
                
                if (currentLength > 45) {
                    counter.classList.add('warning');
                }
                
                if (currentLength === 50) {
                    counter.classList.add('error');
                }
            }

            // Password strength checker
            function checkPasswordStrength(password) {
                const strengthIndicator = document.getElementById('passwordStrength');
                const strengthFill = document.getElementById('strengthFill');
                const strengthText = document.getElementById('strengthText');
                
                if (!password) {
                    strengthIndicator.style.display = 'none';
                    return;
                }
                
                strengthIndicator.style.display = 'block';
                
                let score = 0;
                let feedback = [];
                
                // Length check
                if (password.length >= 8) score++;
                else feedback.push('at least 8 characters');
                
                // Uppercase check
                if (/[A-Z]/.test(password)) score++;
                else feedback.push('uppercase letter');
                
                // Lowercase check
                if (/[a-z]/.test(password)) score++;
                else feedback.push('lowercase letter');
                
                // Number check
                if (/\d/.test(password)) score++;
                else feedback.push('number');
                
                // Special character check
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
                else feedback.push('special character');
                
                // Update strength indicator
                const percentage = (score / 5) * 100;
                strengthFill.style.width = percentage + '%';
                
                if (score < 2) {
                    strengthFill.className = 'strength-fill strength-weak';
                    strengthText.textContent = 'Weak password. Add: ' + feedback.slice(0, 3).join(', ');
                } else if (score < 4) {
                    strengthFill.className = 'strength-fill strength-medium';
                    strengthText.textContent = 'Medium strength. Consider adding: ' + feedback.slice(0, 2).join(', ');
                } else {
                    strengthFill.className = 'strength-fill strength-strong';
                    strengthText.textContent = 'Strong password! 🎉';
                }
            }

            // Real-time validation
            function validateField(field, validationId, rules) {
                const validation = document.getElementById(validationId);
                const value = field.value.trim();
                let isValid = true;
                let message = '';

                for (const rule of rules) {
                    if (!rule.test(value)) {
                        isValid = false;
                        message = rule.message;
                        break;
                    }
                }

                const formGroup = field.closest('.form-group');
                formGroup.classList.remove('error', 'success');
                
                if (value && !isValid) {
                    formGroup.classList.add('error');
                    validation.innerHTML = `<span class="error"><i class="fas fa-times-circle"></i> ${message}</span>`;
                } else if (value && isValid) {
                    formGroup.classList.add('success');
                    validation.innerHTML = '<span class="success"><i class="fas fa-check-circle"></i> Looks good!</span>';
                } else {
                    validation.innerHTML = '';
                }

                return isValid || !value; // Valid if empty (optional) or passes validation
            }

            // Password toggle functionality
            window.togglePassword = function(fieldId) {
                const field = document.getElementById(fieldId);
                const button = field.nextElementSibling;
                const icon = button.querySelector('i');
                
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    field.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            };

            // Form validation rules
            const nameRules = [
                { test: (v) => v.length >= 2, message: 'Name must be at least 2 characters long' },
                { test: (v) => v.length <= 50, message: 'Name must not exceed 50 characters' },
                { test: (v) => /^[a-zA-Z\s]+$/.test(v), message: 'Name can only contain letters and spaces' }
            ];

            const passwordRules = [
                { test: (v) => v.length >= 8, message: 'Password must be at least 8 characters long' },
                { test: (v) => /[A-Z]/.test(v), message: 'Password must contain at least one uppercase letter' },
                { test: (v) => /[a-z]/.test(v), message: 'Password must contain at least one lowercase letter' },
                { test: (v) => /\d/.test(v), message: 'Password must contain at least one number' }
            ];

            // Initialize elements
            const nameField = document.getElementById('name');
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const form = document.getElementById('profileForm');

            // Character counter
            nameField.addEventListener('input', updateCharCounter);
            updateCharCounter();

            // Password strength
            passwordField.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                validateField(this, 'passwordValidation', passwordRules);
            });

            // Real-time validation
            nameField.addEventListener('blur', () => validateField(nameField, 'nameValidation', nameRules));
            
            // Confirm password validation
            confirmPasswordField.addEventListener('input', function() {
                const validation = document.getElementById('confirmPasswordValidation');
                const formGroup = this.closest('.form-group');
                
                formGroup.classList.remove('error', 'success');
                
                if (this.value && passwordField.value) {
                    if (this.value === passwordField.value) {
                        formGroup.classList.add('success');
                        validation.innerHTML = '<span class="success"><i class="fas fa-check-circle"></i> Passwords match!</span>';
                    } else {
                        formGroup.classList.add('error');
                        validation.innerHTML = '<span class="error"><i class="fas fa-times-circle"></i> Passwords do not match</span>';
                    }
                } else {
                    validation.innerHTML = '';
                }
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                const nameValid = validateField(nameField, 'nameValidation', nameRules);
                const passwordValid = !passwordField.value || validateField(passwordField, 'passwordValidation', passwordRules);
                const confirmValid = !confirmPasswordField.value || confirmPasswordField.value === passwordField.value;

                if (!nameValid || !passwordValid || !confirmValid) {
                    e.preventDefault();
                    alert('⚠️ Please fix the validation errors before submitting.');
                    return false;
                }

                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.innerHTML = '<div class="loading"></div> Updating...';
                submitBtn.disabled = true;
            });

            // Auto-focus on name field
            nameField.focus();

            console.log('✅ KV Bhandup Profile Edit Page Ready!');
            console.log('📅 Current Date: 2025-08-06 13:35:04 UTC');
            console.log('👤 Current User: SakkuruBhomic');
        });
    </script>
</body>
</html>