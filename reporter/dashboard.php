<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole(['student','staff']);
$u=currentUser();
$stT=$pdo->prepare("SELECT COUNT(*) FROM issues WHERE reported_by=?");$stT->execute([$u['id']]);$total=$stT->fetchColumn();
$stP=$pdo->prepare("SELECT COUNT(*) FROM issues WHERE reported_by=? AND status='pending'");$stP->execute([$u['id']]);$pending=$stP->fetchColumn();
$stI=$pdo->prepare("SELECT COUNT(*) FROM issues WHERE reported_by=? AND status='in_progress'");$stI->execute([$u['id']]);$progress=$stI->fetchColumn();
$stR=$pdo->prepare("SELECT COUNT(*) FROM issues WHERE reported_by=? AND status='resolved'");$stR->execute([$u['id']]);$resolved=$stR->fetchColumn();
$rs=$pdo->prepare("SELECT i.*,c.category_name FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id WHERE i.reported_by=? ORDER BY i.created_at DESC LIMIT 5");
$rs->execute([$u['id']]);$recentIssues=$rs->fetchAll();
$unread=getUnreadCount($pdo,$u['id']);
$pageTitle='Dashboard';$pageSubtitle='Welcome back, '.explode(' ',$u['name'])[0];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard – FixMyCampus</title>
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
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-card-list"></i></div><div class="stat-value"><?=$total?></div><div class="stat-label">Total Reported</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--amber);border-color:rgba(245,158,11,.2);background:rgba(245,158,11,.06)"><i class="bi bi-clock"></i></div><div class="stat-value"><?=$pending?></div><div class="stat-label">Pending Review</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--blue);border-color:rgba(59,130,246,.2);background:rgba(59,130,246,.06)"><i class="bi bi-arrow-repeat"></i></div><div class="stat-value"><?=$progress?></div><div class="stat-label">In Progress</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--emerald);border-color:rgba(16,185,129,.2);background:rgba(16,185,129,.06)"><i class="bi bi-check-circle"></i></div><div class="stat-value"><?=$resolved?></div><div class="stat-label">Resolved</div></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 240px;gap:14px;">
        <div class="panel">
          <div class="panel-header"><span><i class="bi bi-journal-text me-2"></i>Recent Issues</span><a href="my_issues.php">View all</a></div>
          <?php if(empty($recentIssues)):?>
            <div class="empty-state"><i class="bi bi-inbox"></i><h5>No issues reported yet</h5><p>Click "Report Issue" to submit your first campus issue.</p><a href="report_issue.php" class="btn btn-primary" style="margin-top:12px;display:inline-flex;">Report Issue</a></div>
          <?php else:?>
            <table class="table-dark-custom">
              <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr></thead>
              <tbody>
              <?php foreach($recentIssues as $iss):?>
              <tr>
                <td><span class="issue-id">#<?=$iss['issue_id']?></span></td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;" title="<?=htmlspecialchars($iss['title'])?>"><?=htmlspecialchars($iss['title'])?></td>
                <td class="text-muted"><?=htmlspecialchars($iss['category_name']??'—')?></td>
                <td><?=getPriorityBadge($iss['priority'])?></td>
                <td><?=getStatusBadge($iss['status'])?></td>
                <td><span class="issue-id"><?=date('d M',strtotime($iss['created_at']))?></span></td>
                <td><a href="view_issue.php?id=<?=$iss['issue_id']?>" class="btn-sm-icon"><i class="bi bi-eye"></i></a></td>
              </tr>
              <?php endforeach;?>
              </tbody>
            </table>
          <?php endif;?>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="panel-burg">
            <div class="panel-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
            <div class="panel-body" style="padding:12px 14px;">
              <a href="report_issue.php" class="quick-action"><i class="bi bi-plus-square"></i>Report New Issue</a>
              <a href="my_issues.php" class="quick-action"><i class="bi bi-list-ul"></i>Track My Issues</a>
              <a href="notifications.php" class="quick-action"><i class="bi bi-bell"></i>Notifications<?php if($unread>0):?><span class="menu-badge" style="margin-left:auto;"><?=$unread?></span><?php endif;?></a>
            </div>
          </div>
          <div class="panel-burg">
            <div class="panel-header"><i class="bi bi-info-circle me-2"></i>Tips</div>
            <div class="panel-body" style="padding:12px 14px;">
              <div style="font-size:.75rem;color:var(--peach-dim);line-height:2.2;">
                <div><i class="bi bi-image me-1" style="color:var(--peach2)"></i>Add photos for faster review</div>
                <div><i class="bi bi-geo-alt me-1" style="color:var(--peach2)"></i>Be specific about location</div>
                <div><i class="bi bi-exclamation-triangle me-1" style="color:var(--peach2)"></i>Mark Critical for safety hazards</div>
                <div><i class="bi bi-bell me-1" style="color:var(--peach2)"></i>Check notifications for updates</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body></html>
