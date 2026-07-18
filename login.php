<?php
session_start();

// Handle captcha refresh first, before any HTML output
if (isset($_GET['refresh_captcha'])) {
    $captcha = '';
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha'] = $captcha;
    echo $captcha;
    exit;
}

// Database connection details
$db_host = "sql211.infinityfree.com";
$db_user = "if0_39511631";
$db_pass = "VTRa58jzFaI";
$db_name = "if0_39511631_complaints";

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Import PHPMailer classes for forgot password
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Generate captcha
function generateCaptcha() {
    $captcha = '';
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}

// Handle different actions
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : 'login');

switch($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleLogin($conn);
        } else {
            showLoginForm();
        }
        break;
    case 'forgot':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleForgotPassword($conn);
        } else {
            showForgotPasswordForm();
        }
        break;
    case 'reset':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handlePasswordReset($conn);
        } else {
            showPasswordResetForm();
        }
        break;
    case 'verify-reset':
        showManualResetForm();
        break;
    default:
        showLoginForm();
}

function showLoginForm() {
    // Generate new captcha for the session
    $_SESSION['captcha'] = generateCaptcha();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PM Shri KV Bhandup Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #059669; --secondary-color: #047857; --accent-color: #10b981;
            --text-primary: #111827; --text-secondary: #6b7280; --border-color: #e5e7eb;
            --danger-color: #ef4444; --success-color: #22c55e;
            --gray-50: #f9fafb; --white: #ffffff;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .form-container { background: var(--white); border-radius: 16px; box-shadow: var(--shadow-xl); padding: 3rem; width: 100%; max-width: 480px; border: 1px solid var(--border-color); }
        .header { text-align: center; margin-bottom: 2rem; }
        .icon { width: 4rem; height: 4rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%); border-radius: 12px; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 1.5rem; }
        .header h1 { font-family: 'Poppins', sans-serif; color: var(--text-primary); font-size: 1.75rem; font-weight: 600; margin-bottom: 0.5rem; }
        .header p { color: var(--text-secondary); font-size: 1rem; }
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
        .error-message { background: #fee2e2; color: #991b1b; border: 1px solid var(--danger-color); }
        .success-message { background: #dcfce7; color: #166534; border: 1px solid var(--success-color); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .input-wrapper { position: relative; }
        .form-group input { width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; background: var(--gray-50); }
        .form-group input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
        .password-toggle { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1rem; }
        .captcha-container { display: flex; gap: 1rem; align-items: center; }
        .captcha-display { background: var(--text-primary); color: var(--white); padding: 0.75rem 1.25rem; border-radius: 8px; font-size: 1.25rem; font-weight: 700; letter-spacing: 3px; font-family: 'Courier New', monospace; }
        .refresh-captcha { background: var(--gray-100); border: 2px solid var(--border-color); border-radius: 8px; padding: 0.75rem; cursor: pointer; transition: all 0.2s ease; font-size: 1.25rem; }
        .refresh-captcha:hover { background: var(--primary-color); color: var(--white); border-color: var(--secondary-color); transform: rotate(180deg); }
        .btn { width: 100%; padding: 0.875rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%); color: var(--white); border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .links { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; }
        .links a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="header">
            <div class="icon"><i class="fas fa-lock"></i></div>
            <h1>Welcome Back</h1>
            <p>Sign in to your complaint portal account</p>
        </div>

        <?php
        if (isset($_SESSION['login_error'])) { echo '<div class="message error-message"><i class="fas fa-exclamation-circle"></i>' . htmlspecialchars($_SESSION['login_error']) . '</div>'; unset($_SESSION['login_error']); }
        if (isset($_SESSION['login_success'])) { echo '<div class="message success-message"><i class="fas fa-check-circle"></i>' . htmlspecialchars($_SESSION['login_success']) . '</div>'; unset($_SESSION['login_success']); }
        ?>
        
        <form method="POST" action="login.php" id="loginForm">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required value="<?php echo isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label for="captcha">Security Code</label>
                <div class="captcha-container">
                    <input type="text" name="captcha" id="captcha" placeholder="Enter Code" required maxlength="6" style="flex:1;">
                    <div class="captcha-display" id="captchaDisplay"><?php echo $_SESSION['captcha']; ?></div>
                    <button type="button" class="refresh-captcha" onclick="refreshCaptcha()" title="Refresh Captcha"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <div class="links">
            <a href="login.php?action=forgot">Forgot password?</a> | <a href="register.php">Create an account</a>
        </div>
    </div>
    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = "password";
                icon.className = 'fas fa-eye';
            }
        }
        function refreshCaptcha() {
            const btn = document.querySelector('.refresh-captcha i');
            btn.classList.add('fa-spin');
            fetch(`login.php?refresh_captcha=1&t=${new Date().getTime()}`)
                .then(res => res.text())
                .then(data => {
                    document.getElementById('captchaDisplay').textContent = data.trim();
                    document.getElementById('captcha').value = '';
                })
                .finally(() => btn.classList.remove('fa-spin'));
        }
        document.getElementById('captcha').addEventListener('input', function() { this.value = this.value.toUpperCase(); });
        <?php unset($_SESSION['login_email']); ?>
    </script>
</body>
</html>
<?php
}

function showForgotPasswordForm() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PM Shri KV Bhandup Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #059669; --secondary-color: #047857; --accent-color: #10b981;
            --text-primary: #111827; --text-secondary: #6b7280; --border-color: #e5e7eb;
            --danger-color: #ef4444; --success-color: #22c55e;
            --gray-50: #f9fafb; --white: #ffffff;
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .form-container { background: var(--white); border-radius: 16px; box-shadow: var(--shadow-xl); padding: 3rem; width: 100%; max-width: 480px; }
        .header { text-align: center; margin-bottom: 2rem; }
        .icon { width: 4rem; height: 4rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%); border-radius: 12px; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 1.5rem; }
        .header h1 { font-family: 'Poppins', sans-serif; font-size: 1.75rem; font-weight: 600; margin-bottom: 0.5rem; }
        .header p { color: var(--text-secondary); font-size: 1rem; }
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
        .error-message { background: #fee2e2; color: #991b1b; border: 1px solid var(--danger-color); }
        .success-message { background: #dcfce7; color: #166534; border: 1px solid var(--success-color); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .form-group input { width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; }
        .btn { width: 100%; padding: 0.875rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%); color: var(--white); border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .manual-reset { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
        .links { text-align: center; margin-top: 1.5rem; }
        .links a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="header">
            <div class="icon"><i class="fas fa-key"></i></div>
            <h1>Forgot Password</h1>
            <p>Enter your email to receive a password reset code.</p>
        </div>
        <?php
        if (isset($_SESSION['forgot_error'])) { echo '<div class="message error-message"><i class="fas fa-exclamation-circle"></i>' . htmlspecialchars($_SESSION['forgot_error']) . '</div>'; unset($_SESSION['forgot_error']); }
        if (isset($_SESSION['forgot_success'])) { echo '<div class="message success-message"><i class="fas fa-check-circle"></i>' . htmlspecialchars($_SESSION['forgot_success']) . '</div>'; unset($_SESSION['forgot_success']); }
        ?>
        <form method="POST" action="login.php">
            <input type="hidden" name="action" value="forgot">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="Enter your registered email" required>
            </div>
            <button type="submit" class="btn">Send Reset Code</button>
        </form>
        <div class="manual-reset">
            <h3>Have a Reset Code?</h3>
            <form method="GET" action="login.php">
                <input type="hidden" name="action" value="verify-reset">
                <div class="form-group">
                    <label for="manual_code">Enter Reset Code</label>
                    <input type="text" name="code" id="manual_code" placeholder="Paste reset code from email">
                </div>
                <button type="submit" class="btn">Use Reset Code</button>
            </form>
        </div>
        <div class="links"><a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a></div>
    </div>
</body>
</html>
<?php
}

function showManualResetForm() {
    $code = isset($_GET['code']) ? $_GET['code'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PM Shri KV Bhandup Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #059669; --secondary-color: #047857; --accent-color: #10b981;
            --text-primary: #111827; --text-secondary: #6b7280; --border-color: #e5e7eb;
            --danger-color: #ef4444; --success-color: #22c55e;
            --gray-50: #f9fafb; --white: #ffffff;
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .form-container { background: var(--white); border-radius: 16px; box-shadow: var(--shadow-xl); padding: 3rem; width: 100%; max-width: 480px; }
        .header { text-align: center; margin-bottom: 2rem; }
        .icon { width: 4rem; height: 4rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%); border-radius: 12px; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 1.5rem; }
        .header h1 { font-family: 'Poppins', sans-serif; font-size: 1.75rem; font-weight: 600; margin-bottom: 0.5rem; }
        .header p { color: var(--text-secondary); font-size: 1rem; }
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
        .error-message { background: #fee2e2; color: #991b1b; border: 1px solid var(--danger-color); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .input-wrapper { position: relative; }
        .form-group input { width: 100%; padding: 0.875rem 1rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; }
        .password-toggle { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; }
        .password-requirements { background: var(--gray-50); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; margin-top: 0.75rem; font-size: 0.75rem; }
        .requirement { display: flex; align-items: center; margin: 0.5rem 0; color: var(--text-secondary); }
        .requirement.valid { color: var(--success-color); }
        .requirement-icon { margin-right: 0.5rem; width: 1rem; }
        .btn { width: 100%; padding: 0.875rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%); color: var(--white); border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .links { text-align: center; margin-top: 1.5rem; }
        .links a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="header">
            <div class="icon"><i class="fas fa-redo-alt"></i></div>
            <h1>Reset Your Password</h1>
            <p>Create a new, strong password for your account.</p>
        </div>
        <?php if (isset($_SESSION['reset_error'])) { echo '<div class="message error-message"><i class="fas fa-exclamation-circle"></i>' . htmlspecialchars($_SESSION['reset_error']) . '</div>'; unset($_SESSION['reset_error']); } ?>
        <form method="POST" action="login.php" id="resetForm">
            <input type="hidden" name="action" value="reset">
            <div class="form-group">
                <label for="reset_code">Reset Code</label>
                <input type="text" name="reset_code" id="reset_code" placeholder="Enter reset code" value="<?php echo htmlspecialchars($code); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" placeholder="Enter new password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)"><i class="fas fa-eye"></i></button>
                </div>
                <div class="password-requirements">
                    <div class="requirement" id="length"><span class="requirement-icon">○</span>At least 8 characters</div>
                    <div class="requirement" id="uppercase"><span class="requirement-icon">○</span>Contains uppercase letter</div>
                    <div class="requirement" id="lowercase"><span class="requirement-icon">○</span>Contains lowercase letter</div>
                    <div class="requirement" id="number"><span class="requirement-icon">○</span>Contains a number</div>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="btn" id="submitBtn" disabled>Reset Password</button>
        </form>
        <div class="links"><a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a></div>
    </div>
    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === "password") { input.type = "text"; icon.className = 'fas fa-eye-slash'; } else { input.type = "password"; icon.className = 'fas fa-eye'; }
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
                const el = document.getElementById(req);
                if (requirements[req]) { el.classList.add('valid'); el.querySelector('.requirement-icon').textContent = '✓'; } else { el.classList.remove('valid'); el.querySelector('.requirement-icon').textContent = '○'; }
            });
            const allValid = Object.values(requirements).every(v => v);
            const passwordsMatch = confirmPassword.value === password.value && password.value !== '';
            submitBtn.disabled = !(allValid && passwordsMatch);
        }
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    </script>
</body>
</html>
<?php
}

// All handler functions (handleLogin, handleForgotPassword, handlePasswordReset) and email functions remain unchanged in their logic.
// They are omitted here for brevity but are part of the full file.

function handleLogin($conn) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $captcha = strtoupper(trim($_POST['captcha']));
    
    // Validate captcha
    if (!isset($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
        $_SESSION['login_error'] = 'Invalid security code. Please try again.';
        $_SESSION['login_email'] = $email;
        header('Location: login.php');
        exit;
    }
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Please fill in all fields.';
        $_SESSION['login_email'] = $email;
        header('Location: login.php');
        exit;
    }
    
    // Check user credentials
    $stmt = $conn->prepare("SELECT id, name, password, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['login_error'] = 'Invalid email or password.';
        $_SESSION['login_email'] = $email;
        header('Location: login.php');
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Check if account is verified
    if (!$user['is_verified']) {
        $_SESSION['login_error'] = 'Please verify your email address before logging in.';
        $_SESSION['login_email'] = $email;
        header('Location: login.php');
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        $_SESSION['login_error'] = 'Invalid email or password.';
        $_SESSION['login_email'] = $email;
        header('Location: login.php');
        exit;
    }
    
    // Login successful
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $email;
    
    // Update last login
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;
}

function handleForgotPassword($conn) {
    $email = trim($_POST['email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_error'] = 'Please enter a valid email address.';
        header('Location: login.php?action=forgot');
        exit;
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND is_verified = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Don't reveal if email exists or not for security
        $_SESSION['forgot_success'] = 'If an account with that email exists, a password reset code has been sent.';
        header('Location: login.php?action=forgot');
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Generate simple reset code (6 digits)
    $resetCode = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store code in database
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $resetCode, $expiry, $email);
    $stmt->execute();
    
    // Send reset email
    if (sendPasswordResetEmail($user['name'], $email, $resetCode)) {
        $_SESSION['forgot_success'] = 'Password reset code has been sent to your email.';
    } else {
        $_SESSION['forgot_error'] = 'Failed to send reset email. Please try again.';
    }
    
    header('Location: login.php?action=forgot');
    exit;
}

function handlePasswordReset($conn) {
    // Handle both token-based and code-based reset
    $resetCode = isset($_POST['reset_code']) ? trim($_POST['reset_code']) : '';
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $_SESSION['reset_error'] = 'Password does not meet security requirements.';
        if (!empty($token)) {
            header('Location: login.php?action=reset&token=' . urlencode($token));
        } else {
            header('Location: login.php?action=verify-reset&code=' . urlencode($resetCode));
        }
        exit;
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['reset_error'] = 'Passwords do not match.';
        if (!empty($token)) {
            header('Location: login.php?action=reset&token=' . urlencode($token));
        } else {
            header('Location: login.php?action=verify-reset&code=' . urlencode($resetCode));
        }
        exit;
    }
    
    // Verify reset code or token
    $resetIdentifier = !empty($token) ? $token : $resetCode;
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->bind_param("s", $resetIdentifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['login_error'] = 'Invalid or expired reset code. Please request a new password reset.';
        header('Location: login.php?action=forgot');
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Update password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user['id']);
    $stmt->execute();
    
    $_SESSION['login_success'] = 'Password reset successfully! You can now login with your new password.';
    header('Location: login.php');
    exit;
}

function sendPasswordResetEmail($name, $email, $resetCode) {
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
        $mail->setFrom('noreply@kvbalumni.me', 'KV Alumni Complaint Portal');
        $mail->addAddress($email, $name);

        //Content
        $mail->isHTML(true);
        $mail->Subject = '🔐 Password Reset Code - KV Alumni Portal';
        
        $mail->Body = getPasswordResetEmailTemplate($name, $resetCode);
        $mail->AltBody = "Hello $name,\n\nYou requested a password reset for your KV Alumni account.\n\nYour reset code: $resetCode\n\nGo to the login page and use the 'Have a Reset Code?' option.\n\nThis code will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nKV Alumni Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getPasswordResetEmailTemplate($name, $resetCode) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .code-box { background: #f8f9fa; border: 2px dashed #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
            .code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .instructions { background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 8px; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎓 KV Alumni Portal</h1>
                <p>Password Reset Request</p>
            </div>
            
            <div class='content'>
                <h2>Hello, $name!</h2>
                <p>You recently requested to reset your password for your KV Alumni Complaint Portal account.</p>
                
                <p>Use this reset code to change your password:</p>
                
                <div class='code-box'>
                    <div class='code'>$resetCode</div>
                    <p style='margin: 10px 0 0 0; color: #666;'>Your 6-digit reset code</p>
                </div>
                
                <div class='instructions'>
                    <strong>📋 How to use this code:</strong>
                    <ol style='margin: 10px 0 0 20px;'>
                        <li>Go to the login page</li>
                        <li>Click 'Forgot your password?'</li>
                        <li>Scroll down to 'Have a Reset Code?'</li>
                        <li>Enter the code above</li>
                        <li>Set your new password</li>
                    </ol>
                </div>
                
                <p><strong>⏰ Important:</strong> This code will expire in <strong>1 hour</strong> for security reasons.</p>
                
                <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated message from KV Alumni Complaint Portal</p>
                <p>&copy; 2025 KV Alumni. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

$conn->close();
?>