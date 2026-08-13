<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('admin');

$u = currentUser();

foreach (['pending','in_progress','resolved','closed','rejected'] as $s) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE status=? AND parent_id IS NULL");
    $st->execute([$s]);
    $stats[$s] = $st->fetchColumn();
}

$stats['total'] = $pdo->query("SELECT COUNT(*) FROM issues WHERE parent_id IS NULL")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('student','staff')")->fetchColumn();
$criticalOpen = $pdo->query("SELECT COUNT(*) FROM issues WHERE priority='critical' AND status NOT IN ('resolved','closed') AND parent_id IS NULL")->fetchColumn();

$recent = $pdo->query("SELECT i.*,c.category_name,u.full_name AS reporter FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id LEFT JOIN users u ON i.reported_by=u.user_id WHERE i.parent_id IS NULL ORDER BY i.created_at DESC LIMIT 7")->fetchAll();

$catStats = $pdo->query("SELECT c.category_name, COUNT(i.issue_id) as cnt FROM categories c LEFT JOIN issues i ON i.category_id=c.category_id AND i.parent_id IS NULL GROUP BY c.category_id, c.category_name ORDER BY cnt DESC LIMIT 6")->fetchAll();

$hotspotStats = $pdo->query("SELECT location, COUNT(*) as cnt FROM issues WHERE status NOT IN ('resolved','closed','rejected') AND parent_id IS NULL GROUP BY location ORDER BY cnt DESC LIMIT 5")->fetchAll();
$totalHotspotCnt = array_sum(array_column($hotspotStats, 'cnt')) ?: 1;

$pageTitle = 'Dashboard';
$pageSubtitle = 'System overview';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php';?>
  <div class="main-content">
    <?php include '../includes/topbar.php';?>
    <div class="page-content">
      <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-card-list"></i></div><div class="stat-value"><?=$stats['total']?></div><div class="stat-label">Total Issues</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--amber);border-color:rgba(245,158,11,.2);background:rgba(245,158,11,.06)"><i class="bi bi-clock"></i></div><div class="stat-value"><?=$stats['pending']?></div><div class="stat-label">Pending</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--blue);border-color:rgba(59,130,246,.2);background:rgba(59,130,246,.06)"><i class="bi bi-tools"></i></div><div class="stat-value"><?=$stats['in_progress']?></div><div class="stat-label">In Progress</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--emerald);border-color:rgba(16,185,129,.2);background:rgba(16,185,129,.06)"><i class="bi bi-check-circle"></i></div><div class="stat-value"><?=$stats['resolved']?></div><div class="stat-label">Resolved</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:var(--rose);border-color:rgba(244,63,94,.2);background:rgba(244,63,94,.06)"><i class="bi bi-lightning"></i></div><div class="stat-value"><?=$criticalOpen?></div><div class="stat-label">Critical Open</div></div>
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-people"></i></div><div class="stat-value"><?=$totalUsers?></div><div class="stat-label">Registered Users</div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 260px;gap:14px;">
        <div class="panel">
          <div class="panel-header"><span><i class="bi bi-journal-text me-2"></i>Recent Issues</span><a href="issues.php">View all</a></div>
          <table class="table-dark-custom">
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Reporter</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($recent as $r): ?>
            <tr>
              <td><span class="issue-id">#<?=$r['issue_id']?></span></td>
              <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">
                <?=htmlspecialchars($r['title'])?>
                <?php if(!empty($r['affected_count']) && $r['affected_count'] > 1): ?>
                  <span class="inline-flex items-center gap-1 font-mono text-[10px] bg-amber-500/10 text-amber-600 border border-amber-500/20 px-1.5 py-0.5 rounded font-semibold ml-1">
                    👥 <?=$r['affected_count']?> Affected
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?=htmlspecialchars($r['reporter'] ?? 'Unknown')?></td>
              <td class="text-muted"><?=htmlspecialchars($r['category_name'] ?? '—')?></td>
              <td><?=getPriorityBadge($r['priority'])?></td>
              <td><?=getStatusBadge($r['status'])?></td>
              <td><a href="view_issue.php?id=<?=$r['issue_id']?>" class="btn-sm-icon"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
          <!-- By Category Card -->
          <div class="panel-burg">
            <div class="panel-header"><span><i class="bi bi-pie-chart me-2"></i>By Category</span></div>
            <div class="panel-body" style="padding:12px 14px;">
              <?php 
              $cntArr = array_column($catStats, 'cnt');
              $maxCat = (!empty($cntArr) && max($cntArr) > 0) ? max($cntArr) : 1;
              foreach($catStats as $cs):
                $pct = round(($cs['cnt'] / $maxCat) * 100);
              ?>
              <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                  <span style="font-size:.75rem;font-weight:500;color:var(--peach);"><?=htmlspecialchars($cs['category_name'])?></span>
                  <span style="font-size:.72rem;color:var(--peach-dim);"><?=$cs['cnt']?></span>
                </div>
                <div class="mini-bar-track"><div class="mini-bar-fill" style="width:<?=$pct?>%"></div></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Location Hotspot Analytics Card -->
          <div class="panel-burg">
            <div class="panel-header"><span><i class="bi bi-geo-alt me-2"></i>Top Issue Hotspots</span></div>
            <div class="panel-body" style="padding:12px 14px;">
              <?php if(empty($hotspotStats)): ?>
                <div style="font-size:0.75rem;color:var(--peach-dim);">No active hotspots found.</div>
              <?php else: ?>
                <?php foreach($hotspotStats as $hs): 
                  $hsPct = round(($hs['cnt'] / $totalHotspotCnt) * 100);
                ?>
                <div style="margin-bottom:10px;">
                  <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                    <span style="font-size:.75rem;font-weight:600;color:var(--peach);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;"><?=htmlspecialchars(ucwords($hs['location']))?></span>
                    <span style="font-size:.72rem;color:var(--peach-dim);"><?=$hs['cnt']?> issues (<?=$hsPct?>%)</span>
                  </div>
                  <div class="mini-bar-track"><div class="mini-bar-fill" style="width:<?=$hsPct?>%;background:#f59e0b;"></div></div>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Quick Actions Card -->
          <div class="panel-burg">
            <div class="panel-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
            <div class="panel-body" style="padding:12px 14px;">
              <a href="issues.php?status=pending" class="quick-action"><i class="bi bi-clock"></i>Review Pending</a>
              <a href="issues.php?priority=critical" class="quick-action"><i class="bi bi-exclamation-triangle"></i>Critical Issues</a>
              <a href="users.php" class="quick-action"><i class="bi bi-people"></i>Manage Users</a>
              <a href="reports.php" class="quick-action"><i class="bi bi-bar-chart-line"></i>View Reports</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
