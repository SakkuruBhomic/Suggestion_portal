<?php
// Start session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credits - KV Bhandup Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #059669;
            --secondary-color: #047857;
            --accent-color: #10b981;
            --accent-light: #6ee7b7;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-colored: 0 10px 30px -5px rgba(5, 150, 105, 0.25);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--gray-50);
            overflow-x: hidden;
        }

        /* ─── SCROLLBAR ─────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--gray-100); }
        ::-webkit-scrollbar-thumb { background: var(--accent-color); border-radius: 3px; }

        /* ─── HEADER ────────────────────────────────────────────────── */
        .header {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 1px 0 rgba(0,0,0,0.08);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            transition: opacity 0.2s;
        }
        .logo:hover { opacity: 0.8; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.45rem 1rem;
            border-radius: 8px;
            border: 1.5px solid var(--border-color);
            background: var(--white);
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }
        .back-link:hover {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-colored);
            transform: translateY(-1px);
        }

        /* ─── HERO ──────────────────────────────────────────────────── */
        .hero {
            position: relative;
            text-align: center;
            padding: 6rem 2rem 5rem;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(16,185,129,0.15) 0%, transparent 70%),
                radial-gradient(ellipse 40% 40% at 10% 80%, rgba(5,150,105,0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        /* floating decorative blobs */
        .hero-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.18;
            pointer-events: none;
            animation: float 8s ease-in-out infinite;
        }
        .hero-blob-1 {
            width: 380px; height: 380px;
            background: var(--accent-color);
            top: -120px; left: -100px;
        }
        .hero-blob-2 {
            width: 280px; height: 280px;
            background: var(--primary-color);
            bottom: -60px; right: -60px;
            animation-delay: -4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-20px) scale(1.04); }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(5,150,105,0.1);
            color: var(--primary-color);
            border: 1px solid rgba(5,150,105,0.25);
            padding: 0.35rem 1rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .page-title span {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 560px;
            margin: 0 auto 2.5rem;
            line-height: 1.75;
        }

        .hero-stats {
            display: inline-flex;
            gap: 2.5rem;
            background: var(--white);
            border: 1px solid var(--border-color);
            padding: 1rem 2.5rem;
            border-radius: 999px;
            box-shadow: var(--shadow-lg);
        }

        .stat {
            text-align: center;
        }
        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 0.2rem;
        }

        /* ─── MAIN CONTAINER ────────────────────────────────────────── */
        .container {
            max-width: 1060px;
            margin: 0 auto;
            padding: 0 2rem 6rem;
        }

        /* ─── SECTION ───────────────────────────────────────────────── */
        .section {
            margin-bottom: 3.5rem;
            opacity: 0;
            transform: translateY(32px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        .section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .section-inner {
            background: var(--white);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        /* top accent stripe */
        .section-inner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 20px 20px 0 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-icon {
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(5,150,105,0.35);
        }

        .section-title-group h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.15rem;
        }

        .section-title-group p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* ─── CARDS GRID ────────────────────────────────────────────── */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
        }

        /* ─── CREDIT CARD ───────────────────────────────────────────── */
        .credit-card {
            position: relative;
            background: var(--gray-50);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            text-align: center;
            cursor: default;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }

        .credit-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(5,150,105,0.06) 0%, rgba(16,185,129,0.04) 100%);
            opacity: 0;
            transition: opacity 0.3s;
            border-radius: inherit;
        }

        .credit-card:hover {
            transform: translateY(-6px) scale(1.015);
            box-shadow: var(--shadow-xl), var(--shadow-colored);
            border-color: rgba(5,150,105,0.3);
            background: var(--white);
        }

        .credit-card:hover::after {
            opacity: 1;
        }

        /* highlight ring on featured card */
        .credit-card.featured {
            border-color: rgba(5,150,105,0.4);
            background: linear-gradient(145deg, #f0fdf9, var(--white));
        }

        .credit-card.featured::before {
            content: 'Lead';
            position: absolute;
            top: 12px; right: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: var(--white);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
        }

        /* ─── AVATAR ────────────────────────────────────────────────── */
        .avatar-wrap {
            position: relative;
            width: 5rem;
            height: 5rem;
            margin: 0 auto 1.1rem;
        }

        .avatar-ring {
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            z-index: 0;
        }

        .avatar-inner {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--white);
        }

        .avatar-inner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .avatar-fallback {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 50%;
            border: 3px solid var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 0.02em;
        }

        /* online dot for active contributors */
        .status-dot {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2.5px solid var(--white);
            z-index: 2;
        }
        .status-dot.active { background: #22c55e; }
        .status-dot.alumni { background: var(--text-secondary); }
        .status-dot.beta   { background: #f59e0b; }

        /* ─── CARD TEXT ─────────────────────────────────────────────── */
        .credit-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .credit-role {
            display: inline-block;
            color: var(--primary-color);
            background: rgba(5,150,105,0.09);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.7rem;
            border-radius: 999px;
            letter-spacing: 0.02em;
        }

        /* ─── DIVIDER ───────────────────────────────────────────────── */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-color) 30%, var(--border-color) 70%, transparent);
            margin: 0.75rem 0 1.5rem;
        }

        /* ─── FOOTER NOTE ───────────────────────────────────────────── */
        .footer-note {
            text-align: center;
            margin-top: 4rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .footer-note i { color: #ef4444; animation: pulse 1.8s ease-in-out infinite; }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.25); }
        }

        /* ─── RESPONSIVE ────────────────────────────────────────────── */
        @media (max-width: 640px) {
            .hero { padding: 4rem 1.5rem 3.5rem; }
            .hero-stats { gap: 1.5rem; padding: 0.85rem 1.5rem; }
            .container { padding: 0 1rem 4rem; }
            .section-inner { padding: 1.75rem 1.25rem; }
            .section-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .cards-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
        }
    </style>
</head>
<body>

    <!-- ── HEADER ──────────────────────────────────────────────── -->
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-comments"></i>
                KV Bhandup Portal
            </a>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </header>

    <!-- ── HERO ────────────────────────────────────────────────── -->
    <section class="hero">
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>

        <div class="hero-badge">
            <i class="fas fa-star"></i>
            Meet the Team
        </div>

        <h1 class="page-title">Project <span>Credits</span></h1>

        <p class="page-subtitle">
            Acknowledging everyone who contributed to making the
            PM&nbsp;Shri Kendriya Vidyalaya Bhandup Feedback Portal possible.
        </p>

        <div class="hero-stats">
            <div class="stat">
                <div class="stat-number">2</div>
                <div class="stat-label">Developers</div>
            </div>
            <div class="stat">
                <div class="stat-number">2</div>
                <div class="stat-label">Alumni</div>
            </div>
            <div class="stat">
                <div class="stat-number">3</div>
                <div class="stat-label">Beta Testers</div>
            </div>
        </div>
    </section>

    <!-- ── CONTENT ─────────────────────────────────────────────── -->
    <div class="container">

        <!-- Core Development Team -->
        <div class="section">
            <div class="section-inner">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-code"></i></div>
                    <div class="section-title-group">
                        <h2>Core Development Team</h2>
                        <p>The primary developers who built and maintain the portal</p>
                    </div>
                </div>
                <div class="cards-grid">

                    <!-- Sakkuru Bhomic -->
                    <div class="credit-card featured">
                        <div class="avatar-wrap">
                            <div class="avatar-ring"></div>
                            <div class="avatar-inner">
                                <img
                                    src="/assets/images/bhomic.png"
                                    alt="Sakkuru Bhomic"
                                    onerror="this.closest('.avatar-inner').outerHTML='<div class=\'avatar-fallback\'>SB</div>'"
                                >
                            </div>
                            <span class="status-dot active" title="Active"></span>
                        </div>
                        <div class="credit-name">Sakkuru Bhomic</div>
                        <span class="credit-role">Main Developer</span>
                    </div>

                    <!-- Sanchay Sheshadri -->
                    <div class="credit-card">
                        <div class="avatar-wrap">
                            <div class="avatar-ring"></div>
                            <div class="avatar-fallback">SS</div>
                            <span class="status-dot active" title="Active"></span>
                        </div>
                        <div class="credit-name">Sanchay Sheshadri</div>
                        <span class="credit-role">Contributor &amp; Main Tester</span>
                    </div>

                </div>
            </div>
        </div>

        <!-- Former Team Members -->
        <div class="section">
            <div class="section-inner">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-user-friends"></i></div>
                    <div class="section-title-group">
                        <h2>Former Team Members</h2>
                        <p>Previous contributors who helped shape the project</p>
                    </div>
                </div>
                <div class="cards-grid">

                    <div class="credit-card">
                        <div class="avatar-wrap">
                            <div class="avatar-ring"></div>
                            <div class="avatar-fallback">SG</div>
                            <span class="status-dot alumni" title="Alumni"></span>
                        </div>
                        <div class="credit-name">Saswat Gauda</div>
                        <span class="credit-role">Ex-Member</span>
                    </div>

                    <div class="credit-card">
                        <div class="avatar-wrap">
                            <div class="avatar-ring"></div>
                            <div class="avatar-fallback">AG</div>
                            <span class="status-dot alumni" title="Alumni"></span>
                        </div>
                        <div class="credit-name">Aditya Gautam</div>
                        <span class="credit-role">Ex-Member</span>
                    </div>

                </div>
            </div>
        </div>

        <!-- Beta Testing Team -->
        <div class="section">
            <div class="section-inner">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-bug"></i></div>
                    <div class="section-title-group">
                        <h2>Beta Testing Team</h2>
                        <p>Students who helped test and improve the portal during development</p>
                    </div>
                </div>
                <div class="cards-grid">

                    <div class="credit-card">
                        <div class="avatar-wrap">
                            <div class="avatar-ring"></div>
                            <div class="avatar-fallback">AM</div>
                            <span class="status-dot beta" title="Beta Tester"></span>
                        </div>
                        <div class="credit-name">Anmol Mishra</div>
                        <span class="credit-role">Beta Tester</span>
                    </div>

                    <div class="credit-card">
                        <div class="avatar-wrap">
                            <div class="avatar-ring"></div>
                            <div class="avatar-fallback">KM</div>
                            <span class="status-dot beta" title="Beta Tester"></span>
                        </div>
                        <div class="credit-name">Khushi Mishra</div>
                        <span class="credit-role">Beta Tester</span>
                    </div>

                    <div class="credit-card">
                        <div class="avatar-wrap">
                            <div class="avatar-ring"></div>
                            <div class="avatar-fallback">SA</div>
                            <span class="status-dot beta" title="Beta Tester"></span>
                        </div>
                        <div class="credit-name">Sanjana Rao Anthakapalli</div>
                        <span class="credit-role">Beta Tester</span>
                    </div>

                </div>
            </div>
        </div>

        <!-- Footer note -->
        <div class="footer-note">
            <i class="fas fa-heart"></i>
            Built with love for PM Shri Kendriya Vidyalaya Bhandup
        </div>

    </div><!-- /container -->

    <script>
        // Intersection Observer — fade-in sections on scroll
        const sections = document.querySelectorAll('.section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    // stagger delay based on DOM order
                    const delay = Array.from(sections).indexOf(entry.target) * 100;
                    setTimeout(() => entry.target.classList.add('visible'), delay);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        sections.forEach(s => observer.observe(s));

        // Card tilt micro-interaction
        document.querySelectorAll('.credit-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect  = card.getBoundingClientRect();
                const x     = (e.clientX - rect.left) / rect.width  - 0.5;
                const y     = (e.clientY - rect.top)  / rect.height - 0.5;
                card.style.transform = `translateY(-6px) scale(1.015) rotateX(${-y * 6}deg) rotateY(${x * 6}deg)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    </script>
</body>
</html>