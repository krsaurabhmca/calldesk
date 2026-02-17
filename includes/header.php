<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$today = date('Y-m-d');

// Fetch notification count (Today's follow-ups)
$notif_sql = "SELECT COUNT(DISTINCT f.lead_id) as count FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE f.next_follow_up_date = '$today'";
if ($role !== 'admin') {
    $notif_sql .= " AND l.assigned_to = $user_id";
}
$notif_res = mysqli_query($conn, $notif_sql);
$notif_count = mysqli_fetch_assoc($notif_res)['count'];

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calldesk CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.5rem 2rem 0.5rem;">
                <div style="background: var(--primary); color: white; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-headset" style="font-size: 0.875rem;"></i>
                </div>
                <span style="font-weight: 800; font-size: 1.125rem; color: var(--text-main);">Calldesk</span>
            </div>

            <nav>
                <div class="nav-section-label">Main Menu</div>
                <a href="<?php echo BASE_URL; ?>index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> <span>Overview</span>
                </a>
                <a href="<?php echo BASE_URL; ?>leads.php" class="nav-link <?php echo ($current_page == 'leads.php' || $current_page == 'lead_view.php' || $current_page == 'lead_add.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <span>Leads</span>
                </a>
                <a href="<?php echo BASE_URL; ?>followups.php" class="nav-link <?php echo $current_page == 'followups.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> <span>Tasks</span>
                </a>
                <a href="<?php echo BASE_URL; ?>call_logs.php" class="nav-link <?php echo $current_page == 'call_logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-phone-volume"></i> <span>Call Logs</span>
                </a>

                <div class="nav-section-label">Communication</div>
                <a href="<?php echo BASE_URL; ?>messages.php" class="nav-link <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fab fa-whatsapp"></i> <span>WA Templates</span>
                </a>
                
                <?php if (isAdmin()): ?>
                <div class="nav-section-label">Administration</div>
                <a href="<?php echo BASE_URL; ?>sources.php" class="nav-link <?php echo $current_page == 'sources.php' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i> <span>Lead Sources</span>
                </a>
                <a href="<?php echo BASE_URL; ?>users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-gear"></i> <span>Team Access</span>
                </a>
                <a href="<?php echo BASE_URL; ?>reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> <span>Reports</span>
                </a>
                <a href="<?php echo BASE_URL; ?>docs.php" class="nav-link <?php echo $current_page == 'docs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-code"></i> <span>Developer API</span>
                </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout-link">
                    <i class="fas fa-power-off"></i> <span>Sign out</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                    <div style="position: relative; width: 100%; max-width: 400px;">
                        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.875rem;"></i>
                        <input type="text" placeholder="Search leads, tasks..." style="width: 100%; padding: 0.625rem 1rem 0.625rem 2.5rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.875rem; background: var(--background);">
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <?php if ($notif_count > 0): ?>
                    <a href="<?php echo BASE_URL; ?>followups.php" style="position: relative; color: var(--text-muted); padding: 0.5rem; border-radius: 8px; background: #fff; border: 1px solid var(--border);">
                        <i class="fas fa-bell"></i>
                        <span style="position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.625rem; font-weight: 700; border: 2px solid white;">
                            <?php echo $notif_count; ?>
                        </span>
                    </a>
                    <?php endif; ?>
                    
                    <div style="height: 32px; width: 1px; background: var(--border);"></div>

                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="text-align: right;">
                            <div style="font-weight: 700; font-size: 0.8125rem; color: var(--text-main);"><?php echo $_SESSION['name']; ?></div>
                            <div style="font-size: 0.6875rem; color: var(--text-muted); text-transform: capitalize;"><?php echo $_SESSION['role']; ?></div>
                        </div>
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.875rem; box-shadow: var(--shadow-sm);">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>

            <main class="content-body" style="padding: 1.5rem 2rem;">
