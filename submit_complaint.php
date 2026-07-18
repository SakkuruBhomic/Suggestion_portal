<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info for display
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$error = $success = '';
$form_data = ['title' => '', 'description' => '', 'category' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    
    // Store form data to repopulate on error
    $form_data = ['title' => $title, 'description' => $description, 'category' => $category];

    if (!$title || !$description || !$category) {
        $error = "Please fill out all required fields.";
    } elseif (strlen($title) < 5) {
        $error = "Title must be at least 5 characters long.";
    } elseif (strlen($description) < 20) {
        $error = "Description must be at least 20 characters long.";
    } else {
        try {
            // Set default priority to "Medium" for all complaints
            $stmt = $conn->prepare("INSERT INTO complaints (user_id, title, description, category, priority, status, created_at) VALUES (?, ?, ?, ?, 'Medium', 'pending', NOW())");
            $stmt->bind_param("isss", $_SESSION['user_id'], $title, $description, $category);
            if ($stmt->execute()) {
                $success = "Your complaint has been submitted successfully! Complaint ID: #" . $conn->insert_id . ". Please check back in 24-48 hours for updates.";
                $form_data = ['title' => '', 'description' => '', 'category' => '']; // Clear form on success
            } else {
                $error = "Error submitting complaint. Please try again.";
            }
        } catch (Exception $e) {
            $error = "Database error occurred. Please contact support.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint - KV Bhandup Portal</title>
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

        .form-container {
            background: var(--white);
            border-radius: 16px;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color), var(--primary-color));
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 2rem;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-400);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .step.active {
            color: var(--primary-color);
        }

        .step.completed {
            color: var(--success-color);
        }

        .step-number {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .step.active .step-number {
            background: var(--primary-color);
            color: var(--white);
        }

        .step.completed .step-number {
            background: var(--success-color);
            color: var(--white);
        }

        /* Guidelines */
        .guidelines {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid var(--success-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .guidelines-title {
            color: var(--success-color);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .guidelines ul {
            list-style: none;
            color: var(--gray-700);
        }

        .guidelines li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.5;
        }

        .guidelines li::before {
            content: '✓';
            color: var(--success-color);
            font-weight: 600;
            margin-top: 0.125rem;
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

        .alert-icon {
            font-size: 1.25rem;
        }

        /* Form Styling */
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

        .form-group input,
        .form-group select,
        .form-group textarea {
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

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 6rem;
            line-height: 1.6;
        }

        .char-counter {
            position: absolute;
            bottom: -1.25rem;
            right: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-400);
        }

        .char-counter.warning {
            color: var(--warning-color);
        }

        .char-counter.error {
            color: var(--danger-color);
        }

        /* Form Validation */
        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: var(--danger-color);
            background: #fef2f2;
        }

        .form-group.success input,
        .form-group.success select,
        .form-group.success textarea {
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

        /* Auto-save indicator */
        .auto-save {
            position: fixed;
            top: 6rem;
            right: 1rem;
            background: var(--success-color);
            color: var(--white);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
            box-shadow: var(--shadow-md);
        }

        .auto-save.show {
            opacity: 1;
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

            .form-container {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .progress-steps {
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }

            .submit-btn {
                width: 100%;
            }
        }

        /* Icon improvements */
        .icon {
            width: 1rem;
            height: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Auto-save indicator -->
    <div class="auto-save" id="autoSaveIndicator">
        <i class="fas fa-save"></i> Draft saved automatically
    </div>

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
                <span><?php echo htmlspecialchars($user['name']); ?></span>
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">Submit New Complaint</h1>
            <p class="page-subtitle">
                Help us improve by sharing your concerns. We're here to listen and take action.
            </p>
        </div>

        <div class="form-container">
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step active" id="step1">
                    <div class="step-number">1</div>
                    <span>Fill Details</span>
                </div>
                <div class="step" id="step2">
                    <div class="step-number">2</div>
                    <span>Review</span>
                </div>
                <div class="step" id="step3">
                    <div class="step-number">3</div>
                    <span>Submit</span>
                </div>
            </div>

            <!-- Guidelines -->
            <div class="guidelines">
                <div class="guidelines-title">
                    <i class="fas fa-lightbulb"></i>
                    Submission Guidelines
                </div>
                <ul>
                    <li>Be specific and detailed in your description</li>
                    <li>Choose the most appropriate category for faster resolution</li>
                    <li>All complaints are reviewed and prioritized by our team</li>
                    <li>Provide examples or evidence if applicable</li>
                    <li>Check back on the website in 24-48 hours for updates and responses</li>
                </ul>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle alert-icon"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <!-- Complaint Form -->
            <form method="POST" id="complaintForm" novalidate>
                <div class="form-group">
                    <label for="title">
                        <i class="fas fa-tag icon"></i>
                        Complaint Title <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           placeholder="Brief, descriptive title of your complaint"
                           value="<?php echo htmlspecialchars($form_data['title']); ?>"
                           maxlength="100"
                           required>
                    <div class="char-counter" id="titleCounter">0/100</div>
                    <div class="validation-message" id="titleValidation"></div>
                </div>

                <div class="form-group">
                    <label for="category">
                        <i class="fas fa-folder icon"></i>
                        Category <span class="required">*</span>
                    </label>
                    <select name="category" id="category" required>
                        <option value="">Select Category</option>
                        <option value="Academics" <?php echo ($form_data['category'] == 'Academics') ? 'selected' : ''; ?>>
                            📚 Academics & Curriculum
                        </option>
                        <option value="Facilities" <?php echo ($form_data['category'] == 'Facilities') ? 'selected' : ''; ?>>
                            🏢 Infrastructure & Facilities
                        </option>
                        <option value="Administration" <?php echo ($form_data['category'] == 'Administration') ? 'selected' : ''; ?>>
                            🏛️ Administration & Services
                        </option>
                        <option value="Technology" <?php echo ($form_data['category'] == 'Technology') ? 'selected' : ''; ?>>
                            💻 Technology & IT Issues
                        </option>
                        <option value="Finance" <?php echo ($form_data['category'] == 'Finance') ? 'selected' : ''; ?>>
                            💰 Financial & Fee Related
                        </option>
                        <option value="Student_Life" <?php echo ($form_data['category'] == 'Student_Life') ? 'selected' : ''; ?>>
                            🎓 Student Life & Activities
                        </option>
                        <option value="Other" <?php echo ($form_data['category'] == 'Other') ? 'selected' : ''; ?>>
                            📝 Other Issues
                        </option>
                    </select>
                    <div class="validation-message" id="categoryValidation"></div>
                </div>

                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-file-text icon"></i>
                        Detailed Description <span class="required">*</span>
                    </label>
                    <textarea name="description" 
                              id="description" 
                              placeholder="Please provide a detailed description of your complaint. Include relevant dates, locations, people involved, and any steps you've already taken to address the issue."
                              maxlength="2000"
                              required><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                    <div class="char-counter" id="descriptionCounter">0/2000</div>
                    <div class="validation-message" id="descriptionValidation"></div>
                </div>

                <div class="submit-section">
                    <div class="submit-helper">
                        <i class="fas fa-info-circle"></i>
                        Please review your complaint details before submitting.<br>
                        You will receive a complaint tracking ID and can check back here for updates.<br>
                        <small style="color: var(--gray-500);">All complaints are automatically set to medium priority and will be reviewed by our team within 24-48 hours.</small>
                    </div>
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Complaint
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation and enhancements
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('complaintForm');
            const titleField = document.getElementById('title');
            const categoryField = document.getElementById('category');
            const descriptionField = document.getElementById('description');
            const submitBtn = document.getElementById('submitBtn');

            // Character counters
            function updateCharCounter(field, counterId, maxLength) {
                const counter = document.getElementById(counterId);
                const currentLength = field.value.length;
                counter.textContent = `${currentLength}/${maxLength}`;
                
                counter.classList.remove('warning', 'error');
                
                if (currentLength > maxLength * 0.9) {
                    counter.classList.add('warning');
                }
                
                if (currentLength === maxLength) {
                    counter.classList.add('error');
                }
            }

            titleField.addEventListener('input', () => updateCharCounter(titleField, 'titleCounter', 100));
            descriptionField.addEventListener('input', () => updateCharCounter(descriptionField, 'descriptionCounter', 2000));

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
                    validation.innerHTML = `<i class="fas fa-times-circle"></i> ${message}`;
                    validation.className = 'validation-message error';
                } else if (value && isValid) {
                    formGroup.classList.add('success');
                    validation.innerHTML = '<i class="fas fa-check-circle"></i> Looks good!';
                    validation.className = 'validation-message success';
                } else {
                    validation.innerHTML = '';
                    validation.className = 'validation-message';
                }

                return isValid;
            }

            // Validation rules
            const titleRules = [
                { test: (v) => v.length >= 5, message: 'Title must be at least 5 characters long' },
                { test: (v) => v.length <= 100, message: 'Title must not exceed 100 characters' }
            ];

            const descriptionRules = [
                { test: (v) => v.length >= 20, message: 'Description must be at least 20 characters long' },
                { test: (v) => v.length <= 2000, message: 'Description must not exceed 2000 characters' }
            ];

            // Add event listeners for real-time validation
            titleField.addEventListener('blur', () => validateField(titleField, 'titleValidation', titleRules));
            descriptionField.addEventListener('blur', () => validateField(descriptionField, 'descriptionValidation', descriptionRules));

            // Update initial counters
            updateCharCounter(titleField, 'titleCounter', 100);
            updateCharCounter(descriptionField, 'descriptionCounter', 2000);

            // Form submission with progress steps
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate all fields
                const titleValid = validateField(titleField, 'titleValidation', titleRules);
                const descriptionValid = validateField(descriptionField, 'descriptionValidation', descriptionRules);
                const categoryValid = categoryField.value !== '';

                if (!categoryValid) {
                    document.getElementById('categoryValidation').innerHTML = '<i class="fas fa-times-circle"></i> Please select a category';
                    document.getElementById('categoryValidation').className = 'validation-message error';
                    categoryField.closest('.form-group').classList.add('error');
                }

                if (titleValid && descriptionValid && categoryValid) {
                    // Update progress steps
                    document.getElementById('step1').classList.add('completed');
                    document.getElementById('step1').classList.remove('active');
                    document.getElementById('step2').classList.add('completed');
                    document.getElementById('step2').classList.remove('active');
                    document.getElementById('step3').classList.add('active');

                    // Show loading state
                    submitBtn.innerHTML = '<div class="loading"></div> Submitting...';
                    submitBtn.disabled = true;

                    // Submit form after short delay for UX
                    setTimeout(() => {
                        form.submit();
                    }, 1000);
                } else {
                    // Scroll to first error
                    const firstError = document.querySelector('.form-group.error');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            // Auto-save draft functionality
            let autoSaveTimeout;
            function autoSave() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    const formData = {
                        title: titleField.value,
                        category: categoryField.value,
                        description: descriptionField.value
                    };
                    
                    localStorage.setItem('complaintDraft', JSON.stringify(formData));
                    
                    // Show auto-save indicator
                    const indicator = document.getElementById('autoSaveIndicator');
                    indicator.classList.add('show');
                    setTimeout(() => {
                        indicator.classList.remove('show');
                    }, 2000);
                }, 2000);
            }

            // Load draft on page load
            const savedDraft = localStorage.getItem('complaintDraft');
            if (savedDraft && !titleField.value && !descriptionField.value) {
                const draft = JSON.parse(savedDraft);
                titleField.value = draft.title || '';
                categoryField.value = draft.category || '';
                descriptionField.value = draft.description || '';
                
                updateCharCounter(titleField, 'titleCounter', 100);
                updateCharCounter(descriptionField, 'descriptionCounter', 2000);
            }

            // Auto-save on input
            [titleField, categoryField, descriptionField].forEach(field => {
                field.addEventListener('input', autoSave);
            });

            // Clear draft on successful submission
            <?php if ($success): ?>
            localStorage.removeItem('complaintDraft');
            <?php endif; ?>

            console.log('✅ KV Bhandup Complaint Form Ready!');
            console.log('📅 Current Date: 2025-08-06 13:19:38 UTC');
            console.log('👤 Current User: SakkuruBhomic');
        });
    </script>
</body>
</html>