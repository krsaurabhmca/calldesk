<?php
// call_logs.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$org_id = getOrgId();
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$type_filter = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : '';

$where = "WHERE c.organization_id = $org_id";
if ($role !== 'admin') {
    $where .= " AND c.executive_id = $user_id";
}

if ($search) {
    $where .= " AND (c.mobile LIKE '%$search%' OR l.name LIKE '%$search%')";
}
if ($type_filter) {
    $where .= " AND c.type = '$type_filter'";
}

$sql = "SELECT c.*, l.name as lead_name, u.name as executive_name 
        FROM call_logs c 
        LEFT JOIN leads l ON c.lead_id = l.id 
        LEFT JOIN users u ON c.executive_id = u.id 
        $where ORDER BY c.call_time DESC";
$result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">Global Call Logs</h2>
    <p style="color: var(--text-muted); font-size: 0.875rem;">View synchronized call activity.</p>
</div>

<div class="card" style="margin-bottom: 2rem; padding: 1rem;">
    <form method="GET" style="display: flex; gap: 1rem;">
        <input type="text" name="search" class="form-control" placeholder="Search mobile or name..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="type" class="form-control" style="width: 200px;">
            <option value="">All Types</option>
            <option value="Incoming" <?php echo $type_filter == 'Incoming' ? 'selected' : ''; ?>>Incoming</option>
            <option value="Outgoing" <?php echo $type_filter == 'Outgoing' ? 'selected' : ''; ?>>Outgoing</option>
            <option value="Missed" <?php echo $type_filter == 'Missed' ? 'selected' : ''; ?>>Missed</option>
        </select>
        <button type="submit" class="btn btn-primary" style="width: auto;">Filter</button>
        <a href="call_logs.php" class="btn" style="width: auto; background: #f1f5f9; color: var(--text-main); text-decoration: none;">Clear</a>
    </form>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Lead / Contact</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Executive</th>
                    <th>Call Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>
                        <div style="font-weight: 700; color: var(--text-main);"><?php echo $row['lead_name'] ?: 'Unknown'; ?></div>
                        <div style="font-size: 0.75rem; color: var(--primary);"><i class="fas fa-phone"></i> <?php echo $row['mobile']; ?></div>
                    </td>
                    <td>
                        <span class="badge" style="background: <?php 
                            echo $row['type'] == 'Incoming' ? '#dcfce7; color: #166534;' : 
                                ($row['type'] == 'Outgoing' ? '#e0e7ff; color: #3730a3;' : '#fee2e2; color: #991b1b;'); 
                        ?>">
                            <?php echo $row['type']; ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $m = floor($row['duration'] / 60);
                        $s = $row['duration'] % 60;
                        echo "{$m}m {$s}s";
                        ?>
                    </td>
                    <td><?php echo $row['executive_name']; ?></td>
                    <td><?php echo date('d M, Y h:i A', strtotime($row['call_time'])); ?></td>
                    <td>
                        <?php if (!$row['lead_id']): ?>
                            <a href="lead_add.php?mobile=<?php echo $row['mobile']; ?>&call_id=<?php echo $row['id']; ?>" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background: var(--success); color: white;">+ Add Lead</a>
                        <?php else: ?>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Lead Linked</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
