<?php
// recordings.php - All synced recordings with mobile mapping
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$org_id = getOrgId();
$role   = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Fetch all call_logs that have a recording_path, joined with leads for name
$where = "c.organization_id = $org_id AND c.recording_path IS NOT NULL AND c.recording_path != ''";
if ($role !== 'admin') {
    $where .= " AND c.executive_id = $user_id";
}
if ($search) {
    $where .= " AND (c.mobile LIKE '%$search%' OR l.name LIKE '%$search%')";
}

$sql = "SELECT c.id, c.mobile, c.call_time, c.duration, c.type, c.recording_path,
               l.id as lead_id, l.name as lead_name, l.status as lead_status,
               u.name as executive_name
        FROM call_logs c
        LEFT JOIN leads l ON c.mobile = l.mobile AND l.organization_id = $org_id
        LEFT JOIN users u ON c.executive_id = u.id
        WHERE $where
        GROUP BY c.id
        ORDER BY c.call_time DESC
        LIMIT 500";
$result = mysqli_query($conn, $sql);
$rows = [];
while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
}

// Stats
$total    = count($rows);
$unlinked = count(array_filter($rows, fn($r) => empty($r['lead_id'])));
$linked   = $total - $unlinked;

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.02em;">
            <i class="fas fa-microphone-alt" style="color: var(--primary); margin-right: 0.5rem;"></i>All Recordings
        </h2>
        <p style="color: var(--text-muted); font-size: 0.8125rem; margin-top: 0.25rem;">
            All call recordings synced from field executives, mapped to leads by mobile number.
        </p>
    </div>
    <a href="call_logs.php" class="btn" style="background: #f1f5f9; color: var(--text-main); text-decoration: none;">
        <i class="fas fa-phone-volume"></i> All Call Logs
    </a>
</div>

<!-- Stats Row -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.25rem;">
    <div class="card" style="padding: 1rem 1.25rem; border-left: 4px solid var(--primary);">
        <div style="font-size: 1.875rem; font-weight: 800; color: var(--primary);"><?php echo $total; ?></div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Total Recordings</div>
    </div>
    <div class="card" style="padding: 1rem 1.25rem; border-left: 4px solid #10b981;">
        <div style="font-size: 1.875rem; font-weight: 800; color: #10b981;"><?php echo $linked; ?></div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Linked to Leads</div>
    </div>
    <div class="card" style="padding: 1rem 1.25rem; border-left: 4px solid #f59e0b;">
        <div style="font-size: 1.875rem; font-weight: 800; color: #f59e0b;"><?php echo $unlinked; ?></div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Unlinked Numbers</div>
    </div>
</div>

<!-- Search -->
<div class="card" style="padding: 0.75rem; margin-bottom: 1rem;">
    <form method="GET" style="display: flex; gap: 0.5rem;">
        <div style="flex: 1; position: relative;">
            <i class="fas fa-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.75rem;"></i>
            <input type="text" name="search" class="form-control" style="padding-left: 2rem;" placeholder="Search by mobile or lead name..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 0 1.25rem;">Search</button>
        <?php if ($search): ?>
            <a href="recordings.php" class="btn" style="background: #f1f5f9; color: var(--text-main); text-decoration: none;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Recordings Table -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-container">
        <table style="font-size: 0.8125rem; width: 100%; border-collapse: collapse;">
            <thead style="background: #fafafa; border-bottom: 2px solid var(--border);">
                <tr style="text-align: left;">
                    <th style="padding: 0.75rem 1rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Mobile & Lead</th>
                    <th style="padding: 0.75rem 1rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Date & Time</th>
                    <th style="padding: 0.75rem 1rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Type / Duration</th>
                    <th style="padding: 0.75rem 1rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Filename</th>
                    <th style="padding: 0.75rem 1rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 4rem; color: var(--text-muted);">
                        <i class="fas fa-microphone-slash" style="font-size: 2.5rem; opacity: 0.15; display: block; margin-bottom: 1rem;"></i>
                        No recordings found. Sync from the mobile app to see recordings here.
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                    <!-- Mobile & Lead -->
                    <td style="padding: 0.75rem 1rem;">
                        <div style="font-weight: 700; color: var(--primary); font-size: 0.9rem;">
                            <i class="fas fa-phone-alt" style="font-size: 0.625rem;"></i> <?php echo $row['mobile']; ?>
                        </div>
                        <?php if ($row['lead_id']): ?>
                            <a href="lead_view.php?id=<?php echo $row['lead_id']; ?>" style="font-size: 0.75rem; color: var(--text-main); font-weight: 600; text-decoration: none;">
                                <i class="fas fa-user" style="font-size: 0.6rem; color: #10b981;"></i> <?php echo htmlspecialchars($row['lead_name']); ?>
                                <span style="font-size: 0.65rem; color: var(--text-muted);">(<?php echo $row['lead_status']; ?>)</span>
                            </a>
                        <?php else: ?>
                            <a href="lead_add.php?mobile=<?php echo $row['mobile']; ?>" 
                               style="font-size: 0.7rem; color: #f59e0b; font-weight: 600; text-decoration: none;">
                                <i class="fas fa-plus-circle"></i> Add as Lead
                            </a>
                        <?php endif; ?>
                    </td>

                    <!-- Date & Time -->
                    <td style="padding: 0.75rem 1rem;">
                        <div style="font-weight: 700; color: var(--text-main);"><?php echo date('d M Y', strtotime($row['call_time'])); ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('h:i A', strtotime($row['call_time'])); ?></div>
                    </td>

                    <!-- Type / Duration -->
                    <td style="padding: 0.75rem 1rem;">
                        <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 4px; background: <?php
                            echo $row['type'] == 'Incoming' ? '#dcfce7; color: #166534' : ($row['type'] == 'Outgoing' ? '#e0e7ff; color: #3730a3' : '#fee2e2; color: #991b1b');
                        ?>;">
                            <?php echo $row['type'] ?: 'Unknown'; ?>
                        </span>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">
                            <?php 
                                $dur = (int)$row['duration'];
                                echo $dur > 0 ? floor($dur/60).'m '.($dur%60).'s' : '—';
                            ?>
                        </div>
                    </td>

                    <!-- Filename -->
                    <td style="padding: 0.75rem 1rem; max-width: 220px;">
                        <div style="font-family: monospace; font-size: 0.65rem; color: var(--text-muted); word-break: break-all; line-height: 1.4;">
                            <?php echo basename($row['recording_path']); ?>
                        </div>
                    </td>

                    <!-- Actions -->
                    <td style="padding: 0.75rem 1rem; text-align: right; white-space: nowrap;">
                        <button onclick="playRecord('<?php echo htmlspecialchars($row['recording_path']); ?>')"
                            style="background: var(--primary); color: white; border: none; padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                            <i class="fas fa-play"></i> Listen
                        </button>
                        <?php if ($row['lead_id']): ?>
                        <a href="lead_view.php?id=<?php echo $row['lead_id']; ?>"
                           style="background: #f1f5f9; color: var(--text-main); text-decoration: none; padding: 0.35rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; margin-left: 4px;">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
