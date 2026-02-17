<?php
// lead_edit.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Lead Details
$sql = "SELECT * FROM leads WHERE id = $lead_id";
if ($role !== 'admin') {
    $sql .= " AND assigned_to = $user_id";
}
$result = mysqli_query($conn, $sql);
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    die("Lead not found or access denied.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $source_id = !empty($_POST['source_id']) ? (int)$_POST['source_id'] : "NULL";
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : "NULL";
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $sql = "UPDATE leads SET 
            name = '$name', 
            mobile = '$mobile', 
            source_id = $source_id, 
            status = '$status', 
            assigned_to = $assigned_to, 
            remarks = '$remarks' 
            WHERE id = $lead_id";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: lead_view.php?id=$lead_id&success=1");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

$users_result = mysqli_query($conn, "SELECT id, name FROM users WHERE status = 1 ORDER BY name ASC");
$sources_result = mysqli_query($conn, "SELECT id, source_name FROM lead_sources ORDER BY source_name ASC");

include 'includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; align-items: center; margin-bottom: 2rem;">
        <a href="lead_view.php?id=<?php echo $lead_id; ?>" style="margin-right: 1rem; color: var(--text-muted);"><i class="fas fa-arrow-left"></i></a>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">Edit Lead</h2>
    </div>

    <?php if ($error): ?>
        <div style="background: #fff1f2; color: #be123c; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid #fecdd3;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($lead['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($lead['mobile']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Lead Source</label>
                    <select name="source_id" class="form-control" required>
                        <option value="">-- Select Source --</option>
                        <?php while ($s = mysqli_fetch_assoc($sources_result)): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $lead['source_id'] == $s['id'] ? 'selected' : ''; ?>><?php echo $s['source_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="New" <?php echo $lead['status'] == 'New' ? 'selected' : ''; ?>>New</option>
                        <option value="Follow-up" <?php echo $lead['status'] == 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                        <option value="Interested" <?php echo $lead['status'] == 'Interested' ? 'selected' : ''; ?>>Interested</option>
                        <option value="Converted" <?php echo $lead['status'] == 'Converted' ? 'selected' : ''; ?>>Converted</option>
                        <option value="Lost" <?php echo $lead['status'] == 'Lost' ? 'selected' : ''; ?>>Lost</option>
                    </select>
                </div>
                <?php if (isAdmin()): ?>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Assign To Executive</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">-- Select Executive --</option>
                        <?php while ($u = mysqli_fetch_assoc($users_result)): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $lead['assigned_to'] == $u['id'] ? 'selected' : ''; ?>><?php echo $u['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="assigned_to" value="<?php echo $lead['assigned_to']; ?>">
                <?php endif; ?>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Remarks / Notes</label>
                    <textarea name="remarks" class="form-control" rows="4"><?php echo htmlspecialchars($lead['remarks']); ?></textarea>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem;">Update Lead</button>
                <a href="lead_view.php?id=<?php echo $lead_id; ?>" class="btn" style="width: auto; padding: 0.75rem 2.5rem; background: #f1f5f9; color: var(--text-main); text-decoration: none;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
