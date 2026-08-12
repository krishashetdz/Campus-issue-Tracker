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
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">
      <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Search..." value="<?= htmlspecialchars($search) ?>" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" style="flex:1;min-width:200px;">
        <select name="status" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700">
          <option value="">All Status</option>
          <?php foreach(['in_progress','resolved','closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="priority" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700">
          <option value="">All Priority</option>
          <?php foreach(['low','medium','high','critical'] as $p): ?>
            <option value="<?= $p ?>" <?= $priorityFilter===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-zinc-800 text-zinc-300 border border-zinc-700/60 hover:bg-zinc-700/80 hover:text-zinc-100 font-sans text-xs font-semibold px-3 py-1.5 rounded-md transition-colors shadow-none focus:outline-none focus:ring-1 focus:ring-zinc-600">Filter</button>
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
