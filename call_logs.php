<?php
// call_logs.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch call logs
$where = "WHERE 1=1";
if ($role !== 'admin') {
    $where .= " AND (c.executive_id = $user_id OR c.executive_id IS NULL)";
}

$sql = "SELECT c.*, l.name as lead_name, u.name as executive_name 
        FROM call_logs c 
        LEFT JOIN leads l ON c.lead_id = l.id 
        LEFT JOIN users u ON c.executive_id = u.id 
        $where 
        ORDER BY c.call_time DESC";

$result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">Call Logs</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">History of incoming and outgoing calls.</p>
    </div>
    <div style="display: flex; gap: 1rem;">
         <a href="call_sync_mock.php" class="btn btn-primary" style="width: auto; background: var(--secondary);"><i class="fas fa-sync" style="margin-right: 0.5rem;"></i> Sync Logs</a>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="padding: 1rem 1.5rem;">Time & Date</th>
                    <th style="padding: 1rem 1.5rem;">Type</th>
                    <th style="padding: 1rem 1.5rem;">Moblie / Lead</th>
                    <th style="padding: 1rem 1.5rem;">Duration</th>
                    <th style="padding: 1rem 1.5rem;">Executive</th>
                    <th style="padding: 1rem 1.5rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $type_color = $row['type'] == 'Incoming' ? 'var(--success)' : ($row['type'] == 'Outgoing' ? 'var(--primary)' : 'var(--danger)');
                    $type_icon = $row['type'] == 'Incoming' ? 'fa-arrow-left' : ($row['type'] == 'Outgoing' ? 'fa-arrow-right' : 'fa-phone-slash');
                ?>
                <tr>
                    <td style="padding: 1rem 1.5rem;">
                        <div style="font-weight: 600; color: var(--text-main);"><?php echo date('h:i A', strtotime($row['call_time'])); ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('d M, Y', strtotime($row['call_time'])); ?></div>
                    </td>
                    <td style="padding: 1rem 1.5rem;">
                        <span style="display: flex; align-items: center; gap: 0.5rem; color: <?php echo $type_color; ?>; font-weight: 600; font-size: 0.8125rem;">
                            <i class="fas <?php echo $type_icon; ?>" style="font-size: 0.75rem;"></i>
                            <?php echo $row['type']; ?>
                        </span>
                    </td>
                    <td style="padding: 1rem 1.5rem;">
                        <div style="font-weight: 700; color: var(--text-main);"><?php echo $row['lead_name'] ?: 'Unknown'; ?></div>
                        <div style="color: var(--text-muted); font-size: 0.75rem;"><?php echo $row['mobile']; ?></div>
                    </td>
                    <td style="padding: 1rem 1.5rem; font-family: monospace;">
                        <?php 
                            $m = floor($row['duration'] / 60);
                            $s = $row['duration'] % 60;
                            echo sprintf("%02d:%02d", $m, $s);
                        ?>
                    </td>
                    <td style="padding: 1rem 1.5rem; color: var(--text-muted);"><?php echo $row['executive_name'] ?: 'System'; ?></td>
                    <td style="padding: 1rem 1.5rem; text-align: right;">
                        <?php if ($row['lead_id']): ?>
                            <a href="lead_view.php?id=<?php echo $row['lead_id']; ?>" class="btn" style="width: auto; padding: 0.4rem 0.75rem; background: #f1f5f9; color: var(--primary); font-size: 0.75rem;">View Lead</a>
                        <?php else: ?>
                            <a href="lead_add.php?mobile=<?php echo $row['mobile']; ?>&call_id=<?php echo $row['id']; ?>" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.75rem; font-size: 0.75rem;">Convert to Lead</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-muted);">No call logs found. Sync to fetch latest calls.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
