<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$executive_id = $_SESSION['user_id'];
$org_id = getOrgId();

// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if ($is_default) {
            mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id AND organization_id = $org_id");
        }

        $sql = "INSERT INTO whatsapp_messages (organization_id, executive_id, title, message, is_default) VALUES ($org_id, $executive_id, '$title', '$message', $is_default)";
        mysqli_query($conn, $sql);
        echo "<script>window.location.href='messages.php';</script>";
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM whatsapp_messages WHERE id = $id AND executive_id = $executive_id AND organization_id = $org_id");
        echo "<script>window.location.href='messages.php';</script>";
    } elseif ($_POST['action'] === 'set_default') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id AND organization_id = $org_id");
        mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 1 WHERE id = $id AND executive_id = $executive_id AND organization_id = $org_id");
        echo "<script>window.location.href='messages.php';</script>";
    }
}

// Fetch Messages
$sql = "SELECT * FROM whatsapp_messages WHERE executive_id = $executive_id AND organization_id = $org_id ORDER BY is_default DESC, id DESC";
$result = mysqli_query($conn, $sql);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div>
        <h2 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">WhatsApp Templates</h2>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
        <i class="fas fa-plus"></i> New Template
    </button>
</div>

<div class="message-grid">
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="message-card <?php echo $row['is_default'] ? 'default' : ''; ?>">
            <div class="message-header">
                <div class="message-title">
                    <?php echo htmlspecialchars($row['title']); ?>
                    <?php if ($row['is_default']): ?>
                        <span class="default-badge">DEFAULT</span>
                    <?php endif; ?>
                </div>
                <div class="message-actions">
                    <?php if (!$row['is_default']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="set_default">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="action-btn" title="Set as Default">
                                <i class="far fa-star"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="action-btn delete" title="Delete">
                            <i class="far fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="message-content">
                <?php echo nl2br(htmlspecialchars($row['message'])); ?>
            </div>
            <div class="message-footer">
                <i class="far fa-clock"></i> Created on <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
            </div>
        </div>
    <?php endwhile; ?>
    
    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="empty-state">
            <i class="fab fa-whatsapp"></i>
            <h3>No Templates Found</h3>
            <p>Add your first WhatsApp message template to start saving time!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <div class="modal-header">
            <h3>New Message Template</h3>
            <button class="close-btn" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Template Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Welcome Message" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Message Content</label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Type your WhatsApp message here..." required></textarea>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="is_default" id="is_default">
                    <label for="is_default" style="font-size: 0.875rem; font-weight: 500; color: var(--text-main);">Set as Default Template</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: #f1f5f9; color: var(--text-main);" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Template</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}
.page-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-main);
}
.page-subtitle {
    font-size: 0.875rem;
    color: var(--text-muted);
}
.message-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}
.message-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-sm);
}
.message-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow);
}
.message-card.default {
    border-color: var(--primary);
    background: #f5f7ff;
}
.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.message-title {
    font-weight: 700;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.default-badge {
    background: var(--primary);
    color: white;
    font-size: 0.625rem;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-weight: 800;
}
.message-actions {
    display: flex;
    gap: 0.5rem;
}
.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
}
.action-btn:hover {
    color: var(--primary);
    border-color: var(--primary);
    background: #f5f7ff;
}
.action-btn.delete:hover {
    color: var(--danger);
    border-color: var(--danger);
    background: #fff5f5;
}
.message-content {
    font-size: 0.875rem;
    color: var(--text-main);
    line-height: 1.6;
    flex: 1;
    margin-bottom: 1.25rem;
}
.message-footer {
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
    border: 2px dashed var(--border);
}
.empty-state i {
    font-size: 3rem;
    color: #25d366;
    margin-bottom: 1rem;
}
.empty-state h3 {
    font-weight: 700;
    color: var(--text-main);
}
.empty-state p {
    color: var(--text-muted);
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}
.modal-card {
    background: white;
    width: 100%;
    max-width: 500px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}
.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    font-weight: 800;
    color: var(--text-main);
}
.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--text-muted);
    cursor: pointer;
}
.modal-body {
    padding: 1.5rem;
}
.modal-footer {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>
