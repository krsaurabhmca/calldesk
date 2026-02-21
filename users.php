<?php
// users.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$message = '';
$error = '';

// Handle Add/Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $org_id = getOrgId();

    if ($user_id > 0) {
        // Edit
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = '$name', mobile = '$mobile', role = '$role', password = '$pass' WHERE id = $user_id AND organization_id = $org_id";
        } else {
            $sql = "UPDATE users SET name = '$name', mobile = '$mobile', role = '$role' WHERE id = $user_id AND organization_id = $org_id";
        }
    } else {
        // Add
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (organization_id, name, mobile, password, role) VALUES ($org_id, '$name', '$mobile', '$pass', '$role')";
    }

    if (mysqli_query($conn, $sql)) {
        $message = "User updated successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

$org_id = getOrgId();
$sql = "SELECT * FROM users WHERE organization_id = $org_id ORDER BY name ASC";
$result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700;">User Management</h2>
    <button onclick="openModal()" class="btn btn-primary" style="width: auto;"><i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Add Executive</button>
</div>

<?php if ($message): ?>
    <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Join Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><strong><?php echo $row['name']; ?></strong></td>
                    <td><?php echo $row['mobile']; ?></td>
                    <td><span class="badge" style="background: #f1f5f9; color: #475569;"><?php echo strtoupper($row['role']); ?></span></td>
                    <td>
                        <span class="badge badge-<?php echo $row['status'] == 1 ? 'converted' : 'lost'; ?>">
                            <?php echo $row['status'] == 1 ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <button onclick='editUser(<?php echo json_encode($row); ?>)' class="btn" style="width: auto; padding: 0.4rem 0.75rem; background: var(--gray-100); color: var(--warning);">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Simple Modal (Hidden by default) -->
<div id="userModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div class="card" style="width: 100%; max-width: 500px; margin-bottom: 0;">
        <h3 id="modalTitle" style="margin-bottom: 1.5rem;">Add New User</h3>
        <form action="" method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Mobile Number</label>
                <input type="text" name="mobile" id="edit_mobile" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password (Leave blank if not changing)</label>
                <input type="password" name="password" id="edit_password" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" id="edit_role" class="form-control" required>
                    <option value="executive">Sales Executive</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Save User</button>
                <button type="button" onclick="closeModal()" class="btn" style="background: var(--gray-200); color: var(--dark);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Add New User';
    document.getElementById('edit_user_id').value = '';
    document.getElementById('edit_name').value = '';
    document.getElementById('edit_mobile').value = '';
    document.getElementById('edit_role').value = 'executive';
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

function editUser(user) {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Edit User';
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_mobile').value = user.mobile;
    document.getElementById('edit_role').value = user.role;
}
</script>

<?php include 'includes/footer.php'; ?>
