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
    <title>Register | Calldesk SaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-visual">
            <h2>Start Your <br>SaaS Journey.</h2>
            <p>Setup your organization in minutes and start managing leads effectively.</p>
        </div>

        <div class="auth-form-container">
            <div class="auth-card-compact">
                <div class="auth-logo">
                    <i class="fas fa-headset"></i>
                    <span>Calldesk</span>
                </div>

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
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <span style="font-size: 0.875rem; color: var(--text-muted);">Already Have? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Login Here</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
