<?php
// index.php
require_once 'config/db.php';
require_once 'includes/auth.php';
if (!isLoggedIn()) { redirect(BASE_URL . 'landing.php'); }

$user_id = $_SESSION['user_id'];
$org_id  = getOrgId();
$role    = $_SESSION['role'];
$today   = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// ── Stats ─────────────────────────────────────────────────────────────────────
if ($role === 'admin') {
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id=$org_id"); $stats['total_leads'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(DISTINCT f.lead_id) c FROM follow_ups f JOIN leads l ON f.lead_id=l.id WHERE l.organization_id=$org_id AND f.next_follow_up_date='$today'"); $stats['today_followups'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id=$org_id AND status='Converted'"); $stats['converted_leads'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM call_logs c JOIN users u ON c.executive_id=u.id WHERE u.organization_id=$org_id AND DATE(c.call_time)='$today'"); $stats['today_calls'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM call_logs c JOIN users u ON c.executive_id=u.id WHERE u.organization_id=$org_id AND c.recording_path IS NOT NULL AND c.recording_path!=''"); $stats['total_recordings'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM call_logs c JOIN users u ON c.executive_id=u.id WHERE u.organization_id=$org_id AND DATE(c.call_time)='$yesterday'"); $yesterday_calls = mysqli_fetch_assoc($r)['c'];
} else {
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id=$org_id AND assigned_to=$user_id"); $stats['total_leads'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(DISTINCT f.lead_id) c FROM follow_ups f JOIN leads l ON f.lead_id=l.id WHERE l.organization_id=$org_id AND l.assigned_to=$user_id AND f.next_follow_up_date='$today'"); $stats['today_followups'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id=$org_id AND assigned_to=$user_id AND status='Converted'"); $stats['converted_leads'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM call_logs WHERE executive_id=$user_id AND DATE(call_time)='$today'"); $stats['today_calls'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM call_logs WHERE executive_id=$user_id AND recording_path IS NOT NULL AND recording_path!=''"); $stats['total_recordings'] = mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM call_logs WHERE executive_id=$user_id AND DATE(call_time)='$yesterday'"); $yesterday_calls = mysqli_fetch_assoc($r)['c'];
}

$call_trend = $yesterday_calls > 0 ? round((($stats['today_calls'] - $yesterday_calls) / $yesterday_calls) * 100) : 0;
$goal = $role === 'admin' ? 100 : 30;
$progress = min(100, $goal > 0 ? round(($stats['today_calls'] / $goal) * 100) : 0);

include 'includes/header.php';
?>

<style>
.kpi-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
.kpi-card { background: #fff; border-radius: 16px; padding: 1.25rem 1.25rem 1rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 6px rgba(0,0,0,.04); position: relative; overflow: hidden; transition: transform .15s, box-shadow .15s; text-decoration: none; display: block; }
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,.07); }
.kpi-card .kpi-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: .875rem; margin-bottom: .875rem; }
.kpi-card .kpi-val { font-size: 1.875rem; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: .25rem; }
.kpi-card .kpi-label { font-size: .7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.kpi-card .kpi-trend { font-size: .7rem; font-weight: 700; margin-top: .5rem; }
.kpi-card::after { content: ''; position: absolute; right: -12px; bottom: -12px; width: 60px; height: 60px; border-radius: 50%; opacity: .06; }
.section-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 6px rgba(0,0,0,.03); overflow: hidden; }
.section-head { padding: 1rem 1.25rem; border-bottom: 1px solid #f8fafc; display: flex; justify-content: space-between; align-items: center; }
.section-head h3 { font-size: .9375rem; font-weight: 800; color: #0f172a; margin: 0; }
.section-head p { font-size: .75rem; color: #94a3b8; margin: .2rem 0 0; }
.badge-in { background: #dcfce7; color: #15803d; }
.badge-out { background: #e0e7ff; color: #3730a3; }
.badge-miss { background: #fee2e2; color: #b91c1c; }
.dash-table th { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; padding: .75rem 1.25rem; background: #fafafa; }
.dash-table td { padding: .8rem 1.25rem; border-bottom: 1px solid #f8fafc; font-size: .8125rem; }
.dash-table tr:last-child td { border-bottom: none; }
.dash-table tr:hover td { background: #fafbff; }
.avatar-sm { width: 30px; height: 30px; border-radius: 9px; background: #eef2ff; color: #6366f1; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
.pill-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem; border-radius: 8px; font-size: .75rem; font-weight: 700; text-decoration: none; }
.task-item { display: flex; gap: .75rem; padding: .75rem 0; border-bottom: 1px solid #f8fafc; }
.task-item:last-of-type { border-bottom: none; }
.task-dot { width: 4px; min-height: 36px; border-radius: 2px; flex-shrink: 0; }
</style>

<!-- Page Header -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.375rem;font-weight:800;color:#0f172a;letter-spacing:-.03em;margin:0;">
            <?= $role === 'admin' ? 'Organization Overview' : 'My Dashboard' ?>
        </h1>
        <p style="color:#64748b;font-size:.8125rem;margin:.25rem 0 0;">
            <?= date('l, d F Y') ?> &nbsp;·&nbsp; Welcome back, <strong style="color:#6366f1;"><?= $_SESSION['name'] ?></strong>
        </p>
    </div>
    <span style="background:#f1f5f9;color:#64748b;font-size:.7rem;font-weight:700;padding:.4rem 1rem;border-radius:100px;letter-spacing:.04em;text-transform:uppercase;">
        <?= ucfirst($role) ?>
    </span>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">
    <a href="leads.php" class="kpi-card">
        <div class="kpi-icon" style="background:#eef2ff;color:#6366f1;"><i class="fas fa-users"></i></div>
        <div class="kpi-val"><?= number_format($stats['total_leads']) ?></div>
        <div class="kpi-label"><?= $role === 'admin' ? 'Total Leads' : 'My Leads' ?></div>
        <div class="kpi-trend" style="color:#6366f1;"><i class="fas fa-arrow-right"></i> View all</div>
        <div style="position:absolute;right:-10px;bottom:-10px;width:56px;height:56px;border-radius:50%;background:#6366f1;opacity:.05;"></div>
    </a>
    <a href="call_logs.php?date_from=<?= $today ?>&date_to=<?= $today ?>" class="kpi-card">
        <div class="kpi-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-phone-volume"></i></div>
        <div class="kpi-val"><?= $stats['today_calls'] ?></div>
        <div class="kpi-label">Calls Today</div>
        <div class="kpi-trend" style="color:<?= $call_trend >= 0 ? '#16a34a' : '#dc2626' ?>">
            <i class="fas fa-arrow-<?= $call_trend >= 0 ? 'up' : 'down' ?>"></i>
            <?= abs($call_trend) ?>% vs yesterday
        </div>
        <div style="position:absolute;right:-10px;bottom:-10px;width:56px;height:56px;border-radius:50%;background:#16a34a;opacity:.05;"></div>
    </a>
    <a href="followups.php" class="kpi-card">
        <div class="kpi-icon" style="background:#fffbeb;color:#d97706;"><i class="fas fa-calendar-day"></i></div>
        <div class="kpi-val"><?= $stats['today_followups'] ?></div>
        <div class="kpi-label">Tasks Today</div>
        <div class="kpi-trend" style="color:#d97706;"><i class="fas fa-clock"></i> Due today</div>
        <div style="position:absolute;right:-10px;bottom:-10px;width:56px;height:56px;border-radius:50%;background:#d97706;opacity:.05;"></div>
    </a>
    <a href="leads.php?status=Converted" class="kpi-card">
        <div class="kpi-icon" style="background:#f0fdf4;color:#10b981;"><i class="fas fa-circle-check"></i></div>
        <div class="kpi-val"><?= $stats['converted_leads'] ?></div>
        <div class="kpi-label">Converted</div>
        <div class="kpi-trend" style="color:#10b981;"><i class="fas fa-trophy"></i> Closed deals</div>
        <div style="position:absolute;right:-10px;bottom:-10px;width:56px;height:56px;border-radius:50%;background:#10b981;opacity:.05;"></div>
    </a>
    <a href="recordings.php" class="kpi-card">
        <div class="kpi-icon" style="background:#fdf4ff;color:#9333ea;"><i class="fas fa-microphone-alt"></i></div>
        <div class="kpi-val"><?= $stats['total_recordings'] ?></div>
        <div class="kpi-label">Recordings</div>
        <div class="kpi-trend" style="color:#9333ea;"><i class="fas fa-headphones"></i> Total synced</div>
        <div style="position:absolute;right:-10px;bottom:-10px;width:56px;height:56px;border-radius:50%;background:#9333ea;opacity:.05;"></div>
    </a>
</div>

<!-- Main Grid -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;">
    <div style="display:flex;flex-direction:column;gap:1.25rem;">

        <?php if ($role === 'admin'): ?>
        <!-- Team Analytics -->
        <div class="section-card">
            <div class="section-head">
                <div>
                    <h3><i class="fas fa-chart-bar" style="color:#6366f1;margin-right:.5rem;"></i>Team Analytics — Today</h3>
                    <p>Live call activity for all executives</p>
                </div>
                <a href="call_logs.php" class="pill-btn" style="background:#eef2ff;color:#6366f1;">Full Logs <i class="fas fa-external-link-alt"></i></a>
            </div>
            <table class="dash-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>Executive</th>
                        <th style="text-align:center">Out</th>
                        <th style="text-align:center">In</th>
                        <th style="text-align:center">Missed</th>
                        <th style="text-align:center">Tasks</th>
                        <th style="text-align:right">Talk Time</th>
                        <th style="text-align:right">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $perf_sql = "SELECT u.id, u.name,
                        SUM(CASE WHEN c.type='Outgoing'  AND DATE(c.call_time)='$today' THEN 1 ELSE 0 END) out_c,
                        SUM(CASE WHEN c.type='Incoming'  AND DATE(c.call_time)='$today' THEN 1 ELSE 0 END) in_c,
                        SUM(CASE WHEN c.type='Missed'    AND DATE(c.call_time)='$today' THEN 1 ELSE 0 END) miss_c,
                        SUM(CASE WHEN DATE(c.call_time)='$today' THEN IFNULL(c.duration,0) ELSE 0 END) dur,
                        (SELECT COUNT(*) FROM follow_ups f JOIN leads l ON f.lead_id=l.id WHERE l.assigned_to=u.id AND f.next_follow_up_date='$today') tasks
                        FROM users u
                        LEFT JOIN call_logs c ON u.id=c.executive_id
                        WHERE u.organization_id=$org_id AND u.role='executive'
                        GROUP BY u.id ORDER BY out_c DESC";
                    $pr = mysqli_query($conn, $perf_sql);
                    while ($p = mysqli_fetch_assoc($pr)):
                        $total = ($p['out_c'] + $p['in_c'] + $p['miss_c']);
                        $pct   = $total > 0 ? min(100, round($total/30*100)) : 0;
                        $m = floor($p['dur']/60); $s = $p['dur']%60;
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:.625rem;">
                                <div class="avatar-sm"><?= strtoupper(substr($p['name'],0,1)) ?></div>
                                <span style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($p['name']) ?></span>
                            </div>
                        </td>
                        <td style="text-align:center;font-weight:800;color:#6366f1;"><?= $p['out_c'] ?: '—' ?></td>
                        <td style="text-align:center;font-weight:800;color:#10b981;"><?= $p['in_c'] ?: '—' ?></td>
                        <td style="text-align:center;font-weight:800;color:#ef4444;"><?= $p['miss_c'] ?: '—' ?></td>
                        <td style="text-align:center;">
                            <span style="background:<?= $p['tasks']>0?'#fffbeb':'#f0fdf4' ?>;color:<?= $p['tasks']>0?'#b45309':'#16a34a' ?>;font-size:.7rem;font-weight:700;padding:.2rem .5rem;border-radius:6px;">
                                <?= $p['tasks'] ?> tasks
                            </span>
                        </td>
                        <td style="text-align:right;font-family:monospace;font-size:.8rem;color:#64748b;font-weight:600;"><?= "{$m}m {$s}s" ?></td>
                        <td style="text-align:right;min-width:100px;">
                            <div style="display:flex;align-items:center;gap:.5rem;justify-content:flex-end;">
                                <div style="flex:1;background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden;">
                                    <div style="width:<?= $pct ?>%;height:100%;background:<?= $pct>=80?'#10b981':($pct>=40?'#f59e0b':'#6366f1') ?>;border-radius:4px;"></div>
                                </div>
                                <span style="font-size:.65rem;font-weight:700;color:#64748b;width:28px;"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <!-- Executive: Recent Calls -->
        <div class="section-card">
            <div class="section-head">
                <div>
                    <h3><i class="fas fa-phone-alt" style="color:#6366f1;margin-right:.5rem;"></i>My Recent Calls</h3>
                    <p>Latest synced activity from your device</p>
                </div>
                <a href="call_logs.php" class="pill-btn" style="background:#eef2ff;color:#6366f1;">All Logs <i class="fas fa-arrow-right"></i></a>
            </div>
            <table class="dash-table" style="width:100%;border-collapse:collapse;">
                <thead><tr>
                    <th>Contact</th><th>Type</th><th>Duration</th><th>Recording</th><th style="text-align:right">Time</th>
                </tr></thead>
                <tbody>
                <?php
                $cr = mysqli_query($conn, "SELECT * FROM call_logs WHERE executive_id=$user_id ORDER BY call_time DESC LIMIT 8");
                while ($c = mysqli_fetch_assoc($cr)):
                    $badgeCls = $c['type']=='Incoming'?'badge-in':($c['type']=='Missed'?'badge-miss':'badge-out');
                    $m = floor($c['duration']/60); $s = $c['duration']%60;
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:#1e293b;"><?= $c['contact_name'] ?: $c['mobile'] ?></div>
                        <div style="font-size:.7rem;color:#6366f1;"><?= $c['mobile'] ?></div>
                    </td>
                    <td><span class="badge <?= $badgeCls ?>" style="font-size:.65rem;font-weight:700;padding:.2rem .5rem;border-radius:6px;"><?= $c['type'] ?></span></td>
                    <td style="font-size:.8rem;color:#64748b;"><?= "{$m}m {$s}s" ?></td>
                    <td>
                        <?php if ($c['recording_path']): ?>
                            <button onclick="playRecord('<?= $c['recording_path'] ?>')" style="background:#f5f3ff;color:#7c3aed;border:none;padding:.25rem .625rem;border-radius:6px;font-size:.7rem;font-weight:700;cursor:pointer;">
                                <i class="fas fa-play"></i> Play
                            </button>
                        <?php else: ?>
                            <span style="color:#e2e8f0;font-size:.75rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;color:#94a3b8;font-size:.75rem;"><?= date('d M, h:i A', strtotime($c['call_time'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Recent Leads -->
        <div class="section-card">
            <div class="section-head">
                <div>
                    <h3><i class="fas fa-user-plus" style="color:#6366f1;margin-right:.5rem;"></i>Recent Leads</h3>
                    <p>Latest prospects added to the system</p>
                </div>
                <a href="leads.php" class="pill-btn" style="background:#eef2ff;color:#6366f1;">View All <i class="fas fa-chevron-right"></i></a>
            </div>
            <table class="dash-table" style="width:100%;border-collapse:collapse;">
                <thead><tr>
                    <th>Lead</th><th>Status</th><?php if($role==='admin'): ?><th>Executive</th><?php endif; ?><th style="text-align:right">Added</th>
                </tr></thead>
                <tbody>
                <?php
                $lsql = "SELECT l.*, u.name executive_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id WHERE l.organization_id=$org_id" . ($role!=='admin' ? " AND l.assigned_to=$user_id" : "") . " ORDER BY l.id DESC LIMIT 6";
                $lr = mysqli_query($conn, $lsql);
                while ($row = mysqli_fetch_assoc($lr)):
                    $sc = strtolower(str_replace([' ','-'],'',$row['status']));
                    $sbg = ['Converted'=>'#dcfce7;color:#16a34a','Lost'=>'#fee2e2;color:#b91c1c','Follow-up'=>'#fffbeb;color:#b45309','Interested'=>'#eef2ff;color:#3730a3','Pending'=>'#f1f5f9;color:#475569'];
                    $ss  = $sbg[$row['status']] ?? '#f1f5f9;color:#475569';
                ?>
                <tr>
                    <td>
                        <a href="lead_view.php?id=<?= $row['id'] ?>" style="text-decoration:none;">
                            <div style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($row['name']) ?></div>
                            <div style="font-size:.7rem;color:#6366f1;"><?= $row['mobile'] ?></div>
                        </a>
                    </td>
                    <td><span style="font-size:.65rem;font-weight:700;padding:.25rem .6rem;border-radius:6px;background:<?= $ss ?>;"><?= strtoupper($row['status']) ?></span></td>
                    <?php if($role==='admin'): ?>
                    <td style="color:#64748b;font-size:.8rem;"><?= $row['executive_name'] ?? '<em>Unassigned</em>' ?></td>
                    <?php endif; ?>
                    <td style="text-align:right;color:#94a3b8;font-size:.75rem;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div><!-- /left col -->

    <!-- Right sidebar -->
    <div style="display:flex;flex-direction:column;gap:1.25rem;">

        <!-- Goal Card -->
        <div style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);border-radius:16px;padding:1.5rem;color:#fff;box-shadow:0 8px 24px rgba(99,102,241,.25);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.25rem;">
                <div>
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.7;margin-bottom:.3rem;">Daily Goal</div>
                    <div style="font-size:1.5rem;font-weight:800;"><?= $stats['today_calls'] ?> / <?= $goal ?></div>
                    <div style="font-size:.75rem;opacity:.7;margin-top:.2rem;">calls completed</div>
                </div>
                <div style="background:rgba(255,255,255,.15);width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-bullseye"></i>
                </div>
            </div>
            <div style="background:rgba(255,255,255,.15);border-radius:8px;height:10px;overflow:hidden;margin-bottom:.75rem;">
                <div style="width:<?= $progress ?>%;height:100%;background:#fff;border-radius:8px;transition:width .4s;"></div>
            </div>
            <div style="font-size:.75rem;opacity:.8;margin-bottom:1.25rem;">
                <?= $progress >= 100 ? '🎉 Goal achieved! Great work.' : ('⚡ ' . ($goal - $stats['today_calls']) . ' more calls to reach target') ?>
            </div>
            <a href="leads.php" style="display:block;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;text-align:center;padding:.625rem;border-radius:10px;font-size:.8125rem;font-weight:700;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                <i class="fas fa-list-check"></i> Manage Leads
            </a>
        </div>

        <!-- Quick Stats breakdown -->
        <div class="section-card" style="padding:1.25rem;">
            <h3 style="font-size:.875rem;font-weight:800;color:#1e293b;margin:0 0 1rem;"><i class="fas fa-chart-pie" style="color:#f59e0b;margin-right:.5rem;"></i>Lead Breakdown</h3>
            <?php
            $statuses = ['Pending'=>'#64748b','Follow-up'=>'#f59e0b','Interested'=>'#6366f1','Converted'=>'#10b981','Lost'=>'#ef4444'];
            $where_e = $role !== 'admin' ? "AND assigned_to=$user_id" : '';
            foreach ($statuses as $st => $color):
                $r = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id=$org_id AND status='$st' $where_e");
                $cnt = mysqli_fetch_assoc($r)['c'];
                $total_l = max(1, $stats['total_leads']);
                $pct = round($cnt/$total_l*100);
            ?>
            <div style="margin-bottom:.875rem;">
                <div style="display:flex;justify-content:space-between;font-size:.75rem;margin-bottom:.3rem;">
                    <span style="font-weight:700;color:#374151;"><?= $st ?></span>
                    <span style="font-weight:800;color:<?= $color ?>;"><?= $cnt ?></span>
                </div>
                <div style="background:#f1f5f9;border-radius:4px;height:5px;overflow:hidden;">
                    <div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:4px;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Upcoming Tasks -->
        <div class="section-card">
            <div class="section-head">
                <div><h3><i class="fas fa-clock" style="color:#f59e0b;margin-right:.5rem;"></i>Upcoming Tasks</h3></div>
                <a href="followups.php" class="pill-btn" style="background:#fffbeb;color:#b45309;">All</a>
            </div>
            <div style="padding:.5rem 1.25rem 1rem;">
            <?php
            $tsql = $role === 'admin'
                ? "SELECT l.name, f.remark, f.next_follow_up_date FROM follow_ups f JOIN leads l ON f.lead_id=l.id WHERE l.organization_id=$org_id AND f.next_follow_up_date>='$today' ORDER BY f.next_follow_up_date ASC LIMIT 4"
                : "SELECT l.name, f.remark, f.next_follow_up_date FROM follow_ups f JOIN leads l ON f.lead_id=l.id WHERE l.organization_id=$org_id AND l.assigned_to=$user_id AND f.next_follow_up_date>='$today' ORDER BY f.next_follow_up_date ASC LIMIT 4";
            $tr = mysqli_query($conn, $tsql);
            $any = false;
            while ($t = mysqli_fetch_assoc($tr)):
                $any = true;
                $isToday = $t['next_follow_up_date'] === $today;
                $dotColor = $isToday ? '#f59e0b' : '#6366f1';
            ?>
            <div class="task-item">
                <div class="task-dot" style="background:<?= $dotColor ?>;"></div>
                <div>
                    <div style="font-size:.8125rem;font-weight:700;color:#1e293b;"><?= htmlspecialchars($t['name']) ?></div>
                    <div style="font-size:.7rem;color:#94a3b8;margin-top:.2rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;"><?= htmlspecialchars($t['remark']) ?></div>
                    <div style="font-size:.65rem;font-weight:700;margin-top:.3rem;color:<?= $dotColor ?>;">
                        <i class="fas fa-calendar-alt"></i> <?= $isToday ? 'Today' : date('d M', strtotime($t['next_follow_up_date'])) ?>
                    </div>
                </div>
            </div>
            <?php endwhile; if (!$any): ?>
                <p style="font-size:.8125rem;color:#cbd5e1;text-align:center;padding:1rem 0;">No upcoming tasks 🎉</p>
            <?php endif; ?>
            </div>
        </div>

    </div><!-- /right sidebar -->
</div>

<?php include 'includes/footer.php'; ?>
