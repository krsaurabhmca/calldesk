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
        $name = mysqli_real_escape_string($conn, trim($_POST['source_name']));
        try {
            if (mysqli_query($conn, "INSERT INTO lead_sources (organization_id, source_name) VALUES ($org_id, '$name')")) {
                $message = "Source added successfully!";
            } else {
                $error = "Failed to add source.";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Duplicate entry code
                $error = "This source name already exists in your organization.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['delete_source'])) {
        $id = (int)$_POST['source_id'];
        try {
            if (mysqli_query($conn, "DELETE FROM lead_sources WHERE id = $id AND organization_id = $org_id")) {
                $message = "Source deleted successfully!";
            } else {
                $error = "Failed to delete source.";
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$org_id = getOrgId();
$sources = mysqli_query($conn, "SELECT * FROM lead_sources WHERE organization_id = $org_id ORDER BY source_name ASC");

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div>
        <h2 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">Lead Sources</h2>
    </div>
</div>

<?php if ($message): ?>
<div style="background: #ecfdf5; color: #065f46; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #d1fae5; margin-bottom: 1rem; font-size: 0.8125rem; font-weight: 600;">
    <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> <?php echo $message; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="background: #fef2f2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #fee2e2; margin-bottom: 1rem; font-size: 0.8125rem; font-weight: 600;">
    <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Add Source Form -->
    <div class="card">
        <h3 style="font-size: 0.875rem; font-weight: 800; margin-bottom: 1rem;">Add New Source</h3>
        <form action="" method="POST">
            <input type="hidden" name="add_source" value="1">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label" style="font-size: 0.75rem;">Source Name</label>
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
