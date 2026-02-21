<?php
// lead_view.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$org_id = getOrgId();
$sql = "SELECT l.*, u.name as executive_name, s.source_name 
        FROM leads l 
        LEFT JOIN users u ON l.assigned_to = u.id 
        LEFT JOIN lead_sources s ON l.source_id = s.id
        WHERE l.id = $lead_id AND l.organization_id = $org_id";
if ($role !== 'admin') {
    $sql .= " AND l.assigned_to = $user_id";
}
$result = mysqli_query($conn, $sql);
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    die("Lead not found or access denied.");
}

// Handle New Follow-up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_followup'])) {
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);
    $next_date = mysqli_real_escape_string($conn, $_POST['next_follow_up_date']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);

    // Insert follow-up
    mysqli_query($conn, "INSERT INTO follow_ups (organization_id, lead_id, executive_id, remark, next_follow_up_date) VALUES ($org_id, $lead_id, $user_id, '$remark', '$next_date')");
    
    // Update lead status
    mysqli_query($conn, "UPDATE leads SET status = '$new_status', remarks = '$remark' WHERE id = $lead_id");
    
    header("Location: lead_view.php?id=$lead_id&success=1");
    exit();
}

// Fetch Follow-up History
$history_res = mysqli_query($conn, "SELECT f.*, u.name as executive_name FROM follow_ups f JOIN users u ON f.executive_id = u.id JOIN leads l ON f.lead_id = l.id WHERE f.lead_id = $lead_id AND l.organization_id = $org_id ORDER BY f.created_at DESC");

include 'includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center;">
            <a href="leads.php" style="margin-right: 1rem; color: var(--secondary);"><i class="fas fa-arrow-left"></i></a>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Lead Details</h2>
        </div>
        <a href="lead_edit.php?id=<?php echo $lead_id; ?>" class="btn" style="width: auto; background: var(--gray-200); color: var(--dark); text-decoration: none;">
            <i class="fas fa-edit"></i> Edit Lead
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Lead Info Card -->
        <div class="card" style="margin-bottom: 0;">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem; color: var(--primary);">Basic Information</h3>
            <div style="display: grid; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Name</label>
                    <div style="font-weight: 600; font-size: 1.125rem;"><?php echo $lead['name']; ?></div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Mobile Number</label>
                    <div style="font-weight: 600; font-size: 1.125rem; color: var(--primary);">
                        <a href="tel:<?php echo $lead['mobile']; ?>" style="color: inherit; text-decoration: none;">
                            <i class="fas fa-phone-alt"></i> <?php echo $lead['mobile']; ?>
                        </a>
                        <a href="https://wa.me/<?php echo $lead['mobile']; ?>" target="_blank" style="margin-left: 1rem; color: #25D366;">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Source</label>
                    <div style="font-weight: 600;"><?php echo $lead['source_name'] ?: 'Direct / Unknown'; ?></div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Current Status</label>
                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $lead['status'])); ?>"><?php echo $lead['status']; ?></span>
                </div>
            </div>
        </div>

        <!-- Follow-up Form -->
        <div class="card" style="margin-bottom: 0; background: #f8fafc; border: 1px dashed var(--gray-300);">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem;">Log Follow-up</h3>
            <form action="" method="POST">
                <input type="hidden" name="add_followup" value="1">
                <div class="form-group">
                    <label class="form-label">Update Status</label>
                    <select name="status" class="form-control" required>
                        <option value="New" <?php echo $lead['status'] == 'New' ? 'selected' : ''; ?>>New</option>
                        <option value="Follow-up" <?php echo $lead['status'] == 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                        <option value="Interested" <?php echo $lead['status'] == 'Interested' ? 'selected' : ''; ?>>Interested</option>
                        <option value="Converted" <?php echo $lead['status'] == 'Converted' ? 'selected' : ''; ?>>Converted</option>
                        <option value="Lost" <?php echo $lead['status'] == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Next Follow-up Date</label>
                    <input type="date" name="next_follow_up_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Remarks</label>
                    <textarea name="remark" class="form-control" rows="3" placeholder="What happened in this call?" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update & Save</button>
            </form>
        </div>
    </div>

    <!-- History -->
    <div class="card">
        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem;">Follow-up History</h3>
        <div style="display: grid; gap: 1rem;">
            <?php while ($h = mysqli_fetch_assoc($history_res)): ?>
                <div style="padding: 1rem; background: var(--gray-100); border-radius: var(--radius-md); position: relative; border-left: 4px solid var(--primary);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="font-weight: 700; font-size: 0.875rem; color: var(--dark);"><?php echo $h['executive_name']; ?></span>
                        <span style="font-size: 0.75rem; color: var(--secondary);"><?php echo date('d M Y, h:i A', strtotime($h['created_at'])); ?></span>
                    </div>
                    <div style="font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--secondary);"><?php echo $h['remark']; ?></div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: var(--primary);">
                        <i class="far fa-clock"></i> Next Follow-up: <?php echo date('d M Y', strtotime($h['next_follow_up_date'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($history_res) === 0): ?>
                <div style="text-align: center; color: var(--secondary); padding: 2rem;">No follow-up history yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
