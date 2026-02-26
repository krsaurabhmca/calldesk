<?php
// signup.php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $org_name = trim(mysqli_real_escape_string($conn, $_POST['org_name']));
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $mobile = trim(mysqli_real_escape_string($conn, $_POST['mobile']));
    $password = $_POST['password'];

    if (empty($org_name) || empty($name) || empty($mobile) || empty($password)) {
        $error = 'All fields are required';
    } elseif (strlen($mobile) !== 10) {
        $error = 'Mobile number must be 10 digits';
    } else {
        // Start Transaction
        mysqli_begin_transaction($conn);
        try {
            // Check if user exists
            $check = mysqli_query($conn, "SELECT id FROM users WHERE mobile = '$mobile'");
            if (mysqli_num_rows($check) > 0) {
                throw new Exception("Mobile number already registered");
            }

            // Create Organization
            mysqli_query($conn, "INSERT INTO organizations (name) VALUES ('$org_name')") or throw new Exception(mysqli_error($conn));
            $org_id = mysqli_insert_id($conn);

            // Create Admin
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "INSERT INTO users (organization_id, name, mobile, password, role) VALUES ($org_id, '$name', '$mobile', '$hashed', 'admin')") or throw new Exception(mysqli_error($conn));
            
            // Default Sources
            $defaults = ['Facebook', 'Google', 'Website', 'WhatsApp', 'Referral'];
            foreach ($defaults as $source) {
                mysqli_query($conn, "INSERT INTO lead_sources (organization_id, source_name) VALUES ($org_id, '$source')");
            }

            mysqli_commit($conn);
            $success = "Registration successful! You can now <a href='login.php'>Login</a>";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Calldesk CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-visual {
            background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%) !important;
        }
        .auth-logo { text-decoration: none; }
        .auth-logo i {
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .btn-primary {
            background: #4f46e5 !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-visual">
            <h1 style="font-size: 3rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem;">Launch Your <br>Sales Engine.</h1>
            <p style="font-size: 1.25rem; opacity: 0.9; max-width: 500px;">Setup your organization in minutes and start managing leads effectively with Calldesk CRM.</p>
        </div>

        <div class="auth-form-container">
            <div class="auth-card-compact">
                <a href="landing.php" class="auth-logo">
                    <i class="fas fa-headset"></i>
                    <span>Calldesk</span>
                </a>

                <div style="margin-bottom: 2rem;">
                    <h2 style="font-size: 1.5rem; font-weight: 700;">Create Organization</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Join India's fastest growing CRM platform.</p>
                </div>

                <?php if ($error): ?>
                    <div style="background: #fff1f2; color: #be123c; padding: 0.875rem 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.875rem; border: 1px solid #fecdd3;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div style="background: #d1fae5; color: #065f46; padding: 0.875rem 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.875rem; border: 1px solid #6ee7b7;">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label class="form-label">Organization Name</label>
                        <input type="text" name="org_name" class="form-control" placeholder="Company Name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admin Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" placeholder="10 Digit Number" maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Create Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register & Setup</button>
                    
                    <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 1rem;">
                        By registering, you agree to our <a href="privacy-policy.php" style="color: var(--primary);">Privacy Policy</a>.
                    </p>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <span style="font-size: 0.875rem; color: var(--text-muted);">Already Have? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Login Here</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
