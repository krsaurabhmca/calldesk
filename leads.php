<?php
// leads.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$org_id = getOrgId();
$where = "WHERE l.organization_id = $org_id";
if ($role !== 'admin') {
    $where .= " AND assigned_to = $user_id";
}
if ($search) {
    $where .= " AND (l.name LIKE '%$search%' OR l.mobile LIKE '%$search%')";
}
if ($status_filter) {
    $where .= " AND l.status = '$status_filter'";
}

$sql = "SELECT l.*, u.name as executive_name, s.source_name 
        FROM leads l 
        LEFT JOIN users u ON l.assigned_to = u.id 
        LEFT JOIN lead_sources s ON l.source_id = s.id
        $where ORDER BY l.id DESC";
$result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Manage Leads</h2>
        <p style="color: var(--text-muted); font-size: 0.8125rem;">View and manage your lead pipeline.</p>
    </div>
    <a href="lead_add.php" class="btn btn-primary" style="width: auto; padding: 0.625rem 1.25rem; font-size: 0.8125rem;">
        <i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Add Lead
    </a>
</div>

<div class="card" style="padding: 1rem; border: 1px solid var(--border); box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
    <form method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px; position: relative;">
            <i class="fas fa-filter" style="position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.75rem;"></i>
            <input type="text" name="search" class="form-control" style="padding: 0.5rem 0.75rem 0.5rem 2.25rem; font-size: 0.875rem; background: var(--background);" placeholder="Search name or mobile..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div style="width: 180px;">
            <select name="status" class="form-control" style="padding: 0.5rem 0.75rem; font-size: 0.875rem; background: var(--background);">
                <option value="">All Statuses</option>
                <option value="New" <?php echo $status_filter == 'New' ? 'selected' : ''; ?>>New</option>
                <option value="Follow-up" <?php echo $status_filter == 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                <option value="Interested" <?php echo $status_filter == 'Interested' ? 'selected' : ''; ?>>Interested</option>
                <option value="Converted" <?php echo $status_filter == 'Converted' ? 'selected' : ''; ?>>Converted</option>
                <option value="Lost" <?php echo $status_filter == 'Lost' ? 'selected' : ''; ?>>Lost</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.5rem 1.25rem; font-size: 0.875rem;">Filter</button>
        <?php if ($search || $status_filter): ?>
            <a href="leads.php" class="btn" style="width: auto; padding: 0.5rem 1.25rem; font-size: 0.875rem; background: #f1f5f9; color: var(--text-main); text-decoration: none; display: flex; align-items: center;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card" style="padding: 0; border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden;">
    <div class="table-container">
        <table style="font-size: 0.8125rem;">
            <thead style="background: #fafafa; border-bottom: 2px solid var(--border);">
                <tr>
                    <th style="padding: 0.75rem 1.25rem;">Lead Details</th>
                    <th style="padding: 0.75rem 1.25rem;">Source</th>
                    <th style="padding: 0.75rem 1.25rem;">Status</th>
                    <th style="padding: 0.75rem 1.25rem;">Assigned To</th>
                    <th style="padding: 0.75rem 1.25rem;">Created</th>
                    <th style="padding: 0.75rem 1.25rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                    <td style="padding: 0.75rem 1.25rem;">
                        <div style="font-weight: 700; color: var(--text-main);"><?php echo $row['name']; ?></div>
                        <div style="color: var(--primary); font-size: 0.75rem; font-weight: 600;"><i class="fas fa-phone-alt" style="font-size: 0.625rem;"></i> <?php echo $row['mobile']; ?></div>
                    </td>
                    <td style="padding: 0.75rem 1.25rem;">
                        <div style="background: #f1f5f9; padding: 0.125rem 0.5rem; border-radius: 4px; display: inline-block; font-size: 0.625rem; color: var(--text-muted);"><?php echo $row['source_name'] ?: 'Direct'; ?></div>
                    </td>
                    <td style="padding: 0.75rem 1.25rem;">
                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>" style="padding: 0.25rem 0.625rem; font-size: 0.6875rem;"><?php echo $row['status']; ?></span>
                    </td>
                    <td style="padding: 0.75rem 1.25rem; color: var(--text-muted);"><?php echo $row['executive_name'] ?? '<span style="color: var(--danger)">Pending</span>'; ?></td>
                    <td style="padding: 0.75rem 1.25rem; color: var(--text-muted);"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    <td style="padding: 0.75rem 1.25rem; text-align: right;">
                        <div style="display: flex; gap: 0.375rem; justify-content: flex-end;">
                            <a href="lead_view.php?id=<?php echo $row['id']; ?>" class="btn" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--primary);">
                                <i class="fas fa-eye" style="font-size: 0.75rem;"></i>
                            </a>
                            <a href="lead_edit.php?id=<?php echo $row['id']; ?>" class="btn" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--warning);">
                                <i class="fas fa-pen-to-square" style="font-size: 0.75rem;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-muted);">
                            <i class="fas fa-search" style="font-size: 2rem; opacity: 0.2; margin-bottom: 1rem; display: block;"></i>
                            No leads found matching your criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
