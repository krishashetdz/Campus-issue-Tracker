<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('maintenance');
$u = currentUser();

$stmtTotal    = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to=?"); $stmtTotal->execute([$u['id']]);
$stmtActive   = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to=? AND status='in_progress'"); $stmtActive->execute([$u['id']]);
$stmtResolved = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to=? AND status='resolved'"); $stmtResolved->execute([$u['id']]);
$stmtCritical = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to=? AND priority='critical' AND status NOT IN ('resolved','closed')"); $stmtCritical->execute([$u['id']]);

$total=$stmtTotal->fetchColumn(); $active=$stmtActive->fetchColumn();
$resolved=$stmtResolved->fetchColumn(); $critical=$stmtCritical->fetchColumn();

$recent = $pdo->prepare("SELECT i.*,c.category_name,u.full_name AS reporter FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id LEFT JOIN users u ON i.reported_by=u.user_id WHERE i.assigned_to=? ORDER BY FIELD(i.priority,'critical','high','medium','low'),i.created_at DESC LIMIT 6");
$recent->execute([$u['id']]); $recent=$recent->fetchAll();

$pageTitle='Maintenance Dashboard'; $pageSubtitle='My work assignments';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Maintenance Dashboard – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px;">
        <div class="stat-card primary"><div class="stat-icon"><i class="bi bi-card-list"></i></div><div class="stat-value"><?= $total ?></div><div class="stat-label">Total Assigned</div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="bi bi-tools"></i></div><div class="stat-value"><?= $active ?></div><div class="stat-label">Active Tasks</div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div class="stat-value"><?= $resolved ?></div><div class="stat-label">Resolved</div></div>
        <div class="stat-card danger"><div class="stat-icon"><i class="bi bi-lightning"></i></div><div class="stat-value"><?= $critical ?></div><div class="stat-label">Critical Pending</div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;">
        <div class="panel">
          <div class="panel-header"><span><i class="bi bi-wrench-adjustable me-2"></i>Recent Assignments</span>
            <a href="my_assignments.php" style="font-size:0.8rem;color:var(--text-muted);text-decoration:none;">View all →</a></div>
          <?php if(empty($recent)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i><h5>No assignments yet</h5><p>New tasks will appear here once assigned by admin.</p></div>
          <?php else: ?>
            <table class="table-dark-custom">
              <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Location</th><th>Priority</th><th>Status</th><th>Action</th></tr></thead>
              <tbody>
              <?php foreach($recent as $r): ?>
              <tr>
                <td><code style="color:var(--text-muted);font-size:0.78rem;">#<?= $r['issue_id'] ?></code></td>
                <td style="max-width:160px;font-size:0.85rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($r['title']) ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars($r['category_name']??'N/A') ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($r['location']) ?></td>
                <td><?= getPriorityBadge($r['priority']) ?></td>
                <td><?= getStatusBadge($r['status']) ?></td>
                <td><a href="update_issue.php?id=<?= $r['issue_id'] ?>" class="btn-sm-icon"><i class="bi bi-pencil-square"></i></a></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div class="panel">
            <div class="panel-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
            <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;">
              <a href="my_assignments.php?status=in_progress" style="display:flex;align-items:center;gap:10px;padding:12px;background:rgba(0,0,0,0.3);border:1px solid rgba(0,212,255,0.3);border-radius:8px;text-decoration:none;color:var(--text-primary);font-size:0.85rem;font-weight:500;transition:all .2s;" onmouseover="this.style.transform='translateX(3px)'" onmouseout="this.style.transform=''">
                <i class="bi bi-tools" style="color:#8b5cf6;"></i> Active Tasks (<?= $active ?>)
              </a>
              <a href="my_assignments.php?priority=critical" style="display:flex;align-items:center;gap:10px;padding:12px;background:rgba(0,0,0,0.3);border:1px solid rgba(0,212,255,0.3);border-radius:8px;text-decoration:none;color:var(--text-primary);font-size:0.85rem;font-weight:500;transition:all .2s;" onmouseover="this.style.transform='translateX(3px)'" onmouseout="this.style.transform=''">
                <i class="bi bi-lightning-charge" style="color:#ef4444;"></i> Critical Issues (<?= $critical ?>)
              </a>
              <a href="notifications.php" style="display:flex;align-items:center;gap:10px;padding:12px;background:rgba(0,0,0,0.3);border:1px solid rgba(0,212,255,0.3);border-radius:8px;text-decoration:none;color:var(--text-primary);font-size:0.85rem;font-weight:500;transition:all .2s;" onmouseover="this.style.transform='translateX(3px)'" onmouseout="this.style.transform=''">
                <i class="bi bi-bell" style="color:var(--text-muted);"></i> Notifications
              </a>
            </div>
          </div>
          <div class="panel">
            <div class="panel-header"><i class="bi bi-person me-2"></i>My Profile</div>
            <div class="panel-body">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <div class="user-avatar-sm" style="width:44px;height:44px;font-size:1rem;"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                <div><div style="font-weight:700;"><?= htmlspecialchars($u['name']) ?></div><div style="font-size:0.78rem;color:var(--text-muted);">Maintenance Staff</div></div>
              </div>
              <div style="font-size:0.82rem;color:var(--text-muted);">Department: <b style="color:var(--text-primary);"><?= htmlspecialchars($u['department']) ?></b></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
