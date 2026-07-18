<?php
// Start session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms of Service - KV Bhandup Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --border-color: #e5e7eb;
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--gray-50);
        }

        .header {
            background: var(--white);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
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

        .container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .document {
            background: var(--white);
            border-radius: 16px;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .document h1 {
            font-family: 'Poppins', sans-serif;
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .last-updated {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-style: italic;
        }

        .document h2 {
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            font-size: 1.5rem;
            margin: 2rem 0 1rem 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .document p, .document li {
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .document ul {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 2rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .back-link:hover {
            background-color: var(--gray-50);
        }

        .contact-info {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }

        .warning-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem;
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-comments"></i>
                KV Bhandup Portal
            </a>
        </div>
    </header>

    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>

        <div class="document">
            <h1>Terms of Service</h1>
            <p class="last-updated">Last updated: July 28, 2025</p>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using the PM Shri Kendriya Vidyalaya Bhandup Feedback & Suggestions Portal, you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you may not use our service.</p>

            <h2>2. Eligibility</h2>
            <p>This portal is exclusively for:</p>
            <ul>
                <li>Current students of PM Shri Kendriya Vidyalaya Bhandup</li>
                <li>Authorized school staff and administrators</li>
                <li>Users must be at least 13 years old or have parental consent</li>
                <li>Users must provide accurate and truthful information</li>
            </ul>

            <h2>3. Account Responsibilities</h2>
            <p>You are responsible for:</p>
            <ul>
                <li>Maintaining the confidentiality of your login credentials</li>
                <li>All activities that occur under your account</li>
                <li>Immediately notifying us of any unauthorized use</li>
                <li>Providing accurate and up-to-date information</li>
            </ul>

            <h2>4. Acceptable Use Policy</h2>
            <div class="warning-box">
                <p><strong>Important:</strong> This platform is for constructive feedback and legitimate concerns only.</p>
            </div>
            <p>You agree NOT to:</p>
            <ul>
                <li>Submit false, misleading, or malicious content</li>
                <li>Harass, bully, or defame any individual</li>
                <li>Use offensive, inappropriate, or abusive language</li>
                <li>Share confidential information about other students</li>
                <li>Attempt to hack, disrupt, or compromise the system</li>
                <li>Create multiple accounts or impersonate others</li>
                <li>Use the platform for any illegal activities</li>
            </ul>

            <h2>5. Content Guidelines</h2>
            <p>All feedback submissions should be:</p>
            <ul>
                <li><strong>Constructive:</strong> Focused on improving school experiences</li>
                <li><strong>Respectful:</strong> Maintains dignity of all individuals</li>
                <li><strong>Truthful:</strong> Based on facts and honest experiences</li>
                <li><strong>Relevant:</strong> Related to school matters and policies</li>
                <li><strong>Appropriate:</strong> Suitable for an educational environment</li>
            </ul>

            <h2>6. Privacy and Confidentiality</h2>
            <p>We are committed to protecting your privacy:</p>
            <ul>
                <li>Your feedback is confidential and secure</li>
                <li>Only authorized personnel can access submissions</li>
                <li>We follow our Privacy Policy for data handling</li>
                <li>Anonymous feedback options are available when appropriate</li>
            </ul>

            <h2>7. Service Availability</h2>
            <p>Please note that:</p>
            <ul>
                <li>The portal is currently in Beta testing phase</li>
                <li>Service interruptions may occur for maintenance</li>
                <li>We strive for 99% uptime but cannot guarantee it</li>
                <li>Features may be added, modified, or removed</li>
            </ul>

            <h2>8. Intellectual Property</h2>
            <p>This portal and its content are protected by:</p>
            <ul>
                <li>Copyright owned by the development team</li>
                <li>School policies and educational fair use</li>
                <li>Open source components with respective licenses</li>
                <li>Users retain rights to their original feedback content</li>
            </ul>

            <h2>9. Limitation of Liability</h2>
            <p>This is an educational project developed by students. We:</p>
            <ul>
                <li>Provide the service "as is" without warranties</li>
                <li>Are not liable for any damages from portal use</li>
                <li>Recommend backing up important communications</li>
                <li>May not be able to guarantee data recovery</li>
            </ul>

            <h2>10. Termination</h2>
            <p>We may suspend or terminate accounts for:</p>
            <ul>
                <li>Violation of these terms</li>
                <li>Inappropriate or harmful behavior</li>
                <li>Graduation or leaving the school</li>
                <li>Extended inactivity (over 1 year)</li>
            </ul>

            <h2>11. Changes to Terms</h2>
            <p>We reserve the right to update these terms. Users will be notified of significant changes through:</p>
            <ul>
                <li>Email notifications to registered users</li>
                <li>Announcements on the portal homepage</li>
                <li>Updated "Last modified" date on this page</li>
            </ul>

            <h2>12. Contact Information</h2>
            <div class="contact-info">
                <p>For questions about these Terms of Service:</p>
                <ul>
                    <li><strong>Email:</strong> Owner@kvbalumni.me</li>
                    <li><strong>Developers:</strong> Bhomic Sakkuru & Sanchay Seshadri</li>
                    <li><strong>School:</strong> PM Shri Kendriya Vidyalaya Bhandup</li>
                    <li><strong>Help:</strong> Use the portal's help section</li>
                </ul>
                <p><em>By using our portal, you acknowledge that you have read, understood, and agree to these Terms of Service.</em></p>
            </div>
        </div>
    </div>
</body>
</html>