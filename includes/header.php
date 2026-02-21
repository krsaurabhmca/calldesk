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
            <div style="padding: 0.5rem 0.75rem 2rem 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.875rem; margin-bottom: 0.5rem;">
                    <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);">
                        <i class="fas fa-headset" style="font-size: 1rem;"></i>
                    </div>
                    <div style="display: flex; flex-direction: column; line-height: 1.2;">
                        <span style="font-weight: 800; font-size: 1.25rem; color: var(--text-main); letter-spacing: -0.02em;">Calldesk</span>
                        <span style="font-size: 0.625rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em;">CRM System</span>
                    </div>
                </div>
                <?php if (isset($_SESSION['organization_name'])): ?>
                <div style="background: #f8fafc; padding: 0.625rem 0.875rem; border-radius: 10px; border: 1px solid var(--border); margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-building" style="font-size: 0.75rem; color: var(--text-muted);"></i>
                    <span style="font-size: 0.75rem; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo $_SESSION['organization_name']; ?>
                    </span>
                </div>
                <?php endif; ?>
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
                        <i class="fas fa-search" style="position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.75rem;"></i>
                        <input type="text" placeholder="Search leads, tasks..." style="width: 100%; padding: 0.5rem 0.875rem 0.5rem 2.25rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.8125rem; background: var(--background);">
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <?php if ($notif_count > 0): ?>
                    <a href="<?php echo BASE_URL; ?>followups.php" style="position: relative; color: var(--text-muted); padding: 0.4rem; border-radius: 6px; background: #fff; border: 1px solid var(--border);">
                        <i class="fas fa-bell" style="font-size: 0.875rem;"></i>
                        <span style="position: absolute; top: -4px; right: -4px; background: var(--danger); color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 700; border: 1.5px solid white;">
                            <?php echo $notif_count; ?>
                        </span>
                    </a>
                    <?php endif; ?>
                    
                    <div style="height: 32px; width: 1px; background: var(--border);"></div>

                    <div style="display: flex; align-items: center; gap: 0.625rem;">
                        <div style="text-align: right;">
                            <div style="font-weight: 700; font-size: 0.75rem; color: var(--text-main); line-height: 1.2;"><?php echo $_SESSION['name']; ?></div>
                            <div style="font-size: 0.625rem; color: var(--text-muted); text-transform: capitalize;"><?php echo $_SESSION['role']; ?></div>
                        </div>
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; box-shadow: var(--shadow-sm);">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>

            <main class="content-body" style="padding: 1rem 1.5rem;">
