<?php
// followups.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$today = date('Y-m-d');

$org_id = getOrgId();
$where = "WHERE l.organization_id = $org_id AND f.next_follow_up_date IS NOT NULL";
if ($role !== 'admin') {
    $where .= " AND l.assigned_to = $user_id";
}

// Group by date or just list upcoming
$sql = "SELECT f.*, l.name as lead_name, l.mobile as lead_mobile, l.status as lead_status, u.name as executive_name 
        FROM follow_ups f 
        JOIN leads l ON f.lead_id = l.id 
        JOIN users u ON f.executive_id = u.id 
        $where 
        AND f.id IN (SELECT MAX(id) FROM follow_ups GROUP BY lead_id)
        ORDER BY f.next_follow_up_date ASC";

$result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<div style="margin-bottom: 1rem;">
    <h2 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">Scheduled Follow-ups</h2>
</div>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Next Follow-up</th>
                    <th>Lead Name</th>
                    <th>Mobile</th>
                    <th>Last Remark</th>
                    <th>Executive</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $is_today = $row['next_follow_up_date'] == $today;
                    $is_overdue = $row['next_follow_up_date'] < $today;
                ?>
                <tr style="<?php echo $is_today ? 'background: #fffbeb;' : ($is_overdue ? 'background: #fef2f2;' : ''); ?>">
                    <td>
                        <div style="font-weight: 700; color: <?php echo $is_overdue ? 'var(--danger)' : ($is_today ? 'var(--warning)' : 'var(--success)'); ?>">
                            <?php echo date('d M, Y', strtotime($row['next_follow_up_date'])); ?>
                            <?php if ($is_today): ?> <span class="badge badge-followup">Today</span> <?php endif; ?>
                            <?php if ($is_overdue): ?> <span class="badge badge-lost">Overdue</span> <?php endif; ?>
                        </div>
                    </td>
                    <td><strong><?php echo $row['lead_name']; ?></strong></td>
                    <td>
                         <a href="tel:<?php echo $row['lead_mobile']; ?>" style="color: var(--primary); text-decoration: none;">
                            <i class="fas fa-phone-alt"></i> <?php echo $row['lead_mobile']; ?>
                        </a>
                    </td>
                    <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo $row['remark']; ?>
                    </td>
                    <td><?php echo $row['executive_name']; ?></td>
                    <td>
                        <a href="lead_view.php?id=<?php echo $row['lead_id']; ?>" class="btn btn-primary" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.75rem;">
                            Update
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: var(--secondary);">No scheduled follow-ups found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
