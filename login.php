<?php
// login.php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $mobile = trim(mysqli_real_escape_string($conn, $_POST['mobile']));
    $password = $_POST['password']; // Don't trim password as it might have spaces

    // Debug: echo "Mobile: [$mobile]"; // Comment this out after testing

    $sql = "SELECT u.*, o.name as organization_name 
            FROM users u 
            LEFT JOIN organizations o ON u.organization_id = o.id 
            WHERE u.mobile = '$mobile'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Check if user is active
        if ($user['status'] == 0) {
            $error = 'Your account is currently disabled';
        } else {
            // Verify password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['organization_id'] = $user['organization_id'];
                $_SESSION['organization_name'] = $user['organization_name'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                redirect('index.php');
            } else {
                $error = 'Invalid mobile number or password';
            }
        }
    } else {
        $error = 'Invalid mobile number or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Calldesk CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-visual {
            background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%) !important;
        }
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
        <!-- Visual Section (Desktop Only) -->
        <div class="auth-visual">
            <h1 style="font-size: 3rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem;">Accelerate Your <br>Sales Pipeline.</h1>
            <p style="font-size: 1.25rem; opacity: 0.9; max-width: 500px;">Manage leads, track follow-ups, and convert more prospects with Calldesk CRM.</p>
            <div style="margin-top: 3rem; display: flex; gap: 2rem;">
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="font-size: 1.5rem; font-weight: 800;">50%</div>
                    <div style="font-size: 0.875rem; opacity: 0.8;">Higher conversion</div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="font-size: 1.5rem; font-weight: 800;">10k+</div>
                    <div style="font-size: 0.875rem; opacity: 0.8;">Calls tracked</div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="auth-form-container">
            <div class="auth-card-compact">
                <a href="landing.php" class="auth-logo" style="text-decoration: none;">
                    <i class="fas fa-headset"></i>
                    <span>Calldesk</span>
                </a>

                <div style="margin-bottom: 2rem;">
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Sign in</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.25rem;">Welcome back! Please enter your details.</p>
                </div>

                <?php if ($error): ?>
                    <div style="background: #fff1f2; color: #be123c; padding: 0.875rem 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.875rem; border: 1px solid #fecdd3; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" placeholder="99999 99999" maxlength="10" required autofocus>
                    </div>
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <label class="form-label" style="margin-bottom: 0;">Password</label>
                            <a href="#" style="font-size: 0.75rem; color: var(--primary); font-weight: 600; text-decoration: none;">Forgot?</a>
                        </div>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 2rem;">
                        <input type="checkbox" id="remember" style="width: 16px; height: 16px; border-radius: 4px; border: 1px solid var(--border);">
                        <label for="remember" style="font-size: 0.875rem; color: var(--text-muted); cursor: pointer;">Remember for 30 days</label>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Sign in to account</button>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <span style="font-size: 0.875rem; color: var(--text-muted);">Don't have an account? <a href="signup.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Register Organization</a></span>
                    </div>
                </form>

                <div style="margin-top: 3rem; text-align: center; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <div style="display: flex; gap: 1.5rem; justify-content: center; font-size: 0.75rem;">
                        <a href="privacy-policy.php" style="color: var(--text-muted); text-decoration: none;">Privacy Policy</a>
                        <a href="delete-account.php" style="color: var(--text-muted); text-decoration: none;">Delete Account</a>
                    </div>
                    <p style="font-size: 0.75rem; color: #cbd5e1; margin-top: 0.75rem;">&copy; <?php echo date('Y'); ?> Calldesk CRM by Digital Seal</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
