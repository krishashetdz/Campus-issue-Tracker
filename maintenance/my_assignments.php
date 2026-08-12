<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('maintenance');
$u = currentUser();

$statusFilter   = $_GET['status']   ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$search         = trim($_GET['search'] ?? '');

$where = ["i.assigned_to = ?"];
$params = [$u['id']];
if ($statusFilter)   { $where[] = "i.status=?";   $params[] = $statusFilter; }
if ($priorityFilter) { $where[] = "i.priority=?"; $params[] = $priorityFilter; }
if ($search)         { $where[] = "(i.title LIKE ? OR i.location LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql = "SELECT i.*,c.category_name,u.full_name AS reporter FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id LEFT JOIN users u ON i.reported_by=u.user_id WHERE ".implode(' AND ',$where)." ORDER BY FIELD(i.priority,'critical','high','medium','low'),i.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $issues=$stmt->fetchAll();

$pageTitle='My Assignments'; $pageSubtitle='Issues assigned to you';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Assignments – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">
      <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Search..." value="<?= htmlspecialchars($search) ?>" style="flex:1;min-width:200px;">
        <select name="status">
          <option value="">All Status</option>
          <?php foreach(['in_progress','resolved','closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="priority">
          <option value="">All Priority</option>
          <?php foreach(['low','medium','high','critical'] as $p): ?>
            <option value="<?= $p ?>" <?= $priorityFilter===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-glow" style="padding:8px 18px;">Filter</button>
        <a href="my_assignments.php" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;align-self:center;">Clear</a>
      </form>

      <div class="panel">
        <div class="panel-header"><span><i class="bi bi-wrench me-2"></i>Assigned Issues (<?= count($issues) ?>)</span></div>
        <?php if(empty($issues)): ?>
          <div class="empty-state"><i class="bi bi-inbox"></i><h5>No assignments</h5><p>Issues assigned to you will appear here.</p></div>
        <?php else: ?>
          <table class="table-dark-custom">
            <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Location</th><th>Reporter</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($issues as $iss): ?>
            <tr>
              <td><code style="color:var(--text-muted);font-size:0.78rem;">#<?= $iss['issue_id'] ?></code></td>
              <td style="max-width:140px;font-size:0.85rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($iss['title']) ?></td>
              <td style="font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars($iss['category_name']??'N/A') ?></td>
              <td style="font-size:0.78rem;color:var(--text-muted);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($iss['location']) ?></td>
              <td style="font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars($iss['reporter']) ?></td>
              <td><?= getPriorityBadge($iss['priority']) ?></td>
              <td><?= getStatusBadge($iss['status']) ?></td>
              <td style="font-size:0.75rem;color:var(--text-muted);"><?= timeAgo($iss['updated_at']??$iss['created_at']) ?></td>
              <td>
                <a href="update_issue.php?id=<?= $iss['issue_id'] ?>" class="btn-sm-icon success" title="Update Status"><i class="bi bi-pencil-square"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
