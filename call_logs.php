<?php
// call_logs.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$org_id   = getOrgId();
$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$today    = date('Y-m-d');

$search      = isset($_GET['search'])       ? mysqli_real_escape_string($conn, $_GET['search'])       : '';
$type_filter = isset($_GET['type'])         ? mysqli_real_escape_string($conn, $_GET['type'])         : '';
$exec_filter = isset($_GET['executive_id']) ? (int)$_GET['executive_id']                              : 0;
$date_from   = isset($_GET['date_from'])    ? mysqli_real_escape_string($conn, $_GET['date_from'])    : '';
$date_to     = isset($_GET['date_to'])      ? mysqli_real_escape_string($conn, $_GET['date_to'])      : '';
$rec_only    = isset($_GET['rec_only'])     ? (bool)$_GET['rec_only']                                : false;

$where = "WHERE (c.organization_id = $org_id OR u.organization_id = $org_id)";
if ($role !== 'admin') {
    $where .= " AND c.executive_id = $user_id";
} elseif ($exec_filter > 0) {
    $where .= " AND c.executive_id = $exec_filter";
}
if ($search)      $where .= " AND (c.mobile LIKE '%$search%' OR l.name LIKE '%$search%' OR c.contact_name LIKE '%$search%')";
if ($type_filter) $where .= " AND c.type = '$type_filter'";
if ($date_from)   $where .= " AND DATE(c.call_time) >= '$date_from'";
if ($date_to)     $where .= " AND DATE(c.call_time) <= '$date_to'";
if ($rec_only)    $where .= " AND c.recording_path IS NOT NULL AND c.recording_path != ''";

$sql = "SELECT c.*, l.id as lead_id, l.name as lead_name, l.status as lead_status, u.name as executive_name
        FROM call_logs c
        LEFT JOIN leads l ON c.mobile = l.mobile AND l.organization_id = $org_id
        LEFT JOIN users u ON c.executive_id = u.id
        $where ORDER BY c.call_time DESC LIMIT 300";
$result = mysqli_query($conn, $sql);
$rows = []; while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;

// Summary stats
$total     = count($rows);
$incoming  = count(array_filter($rows, fn($r) => $r['type'] === 'Incoming'));
$outgoing  = count(array_filter($rows, fn($r) => $r['type'] === 'Outgoing'));
$missed    = count(array_filter($rows, fn($r) => $r['type'] === 'Missed'));
$recorded  = count(array_filter($rows, fn($r) => !empty($r['recording_path'])));
$total_dur = array_sum(array_column($rows, 'duration'));
$dur_m = floor($total_dur / 60); $dur_s = $total_dur % 60;

// Executives for filter
$executives = [];
if ($role === 'admin') {
    $er = mysqli_query($conn, "SELECT id, name FROM users WHERE organization_id=$org_id AND status=1 ORDER BY name ASC");
    while ($e = mysqli_fetch_assoc($er)) $executives[] = $e;
}

include 'includes/header.php';
?>

<style>
.cl-kpi { display:grid; grid-template-columns:repeat(6,1fr); gap:.875rem; margin-bottom:1.5rem; }
.cl-kpi-card { background:#fff; border-radius:14px; padding:1rem; border:1px solid #f1f5f9; text-align:center; }
.cl-kpi-card .val { font-size:1.5rem; font-weight:800; }
.cl-kpi-card .lbl { font-size:.65rem; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:.04em; margin-top:.25rem; }
.filter-bar { background:#fff; border-radius:14px; border:1px solid #f1f5f9; padding:1rem 1.25rem; margin-bottom:1.25rem; }
.log-table { width:100%; border-collapse:collapse; font-size:.8125rem; }
.log-table thead th { background:#fafafa; border-bottom:2px solid #f1f5f9; padding:.75rem 1rem; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; text-align:left; white-space:nowrap; }
.log-table tbody tr { border-bottom:1px solid #f8fafc; transition:background .1s; }
.log-table tbody tr:hover { background:#f8faff; }
.log-table tbody td { padding:.75rem 1rem; vertical-align:middle; }
.log-table tbody tr:last-child { border-bottom:none; }
.type-badge { font-size:.65rem; font-weight:700; padding:.2rem .55rem; border-radius:6px; display:inline-block; }
.type-in  { background:#dcfce7; color:#15803d; }
.type-out { background:#e0e7ff; color:#3730a3; }
.type-miss{ background:#fee2e2; color:#b91c1c; }
.play-btn { background:#f5f3ff; color:#7c3aed; border:1px solid #ede9fe; padding:.3rem .7rem; border-radius:7px; font-size:.72rem; font-weight:700; cursor:pointer; transition:all .15s;display:inline-flex;align-items:center;gap:.3rem; }
.play-btn:hover { background:#7c3aed; color:#fff; }
.chip-btn { display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:8px;font-size:.72rem;font-weight:700;text-decoration:none;border:none;cursor:pointer; }
.active-filter { background:#eef2ff; color:#4f46e5; }
</style>

<!-- Header -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
    <div>
        <h1 style="font-size:1.25rem;font-weight:800;color:#0f172a;margin:0;">
            <i class="fas fa-phone-volume" style="color:#6366f1;margin-right:.5rem;"></i>Call Logs
        </h1>
        <p style="color:#94a3b8;font-size:.8rem;margin:.25rem 0 0;">All synced call activity from field executives</p>
    </div>
    <a href="recordings.php" style="display:inline-flex;align-items:center;gap:.4rem;background:#f5f3ff;color:#7c3aed;padding:.5rem 1rem;border-radius:10px;font-size:.8rem;font-weight:700;text-decoration:none;">
        <i class="fas fa-microphone-alt"></i> Recordings
    </a>
</div>

<!-- KPI Summary Strip -->
<div class="cl-kpi">
    <div class="cl-kpi-card"><div class="val" style="color:#6366f1;"><?= $total ?></div><div class="lbl">Total Calls</div></div>
    <div class="cl-kpi-card"><div class="val" style="color:#16a34a;"><?= $incoming ?></div><div class="lbl">Incoming</div></div>
    <div class="cl-kpi-card"><div class="val" style="color:#3730a3;"><?= $outgoing ?></div><div class="lbl">Outgoing</div></div>
    <div class="cl-kpi-card"><div class="val" style="color:#dc2626;"><?= $missed ?></div><div class="lbl">Missed</div></div>
    <div class="cl-kpi-card"><div class="val" style="color:#7c3aed;"><?= $recorded ?></div><div class="lbl">Recorded</div></div>
    <div class="cl-kpi-card"><div class="val" style="color:#0369a1;"><?= "{$dur_m}m" ?></div><div class="lbl">Talk Time</div></div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label style="font-size:.65rem;font-weight:700;color:#94a3b8;display:block;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.05em;">Search</label>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#cbd5e1;font-size:.75rem;"></i>
                <input type="text" name="search" class="form-control" style="padding-left:2rem;" placeholder="Mobile, name, contact..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>

        <?php if ($role === 'admin'): ?>
        <div style="min-width:160px;">
            <label style="font-size:.65rem;font-weight:700;color:#94a3b8;display:block;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.05em;">Executive</label>
            <select name="executive_id" class="form-control">
                <option value="">All Executives</option>
                <?php foreach ($executives as $e): ?>
                <option value="<?= $e['id'] ?>" <?= $exec_filter==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div style="min-width:130px;">
            <label style="font-size:.65rem;font-weight:700;color:#94a3b8;display:block;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.05em;">Call Type</label>
            <select name="type" class="form-control">
                <option value="">All Types</option>
                <option value="Incoming"  <?= $type_filter=='Incoming' ?'selected':'' ?>>Incoming</option>
                <option value="Outgoing"  <?= $type_filter=='Outgoing' ?'selected':'' ?>>Outgoing</option>
                <option value="Missed"    <?= $type_filter=='Missed'   ?'selected':'' ?>>Missed</option>
            </select>
        </div>

        <div style="width:130px;">
            <label style="font-size:.65rem;font-weight:700;color:#94a3b8;display:block;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.05em;">From</label>
            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
        </div>
        <div style="width:130px;">
            <label style="font-size:.65rem;font-weight:700;color:#94a3b8;display:block;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.05em;">To</label>
            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
        </div>

        <div style="display:flex;gap:.5rem;align-items:flex-end;">
            <button type="submit" class="btn btn-primary" style="width:auto;padding:.5rem 1.25rem;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button type="button" onclick="window.location.href='call_logs.php?date_from=<?= $today ?>&date_to=<?= $today ?>'" class="btn" style="width:auto;padding:.5rem 1rem;background:#e0f2fe;color:#0369a1;">
                <i class="fas fa-sun"></i> Today
            </button>
            <?php if ($search||$type_filter||$date_from||$date_to||$exec_filter||$rec_only): ?>
            <a href="call_logs.php" class="btn" style="width:auto;padding:.5rem 1rem;background:#fee2e2;color:#b91c1c;text-decoration:none;">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Quick filter chips -->
    <div style="display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap;">
        <a href="call_logs.php?rec_only=1<?= $exec_filter?" &executive_id=$exec_filter":"" ?>" class="chip-btn <?= $rec_only?'active-filter':''; ?>" style="<?= !$rec_only?'background:#f1f5f9;color:#64748b;':''; ?>">
            <i class="fas fa-microphone-alt"></i> With Recording
        </a>
        <a href="call_logs.php?type=Missed" class="chip-btn" style="background:#fee2e2;color:#b91c1c;">
            <i class="fas fa-phone-missed"></i> All Missed
        </a>
        <a href="call_logs.php?date_from=<?= date('Y-m-d',strtotime('monday this week')) ?>&date_to=<?= $today ?>" class="chip-btn" style="background:#fffbeb;color:#b45309;">
            <i class="fas fa-calendar-week"></i> This Week
        </a>
        <a href="call_logs.php?date_from=<?= date('Y-m-01') ?>&date_to=<?= $today ?>" class="chip-btn" style="background:#f0fdf4;color:#15803d;">
            <i class="fas fa-calendar"></i> This Month
        </a>
    </div>
</div>

<!-- Table -->
<div style="background:#fff;border-radius:16px;border:1px solid #f1f5f9;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.03);">
    <table class="log-table">
        <thead>
            <tr>
                <th>Lead / Contact</th>
                <th>Type</th>
                <th>Duration</th>
                <?php if ($role === 'admin'): ?><th>Executive</th><?php endif; ?>
                <th>Call Time</th>
                <th>Recording</th>
                <th style="text-align:right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="<?= $role==='admin'?7:6 ?>" style="text-align:center;padding:4rem;color:#cbd5e1;">
                <i class="fas fa-phone-slash" style="font-size:2.5rem;display:block;margin-bottom:1rem;opacity:.3;"></i>
                No call logs found for the selected filters.
            </td></tr>
            <?php endif; ?>

            <?php foreach ($rows as $row):
                $m = floor($row['duration']/60); $s = $row['duration']%60;
                $typeClass = $row['type']==='Incoming'?'type-in':($row['type']==='Outgoing'?'type-out':'type-miss');
                $displayName = $row['lead_name'] ?: $row['contact_name'] ?: 'Unknown';
            ?>
            <tr>
                <td>
                    <?php if ($row['lead_id']): ?>
                    <a href="lead_view.php?id=<?= $row['lead_id'] ?>" style="text-decoration:none;">
                        <div style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($displayName) ?></div>
                        <div style="font-size:.7rem;color:#6366f1;margin-top:2px;"><i class="fas fa-phone-alt" style="font-size:.6rem;"></i> <?= $row['mobile'] ?></div>
                    </a>
                    <?php else: ?>
                        <div style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($displayName) ?></div>
                        <div style="font-size:.7rem;color:#6366f1;margin-top:2px;"><i class="fas fa-phone-alt" style="font-size:.6rem;"></i> <?= $row['mobile'] ?></div>
                    <?php endif; ?>
                </td>

                <td>
                    <span class="type-badge <?= $typeClass ?>">
                        <i class="fas fa-phone-<?= $row['type']==='Missed'?'slash':($row['type']==='Incoming'?'volume-down':'volume') ?>" style="font-size:.6rem;"></i>
                        <?= $row['type'] ?>
                    </span>
                </td>

                <td>
                    <?php if ($row['duration'] > 0): ?>
                        <span style="font-family:monospace;font-size:.8rem;font-weight:700;color:#374151;"><?= "{$m}m {$s}s" ?></span>
                    <?php else: ?>
                        <span style="color:#cbd5e1;">—</span>
                    <?php endif; ?>
                </td>

                <?php if ($role === 'admin'): ?>
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <div style="width:24px;height:24px;border-radius:7px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.65rem;">
                            <?= strtoupper(substr($row['executive_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span style="font-size:.8rem;color:#374151;font-weight:600;"><?= htmlspecialchars($row['executive_name'] ?? '') ?></span>
                    </div>
                </td>
                <?php endif; ?>

                <td>
                    <div style="font-weight:700;color:#374151;font-size:.8rem;"><?= date('d M Y', strtotime($row['call_time'])) ?></div>
                    <div style="font-size:.7rem;color:#94a3b8;"><?= date('h:i A', strtotime($row['call_time'])) ?></div>
                </td>

                <td>
                    <?php if ($row['recording_path']): ?>
                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                        <button class="play-btn" onclick="playRecord('<?= htmlspecialchars($row['recording_path']) ?>')">
                            <i class="fas fa-play"></i> Listen
                        </button>
                        <span style="font-size:.6rem;color:#cbd5e1;font-family:monospace;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= basename($row['recording_path']) ?>">
                            <?= basename($row['recording_path']) ?>
                        </span>
                    </div>
                    <?php else: ?>
                        <span style="color:#e2e8f0;font-size:.75rem;">—</span>
                    <?php endif; ?>
                </td>

                <td style="text-align:right;white-space:nowrap;">
                    <?php if (!$row['lead_id']): ?>
                        <a href="lead_add.php?mobile=<?= $row['mobile'] ?>&call_id=<?= $row['id'] ?>" style="background:#10b981;color:#fff;padding:.3rem .7rem;border-radius:7px;font-size:.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                            <i class="fas fa-plus"></i> Add Lead
                        </a>
                    <?php else: ?>
                        <a href="lead_view.php?id=<?= $row['lead_id'] ?>" style="background:#eef2ff;color:#6366f1;padding:.3rem .7rem;border-radius:7px;font-size:.72rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                            <i class="fas fa-eye"></i> View
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total === 300): ?>
    <div style="text-align:center;padding:.75rem;font-size:.75rem;color:#94a3b8;border-top:1px solid #f1f5f9;">
        Showing top 300 results — use filters to narrow down.
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
