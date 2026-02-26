<?php
// privacy-policy.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Calldesk CRM by Digital Seal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background-color: #f8fafc;
            color: var(--text-main);
            line-height: 1.8;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        nav {
            height: 80px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8%;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.5rem;
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
        }

        .content {
            flex: 1;
            max-width: 900px;
            margin: 60px auto;
            padding: 60px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 40px;
            color: var(--text-main);
            border-bottom: 2px solid var(--primary-soft);
            padding-bottom: 20px;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 30px 0 15px;
            color: var(--primary-dark);
        }

        p {
            margin-bottom: 20px;
            color: var(--text-main);
        }

        ul {
            margin-bottom: 20px;
            padding-left: 20px;
        }

        li {
            margin-bottom: 10px;
        }

        footer {
            padding: 40px 8%;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            border-top: 1px solid var(--border);
            background: white;
        }
    </style>
</head>
<body>

    <nav>
        <a href="landing.php" class="logo">
            <i class="fas fa-headset"></i> Calldesk
        </a>
        <a href="landing.php" style="text-decoration: none; color: var(--primary); font-weight: 700;">Back to Home</a>
    </nav>

    <div class="content">
        <h1>Privacy Policy</h1>
        <p>Last updated: <?php echo date('F d, Y'); ?></p>
        
        <p>Welcome to <strong>Calldesk CRM</strong>, an application developed and managed by <strong>Digital Seal</strong> ("Digital Seal", "we", "us", or "our"). We are committed to protecting your privacy and ensuring that your personal data is handled in a safe and responsible manner.</p>

        <h2>1. Information We Collect</h2>
        <p>When you use the Calldesk CRM app and services, we collect various types of information to provide and improve our service:</p>
        <ul>
            <li><strong>Personal Information:</strong> Name, email address, mobile number, and organization details provided during registration.</li>
            <li><strong>Call Logs:</strong> The app requests permission to access your device's call logs to sync business interactions with the CRM. This includes call duration, time, and phone numbers.</li>
            <li><strong>Contact Information:</strong> Permission to access contacts to identify existing leads and create new ones.</li>
            <li><strong>Usage Data:</strong> Information about how you use the app, including features accessed and time spent.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use the collected information for the following purposes:</p>
        <ul>
            <li>To provide and maintain the Calldesk CRM service.</li>
            <li>To sync call logs and manage your sales pipeline effectively.</li>
            <li>To communicate with you regarding your account, updates, and support.</li>
            <li>To improve our application features and user experience.</li>
        </ul>

        <h2>3. Data Sharing and Disclosure</h2>
        <p>Digital Seal does not sell your personal data to third parties. We may share data only in the following circumstances:</p>
        <ul>
            <li>With your explicit consent.</li>
            <li>To comply with legal obligations or government requests.</li>
            <li>With service providers who assist us in operating our platform (e.g., cloud hosting).</li>
        </ul>

        <h2>4. Data Security</h2>
        <p>We implement industry-standard security measures to protect your data from unauthorized access, alteration, or destruction. Your data is stored on secure cloud servers with encryption.</p>

        <h2>5. Account and Data Deletion</h2>
        <p>Users have the right to request the deletion of their account and all associated data. You can submit a deletion request through our <a href="delete-account.php" style="color: var(--primary); font-weight: 700;">Account Deletion Page</a>. Once processed, all your data will be permanently removed from our servers.</p>

        <h2>6. Changes to This Policy</h2>
        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>

        <h2>7. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at:</p>
        <p><strong>Digital Seal</strong><br>
        Email: digitalsealorg@gmail.com<br>
        Website: https://calldesk.offerplant.com</p>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> Calldesk CRM by Digital Seal. All rights reserved.
    </footer>

</body>
</html>
