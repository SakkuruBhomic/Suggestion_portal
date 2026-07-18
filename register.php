<?php
session_start();

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files (adjust path if needed)
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require 'db.php';

// Handle different steps
$step = isset($_POST['step']) ? $_POST['step'] : (isset($_GET['step']) ? $_GET['step'] : 'register');

switch($step) {
    case 'register':
        showRegistrationForm();
        break;
    case 'send_code':
        handleRegistration($conn);
        break;
    case 'verify':
        showVerificationForm();
        break;
    case 'complete':
        handleVerification($conn);
        break;
    default:
        showRegistrationForm();
}

function showRegistrationForm() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - KV Bhandup Portal</title>
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><polygon fill="rgba(255,255,255,0.1)" points="1000,100 1000,0 0,100"/></svg>') no-repeat;
            background-size: 100% 100%;
            pointer-events: none;
        }

        .registration-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            padding: 3rem;
            width: 100%;
            max-width: 480px;
            border: 1px solid var(--border-color);
            position: relative;
            z-index: 1;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .icon {
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

        .password-requirements {
            background: var(--gray-50);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.75rem;
            font-size: 0.75rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin: 0.5rem 0;
            color: var(--text-secondary);
            transition: color 0.2s ease;
        }

        .requirement.valid {
            color: var(--success-color);
        }

        .requirement-icon {
            margin-right: 0.5rem;
            width: 1rem;
            font-weight: 600;
        }

        .register-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-md);
            margin-top: 1rem;
        }

        .register-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
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

        @media (max-width: 480px) {
            .registration-container {
                padding: 2rem;
                margin: 1rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }

            .back-to-home {
                position: static;
                margin-bottom: 1rem;
                align-self: flex-start;
            }

            body {
                align-items: flex-start;
                padding-top: 2rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>

    <div class="registration-container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Create Account</h1>
            <p>Join the KV Bhandup Portal community</p>
        </div>
        
        <form method="POST" action="register.php" id="registrationForm">
            <input type="hidden" name="step" value="send_code">
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="Enter your email address" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" class="has-icon" placeholder="Create a strong password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-requirements">
                    <div class="requirement" id="length">
                        <span class="requirement-icon">○</span>
                        At least 8 characters long
                    </div>
                    <div class="requirement" id="uppercase">
                        <span class="requirement-icon">○</span>
                        Contains uppercase letter
                    </div>
                    <div class="requirement" id="lowercase">
                        <span class="requirement-icon">○</span>
                        Contains lowercase letter
                    </div>
                    <div class="requirement" id="number">
                        <span class="requirement-icon">○</span>
                        Contains a number
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="has-icon" placeholder="Confirm your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="register-btn" id="submitBtn" disabled>
                <i class="fas fa-paper-plane"></i>
                Send Verification Code
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');

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

        function validatePassword() {
            const value = password.value;
            const requirements = {
                length: value.length >= 8,
                uppercase: /[A-Z]/.test(value),
                lowercase: /[a-z]/.test(value),
                number: /[0-9]/.test(value)
            };

            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(req);
                if (requirements[req]) {
                    element.classList.add('valid');
                    element.querySelector('.requirement-icon').textContent = '✓';
                } else {
                    element.classList.remove('valid');
                    element.querySelector('.requirement-icon').textContent = '○';
                }
            });

            const allValid = Object.values(requirements).every(req => req);
            const passwordsMatch = confirmPassword.value === password.value && password.value !== '';
            
            submitBtn.disabled = !(allValid && passwordsMatch);
            
            if (allValid && passwordsMatch) {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Verification Code';
            } else {
                submitBtn.innerHTML = '<i class="fas fa-lock"></i> Complete Requirements';
            }
        }

        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);

        // Form submission animation
        document.getElementById('registrationForm').addEventListener('submit', function() {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
<?php
}

function showVerificationForm() {
    if (!isset($_SESSION['temp_user'])) {
        header('Location: register.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - KV Bhandup Portal</title>
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><polygon fill="rgba(255,255,255,0.1)" points="1000,100 1000,0 0,100"/></svg>') no-repeat;
            background-size: 100% 100%;
            pointer-events: none;
        }

        .verification-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            padding: 3rem;
            width: 100%;
            max-width: 480px;
            border: 1px solid var(--border-color);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, var(--info-color) 0%, var(--primary-color) 100%);
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
            margin-bottom: 2rem;
        }

        .email-info {
            background: var(--gray-50);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .email-info strong {
            color: var(--primary-color);
            font-size: 1.125rem;
        }

        .email-info p {
            color: var(--text-secondary);
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .code-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.25rem;
            transition: all 0.2s ease;
            background: var(--gray-50);
            font-weight: 700;
            color: var(--text-primary);
        }

        .code-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .verify-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-md);
            margin-bottom: 1rem;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .resend-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .timer {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .timer strong {
            color: var(--warning-color);
        }

        @media (max-width: 480px) {
            .verification-container {
                padding: 2rem;
                margin: 1rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-envelope-open"></i>
            </div>
            <h1>Verify Your Email</h1>
            <p>We've sent a verification code to your email address</p>
        </div>

        <div class="email-info">
            <strong><?php echo htmlspecialchars($_SESSION['temp_user']['email']); ?></strong>
            <p>Check your inbox (and spam folder) for the 6-digit verification code</p>
        </div>
        
        <form method="POST" action="register.php">
            <input type="hidden" name="step" value="complete">
            
            <div class="form-group">
                <label for="verification_code">Enter 6-Digit Code</label>
                <input type="text" name="verification_code" id="verification_code" 
                       class="code-input" placeholder="000000" maxlength="6" required>
            </div>

            <button type="submit" class="verify-btn">
                <i class="fas fa-check-circle"></i>
                Complete Registration
            </button>
        </form>

        <a href="register.php?step=send_code&resend=1" class="resend-link">
            <i class="fas fa-redo"></i>
            Didn't receive the code? Resend
        </a>

        <div class="timer" id="timer">
            <i class="fas fa-clock"></i>
            Code expires in: <strong><span id="countdown">10:00</span></strong>
        </div>
    </div>

    <script>
        // Auto-format verification code input
        document.getElementById('verification_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Countdown timer
        let timeLeft = 600; // 10 minutes in seconds
        const countdown = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdown.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                countdown.textContent = 'Expired';
                countdown.style.color = 'var(--danger-color)';
                document.querySelector('.verify-btn').disabled = true;
                document.querySelector('.verify-btn').style.opacity = '0.6';
                document.querySelector('.verify-btn').innerHTML = '<i class="fas fa-times-circle"></i> Code Expired';
            }
            timeLeft--;
        }, 1000);

        // Form submission animation
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.querySelector('.verify-btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
<?php
}

function handleRegistration($conn) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validation
    if (empty($name) || strlen($name) < 2) {
        showMessage("Please enter a valid name (at least 2 characters)!", "error");
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showMessage("Invalid email address!", "error");
        exit;
    }
    
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        showMessage("Password does not meet security requirements!", "error");
        exit;
    }
    
    if ($password !== $confirm_password) {
        showMessage("Passwords do not match!", "error");
        exit;
    }
    
    // Check if email already exists and is verified
    $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['is_verified']) {
            showMessage("Email already registered and verified! Please login.", "error");
            exit;
        }
    }
    
    // Generate verification code
    $code = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Store in session temporarily
    $_SESSION['temp_user'] = [
        'name' => $name,
        'email' => $email,
        'password' => $hashed_password,
        'code' => $code,
        'expiry' => $expiry
    ];
    
    // Send verification email
    if (sendVerificationEmail($name, $email, $code)) {
        header('Location: register.php?step=verify');
        exit;
    } else {
        showMessage("Failed to send verification email. Please try again.", "error");
        exit;
    }
}

function handleVerification($conn) {
    if (!isset($_SESSION['temp_user'])) {
        showMessage("Session expired. Please register again.", "error");
        exit;
    }
    
    $verification_code = isset($_POST['verification_code']) ? trim($_POST['verification_code']) : '';
    $user_data = $_SESSION['temp_user'];
    
    // Check if code is expired
    if (strtotime($user_data['expiry']) < time()) {
        unset($_SESSION['temp_user']);
        showMessage("Verification code has expired. Please register again.", "error");
        exit;
    }
    
    // Check if code matches
    if ($verification_code != $user_data['code']) {
        showMessage("Invalid verification code. Please try again.", "error");
        exit;
    }
    
    // Insert/Update user in database
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_verified, created_at) VALUES (?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), is_verified = 1, created_at = NOW()");
    $stmt->bind_param("sss", $user_data['name'], $user_data['email'], $user_data['password']);
    
    if ($stmt->execute()) {
        unset($_SESSION['temp_user']);
        showMessage("🎉 Registration completed successfully! Welcome to the KV Bhandup Portal family. You can now login with your credentials.", "success", "login.php");
    } else {
        showMessage("Registration failed. Please try again.", "error");
    }
}

function sendVerificationEmail($name, $email, $code) {
    $mail = new PHPMailer(true);
    
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.zoho.in';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@kvbalumni.me';
        $mail->Password   = 'ovxf@8qM';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        //Recipients
        $mail->setFrom('noreply@kvbalumni.me', 'KV Bhandup Portal');
        $mail->addAddress($email, $name);

        //Content
        $mail->isHTML(true);
        $mail->Subject = '🔐 Email Verification Code - KV Bhandup Portal';
        
        $mail->Body = getEmailTemplate($name, $code);
        $mail->AltBody = "Hello $name,\n\nWelcome to KV Bhandup Portal!\n\nYour email verification code is: $code\n\nThis code will expire in 10 minutes for your security.\n\nIf you didn't request this code, please ignore this email.\n\nBest regards,\nKV Bhandup Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getEmailTemplate($name, $code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; line-height: 1.6; color: #111827; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
            .header { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 2rem; text-align: center; }
            .content { padding: 2rem; }
            .code-box { background: #f9fafb; border: 2px dashed #059669; border-radius: 8px; padding: 1.5rem; text-align: center; margin: 1.5rem 0; }
            .code { font-size: 2rem; font-weight: bold; color: #059669; letter-spacing: 0.25rem; font-family: 'Courier New', monospace; }
            .footer { background: #f9fafb; padding: 1.5rem; text-align: center; font-size: 0.75rem; color: #6b7280; }
            .highlight { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 1rem; margin: 1.5rem 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎓 KV Bhandup Portal</h1>
                <p>Email Verification Required</p>
            </div>
            
            <div class='content'>
                <h2>Hello, $name! 👋</h2>
                <p>Thank you for registering with the <strong>KV Bhandup Portal</strong>. To complete your registration and join our community, please verify your email address.</p>
                
                <p>Enter this verification code on the registration page:</p>
                
                <div class='code-box'>
                    <div class='code'>$code</div>
                    <p style='margin: 0.75rem 0 0 0; color: #6b7280; font-size: 0.875rem;'>Your 6-digit verification code</p>
                </div>
                
                <div class='highlight'>
                    <strong>⏰ Important:</strong> This code will expire in <strong>10 minutes</strong> for your security.
                </div>
                
                <p>If you didn't request this registration, please ignore this email and the code will expire automatically.</p>
                
                <p>Welcome to the KV Bhandup family! 🎉</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated message from KV Bhandup Portal</p>
                <p>&copy; 2025 KV Bhandup Portal. All rights reserved.</p>
                <p>Developed by Bhomic Sakkuru & Sanchay Seshadri</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function showMessage($message, $type, $redirect = null) {
    $bgColor = '';
    $textColor = '';
    $icon = '';
    
    switch($type) {
        case 'success':
            $bgColor = '#d1fae5';
            $textColor = '#047857';
            $icon = '✅';
            break;
        case 'error':
            $bgColor = '#fee2e2';
            $textColor = '#991b1b';
            $icon = '❌';
            break;
        case 'warning':
            $bgColor = '#fef3c7';
            $textColor = '#92400e';
            $icon = '⚠️';
            break;
        default:
            $bgColor = '#dbeafe';
            $textColor = '#1e40af';
            $icon = 'ℹ️';
    }
    
    $redirectScript = '';
    $buttonText = '← Back to Registration';
    $buttonLink = 'register.php';
    
    if ($redirect) {
        $redirectScript = "<script>setTimeout(() => { window.location.href = '$redirect'; }, 4000);</script>";
        $buttonText = 'Continue to Login →';
        $buttonLink = $redirect;
    }
    
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Registration Status - KV Bhandup Portal</title>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                position: relative;
            }
            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1000 100\" preserveAspectRatio=\"none\"><polygon fill=\"rgba(255,255,255,0.1)\" points=\"1000,100 1000,0 0,100\"/></svg>') no-repeat;
                background-size: 100% 100%;
                pointer-events: none;
            }
            .message-container {
                background: white;
                border-radius: 16px;
                padding: 2.5rem;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                position: relative;
                z-index: 1;
            }
            .message {
                background: $bgColor;
                color: $textColor;
                padding: 1.5rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
                font-size: 1rem;
                line-height: 1.6;
                border: 1px solid " . ($type === 'success' ? '#10b981' : ($type === 'error' ? '#ef4444' : ($type === 'warning' ? '#f59e0b' : '#3b82f6'))) . ";
            }
            .back-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: linear-gradient(135deg, #059669 0%, #10b981 100%);
                color: white;
                padding: 0.875rem 1.5rem;
                text-decoration: none;
                border-radius: 8px;
                margin-top: 1rem;
                transition: all 0.2s ease;
                font-weight: 600;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            .back-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }
            .countdown {
                margin-top: 1rem;
                color: #6b7280;
                font-size: 0.875rem;
            }
        </style>
        $redirectScript
    </head>
    <body>
        <div class='message-container'>
            <div class='message'>
                $icon $message
            </div>
            <a href='$buttonLink' class='back-btn'>
                <i class='fas fa-" . ($redirect ? 'sign-in-alt' : 'arrow-left') . "'></i>
                $buttonText
            </a>" . 
            ($redirect ? "<div class='countdown'>Automatically redirecting in <span id='counter'>4</span> seconds...</div>" : "") . "
        </div>" . 
        ($redirect ? "
        <script>
            let count = 4;
            const counter = document.getElementById('counter');
            const interval = setInterval(() => {
                count--;
                counter.textContent = count;
                if (count <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
        </script>" : "") . "
    </body>
    </html>
    ";
}

$conn->close();
?>