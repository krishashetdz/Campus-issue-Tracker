<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('maintenance');
$u=currentUser();
$stA=$pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to=?");$stA->execute([$u['id']]);$assigned=$stA->fetchColumn();
$stP=$pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to=? AND status='in_progress'");$stP->execute([$u['id']]);$inprog=$stP->fetchColumn();
$stD=$pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to=? AND status='resolved'");$stD->execute([$u['id']]);$done=$stD->fetchColumn();
$myIssues=$pdo->prepare("SELECT i.*,c.category_name,u.full_name AS reporter FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id LEFT JOIN users u ON i.reported_by=u.user_id WHERE i.assigned_to=? ORDER BY FIELD(i.priority,'critical','high','medium','low'),i.created_at DESC LIMIT 8");
$myIssues->execute([$u['id']]);$assignments=$myIssues->fetchAll();
$unread=getUnreadCount($pdo,$u['id']);
$pageTitle='Dashboard';$pageSubtitle='Your active assignments';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Maintenance Dashboard – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php';?>
  <div class="main-content">
    <?php include '../includes/topbar.php';?>
    <div class="page-content">
      <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-clipboard-check"></i></div><div class="stat-value"><?=$assigned?></div><div class="stat-label">Total Assigned</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--blue);border-color:rgba(59,130,246,.2);background:rgba(59,130,246,.06)"><i class="bi bi-tools"></i></div><div class="stat-value"><?=$inprog?></div><div class="stat-label">In Progress</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--emerald);border-color:rgba(16,185,129,.2);background:rgba(16,185,129,.06)"><i class="bi bi-check-circle"></i></div><div class="stat-value"><?=$done?></div><div class="stat-label">Resolved</div></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 220px;gap:14px;">
        <div class="panel">
          <div class="panel-header"><span><i class="bi bi-wrench me-2"></i>My Assignments</span><a href="my_assignments.php">View all</a></div>
          <?php if(empty($assignments)):?>
            <div class="empty-state"><i class="bi bi-inbox"></i><h5>No assignments yet</h5><p>You have no issues assigned. Check back later.</p></div>
          <?php else:?>
            <table class="table-dark-custom">
              <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Location</th><th>Priority</th><th>Status</th><th></th></tr></thead>
              <tbody>
              <?php foreach($assignments as $iss):?>
              <tr>
                <td><span class="issue-id">#<?=$iss['issue_id']?></span></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;"><?=htmlspecialchars($iss['title'])?></td>
                <td class="text-muted"><?=htmlspecialchars($iss['category_name']??'—')?></td>
                <td class="text-muted" style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($iss['location']??'—')?></td>
                <td><?=getPriorityBadge($iss['priority'])?></td>
                <td><?=getStatusBadge($iss['status'])?></td>
                <td><a href="update_issue.php?id=<?=$iss['issue_id']?>" class="btn-sm-icon" title="Update"><i class="bi bi-pencil"></i></a></td>
              </tr>
              <?php endforeach;?>
              </tbody>
            </table>
          <?php endif;?>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="panel">
            <div class="panel-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
            <div class="panel-body" style="padding:12px 14px;">
              <a href="my_assignments.php" class="quick-action"><i class="bi bi-clipboard-check"></i>All Assignments</a>
              <a href="my_assignments.php?status=in_progress" class="quick-action"><i class="bi bi-tools"></i>In Progress</a>
              <a href="notifications.php" class="quick-action"><i class="bi bi-bell"></i>Notifications<?php if($unread>0):?><span class="menu-badge" style="margin-left:auto;"><?=$unread?></span><?php endif;?></a>
            </div>
          </div>
          <div class="panel">
            <div class="panel-header"><i class="bi bi-person me-2"></i>Profile</div>
            <div class="panel-body" style="padding:12px 14px;text-align:center;">
              <div style="width:44px;height:44px;border-radius:50%;background:var(--bg-raised);border:1px solid var(--border-sub);display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:600;color:var(--text);margin:0 auto 8px;"><?=strtoupper(substr($u['name'],0,1))?></div>
              <div style="font-size:.82rem;font-weight:500;color:var(--text);"><?=htmlspecialchars($u['name'])?></div>
              <div style="font-size:.72rem;color:var(--text-dim);text-transform:capitalize;margin-top:2px;"><?=$u['role']?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body></html>
