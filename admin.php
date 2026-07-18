<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Handle captcha refresh first, before any HTML output
if (isset($_GET['refresh_captcha'])) {
    $captcha = '';
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['admin_captcha'] = $captcha;
    echo $captcha;
    exit;
}

// Database connection
require 'db.php';

// Generate captcha function
function generateCaptcha() {
    $captcha = '';
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}

// Initialize captcha if not set
if (!isset($_SESSION['admin_captcha'])) {
    $_SESSION['admin_captcha'] = generateCaptcha();
}

// Predefined admin credentials with their names
$admin_credentials = [
    'sanchay@kvbalumni.me' => [
        'password' => 'Sanchay@29',
        'name' => 'Sanchay'
    ],
    'bhomic@kvbalumni.me' => [
        'password' => 'Bhomic#2008',
        'name' => 'Bhomic'
    ],
    'saswat@kvbalumni.me' => [
        'password' => 'Theonlynigger@69',
        'name' => 'Saswat'
    ]
];

// Admin login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $captcha = strtoupper(trim($_POST['captcha']));

    // Validate captcha
    if (!isset($_SESSION['admin_captcha']) || $captcha !== $_SESSION['admin_captcha']) {
        $error = 'Invalid security code. Please try again.';
        $_SESSION['admin_captcha'] = generateCaptcha(); // Generate new captcha
    }
    // Validate email domain
    elseif (!str_ends_with($email, '@kvbalumni.me')) {
        $error = 'Access restricted to @kvbalumni.me domain only.';
        $_SESSION['admin_captcha'] = generateCaptcha();
    }
    // Validate input
    elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
        $_SESSION['admin_captcha'] = generateCaptcha();
    }
    // Check if user is in the authorized admin list
    elseif (!isset($admin_credentials[$email])) {
        $error = 'Access denied. You are not authorized as an administrator.';
        $_SESSION['admin_captcha'] = generateCaptcha();
    }
    // Validate password
    elseif ($password !== $admin_credentials[$email]['password']) {
        $error = 'Invalid credentials. Please check your password.';
        $_SESSION['admin_captcha'] = generateCaptcha();
    }
    else {
        // Successful authentication
        $admin_name = $admin_credentials[$email]['name'];
        
        // Create or update admin record in database
        $stmt = $conn->prepare("INSERT INTO users (name, email, is_admin, is_verified, last_login, created_at) 
                               VALUES (?, ?, 1, 1, NOW(), NOW()) 
                               ON DUPLICATE KEY UPDATE 
                               is_admin = 1, is_verified = 1, last_login = NOW(), name = VALUES(name)");
        $stmt->bind_param("ss", $admin_name, $email);
        $stmt->execute();
        
        // Get the user ID
        $user_id = $stmt->insert_id;
        if (!$user_id) {
            // If insert failed due to duplicate, get existing ID
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $user_id = $user_data['id'];
        }
        
        // Set session variables
        $_SESSION['admin_id'] = $user_id;
        $_SESSION['admin_name'] = $admin_name;
        $_SESSION['admin_email'] = $email;
        
        // Clear captcha and redirect
        unset($_SESSION['admin_captcha']);
        
        // Log successful admin login
        error_log("Admin login successful: {$email} at " . date('Y-m-d H:i:s'));
        
        header('Location: admin_dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PM Shri KV Bhandup</title>
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-y: auto; /* Allow vertical scrolling */
        }

        body::before {
            content: '';
            position: fixed; /* Use fixed to keep background static */
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><polygon fill="rgba(255,255,255,0.1)" points="1000,100 1000,0 0,100"/></svg>') no-repeat;
            background-size: cover;
            pointer-events: none;
            z-index: -1;
        }

        .admin-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            border: 1px solid var(--border-color);
            position: relative;
            z-index: 1;
            margin: auto; /* Center the container */
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .admin-icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 12px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
        }

        .header h1 {
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--danger-color);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--gray-50);
            color: var(--text-primary);
        }

        .form-group input.has-icon {
            padding-right: 3rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            font-size: 1rem;
            padding: 0.25rem;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .captcha-container {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .captcha-display {
            background: var(--gray-800);
            color: var(--white);
            padding: 0.875rem 1.25rem;
            border-radius: 8px;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 3px;
            text-align: center;
            font-family: 'Courier New', monospace;
            border: 2px solid var(--gray-700);
            user-select: none;
        }

        .refresh-captcha {
            background: var(--gray-100);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }

        .refresh-captcha:hover {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--secondary-color);
            transform: rotate(180deg);
        }

        .btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .security-notice {
            background: var(--gray-50);
            color: var(--text-secondary);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 2rem;
            border: 1px solid var(--border-color);
            text-align: center;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .security-notice .icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
            color: var(--primary-color);
        }

        .back-to-home {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }

        .back-to-home:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 550px) {
            body {
                padding: 1rem;
                align-items: flex-start; /* Align to top on small screens */
            }
            .admin-container {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>

    <div class="admin-container">
        <div class="header">
            <div class="admin-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>PM Shri KV Bhandup Admins</h1>
            <p>Secure Administrator Login</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="admin.php" id="adminForm">
            <div class="form-group">
                <label for="email">Admin Email Address</label>
                <input type="email" name="email" id="email" placeholder="user@kvbalumni.me" required 
                       pattern="[a-zA-Z0-9._%+-]+@kvbalumni\.me$" 
                       title="Please enter a valid @kvbalumni.me email address"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Admin Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" class="has-icon" placeholder="Enter your admin password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="captcha">Security Verification</label>
                <div class="captcha-container">
                    <input type="text" name="captcha" id="captcha" 
                           placeholder="Enter Code" required maxlength="6" style="flex: 1;">
                    <div class="captcha-display" id="captchaDisplay">
                        <?php echo $_SESSION['admin_captcha']; ?>
                    </div>
                    <button type="button" class="refresh-captcha" onclick="refreshCaptcha()" title="Refresh Security Code" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-lock"></i>
                Secure Login
            </button>
        </form>

        <div class="security-notice">
            <span class="icon">
                <i class="fas fa-shield-alt"></i>
            </span>
            <strong>Maximum Security Zone:</strong><br>
            Access is restricted to pre-authorized administrators. All login attempts are logged for security purposes.
        </div>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function refreshCaptcha() {
            const refreshBtn = document.getElementById('refreshBtn');
            const captchaDisplay = document.getElementById('captchaDisplay');
            const captchaInput = document.getElementById('captcha');
            
            const icon = refreshBtn.querySelector('i');
            icon.classList.add('fa-spin');
            
            fetch(`admin.php?refresh_captcha=1&t=${new Date().getTime()}`)
            .then(response => response.text())
            .then(data => {
                captchaDisplay.textContent = data.trim();
                captchaInput.value = '';
                captchaInput.focus();
            })
            .catch(error => {
                console.error('Error refreshing captcha:', error);
                captchaDisplay.textContent = 'ERROR';
            })
            .finally(() => {
                icon.classList.remove('fa-spin');
            });
        }

        document.getElementById('captcha').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            submitBtn.disabled = true;
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>