<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole(['student','staff']);
$u = currentUser();

$statusFilter   = $_GET['status']   ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$search         = trim($_GET['search'] ?? '');

$where = ["i.reported_by = ?"];
$params = [$u['id']];

if ($statusFilter)   { $where[] = "i.status = ?";   $params[] = $statusFilter; }
if ($priorityFilter) { $where[] = "i.priority = ?"; $params[] = $priorityFilter; }
if ($search)         { $where[] = "(i.title LIKE ? OR i.location LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql = "SELECT i.*, c.category_name FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id WHERE " . implode(' AND ', $where) . " ORDER BY i.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$issues = $stmt->fetchAll();

$pageTitle = 'My Issues';
$pageSubtitle = 'All issues reported by you';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Issues – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">

      <!-- Filter Bar -->
      <form method="GET" class="filter-bar" style="margin-bottom:20px;">
        <input type="text" name="search" placeholder="🔍 Search title or location..." value="<?= htmlspecialchars($search) ?>" style="flex:1;min-width:200px;">
        <select name="status">
          <option value="">All Status</option>
          <?php foreach(['pending','in_progress','resolved','closed','rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="priority">
          <option value="">All Priority</option>
          <?php foreach(['low','medium','high','critical'] as $p): ?>
            <option value="<?= $p ?>" <?= $priorityFilter===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-glow" style="padding:8px 18px;">Apply</button>
        <a href="my_issues.php" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;align-self:center;">Clear</a>
      </form>

      <div class="panel">
        <div class="panel-header">
          <span><i class="bi bi-card-list me-2"></i>Issues (<?= count($issues) ?>)</span>
          <a href="report_issue.php" class="btn-glow" style="padding:7px 14px;font-size:0.82rem;text-decoration:none;border-radius:8px;">+ New Issue</a>
        </div>

        <?php if(empty($issues)): ?>
          <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No issues found</h5>
            <p><?= $search || $statusFilter || $priorityFilter ? 'Try adjusting the filters.' : 'You haven\'t reported any issues yet.' ?></p>
            <a href="report_issue.php" class="btn-glow" style="display:inline-block;margin-top:14px;text-decoration:none;">+ Report an Issue</a>
          </div>
        <?php else: ?>
          <table class="table-dark-custom">
            <thead>
              <tr>
                <th>#ID</th><th>Title</th><th>Category</th><th>Location</th>
                <th>Priority</th><th>Status</th><th>Submitted</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($issues as $iss): ?>
              <tr>
                <td><code style="color:var(--text-muted);font-size:0.8rem;">#<?= $iss['issue_id'] ?></code></td>
                <td>
                  <div style="font-weight:600;font-size:0.88rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($iss['title']) ?></div>
                </td>
                <td style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($iss['category_name'] ?? 'N/A') ?></td>
                <td style="font-size:0.8rem;color:var(--text-muted);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($iss['location']) ?></td>
                <td><?= getPriorityBadge($iss['priority']) ?></td>
                <td><?= getStatusBadge($iss['status']) ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;"><?= date('d M Y', strtotime($iss['created_at'])) ?></td>
                <td><a href="view_issue.php?id=<?= $iss['issue_id'] ?>" class="btn-sm-icon" title="View Details"><i class="bi bi-eye"></i></a></td>
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
