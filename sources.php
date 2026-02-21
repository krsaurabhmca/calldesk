<?php
// sources.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$message = '';
$error = '';

// Handle Add/Edit/Delete
$org_id = getOrgId();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_source'])) {
        $name = mysqli_real_escape_string($conn, $_POST['source_name']);
        if (mysqli_query($conn, "INSERT INTO lead_sources (organization_id, source_name) VALUES ($org_id, '$name')")) {
            $message = "Source added successfully!";
        } else {
            $error = "Duplicate or invalid source name.";
        }
    }
    
    if (isset($_POST['delete_source'])) {
        $id = (int)$_POST['source_id'];
        if (mysqli_query($conn, "DELETE FROM lead_sources WHERE id = $id AND organization_id = $org_id")) {
            $message = "Source deleted successfully!";
        } else {
            $error = "Cannot delete source. It might be linked to existing leads.";
        }
    }
}

$org_id = getOrgId();
$sources = mysqli_query($conn, "SELECT * FROM lead_sources WHERE organization_id = $org_id ORDER BY source_name ASC");

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">Lead Sources</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Manage categories for your lead origins.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Add Source Form -->
    <div class="card">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.5rem;">Add New Source</h3>
        <form action="" method="POST">
            <input type="hidden" name="add_source" value="1">
            <div class="form-group">
                <label class="form-label">Source Name</label>
                <input type="text" name="source_name" class="form-control" placeholder="e.g. Instagram Ads" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Source</button>
        </form>
    </div>

    <!-- Source List -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="padding: 1rem 1.5rem;">Source Name</th>
                        <th style="padding: 1rem 1.5rem;">Leads Count</th>
                        <th style="padding: 1rem 1.5rem; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($sources)): 
                        $sid = $row['id'];
                        $count_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE source_id = $sid");
                        $count = mysqli_fetch_assoc($count_res)['count'];
                    ?>
                    <tr>
                        <td style="padding: 1rem 1.5rem; font-weight: 600;"><?php echo $row['source_name']; ?></td>
                        <td style="padding: 1rem 1.5rem; color: var(--text-muted);"><?php echo $count; ?></td>
                        <td style="padding: 1rem 1.5rem; text-align: right;">
                            <form action="" method="POST" onsubmit="return confirm('Are you sure? This will set source to NULL for existing leads.');" style="display: inline;">
                                <input type="hidden" name="source_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="delete_source" value="1">
                                <button type="submit" class="btn" style="background: #fee2e2; color: #b91c1c; padding: 0.5rem; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-trash" style="font-size: 0.75rem;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
