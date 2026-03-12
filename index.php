<?php
// index.php
require_once 'config/db.php';
require_once 'includes/auth.php';
// If not logged in, show landing page
if (!isLoggedIn()) {
    redirect(BASE_URL . 'landing.php');
}

$user_id = $_SESSION['user_id'];
$org_id = getOrgId();
$role = $_SESSION['role'];

// Fetch Stats
$stats = [
    'total_leads' => 0,
    'today_followups' => 0,
    'converted_leads' => 0
];

if ($role === 'admin') {
    // Admin: Organization-wide stats
    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE organization_id = $org_id");
    $stats['total_leads'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    $today = date('Y-m-d');
    $res = mysqli_query($conn, "SELECT COUNT(DISTINCT f.lead_id) as count FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE l.organization_id = $org_id AND f.next_follow_up_date = '$today'");
    $stats['today_followups'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE organization_id = $org_id AND status = 'Converted'");
    $stats['converted_leads'] = mysqli_fetch_assoc($res)['count'] ?? 0;
    
    // New Admin Stat: Total Calls Today
    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM call_logs c JOIN users u ON c.executive_id = u.id WHERE u.organization_id = $org_id AND DATE(c.created_at) = '$today'");
    $stats['today_calls'] = mysqli_fetch_assoc($res)['count'] ?? 0;
} else {
    // Executive: Personal stats
    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE organization_id = $org_id AND assigned_to = $user_id");
    $stats['total_leads'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    $today = date('Y-m-d');
    $res = mysqli_query($conn, "SELECT COUNT(DISTINCT f.lead_id) as count FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE l.organization_id = $org_id AND l.assigned_to = $user_id AND f.next_follow_up_date = '$today'");
    $stats['today_followups'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE organization_id = $org_id AND assigned_to = $user_id AND status = 'Converted'");
    $stats['converted_leads'] = mysqli_fetch_assoc($res)['count'] ?? 0;

    // New Exec Stat: My Calls Today
    $res = mysqli_query($conn, "SELECT COUNT(*) as count FROM call_logs WHERE executive_id = $user_id AND DATE(created_at) = '$today'");
    $stats['today_calls'] = mysqli_fetch_assoc($res)['count'] ?? 0;
}

include 'includes/header.php';
?>

<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.03em;">
            <?php echo $role === 'admin' ? 'Organization Overview' : 'My Performance'; ?>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Welcome back, <span style="color: var(--primary); font-weight: 700;"><?php echo $_SESSION['name']; ?></span>. Here's what's happening today.</p>
    </div>
    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); background: #f1f5f9; padding: 0.5rem 1rem; border-radius: 100px; text-transform: uppercase; letter-spacing: 0.05em;">
        Role: <?php echo ucfirst($role); ?>
    </div>
</div>

<!-- Premium Stats Cards (4 Columns) -->
<div class="stat-grid" style="grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <!-- Metric 1: Leads -->
    <div class="stat-card" style="padding: 1.25rem; border: none; box-shadow: var(--shadow); background: #ffffff; position: relative;">
        <div class="stat-icon" style="background: rgba(79, 70, 229, 0.08); color: var(--primary); width: 42px; height: 42px;">
            <i class="fas fa-users-line"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem;">
                <?php echo $role === 'admin' ? 'Total Leads' : 'My Leads'; ?>
            </h3>
            <div class="value" style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);"><?php echo $stats['total_leads']; ?></div>
        </div>
    </div>

    <!-- Metric 2: Today's Follow-ups -->
    <div class="stat-card" style="padding: 1.25rem; border: none; box-shadow: var(--shadow); background: #ffffff;">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.08); color: var(--warning); width: 42px; height: 42px;">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem;">Tasks Today</h3>
            <div class="value" style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);"><?php echo $stats['today_followups']; ?></div>
        </div>
    </div>

    <!-- Metric 3: Calls Made Today (New) -->
    <div class="stat-card" style="padding: 1.25rem; border: none; box-shadow: var(--shadow); background: #ffffff;">
        <div class="stat-icon" style="background: rgba(99, 102, 241, 0.08); color: #6366f1; width: 42px; height: 42px;">
            <i class="fas fa-phone-volume"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem;">Calls Today</h3>
            <div class="value" style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);"><?php echo $stats['today_calls']; ?></div>
        </div>
    </div>

    <!-- Metric 4: Converted -->
    <div class="stat-card" style="padding: 1.25rem; border: none; box-shadow: var(--shadow); background: #ffffff;">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.08); color: var(--success); width: 42px; height: 42px;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="stat-info">
            <h3 style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem;">Converted</h3>
            <div class="value" style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);"><?php echo $stats['converted_leads']; ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Admin-Only Executive Performance Section -->
        <?php if ($role === 'admin'): ?>
        <div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: var(--shadow);">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fff;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">Team Analytics (Today)</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Real-time tracking for all executives today</p>
                </div>
                <a href="call_logs.php" class="btn" style="width: auto; padding: 0.5rem 1rem; background: #f1f5f9; color: var(--primary); font-size: 0.75rem; text-decoration: none; border-radius: 8px; font-weight: 600;">Full Logs</a>
            </div>
            <div class="table-container">
                <table style="font-size: 0.8125rem;">
                    <thead>
                        <tr style="background: #fafafa;">
                            <th style="padding: 1rem 1.5rem; color: var(--text-muted); font-weight: 700;">EXECUTIVE</th>
                            <th style="padding: 1rem 1.5rem; text-align: center; color: var(--primary);">CALLS</th>
                            <th style="padding: 1rem 1.5rem; text-align: center; color: var(--success);">IN</th>
                            <th style="padding: 1rem 1.5rem; text-align: center; color: var(--danger);">MISS</th>
                            <th style="padding: 1rem 1.5rem; text-align: center; color: var(--warning);">TASKS</th>
                            <th style="padding: 1rem 1.5rem; text-align: right;">TALK TIME</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Optimized for Today's Stats
                        $today = date('Y-m-d');
                        $perf_sql = "SELECT u.id, u.name, 
                                    COUNT(c.id) as total_calls,
                                    SUM(CASE WHEN c.type = 'Incoming' AND DATE(c.created_at) = '$today' THEN 1 ELSE 0 END) as in_count,
                                    SUM(CASE WHEN c.type = 'Outgoing' AND DATE(c.created_at) = '$today' THEN 1 ELSE 0 END) as out_count,
                                    SUM(CASE WHEN c.type = 'Missed' AND DATE(c.created_at) = '$today' THEN 1 ELSE 0 END) as miss_count,
                                    SUM(CASE WHEN DATE(c.created_at) = '$today' THEN c.duration ELSE 0 END) as total_duration,
                                    (SELECT COUNT(*) FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE l.assigned_to = u.id AND f.next_follow_up_date = '$today') as task_count
                                    FROM users u 
                                    LEFT JOIN call_logs c ON u.id = c.executive_id AND DATE(c.created_at) = '$today'
                                    WHERE u.organization_id = $org_id AND u.role = 'executive'
                                    GROUP BY u.id";
                        $perf_res = mysqli_query($conn, $perf_sql);
                        while ($p = mysqli_fetch_assoc($perf_res)):
                        ?>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 1rem 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary);">
                                        <?php echo strtoupper(substr($p['name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 600; color: var(--text-main);"><?php echo $p['name']; ?></span>
                                </div>
                            </td>
                            <td style="padding: 1rem 1.5rem; text-align: center; font-weight: 700; color: var(--primary);"><?php echo $p['out_count'] ?: 0; ?></td>
                            <td style="padding: 1rem 1.5rem; text-align: center; font-weight: 700; color: var(--success);"><?php echo $p['in_count'] ?: 0; ?></td>
                            <td style="padding: 1rem 1.5rem; text-align: center; font-weight: 700; color: var(--danger);"><?php echo $p['miss_count'] ?: 0; ?></td>
                            <td style="padding: 1rem 1.5rem; text-align: center; font-weight: 700; color: var(--warning);"><?php echo $p['task_count'] ?: 0; ?></td>
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
        <?php else: ?>
        <!-- Executive-Specific Widget: Today's Recent Calls -->
        <div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: var(--shadow);">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fff;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">My Recent Syncs</h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Latest calls from your device</p>
                </div>
                <a href="call_logs.php" class="btn" style="width: auto; padding: 0.4rem 0.75rem; background: #f1f5f9; color: var(--primary); font-size: 0.75rem; text-decoration: none; border-radius: 8px; font-weight: 700;">View History</a>
            </div>
            <div class="table-container">
                <table style="font-size: 0.8125rem;">
                    <thead>
                        <tr style="background: #fafafa;">
                            <th style="padding: 0.75rem 1.5rem; color: var(--text-muted); font-weight: 700;">CALLER</th>
                            <th style="padding: 0.75rem 1.5rem; text-align: center;">TYPE</th>
                            <th style="padding: 0.75rem 1.5rem; text-align: center;">REC</th>
                            <th style="padding: 0.75rem 1.5rem; text-align: right;">TIME</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $calls_sql = "SELECT * FROM call_logs WHERE executive_id = $user_id ORDER BY created_at DESC LIMIT 5";
                        $calls_res = mysqli_query($conn, $calls_sql);
                        while ($c = mysqli_fetch_assoc($calls_res)):
                        ?>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 0.75rem 1.5rem;">
                                <div style="font-weight: 700; color: var(--text-main);"><?php echo $c['contact_name'] ?: ($c['mobile'] ?: 'Unknown'); ?></div>
                            </td>
                            <td style="padding: 0.75rem 1.5rem; text-align: center;">
                                <span style="font-size: 0.65rem; font-weight: 700; color: <?php echo $c['type'] == 'Incoming' ? 'var(--success)' : ($c['type'] == 'Missed' ? 'var(--danger)' : 'var(--primary)'); ?>;">
                                    <?php echo strtoupper($c['type']); ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1.5rem; text-align: center;">
                                <?php if ($c['recording_path']): ?>
                                    <i class="fas fa-play-circle" style="color: var(--primary); cursor: pointer;" title="Recording Available" onclick="playRecord('<?php echo $c['recording_path']; ?>')"></i>
                                <?php else: ?>
                                    <span style="color: #ccc;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem 1.5rem; text-align: right; color: var(--text-muted); font-size: 0.7rem;">
                                <?php echo date('h:i A', strtotime($c['created_at'])); ?>
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
                        $sql = "SELECT l.*, u.name as executive_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE l.organization_id = $org_id " . ($role !== 'admin' ? "AND l.assigned_to = $user_id " : "") . "ORDER BY l.id DESC LIMIT 5";
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
        <div class="card" style="padding: 1.5rem; border: none; box-shadow: var(--shadow); background: <?php echo $role === 'admin' ? 'linear-gradient(135deg, #4f46e5 0%, #3730a3 100%)' : 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)'; ?>; color: white;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                <div>
                    <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.25rem;">
                        <?php echo $role === 'admin' ? 'Team Daily Goal' : 'My Daily Goal'; ?>
                    </h3>
                    <p style="font-size: 0.75rem; opacity: 0.7;">
                        <?php echo $role === 'admin' ? 'Combined team activity' : 'Personal call targets'; ?>
                    </p>
                </div>
                <div style="background: rgba(255,255,255,0.1); padding: 0.5rem; border-radius: 8px;">
                    <i class="fas fa-bullseye" style="color: #60a5fa;"></i>
                </div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.5rem;">
                    <?php 
                        $goal = $role === 'admin' ? 100 : 30; // 100 for team, 30 for individual
                        $progress = ($stats['today_calls'] / $goal) * 100;
                        $progress = min(100, round($progress));
                    ?>
                    <span>Progress: <?php echo $stats['today_calls']; ?> / <?php echo $goal; ?></span>
                    <span style="font-weight: 700;"><?php echo $progress; ?>%</span>
                </div>
                <div style="background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="background: #3b82f6; width: <?php echo $progress; ?>%; height: 100%; border-radius: 4px; box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);"></div>
                </div>
            </div>
            
            <p style="font-size: 0.75rem; opacity: 0.7; margin-bottom: 1.5rem;">
                <?php 
                if ($progress >= 100) echo "Goal achieved! Excellent work today.";
                else echo "Keep pushing! " . ($goal - $stats['today_calls']) . " more calls to reach the target.";
                ?>
            </p>
            
            <a href="leads.php" class="btn" style="background: #ffffff; color: #0f172a; border-radius: 10px; font-size: 0.8125rem; font-weight: 700; text-decoration: none; padding: 0.75rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-list-check"></i> Manage Leads
            </a>
        </div>

        <!-- Quick Calendar/Followup Widget -->
        <div class="card" style="padding: 1.5rem; border: none; box-shadow: var(--shadow); background: #ffffff;">
            <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-clock-rotate-left" style="color: var(--warning);"></i> 
                <?php echo $role === 'admin' ? 'Upcoming for Team' : 'My Next Tasks'; ?>
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php
                if ($role === 'admin') {
                    $task_sql = "SELECT l.name, f.remark, f.next_follow_up_date FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE l.organization_id = $org_id AND f.next_follow_up_date >= '$today' ORDER BY f.next_follow_up_date ASC LIMIT 3";
                } else {
                    $task_sql = "SELECT l.name, f.remark, f.next_follow_up_date FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE l.organization_id = $org_id AND l.assigned_to = $user_id AND f.next_follow_up_date >= '$today' ORDER BY f.next_follow_up_date ASC LIMIT 3";
                }
                $task_res = mysqli_query($conn, $task_sql);
                while ($t = mysqli_fetch_assoc($task_res)):
                ?>
                <div style="display: flex; gap: 0.75rem;">
                    <div style="flex-shrink: 0; width: 3px; height: 35px; background: <?php echo strtotime($t['next_follow_up_date']) == strtotime($today) ? 'var(--warning)' : 'var(--primary)'; ?>; border-radius: 2px;"></div>
                    <div>
                        <div style="font-size: 0.8125rem; font-weight: 700; color: var(--text-main); line-height: 1;"><?php echo $t['name']; ?></div>
                        <div style="font-size: 0.6875rem; color: var(--text-muted); margin-top: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;"><?php echo $t['remark']; ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($task_res) === 0): ?>
                    <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; padding: 1rem;">No pending tasks.</p>
                <?php endif; ?>
            </div>
            
            <hr style="border: none; border-top: 1px solid var(--border); margin: 1.25rem 0;">
            <a href="followups.php" style="font-size: 0.75rem; color: var(--primary); font-weight: 700; text-decoration: none; display: block; text-align: center;">Go to Calendar</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
