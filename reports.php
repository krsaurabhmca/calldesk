<?php
// reports.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : '';

$org_id = getOrgId();
$where = "WHERE l.organization_id = $org_id";
if ($status_filter) {
    $where .= " AND l.status = '$status_filter'";
}
if ($start_date) {
    $where .= " AND DATE(l.created_at) >= '$start_date'";
}
if ($end_date) {
    $where .= " AND DATE(l.created_at) <= '$end_date'";
}

$sql = "SELECT l.*, u.name as executive_name, s.source_name 
        FROM leads l 
        LEFT JOIN users u ON l.assigned_to = u.id 
        LEFT JOIN lead_sources s ON l.source_id = s.id 
        $where ORDER BY l.id DESC";
$result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700;">Reports</h2>
    <button onclick="window.print()" class="btn btn-primary" style="width: auto;"><i class="fas fa-print" style="margin-right: 0.5rem;"></i> Print Report</button>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <div>
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="New" <?php echo $status_filter == 'New' ? 'selected' : ''; ?>>New</option>
                <option value="Follow-up" <?php echo $status_filter == 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                <option value="Interested" <?php echo $status_filter == 'Interested' ? 'selected' : ''; ?>>Interested</option>
                <option value="Converted" <?php echo $status_filter == 'Converted' ? 'selected' : ''; ?>>Converted</option>
                <option value="Lost" <?php echo $status_filter == 'Lost' ? 'selected' : ''; ?>>Lost</option>
            </select>
        </div>
        <div>
            <label class="form-label">From Date</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
        </div>
        <div>
            <label class="form-label">To Date</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
        </div>
        <div style="display: flex; align-items: flex-end;">
            <button type="submit" class="btn btn-primary">Generate</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Lead Name</th>
                    <th>Mobile</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><strong><?php echo $row['name']; ?></strong></td>
                    <td><?php echo $row['mobile']; ?></td>
                    <td><?php echo $row['source_name'] ?: 'N/A'; ?></td>
                    <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>"><?php echo $row['status']; ?></span></td>
                    <td><?php echo $row['executive_name'] ?: 'Unassigned'; ?></td>
                    <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--secondary);">No data found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    .sidebar, .header, .btn, form {
        display: none !important;
    }
    .main-content {
        padding: 0;
    }
    .card {
        box-shadow: none;
        border: 1px solid #eee;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
