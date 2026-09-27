<?php
/**
 * Announcements Management
 * Advanced Technological Institute of Tangalle - Attendance Management System
 */
 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Ensure all required tables exist
$db = getDB();
ensureAuthSchema($db);
ensureStudentsTable($db);

$pageTitle = 'Announcements';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            $title = sanitize($_POST['title']);
            $content = sanitize($_POST['content']);
            $publishDate = sanitize($_POST['publish_date']);
            $expiryDate = sanitize($_POST['expiry_date']) ?: null;
            $audience = sanitize($_POST['audience']);
            $priority = sanitize($_POST['priority']);
            $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
            
            $stmt = $db->prepare("INSERT INTO announcements (title, content, publish_date, expiry_date, audience, priority, is_pinned, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $content, $publishDate, $expiryDate, $audience, $priority, $isPinned, $_SESSION['user_id']]);
            
            logActivity($_SESSION['user_id'], 'Announcement Added', "Added: $title");
            setFlash('success', 'Announcement published successfully!');
            redirect('announcements.php');
            break;
            
        case 'update':
            $id = (int)$_POST['id'];
            $title = sanitize($_POST['title']);
            $content = sanitize($_POST['content']);
            $publishDate = sanitize($_POST['publish_date']);
            $expiryDate = sanitize($_POST['expiry_date']) ?: null;
            $audience = sanitize($_POST['audience']);
            $priority = sanitize($_POST['priority']);
            $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
            
            $stmt = $db->prepare("UPDATE announcements SET title = ?, content = ?, publish_date = ?, expiry_date = ?, audience = ?, priority = ?, is_pinned = ? WHERE id = ?");
            $stmt->execute([$title, $content, $publishDate, $expiryDate, $audience, $priority, $isPinned, $id]);
            
            logActivity($_SESSION['user_id'], 'Announcement Updated', "Updated: $title");
            setFlash('success', 'Announcement updated successfully!');
            redirect('announcements.php');
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($_SESSION['user_id'], 'Announcement Deleted', "Deleted announcement ID: $id");
            setFlash('success', 'Announcement deleted successfully!');
            redirect('announcements.php');
            break;
    }
}

// Get announcements
$announcements = $db->query("
    SELECT a.*, u.username 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.is_pinned DESC, a.publish_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0d6efd; --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background: #f5f6fa; }
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: #fff; z-index: 1000; }
        .sidebar-brand { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand h4 { color: #fff; font-weight: 700; margin: 0; }
        .sidebar-menu .nav-link { color: rgba(255,255,255,0.7); padding: 14px 20px; margin: 3px 10px; border-radius: 8px; transition: all 0.3s; }
        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar-menu .nav-link.active { background: var(--primary); }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .top-navbar { background: #fff; padding: 15px 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        .page-header { padding: 25px; background: #fff; margin: 20px 25px; border-radius: 12px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .announcement-card { border-left: 4px solid #0d6efd; transition: all 0.3s; }
        .announcement-card:hover { transform: translateX(5px); }
        .announcement-card.priority-high { border-left-color: #dc3545; }
        .announcement-card.priority-medium { border-left-color: #ffc107; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand"><h4><i class="fas fa-graduation-cap me-2"></i>ATIT</h4><small>Attendance System</small></div>
        <nav class="sidebar-menu mt-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="students.php"><i class="fas fa-user-graduate me-2"></i>Students</a></li>
                <li class="nav-item"><a class="nav-link" href="lecturers.php"><i class="fas fa-chalkboard-teacher me-2"></i>Lecturers</a></li>
                <li class="nav-item"><a class="nav-link" href="courses.php"><i class="fas fa-book me-2"></i>Courses</a></li>
                <li class="nav-item"><a class="nav-link" href="subjects.php"><i class="fas fa-book-open me-2"></i>Subjects</a></li>
                <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check me-2"></i>Attendance</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                <li class="nav-item"><a class="nav-link active" href="announcements.php"><i class="fas fa-bullhorn me-2"></i>Announcements</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </nav>
    </aside>
    
    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><?php echo $pageTitle; ?></h4></div>
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
        </nav>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mx-4 mt-4"><?php echo htmlspecialchars($flash['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div><h5 class="mb-1">Manage Announcements</h5><p class="text-muted mb-0"><?php echo count($announcements); ?> announcements</p></div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-2"></i>New Announcement</button>
            </div>
        </div>
        
        <div class="px-4 mb-4">
            <?php if (empty($announcements)): ?>
            <div class="card text-center py-5">
                <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
                <p class="text-muted">No announcements yet. Create your first one!</p>
            </div>
            <?php else: ?>
            <?php foreach ($announcements as $ann): ?>
            <div class="card announcement-card mb-3 <?php echo 'priority-' . $ann['priority']; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex gap-2 mb-2">
                                <?php if ($ann['is_pinned']): ?><span class="badge bg-primary"><i class="fas fa-thumbtack me-1"></i>Pinned</span><?php endif; ?>
                                <span class="badge bg-<?php echo $ann['priority'] === 'high' ? 'danger' : ($ann['priority'] === 'medium' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($ann['priority']); ?></span>
                                <span class="badge bg-info"><?php echo ucfirst($ann['audience']); ?></span>
                            </div>
                            <h5 class="card-title mb-2"><?php echo htmlspecialchars($ann['title']); ?></h5>
                            <p class="card-text text-muted"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i><?php echo formatDate($ann['publish_date']); ?>
                                <?php if ($ann['expiry_date']): ?> - Expires: <?php echo formatDate($ann['expiry_date']); ?><?php endif; ?>
                                | <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($ann['username']); ?>
                            </small>
                        </div>
                        <div class="btn-group btn-group-sm ms-3">
                            <button class="btn btn-outline-primary" onclick="editAnnouncement(<?php echo htmlspecialchars(json_encode($ann)); ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $ann['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>New Announcement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Title</label><input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label><textarea class="form-control" name="content" rows="5" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Publish Date</label><input type="date" class="form-control" name="publish_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label><input type="date" class="form-control" name="expiry_date">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Audience</label>
                                <select class="form-select" name="audience">
                                    <option value="all">All</option>
                                    <option value="students">Students</option>
                                    <option value="lecturers">Lecturers</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="is_pinned" id="isPinned"><label class="form-check-label" for="isPinned">Pin Announcement</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Publish</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Announcement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update"><input type="hidden" name="id" id="edit_id">
                        <div class="mb-3"><label class="form-label">Title</label><input type="text" class="form-control" name="title" id="edit_title" required></div>
                        <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" name="content" id="edit_content" rows="5" required></textarea></div>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label">Publish Date</label><input type="date" class="form-control" name="publish_date" id="edit_publish_date" required></div>
                            <div class="col-md-6"><label class="form-label">Expiry Date</label><input type="date" class="form-control" name="expiry_date" id="edit_expiry_date"></div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Audience</label>
                                <select class="form-select" name="audience" id="edit_audience">
                                    <option value="all">All</option><option value="students">Students</option><option value="lecturers">Lecturers</option><option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority" id="edit_priority">
                                    <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="is_pinned" id="edit_is_pinned"><label class="form-check-label" for="edit_is_pinned">Pin</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <form method="POST" id="deleteForm" style="display: none;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"></form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function editAnnouncement(ann) {
            document.getElementById('edit_id').value = ann.id;
            document.getElementById('edit_title').value = ann.title;
            document.getElementById('edit_content').value = ann.content;
            document.getElementById('edit_publish_date').value = ann.publish_date;
            document.getElementById('edit_expiry_date').value = ann.expiry_date || '';
            document.getElementById('edit_audience').value = ann.audience;
            document.getElementById('edit_priority').value = ann.priority;
            document.getElementById('edit_is_pinned').checked = ann.is_pinned == 1;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function confirmDelete(id) {
            Swal.fire({ title: 'Delete Announcement?', text: 'This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete' })
                .then(result => { if (result.isConfirmed) { document.getElementById('delete_id').value = id; document.getElementById('deleteForm').submit(); } });
        }
    </script>
</body>
</html>
