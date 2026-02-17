<?php
// lead_add.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$message = '';
$error = '';

$prefill_mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';
$call_id = isset($_GET['call_id']) ? (int)$_GET['call_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $source_id = !empty($_POST['source_id']) ? (int)$_POST['source_id'] : "NULL";
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : "NULL";
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $sql = "INSERT INTO leads (name, mobile, source_id, status, assigned_to, remarks) 
            VALUES ('$name', '$mobile', $source_id, '$status', $assigned_to, '$remarks')";
    
    if (mysqli_query($conn, $sql)) {
        $new_lead_id = mysqli_insert_id($conn);
        
        // If coming from call log, link it
        if ($call_id > 0) {
            mysqli_query($conn, "UPDATE call_logs SET lead_id = $new_lead_id, is_converted = 1 WHERE id = $call_id");
        }
        
        header("Location: leads.php?success=1");
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
        <a href="leads.php" style="margin-right: 1rem; color: var(--text-muted);"><i class="fas fa-arrow-left"></i></a>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">Add New Lead</h2>
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
                    <input type="text" name="name" class="form-control" placeholder="Enter lead name" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="mobile" class="form-control" placeholder="Enter mobile number" value="<?php echo htmlspecialchars($prefill_mobile); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Lead Source</label>
                    <select name="source_id" class="form-control" required>
                        <option value="">-- Select Source --</option>
                        <?php while ($s = mysqli_fetch_assoc($sources_result)): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['source_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="New">New</option>
                        <option value="Follow-up">Follow-up</option>
                        <option value="Interested">Interested</option>
                        <option value="Converted">Converted</option>
                        <option value="Lost">Lost</option>
                    </select>
                </div>
                <?php if (isAdmin()): ?>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Assign To Executive</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">-- Select Executive --</option>
                        <?php while ($u = mysqli_fetch_assoc($users_result)): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo $u['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="assigned_to" value="<?php echo $_SESSION['user_id']; ?>">
                <?php endif; ?>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Remarks / Notes</label>
                    <textarea name="remarks" class="form-control" rows="4" placeholder="Initial notes about the lead..."></textarea>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem;">Create Lead</button>
                <a href="leads.php" class="btn" style="width: auto; padding: 0.75rem 2.5rem; background: #f1f5f9; color: var(--text-main); text-decoration: none;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
