<?php
// landing.php
require_once 'config/db.php';
require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calldesk CRM | Professional Sales & Call Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-soft: #eef2ff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #f1f5f9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--white);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Glass Navbar */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8%;
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.03em;
        }

        .logo i {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .nav-links {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn-auth {
            padding: 12px 28px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-login {
            color: var(--primary);
            background: var(--primary-soft);
        }

        .btn-signup {
            background: var(--primary);
            color: white;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.25);
        }

        .btn-signup:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(99, 102, 241, 0.35);
        }

        /* Hero */
        .hero {
            padding: 160px 8% 120px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 95vh;
            background-image: 
                radial-gradient(circle at 100% 0%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 0% 100%, rgba(99, 102, 241, 0.08) 0%, transparent 40%);
        }

        .hero-content {
            flex: 1;
            max-width: 620px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 800;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .hero h1 {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            color: var(--text-main);
            letter-spacing: -0.05em;
            margin-bottom: 28px;
        }

        .hero h1 span {
            background: linear-gradient(to right, var(--primary), #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 48px;
            max-width: 550px;
        }

        /* Section Layouts */
        section {
            padding: 120px 8%;
        }

        .section-tag {
            text-align: center;
            color: var(--primary);
            font-weight: 800;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 16px;
        }

        .section-title {
            text-align: center;
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 80px;
            letter-spacing: -0.03em;
        }

        /* Features */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .feature-card {
            background: white;
            padding: 48px;
            border-radius: 32px;
            border: 1px solid var(--border);
            transition: all 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-12px);
            border-color: var(--primary);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 32px;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* Workflow Timeline */
        .workflow-timeline {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
        }

        .workflow-step {
            display: flex;
            gap: 48px;
            padding-bottom: 64px;
            position: relative;
        }

        .workflow-step::after {
            content: '';
            position: absolute;
            left: 24px;
            top: 48px;
            bottom: 0;
            width: 2px;
            background: #f1f5f9;
        }

        .workflow-step:last-child {
            padding-bottom: 0;
        }

        .workflow-step:last-child::after {
            display: none;
        }

        .step-number {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.25rem;
            flex-shrink: 0;
            z-index: 1;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.2);
        }

        .step-content h3 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .step-content p {
            font-size: 1.125rem;
            color: var(--text-muted);
            line-height: 1.7;
        }

        /* Call To Action */
        .cta-container {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 48px;
            padding: 100px 48px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-container h2 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            letter-spacing: -0.04em;
        }

        .cta-container p {
            font-size: 1.25rem;
            opacity: 0.8;
            margin-bottom: 48px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Mockup Visuals */
        .mockup-group {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            position: relative;
        }

        .phone-frame {
            width: 300px;
            height: 620px;
            background: #000;
            border-radius: 50px;
            border: 12px solid #1e293b;
            box-shadow: 0 50px 100px rgba(0,0,0,0.2);
            position: relative;
            transform: rotate(-3deg);
            z-index: 10;
            overflow: hidden;
        }

        .phone-screen {
            flex: 1;
            background: #fff;
            height: 100%;
        }

        .desktop-frame {
            position: absolute;
            top: 60px;
            right: 150px;
            width: 550px;
            height: 380px;
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 30px 60px rgba(0,0,0,0.1);
            padding: 30px;
            z-index: 5;
            transform: rotate(2deg);
        }

        /* Footer */
        .site-footer {
            padding: 64px 8% 32px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--text-muted);
        }

        @media (max-width: 1024px) {
            .hero { flex-direction: column; text-align: center; padding-top: 140px; }
            .hero h1 { font-size: 3.5rem; }
            .hero p { margin: 0 auto 40px; }
            .mockup-group { display: none; }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
            section { padding: 80px 8%; }
        }

        @media (max-width: 768px) {
            .feature-grid { grid-template-columns: 1fr; }
            .nav-links { display: none; }
            .hero h1 { font-size: 2.75rem; }
            .cta-container { padding: 64px 24px; }
            .cta-container h2 { font-size: 2.25rem; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="#" class="logo">
            <i class="fas fa-headset"></i> Calldesk
        </a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#how-it-works">Workflow</a>
            <a href="#target">Industries</a>
            <div style="display: flex; gap: 12px; align-items: center; margin-left: 20px;">
                <a href="login.php" class="btn-auth btn-login">Login</a>
                <a href="signup.php" class="btn-auth btn-signup">Get Started</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content" data-aos="fade-up">
            <div class="hero-badge">
                <i class="fas fa-bolt" style="color: #f59e0b;"></i> New: Real-time Call Tracking
            </div>
            <h1>Automate Your <span>Sales Pipeline</span> From Anywhere.</h1>
            <p>The only CRM designed to live inside your business phone. Capture calls, manage leads, and close deals without manual data entry.</p>
            <div style="display: flex; gap: 20px; align-items: center;">
                <a href="signup.php" class="btn-auth btn-signup" style="padding: 18px 40px; font-size: 1.1rem;">Start Free Trial</a>
                <a href="#how-it-works" class="nav-link" style="font-weight: 700; color: var(--text-main); text-decoration: none;">See How it Works <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: 0.8rem;"></i></a>
            </div>
        </div>
        <div class="mockup-group" data-aos="zoom-in" data-aos-delay="200">
            <div class="desktop-frame">
                <div style="height: 12px; width: 80px; background: #e2e8f0; border-radius: 100px; margin-bottom: 24px;"></div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
                    <div style="height: 70px; background: var(--primary-soft); border-radius: 16px;"></div>
                    <div style="height: 70px; background: #fff7ed; border-radius: 16px;"></div>
                    <div style="height: 70px; background: #f0fdf4; border-radius: 16px;"></div>
                </div>
                <div style="height: 100px; background: #f8fafc; border-radius: 16px;"></div>
            </div>
            <div class="phone-frame">
                <div class="phone-screen">
                    <div style="height: 150px; background: var(--primary); padding: 30px 20px; display: flex; flex-direction: column; justify-content: flex-end;">
                        <div style="font-weight: 800; color: white; font-size: 1.5rem;">Sync Today</div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem;">14 Unsynced Interactions</div>
                    </div>
                    <div style="padding: 24px;">
                        <div style="height: 60px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 16px; margin-bottom: 16px;"></div>
                        <div style="height: 60px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 16px; margin-bottom: 16px;"></div>
                        <div style="height: 60px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 16px; margin-bottom: 16px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" style="background: #fafbff;">
        <div class="section-tag">Powerful Features</div>
        <h2 class="section-title">Everything you need to <br>manage your team.</h2>
        <div class="feature-grid">
            <div class="feature-card" data-aos="fade-up">
                <div class="feature-icon"><i class="fas fa-sync-alt"></i></div>
                <h3>Smart Call Logs</h3>
                <p>Fetch incoming and outgoing calls directly from your Android device and sync them with your organization's CRM in one tap.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon"><i class="fas fa-user-plus"></i></div>
                <h3>Lead Integration</h3>
                <p>Instantly identify callers. If it's a new prospect, add them as a lead with pre-filled details from your phonebook.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon"><i class="fab fa-whatsapp"></i></div>
                <h3>WA Messaging</h3>
                <p>Send personalized WhatsApp messages immediately after any call using professional pre-built templates.</p>
            </div>
            <div class="feature-card" data-aos="fade-up">
                <div class="feature-icon" style="background: #fff7ed; color: #ea580c;"><i class="fas fa-bell"></i></div>
                <h3>Task Reminders</h3>
                <p>Set automated follow-up dates and remarks. Receive notifications so you never miss a critical business callback.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon" style="background: #fdf2f2; color: #dc2626;"><i class="fas fa-chart-pie"></i></div>
                <h3>Executive Tracking</h3>
                <p>Admins can monitor call duration, frequency, and conversion stats for every team member in real-time.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon" style="background: #f0fdf4; color: #16a34a;"><i class="fas fa-shield-alt"></i></div>
                <h3>Cloud Security</h3>
                <p>Your data is encrypted and backed up daily. Work offline and sync your changes whenever you have a connection.</p>
            </div>
        </div>
    </section>

    <!-- How it Works (Completed & Professional) -->
    <section id="how-it-works">
        <div class="section-tag">How it Works</div>
        <h2 class="section-title">A Unified Sales Workflow</h2>
        <div class="workflow-timeline">
            <div class="workflow-step" data-aos="fade-right">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Download & Connect</h3>
                    <p>Install the Calldesk Android app and log in with your organization credentials. Our system securely maps your device to your team account.</p>
                </div>
            </div>
            <div class="workflow-step" data-aos="fade-right" data-aos-delay="100">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Sync Daily Calls</h3>
                    <p>At the end of your calls or at scheduled intervals, tap 'Sync Logs'. Every business interaction is pushed to the cloud dashboard automatically.</p>
                </div>
            </div>
            <div class="workflow-step" data-aos="fade-right" data-aos-delay="200">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Manage & Update</h3>
                    <p>Identify prospects from your synced logs, tag them with statuses (Interested, Converted, etc.), and set follow-up tasks to stay organized.</p>
                </div>
            </div>
            <div class="workflow-step" data-aos="fade-right" data-aos-delay="300">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Grow Your Business</h3>
                    <p>Use visual reports on the web dashboard to analyze performance, optimize follow-up times, and increase your conversion rate by up to 40%.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Target Audience -->
    <section id="target" style="background: #f8fafc;">
        <div class="section-tag">Who is it for?</div>
        <h2 class="section-title">Built for Growing Teams</h2>
        <div class="feature-grid">
            <div class="feature-card" data-aos="fade-up" style="padding: 40px; text-align: center;">
                <div class="feature-icon" style="margin: 0 auto 24px; background: #e0f2fe; color: #0284c7;"><i class="fas fa-city" style="font-size: 1.5rem;"></i></div>
                <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 12px;">Real Estate</h4>
                <p>Track every inquiry from property portals and manage thousands of prospects effortlessy.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100" style="padding: 40px; text-align: center;">
                <div class="feature-icon" style="margin: 0 auto 24px; background: #f0fdf4; color: #16a34a;"><i class="fas fa-graduation-cap" style="font-size: 1.5rem;"></i></div>
                <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 12px;">Institutes</h4>
                <p>Perfect for admission teams to handle parent inquiries and student registrations on the go.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200" style="padding: 40px; text-align: center;">
                <div class="feature-icon" style="margin: 0 auto 24px; background: #fff7ed; color: #ea580c;"><i class="fas fa-briefcase" style="font-size: 1.5rem;"></i></div>
                <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 12px;">Agencies</h4>
                <p>Connect your field staff and sales agents with a unified tracking system that works anywhere.</p>
            </div>
        </div>
    </section>

    <!-- Final Call to Action -->
    <section style="padding: 80px 8% 120px;">
        <div class="cta-container" data-aos="zoom-in">
            <h2>Ready to skyrocket your sales?</h2>
            <p>Join over 500+ businesses who have eliminated data entry and increased conversions using Calldesk CRM.</p>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <a href="signup.php" class="btn-auth btn-signup" style="background: white; color: var(--text-main); padding: 20px 48px; font-size: 1.25rem;">Create Your Free Account</a>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <div>&copy; <?php echo date('Y'); ?> Calldesk CRM by Digital Seal. All rights reserved.</div>
        <div style="display: flex; gap: 32px; font-size: 0.9rem;">
            <a href="privacy-policy.php" style="color: inherit; text-decoration: none;">Privacy Policy</a>
            <a href="delete-account.php" style="color: inherit; text-decoration: none;">Delete Account</a>
            <a href="https://calldesk.offerplant.com/" style="color: var(--primary); text-decoration: none; font-weight: 800;">Official Site</a>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            offset: 120,
            once: true,
            easing: 'ease-out-cubic'
        });
    </script>
</body>
</html>
