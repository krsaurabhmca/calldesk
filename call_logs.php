<?php
// call_logs.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- Helper: Get Base URL for Pagination ---
function get_url_param($new_params = []) {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return '?' . http_build_query($params);
}

// --- Filter Parameters ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$actual_calls = isset($_GET['actual_calls']) ? $_GET['actual_calls'] : '';
$executive_filter = isset($_GET['executive_id']) ? $_GET['executive_id'] : '';

// --- Pagination Setup ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// --- Build Query ---
$where = "WHERE 1=1";

if ($role !== 'admin') {
    // Executive sees their own logs OR logs for leads assigned to them
    $where .= " AND (c.executive_id = $user_id OR l.assigned_to = $user_id)";
} elseif ($executive_filter) {
    // Admin filtering by specific executive
    $exe_id = (int)$executive_filter;
    $where .= " AND c.executive_id = $exe_id";
}

// Search (Mobile or Lead Name)
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (c.mobile LIKE '%$s%' OR l.name LIKE '%$s%')";
}

// Call Type
if ($type) {
    $t = mysqli_real_escape_string($conn, $type);
    $where .= " AND c.type = '$t'";
}

// Date Range
if ($start_date) {
    $sd = mysqli_real_escape_string($conn, $start_date);
    $where .= " AND DATE(c.call_time) >= '$sd'";
}
if ($end_date) {
    $ed = mysqli_real_escape_string($conn, $end_date);
    $where .= " AND DATE(c.call_time) <= '$ed'";
}

// Actual Calls Only (Duration > 0)
if ($actual_calls === '1') {
    $where .= " AND c.duration > 0";
}

// --- Count Total for Pagination ---
$count_sql = "SELECT COUNT(*) as total FROM call_logs c LEFT JOIN leads l ON c.lead_id = l.id $where";
$count_res = mysqli_query($conn, $count_sql);
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $limit);

// --- Fetch Data ---
$sql = "SELECT c.*, l.id as lead_id, l.name as lead_name, u.name as executive_name, l.status as lead_status
        FROM call_logs c 
        LEFT JOIN leads l ON c.lead_id = l.id 
        LEFT JOIN users u ON c.executive_id = u.id 
        $where 
        ORDER BY c.call_time DESC 
        LIMIT $offset, $limit";

$result = mysqli_query($conn, $sql);

// --- Fetch Executives for Filter (Admin Only) ---
$executives = [];
if ($role === 'admin') {
    $exe_sql = "SELECT id, name FROM users WHERE role != 'admin' ORDER BY name";
    $exe_res = mysqli_query($conn, $exe_sql);
    while($r = mysqli_fetch_assoc($exe_res)) {
        $executives[] = $r;
    }
}

include 'includes/header.php';
?>

<style>
    /* Compact Filter Bar */
    .filter-bar {
        background: #fff;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: end;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .filter-group label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
    }
    .filter-input {
        padding: 0.5rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 0.875rem;
        color: var(--text-main);
        min-width: 140px;
    }
    .filter-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
    }
    .btn-filter {
        padding: 0.5rem 1rem;
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        height: 38px;
    }
    .btn-reset {
        padding: 0.5rem 1rem;
        background: #f1f5f9;
        color: var(--text-muted);
        border: none;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        height: 38px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    /* Checkbox Style */
    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        height: 38px;
        padding-top: 1.25rem; /* Align with inputs with labels */
    }
    .checkbox-wrapper input {
        width: 16px;
        height: 16px;
        accent-color: var(--primary);
    }
    .checkbox-wrapper label {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-main);
        cursor: pointer;
        margin: 0;
    }
    /* Status Badge */
    .badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-info { background: #e0f2fe; color: #075985; }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }
    .page-link {
        padding: 0.5rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: var(--text-main);
        text-decoration: none;
        font-size: 0.875rem;
        background: #fff;
    }
    .page-link.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .page-link:hover:not(.active) {
        background: #f8fafc;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">Call Management</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">
            Showing <?php echo number_format($total_rows); ?> logs
            <?php if ($actual_calls) echo '(Actual Calls Only)'; ?>
        </p>
    </div>
    <div style="display: flex; gap: 1rem;">
         <!-- If you have a sync page/modal -->
    </div>
</div>

<!-- Filter Form -->
<form method="GET" class="filter-bar">
    <div class="filter-group">
        <label>Search</label>
        <input type="text" name="search" class="filter-input" placeholder="Name or Mobile..." value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <div class="filter-group">
        <label>Call Type</label>
        <select name="type" class="filter-input">
            <option value="">All Types</option>
            <option value="Incoming" <?php echo $type === 'Incoming' ? 'selected' : ''; ?>>Incoming</option>
            <option value="Outgoing" <?php echo $type === 'Outgoing' ? 'selected' : ''; ?>>Outgoing</option>
            <option value="Missed" <?php echo $type === 'Missed' ? 'selected' : ''; ?>>Missed</option>
            <option value="Rejected" <?php echo $type === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
    </div>

    <?php if ($role === 'admin'): ?>
    <div class="filter-group">
        <label>Executive</label>
        <select name="executive_id" class="filter-input">
            <option value="">All Executives</option>
            <?php foreach ($executives as $ex): ?>
                <option value="<?php echo $ex['id']; ?>" <?php echo $executive_filter == $ex['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ex['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="filter-group">
        <label>From Date</label>
        <input type="date" name="start_date" class="filter-input" value="<?php echo htmlspecialchars($start_date); ?>">
    </div>

    <div class="filter-group">
        <label>To Date</label>
        <input type="date" name="end_date" class="filter-input" value="<?php echo htmlspecialchars($end_date); ?>">
    </div>

    <div class="checkbox-wrapper">
        <input type="checkbox" id="actual_calls" name="actual_calls" value="1" <?php echo $actual_calls === '1' ? 'checked' : ''; ?>>
        <label for="actual_calls">Actual Calls Only</label>
    </div>

    <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply</button>
        <a href="call_logs.php" class="btn-reset">Reset</a>
    </div>
</form>

<div class="card" style="padding: 0; overflow: hidden; border-radius: 12px; border: 1px solid #e2e8f0; background: #fff;">
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Date & Time</th>
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Type</th>
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Contact</th>
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Duration</th>
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Executive</th>
                    <th style="padding: 1rem 1.5rem; text-align: right; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_rows > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): 
                        // Determine colors and icons
                        $type_bg = '';
                        $type_text = '';
                        $icon = '';
                        
                        switch(strtolower($row['type'])) {
                            case 'incoming':
                                $type_bg = '#ecfdf5'; $type_text = '#10b981'; $icon = 'fa-arrow-down';
                                break;
                            case 'outgoing':
                                $type_bg = '#f5f3ff'; $type_text = '#6366f1'; $icon = 'fa-arrow-up';
                                break;
                            case 'missed':
                                $type_bg = '#fef2f2'; $type_text = '#ef4444'; $icon = 'fa-arrow-left'; // Rotated usually
                                break;
                            default:
                                $type_bg = '#f1f5f9'; $type_text = '#64748b'; $icon = 'fa-phone';
                        }
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 1rem 1.5rem;">
                            <div style="font-weight: 600; color: var(--text-main); font-size: 0.9rem;">
                                <?php echo date('h:i A', strtotime($row['call_time'])); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                <?php echo date('d M, Y', strtotime($row['call_time'])); ?>
                            </div>
                        </td>
                        <td style="padding: 1rem 1.5rem;">
                            <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.6rem; border-radius: 99px; background: <?php echo $type_bg; ?>; color: <?php echo $type_text; ?>; font-weight: 600; font-size: 0.75rem;">
                                <i class="fas <?php echo $icon; ?>" style="font-size: 0.7rem; <?php echo strtolower($row['type']) === 'missed' ? 'transform: rotate(45deg);' : ''; ?>"></i>
                                <?php echo ucfirst($row['type']); ?>
                            </span>
                        </td>
                        <td style="padding: 1rem 1.5rem;">
                            <?php if ($row['lead_id']): ?>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($row['lead_name']); ?></div>
                                    <?php 
                                        $status_color = 'badge-info';
                                        if ($row['lead_status'] == 'Converted') $status_color = 'badge-success';
                                        if ($row['lead_status'] == 'Follow-up') $status_color = 'badge-warning';
                                        if ($row['lead_status'] == 'Lost') $status_color = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $status_color; ?>"><?php echo $row['lead_status']; ?></span>
                                </div>
                            <?php else: ?>
                                <div style="font-weight: 700; color: #64748b;">Unknown Lead</div>
                            <?php endif; ?>
                            <div style="color: var(--text-muted); font-size: 0.8rem; font-family: monospace; letter-spacing: 0.5px; margin-top: 2px;">
                                <?php echo $row['mobile']; ?>
                            </div>
                        </td>
                        <td style="padding: 1rem 1.5rem;">
                            <div style="font-family: monospace; font-size: 0.9rem; color: var(--text-main);">
                                <?php 
                                    $dur = (int)$row['duration'];
                                    $m = floor($dur / 60);
                                    $s = $dur % 60;
                                    echo $dur > 0 ? sprintf("%02d:%02d", $m, $s) : '<span style="color: #cbd5e1;">--:--</span>';
                                ?>
                            </div>
                            <?php if ($dur > 0): ?>
                                <div style="font-size: 0.7rem; color: #94a3b8;"><?php echo $dur; ?> sec</div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem 1.5rem;">
                             <div style="font-size: 0.875rem; color: var(--text-main); font-weight: 500;">
                                <?php echo htmlspecialchars($row['executive_name'] ?: 'System'); ?>
                             </div>
                        </td>
                        <td style="padding: 1rem 1.5rem; text-align: right;">
                            <?php if ($row['lead_id']): ?>
                                <a href="leads.php?search=<?php echo $row['mobile']; ?>" class="btn" style="width: auto; padding: 0.4rem 0.75rem; background: #eef2ff; color: #6366f1; font-size: 0.75rem; border: 1px solid #e0e7ff; border-radius: 6px; font-weight: 600;">
                                    View Lead
                                </a>
                            <?php else: ?>
                                <a href="leads.php?add_mobile=<?php echo $row['mobile']; ?>" class="btn" style="width: auto; padding: 0.4rem 0.75rem; background: #fff; color: var(--primary); font-size: 0.75rem; border: 1px solid var(--primary); border-radius: 6px; font-weight: 600;">
                                    <i class="fas fa-plus" style="font-size: 0.7rem;"></i> Add Lead
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <div style="margin-bottom: 0.5rem; font-size: 1.5rem; color: #cbd5e1;"><i class="fas fa-search"></i></div>
                            No call logs found matching your criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="<?php echo get_url_param(['page' => $page - 1]); ?>" class="page-link">&laquo; Prev</a>
    <?php endif; ?>

    <?php
    $range = 2;
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
            $active = $i == $page ? 'active' : '';
            echo "<a href='" . get_url_param(['page' => $i]) . "' class='page-link $active'>$i</a>";
        } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
            echo "<span class='page-link' style='border:none; background:none;'>...</span>";
        }
    }
    ?>

    <?php if ($page < $total_pages): ?>
        <a href="<?php echo get_url_param(['page' => $page + 1]); ?>" class="page-link">Next &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<br><br>
<?php include 'includes/footer.php'; ?>
