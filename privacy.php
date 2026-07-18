<?php
// Start session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy - KV Bhandup Portal</title>
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
            <h1>Privacy Policy</h1>
            <p class="last-updated">Last updated: July 28, 2025</p>

            <h2>1. Information We Collect</h2>
            <p>When you use the KV Bhandup Feedback Portal, we collect the following information:</p>
            <ul>
                <li><strong>Personal Information:</strong> Name, email address, student ID, and class information</li>
                <li><strong>Feedback Data:</strong> Your feedback submissions, complaints, and suggestions</li>
                <li><strong>Usage Information:</strong> How you interact with our portal, including login times and feature usage</li>
                <li><strong>Technical Data:</strong> IP address, browser type, and device information for security purposes</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>We use your information to:</p>
            <ul>
                <li>Process and respond to your feedback and complaints</li>
                <li>Improve communication between students and school administration</li>
                <li>Send you updates about your submissions via email</li>
                <li>Maintain the security and integrity of our platform</li>
                <li>Analyze usage patterns to improve our services</li>
            </ul>

            <h2>3. Information Sharing</h2>
            <p>Your information is shared only with:</p>
            <ul>
                <li><strong>School Administration:</strong> Authorized school staff who need to address your feedback</li>
                <li><strong>Technical Team:</strong> Our developers (Bhomic Sakkuru and Sanchay Seshadri) for system maintenance</li>
                <li><strong>No Third Parties:</strong> We never sell or share your data with external organizations</li>
            </ul>

            <h2>4. Data Security</h2>
            <p>We implement robust security measures including:</p>
            <ul>
                <li>Encrypted data transmission and storage</li>
                <li>Secure authentication systems</li>
                <li>Regular security audits and updates</li>
                <li>Access controls limiting who can view your information</li>
            </ul>

            <h2>5. Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal data stored in our system</li>
                <li>Request correction of inaccurate information</li>
                <li>Delete your account and associated data</li>
                <li>Opt out of non-essential email communications</li>
                <li>Lodge a complaint about our data practices</li>
            </ul>

            <h2>6. Data Retention</h2>
            <p>We retain your information for:</p>
            <ul>
                <li><strong>Active Accounts:</strong> As long as your account remains active</li>
                <li><strong>Feedback Records:</strong> Academic year + 2 years for record keeping</li>
                <li><strong>System Logs:</strong> 90 days for security purposes</li>
            </ul>

            <h2>7. Cookies and Tracking</h2>
            <p>Our portal uses minimal cookies for:</p>
            <ul>
                <li>Maintaining your login session</li>
                <li>Remembering your preferences</li>
                <li>Ensuring platform security</li>
                <li>Google AdSense for funding (anonymous data only)</li>
            </ul>

            <h2>8. Contact Us</h2>
            <div class="contact-info">
                <p>If you have questions about this Privacy Policy, contact us:</p>
                <ul>
                    <li><strong>Email:</strong> Owner@kvbalumni.me</li>
                    <li><strong>Portal:</strong> Use the Help section for support</li>
                    <li><strong>School:</strong> PM Shri Kendriya Vidyalaya Bhandup</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>