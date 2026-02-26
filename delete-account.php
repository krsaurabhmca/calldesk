<?php
// delete-account.php
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account | Calldesk CRM by Digital Seal</title>
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
            --danger: #ef4444;
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
            line-height: 1.6;
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
            max-width: 800px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            color: var(--text-main);
        }

        p {
            margin-bottom: 20px;
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .info-box {
            background: #fff1f2;
            border-left: 4px solid var(--danger);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            color: #991b1b;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .info-box p {
            color: #b91c1c;
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        form {
            background: #f8fafc;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid var(--border);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        input, textarea {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus, textarea:focus {
            border-color: var(--primary);
        }

        .btn-delete {
            background: var(--danger);
            color: white;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
            transition: opacity 0.2s;
        }

        .btn-delete:hover {
            opacity: 0.9;
        }

        footer {
            padding: 40px 8%;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            border-top: 1px solid var(--border);
            background: white;
        }

        .success-msg {
            background: #d1fae5;
            color: #065f46;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 24px;
            font-weight: 600;
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
        <h1>Account Deletion Request</h1>
        <p>We are sorry to see you go. If you wish to delete your <strong>Calldesk CRM</strong> account and all associated data, please fill out the form below. Our team at <strong>Digital Seal</strong> will process your request within 7 business days.</p>

        <div class="info-box">
            <h3>Warning: Permanent Action</h3>
            <p>Deleting your account will permanently remove all your lead data, call logs, organization settings, and executive accounts. This action cannot be undone.</p>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = htmlspecialchars($_POST['name']);
            $email = htmlspecialchars($_POST['email']);
            $org = htmlspecialchars($_POST['org']);
            $reason = htmlspecialchars($_POST['reason']);
            
            // In a real app, we would save this to a database or send an email
            // For now, we'll show a success message
            echo '<div class="success-msg">Your request has been submitted successfully. We will contact you at ' . $email . ' once the deletion is complete.</div>';
        }
        ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Enter your full name" required>
            </div>
            <div class="form-group">
                <label>Email Address / Mobile Number</label>
                <input type="text" name="email" placeholder="Email or Mobile used for registration" required>
            </div>
            <div class="form-group">
                <label>Organization Name</label>
                <input type="text" name="org" placeholder="Your registered company name" required>
            </div>
            <div class="form-group">
                <label>Reason for Deletion (Optional)</label>
                <textarea name="reason" rows="4" placeholder="How can we improve?"></textarea>
            </div>
            <button type="submit" class="btn-delete">Request Account Deletion</button>
        </form>

        <div style="margin-top: 40px; font-size: 0.9rem; color: var(--text-muted);">
            <p><strong>Digital Seal</strong><br>
            Developer of Calldesk CRM<br>
            Contact: digitalsealorg@gmail.com</p>
        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> Calldesk CRM by Digital Seal. All rights reserved.
    </footer>

</body>
</html>
