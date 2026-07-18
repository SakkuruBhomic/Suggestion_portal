<?php
// Start session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PM Shri Kendriya Vidyalaya Bhandup Feedback & Suggestions Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google AdSense CODE: Place this just before </head> -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3444788914879855"
         crossorigin="anonymous"></script>
    
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
        }

        /* ===== Top Announcement Strip ===== */
        .top-announcement {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: var(--white);
            padding: 0.65rem 1rem;
            text-align: center;
            position: relative;
            z-index: 200;
            overflow: hidden;
        }

        .top-announcement::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.08) 45%,
                rgba(255, 255, 255, 0.15) 50%,
                rgba(255, 255, 255, 0.08) 55%,
                transparent 100%
            );
            animation: announcement-shine 4s ease-in-out infinite;
        }

        @keyframes announcement-shine {
            0% { transform: translateX(-30%); }
            100% { transform: translateX(30%); }
        }

        .top-announcement-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            position: relative;
            z-index: 1;
        }

        .top-announcement-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
            padding: 0.2rem 0.65rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            white-space: nowrap;
            border: 1px solid rgba(255, 255, 255, 0.25);
            flex-shrink: 0;
        }

        .top-announcement-badge i {
            font-size: 0.5rem;
            animation: pulse-live 1.5s ease-in-out infinite;
        }

        @keyframes pulse-live {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
        }

        .top-announcement-text {
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .top-announcement-text strong {
            font-weight: 700;
        }

        .top-announcement-dismiss {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: var(--white);
            cursor: pointer;
            font-size: 0.75rem;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
            flex-shrink: 0;
        }

        .top-announcement-dismiss:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .top-announcement.hidden {
            display: none;
        }

        /* Ad Container Styling */
        .ad-container {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--border-color);
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

        .nav-container {
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

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            color: var(--primary-color);
            background-color: var(--gray-50);
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--white);
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><polygon fill="rgba(255,255,255,0.1)" points="1000,100 1000,0 0,100"/></svg>') no-repeat;
            background-size: 100% 100%;
        }

        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-size: 3.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Main Content */
        .main-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 5rem 2rem;
        }

        /* Story Section */
        .story-section {
            background: var(--white);
            border-radius: 16px;
            padding: 4rem;
            margin-bottom: 4rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .section-subtitle {
            font-size: 1.125rem;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .story-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            margin-bottom: 3rem;
        }

        .story-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-primary);
        }

        .story-text p {
            margin-bottom: 1.5rem;
        }

        .story-highlight {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .story-highlight h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .story-highlight p {
            font-size: 1rem;
            opacity: 0.95;
        }

        /* Timeline */
        .timeline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .timeline-item {
            background: var(--gray-50);
            padding: 2rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .timeline-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            background: var(--white);
        }

        .timeline-step {
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .timeline-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .timeline-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        /* Features Section */
        .features-section {
            background: var(--white);
            border-radius: 16px;
            padding: 4rem;
            margin-bottom: 4rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: var(--gray-50);
            padding: 2.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
            background: var(--white);
            border-color: var(--primary-color);
        }

        .feature-icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.5rem;
            color: var(--white);
        }

        .feature-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Timer Section */
        .timer-section {
            background: var(--white);
            border-radius: 16px;
            padding: 4rem;
            margin-bottom: 4rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .timer-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .timer-description {
            color: var(--text-secondary);
            margin-bottom: 3rem;
            font-size: 1.125rem;
        }

        .countdown-display {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .time-unit {
            background: var(--gray-50);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            min-width: 120px;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .time-unit:hover {
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
        }

        .time-value {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
            line-height: 1;
        }

        .time-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            margin-top: 0.5rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
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
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, var(--primary-color) 100%);
            color: var(--white);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.125rem;
        }

        /* Team Section */
        .team-section {
            background: var(--white);
            border-radius: 16px;
            padding: 4rem;
            margin-bottom: 4rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-card {
            background: var(--gray-50);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .team-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            background: var(--white);
        }

        .team-avatar {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: var(--white);
            font-weight: 700;
        }

        .team-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .team-role {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Footer */
        .footer {
            background: var(--gray-900);
            color: var(--white);
            padding: 3rem 0 1rem;
            margin-top: 5rem;
        }

        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-link {
            color: var(--gray-300);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .footer-link:hover {
            color: var(--white);
        }

        .footer-divider {
            height: 1px;
            background: var(--gray-700);
            margin: 2rem 0;
        }

        .footer-bottom {
            color: var(--gray-400);
            font-size: 0.875rem;
            line-height: 1.8;
        }

        .footer-bottom a {
            color: var(--accent-color);
            text-decoration: none;
        }

        .footer-bottom a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--white);
                flex-direction: column;
                padding: 1rem;
                box-shadow: var(--shadow-lg);
                border-top: 1px solid var(--border-color);
            }

            .nav-links.show {
                display: flex;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.125rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .story-content {
                grid-template-columns: 1fr;
            }

            .countdown-display {
                gap: 1rem;
            }

            .time-unit {
                min-width: 90px;
                padding: 1.5rem 1rem;
            }

            .time-value {
                font-size: 2rem;
            }

            .main-content {
                padding: 3rem 1rem;
            }

            .nav-container {
                padding: 1rem;
            }

            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }

            .story-section,
            .features-section,
            .timer-section,
            .team-section {
                padding: 2rem;
            }

            .top-announcement-inner {
                padding: 0 2rem;
            }

            .top-announcement-text {
                font-size: 0.78rem;
            }

            .top-announcement-badge {
                font-size: 0.6rem;
                padding: 0.15rem 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .countdown-display {
                flex-direction: column;
                align-items: center;
            }

            .timeline {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- ===== Important Announcement Strip (Top of Page) ===== -->
    <div class="top-announcement" id="topAnnouncement">
        <div class="top-announcement-inner">
            <span class="top-announcement-badge"><i class="fas fa-circle"></i> Important</span>
            <span class="top-announcement-text">
                <strong>New updates are being rolled out gradually.</strong> Exciting features & improvements are on the way — stay tuned!
            </span>
            <button class="top-announcement-dismiss" onclick="dismissTopAnnouncement()" title="Dismiss">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="#" class="logo">
                <i class="fas fa-comments"></i>
                KV Bhandup Portal
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Student Login</a></li>
                <li><a href="register.php" class="nav-link"><i class="fas fa-user-plus"></i> Create Account</a></li>
                <li><a href="help.php" class="nav-link"><i class="fas fa-question-circle"></i> Help</a></li>
            </ul>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <h1 class="hero-title">PM Shri Kendriya Vidyalaya Bhandup<br>Feedback & Suggestions Portal</h1>
            <p class="hero-subtitle">Born from innovation and built with purpose. A digital solution to bridge the gap between students and administration, making every voice heard and every concern addressed efficiently.</p>
            
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </a>
                <a href="login.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
            </div>
        </div>
    </section>

    <!-- AdSense Ad Unit: Placed after Hero Section -->
    <div class="ad-container">
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3444788914879855"
             crossorigin="anonymous"></script>
        <!-- Test -->
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="ca-pub-3444788914879855"
             data-ad-slot="6485808217"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>
             (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Story Section -->
        <section class="story-section">
            <h2 class="section-title">Our Story: From Vision to Reality</h2>
            <p class="section-subtitle">How two passionate students transformed a common challenge into an innovative digital solution for their school community.</p>
            
            <div class="story-content">
                <div class="story-text">
                    <p><strong>It all began with a shared vision.</strong> Sakkuru Bhomic and Sanchay Sheshadri, two Class 12 students at Kendriya Vidyalaya Bhandup, recognized how challenging it was for students to effectively communicate their concerns with the school administration.</p>
                    
                    <p>Endless paperwork, long waiting times, delayed responses - these were everyday obstacles. Students often felt unheard, and important issues remained unresolved simply because there wasn't an efficient communication system in place.</p>
                    
                    <p><strong>"What if we could revolutionize this process?"</strong> This inspiring question led to countless hours of planning, innovative coding sessions, and months of dedicated development work.</p>
                </div>
                
                <div class="story-highlight">
                    <h3>Our Mission</h3>
                    <p>To create a transparent, efficient, and student-centered platform that ensures every voice is heard and every concern receives prompt and proper attention.</p>
                </div>
            </div>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-step">Phase 1</div>
                    <div class="timeline-title">Problem Analysis</div>
                    <div class="timeline-description">Identified communication barriers between students and administration. Thoroughly documented existing challenges and system inefficiencies.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-step">Phase 2</div>
                    <div class="timeline-title">Solution Design</div>
                    <div class="timeline-description">Conducted comprehensive research, gathered student feedback, and architected a robust solution framework for optimal user experience.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-step">Phase 3</div>
                    <div class="timeline-title">Development Sprint</div>
                    <div class="timeline-description">Intensive development phase involving advanced coding, rigorous testing, and continuous refinement to build a reliable platform.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-step">Phase 4</div>
                    <div class="timeline-title">Launch & Evolution</div>
                    <div class="timeline-description">Successfully deployed the portal, creating measurable improvements in school-student communication and ongoing feature enhancements.</div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <h2 class="section-title">Built for Students, By Students</h2>
            <p class="section-subtitle">Every feature was thoughtfully designed with real student needs in mind, ensuring maximum usability and effectiveness.</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Complete Privacy</h3>
                    <p class="feature-description">Your feedback remains confidential and secure. Only authorized administrators can access your submissions, ensuring safe and protected reporting.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Real-time Tracking</h3>
                    <p class="feature-description">Monitor your submission status from initial submission to final resolution. Stay informed throughout the entire process with transparent updates.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3 class="feature-title">Instant Notifications</h3>
                    <p class="feature-description">Receive immediate email notifications whenever there are updates to your feedback. Never miss important communications from administrators.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Universal Access</h3>
                    <p class="feature-description">Responsive design works seamlessly on any device. Submit and track feedback from your smartphone, tablet, or computer with ease.</p>
                </div>
            </div>
        </section>

        <!-- Timer Section -->
        <section class="timer-section">
            <h2 class="timer-title"><i class="fas fa-rocket"></i> Full Launch Countdown</h2>
            <p class="timer-description">The exciting countdown to our fully enhanced feedback management system</p>
            
            <div class="countdown-display">
                <div class="time-unit">
                    <span class="time-value" id="days">00</span>
                    <div class="time-label">Days</div>
                </div>
                <div class="time-unit">
                    <span class="time-value" id="hours">00</span>
                    <div class="time-label">Hours</div>
                </div>
                <div class="time-unit">
                    <span class="time-value" id="minutes">00</span>
                    <div class="time-label">Minutes</div>
                </div>
                <div class="time-unit">
                    <span class="time-value" id="seconds">00</span>
                    <div class="time-label">Seconds</div>
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="login.php" class="btn btn-success btn-lg">
                    <i class="fas fa-play"></i>
                    Start Using Portal
                </a>
            </div>
        </section>

        <!-- Team Section -->
        <section class="team-section">
            <h2 class="section-title">Meet the Development Team</h2>
            <p class="section-subtitle">The innovative students behind this transformative solution</p>
            
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-avatar">SB</div>
                    <h3 class="team-name">Sakkuru Bhomic</h3>
                    <p class="team-role">Lead Developer & Project Architect</p>
                </div>
                <div class="team-card">
                    <div class="team-avatar">SS</div>
                    <h3 class="team-name">Sanchay Sheshadri</h3>
                    <p class="team-role">Co-Developer & System Designer</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="help.php" class="footer-link">Help & Support</a>
                <a href="mailto:Owner@kvbalumni.me" class="footer-link">Contact Us</a>
                <a href="privacy.php" class="footer-link">Privacy Policy</a>
                <a href="terms.php" class="footer-link">Terms of Service</a>
                <a href="credits.php" class="footer-link">Credits</a>
            </div>
            <div class="footer-divider"></div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> PM Shri Kendriya Vidyalaya Bhandup Feedback & Suggestions Portal</p>
                <p>Developed with ❤️ by <strong>Sakkuru Bhomic</strong> | Contributor: <strong>Sanchay Sheshadri</strong></p>
                <p>Class 12 Computer Science Project | For support: <a href="mailto:Owner@kvbalumni.me">Owner@kvbalumni.me</a></p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Updated timer for current date (July 30, 2025 13:43:31 UTC)
        const TIMER_KEY = 'kvb_launch_deadline';
        
        function getDeadline() {
            let deadline = localStorage.getItem(TIMER_KEY);
            if (!deadline) {
                // Set deadline to August 15, 2025 (Independence Day launch)
                const deadlineTime = new Date('2025-08-15T00:00:00Z').getTime();
                localStorage.setItem(TIMER_KEY, deadlineTime);
                return deadlineTime;
            }
            return parseInt(deadline);
        }

        function updateCountdown() {
            const deadline = getDeadline();
            const now = new Date().getTime();
            const timeLeft = deadline - now;

            if (timeLeft > 0) {
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                document.getElementById('days').textContent = days.toString().padStart(2, '0');
                document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            } else {
                // Timer expired - show zeros
                document.getElementById('days').textContent = '00';
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
            }
        }

        // Initialize and update timer
        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Top announcement dismiss
        function dismissTopAnnouncement() {
            const banner = document.getElementById('topAnnouncement');
            banner.style.transition = 'max-height 0.4s ease, padding 0.4s ease, opacity 0.3s ease';
            banner.style.overflow = 'hidden';
            banner.style.opacity = '0';
            setTimeout(() => {
                banner.style.maxHeight = '0';
                banner.style.padding = '0';
            }, 150);
            setTimeout(() => {
                banner.classList.add('hidden');
            }, 500);
            sessionStorage.setItem('kvb_top_announcement_dismissed', 'true');
        }

        // Hide if already dismissed this session
        if (sessionStorage.getItem('kvb_top_announcement_dismissed') === 'true') {
            document.getElementById('topAnnouncement').classList.add('hidden');
        }

        // Mobile menu toggle
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('show');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            const navLinks = document.getElementById('navLinks');
            const toggleButton = document.querySelector('.mobile-menu-toggle');
            
            if (!navLinks.contains(e.target) && !toggleButton.contains(e.target)) {
                navLinks.classList.remove('show');
            }
        });

        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add subtle scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards and sections for animation
        document.querySelectorAll('.feature-card, .timeline-item, .team-card, .story-section, .features-section').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        console.log('🏫 PM Shri Kendriya Vidyalaya Bhandup Feedback & Suggestions Portal - Ready!');
        console.log('📅 Current Date: 2025-07-30 13:43:31 UTC');
        console.log('👥 Developed by Sakkuru Bhomic & Sanchay Sheshadri');
        console.log('⏰ Timer set to August 15, 2025 (Independence Day Launch)');
        console.log('🔐 Admin access: Create a direct link to admin.php or access via /admin.php URL');
    </script>

    <!-- Chatbot Script -->
    <script> window.chtlConfig = { chatbotId: "9833223713" } </script>
<script async data-id="9833223713" id="chtl-script" type="text/javascript" src="https://chatling.ai/js/embed.js"></script>
</html>