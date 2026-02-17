<?php
// index.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Stats
$stats = [
    'total_leads' => 0,
    'today_followups' => 0,
    'converted_leads' => 0
];

if ($role === 'admin') {
    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads");
    $stats['total_leads'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    $today = date('Y-m-d');
    $res = mysqli_query($conn, "SELECT COUNT(DISTINCT lead_id) as count FROM follow_ups WHERE next_follow_up_date = '$today'");
    $stats['today_followups'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE status = 'Converted'");
    $stats['converted_leads'] = mysqli_fetch_assoc($res)['count'] ?? 0;
} else {
    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE assigned_to = $user_id");
    $stats['total_leads'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    $today = date('Y-m-d');
    $res = mysqli_query($conn, "SELECT COUNT(DISTINCT f.lead_id) as count FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE l.assigned_to = $user_id AND f.next_follow_up_date = '$today'");
    $stats['today_followups'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE assigned_to = $user_id AND status = 'Converted'");
    $stats['converted_leads'] = mysqli_fetch_assoc($res)['count'] ?? 0;
}

include 'includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.025em;">Dashboard</h1>
    <p style="color: var(--text-muted); font-size: 0.9375rem;">Welcome back, <span style="color: var(--primary); font-weight: 600;"><?php echo $_SESSION['name']; ?></span>. Here's your overview for today.</p>
</div>

<!-- Premium Stats Cards -->
<div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
    <div class="stat-card" style="padding: 1.5rem; border: none; box-shadow: var(--shadow); background: #ffffff; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -10px; right: -10px; width: 80px; height: 80px; background: rgba(99, 102, 241, 0.05); border-radius: 50%;"></div>
        <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1); color: var(--primary); width: 44px; height: 44px; font-size: 1.25rem;">
            <i class="fas fa-users-line"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Total Leads</h3>
            <div class="value" style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); line-height: 1;"><?php echo $stats['total_leads']; ?></div>
        </div>
    </div>

    <div class="stat-card" style="padding: 1.5rem; border: none; box-shadow: var(--shadow); background: #ffffff; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -10px; right: -10px; width: 80px; height: 80px; background: rgba(245, 158, 11, 0.05); border-radius: 50%;"></div>
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning); width: 44px; height: 44px; font-size: 1.25rem;">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Tasks Today</h3>
            <div class="value" style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); line-height: 1;"><?php echo $stats['today_followups']; ?></div>
        </div>
    </div>

    <div class="stat-card" style="padding: 1.5rem; border: none; box-shadow: var(--shadow); background: #ffffff; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -10px; right: -10px; width: 80px; height: 80px; background: rgba(16, 185, 129, 0.05); border-radius: 50%;"></div>
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success); width: 44px; height: 44px; font-size: 1.25rem;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Converted</h3>
            <div class="value" style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); line-height: 1;"><?php echo $stats['converted_leads']; ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Admin-Only Executive Performance Section -->
        <?php if ($role === 'admin'): ?>
        <div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: var(--shadow);">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fff;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">Executive Performance</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Real-time call tracking per executive</p>
                </div>
                <a href="call_logs.php" class="btn" style="width: auto; padding: 0.5rem 1rem; background: #f1f5f9; color: var(--primary); font-size: 0.75rem; text-decoration: none; border-radius: 8px; font-weight: 600;">View Logs</a>
            </div>
            <div class="table-container">
                <table style="font-size: 0.8125rem;">
                    <thead>
                        <tr style="background: #fafafa;">
                            <th style="padding: 1rem 1.5rem; color: var(--text-muted); font-weight: 700;">EXECUTIVE</th>
                            <th style="padding: 1rem 1.5rem; text-align: center; color: var(--success);">INCOMING</th>
                            <th style="padding: 1rem 1.5rem; text-align: center; color: var(--primary);">OUTGOING</th>
                            <th style="padding: 1rem 1.5rem; text-align: center; color: var(--danger);">MISSED</th>
                            <th style="padding: 1rem 1.5rem; text-align: right;">DURATION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $perf_sql = "SELECT u.name, 
                                    SUM(CASE WHEN c.type = 'Incoming' THEN 1 ELSE 0 END) as in_count,
                                    SUM(CASE WHEN c.type = 'Outgoing' THEN 1 ELSE 0 END) as out_count,
                                    SUM(CASE WHEN c.type = 'Missed' THEN 1 ELSE 0 END) as miss_count,
                                    SUM(c.duration) as total_duration
                                    FROM users u 
                                    LEFT JOIN call_logs c ON u.id = c.executive_id 
                                    WHERE u.role = 'executive'
                                    GROUP BY u.id";
                        $perf_res = mysqli_query($conn, $perf_sql);
                        while ($p = mysqli_fetch_assoc($perf_res)):
                        ?>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 1rem 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary);">
                                        <?php echo strtoupper(substr($p['name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 600; color: var(--text-main);"><?php echo $p['name']; ?></span>
                                </div>
                            </td>
                            <td style="padding: 1rem 1.5rem; text-align: center; font-weight: 700;"><?php echo $p['in_count'] ?: 0; ?></td>
                            <td style="padding: 1rem 1.5rem; text-align: center; font-weight: 700;"><?php echo $p['out_count'] ?: 0; ?></td>
                            <td style="padding: 1rem 1.5rem; text-align: center; font-weight: 700;"><?php echo $p['miss_count'] ?: 0; ?></td>
                            <td style="padding: 1rem 1.5rem; text-align: right; font-family: monospace; color: var(--text-muted); font-weight: 600;">
                                <?php 
                                    $m = floor(($p['total_duration'] ?? 0) / 60);
                                    echo $m . "m " . (($p['total_duration'] ?? 0) % 60) . "s";
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Leads for everyone -->
        <div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: var(--shadow);">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fff;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">Recent Leads</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Latest prospects added to system</p>
                </div>
                <a href="leads.php" style="color: var(--primary); text-decoration: none; font-size: 0.8125rem; font-weight: 700; display: flex; align-items: center; gap: 0.25rem;">
                    View All <i class="fas fa-chevron-right" style="font-size: 0.625rem;"></i>
                </a>
            </div>
            <div class="table-container">
                <table style="font-size: 0.8125rem;">
                    <thead>
                        <tr style="background: #fafafa;">
                            <th style="padding: 1rem 1.5rem;">LEAD</th>
                            <th style="padding: 1rem 1.5rem;">STATUS</th>
                            <th style="padding: 1rem 1.5rem;">EXECUTIVE</th>
                            <th style="padding: 1rem 1.5rem; text-align: right;">CREATED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT l.*, u.name as executive_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id " . ($role !== 'admin' ? "WHERE l.assigned_to = $user_id " : "") . "ORDER BY l.id DESC LIMIT 5";
                        $result = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_assoc($result)):
                        ?>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 1rem 1.5rem;">
                                <div style="font-weight: 700; color: var(--text-main);"><?php echo $row['name']; ?></div>
                                <div style="color: var(--text-muted); font-size: 0.75rem;"><?php echo $row['mobile']; ?></div>
                            </td>
                            <td style="padding: 1rem 1.5rem;">
                                <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>" style="font-size: 0.625rem; padding: 0.25rem 0.625rem; font-weight: 700; border-radius: 6px;"><?php echo strtoupper($row['status']); ?></span>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-muted); font-weight: 500;"><?php echo $row['executive_name'] ?? 'Unassigned'; ?></td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-muted); font-size: 0.75rem; text-align: right;"><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar widgets -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Daily Activity/Goal Widget -->
        <div class="card" style="padding: 1.5rem; border: none; box-shadow: var(--shadow); background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                <div>
                    <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.25rem;">Daily Progress</h3>
                    <p style="font-size: 0.75rem; opacity: 0.7;">Call targets for today</p>
                </div>
                <div style="background: rgba(255,255,255,0.1); padding: 0.5rem; border-radius: 8px;">
                    <i class="fas fa-bullseye" style="color: #60a5fa;"></i>
                </div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.5rem;">
                    <span>Goal: 40 Calls</span>
                    <span style="font-weight: 700;">65%</span>
                </div>
                <div style="background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="background: #3b82f6; width: 65%; height: 100%; border-radius: 4px; box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);"></div>
                </div>
            </div>
            
            <p style="font-size: 0.75rem; opacity: 0.7; margin-bottom: 1.5rem;">You need 14 more calls to reach your daily goal. Keep it up!</p>
            
            <a href="lead_add.php" class="btn" style="background: #ffffff; color: #0f172a; border-radius: 10px; font-size: 0.8125rem; font-weight: 700; text-decoration: none; padding: 0.75rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-plus-circle"></i> New Prospect
            </a>
        </div>

        <!-- Quick Calendar/Followup Widget -->
        <div class="card" style="padding: 1.5rem; border: none; box-shadow: var(--shadow); background: #ffffff;">
            <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-clock-rotate-left" style="color: var(--warning);"></i> Pending Tasks
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php
                if ($role === 'admin') {
                    $task_sql = "SELECT l.name, f.remark, f.next_follow_up_date FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE f.next_follow_up_date >= '$today' ORDER BY f.next_follow_up_date ASC LIMIT 3";
                } else {
                    $task_sql = "SELECT l.name, f.remark, f.next_follow_up_date FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE l.assigned_to = $user_id AND f.next_follow_up_date >= '$today' ORDER BY f.next_follow_up_date ASC LIMIT 3";
                }
                $task_res = mysqli_query($conn, $task_sql);
                while ($t = mysqli_fetch_assoc($task_res)):
                ?>
                <div style="display: flex; gap: 0.75rem;">
                    <div style="flex-shrink: 0; width: 3px; height: 35px; background: var(--border); border-radius: 2px;"></div>
                    <div>
                        <div style="font-size: 0.8125rem; font-weight: 700; color: var(--text-main); line-height: 1;"><?php echo $t['name']; ?></div>
                        <div style="font-size: 0.6875rem; color: var(--text-muted); margin-top: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;"><?php echo $t['remark']; ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($task_res) === 0): ?>
                    <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; padding: 1rem;">No pending tasks for today.</p>
                <?php endif; ?>
            </div>
            
            <hr style="border: none; border-top: 1px solid var(--border); margin: 1.25rem 0;">
            <a href="followups.php" style="font-size: 0.75rem; color: var(--primary); font-weight: 700; text-decoration: none; display: block; text-align: center;">View All Tasks</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
