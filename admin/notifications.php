<?php
// Admin notifications - reuse reporter notifications page with admin session
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('admin');

if (isset($_GET['mark_all'])) { markAllRead($pdo, $_SESSION['user_id']); header('Location: notifications.php'); exit(); }
if (isset($_GET['read'])) {
    $nid = intval($_GET['read']);
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?")->execute([$nid,$_SESSION['user_id']]);
    $n = $pdo->prepare("SELECT issue_id FROM notifications WHERE notification_id=?"); $n->execute([$nid]); $row=$n->fetch();
    if ($row && $row['issue_id']) { header("Location: view_issue.php?id={$row['issue_id']}"); } else { header("Location: notifications.php"); }
    exit();
}
$allNotifs = $pdo->prepare("SELECT n.*,i.title as issue_title FROM notifications n LEFT JOIN issues i ON n.issue_id=i.issue_id WHERE n.user_id=? ORDER BY n.created_at DESC");
$allNotifs->execute([$_SESSION['user_id']]); $allNotifs=$allNotifs->fetchAll();
$u = currentUser(); $pageTitle='Notifications'; $pageSubtitle='Admin alerts and updates';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Notifications – Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">
      <div class="panel">
        <div class="panel-header"><span><i class="bi bi-bell me-2"></i>All Notifications</span>
          <a href="?mark_all=1" class="btn-glow" style="padding:6px 14px;font-size:0.8rem;text-decoration:none;border-radius:8px;">Mark all read</a></div>
        <?php if(empty($allNotifs)): ?>
          <div class="empty-state"><i class="bi bi-bell-slash"></i><h5>No notifications</h5><p>All caught up!</p></div>
        <?php else: foreach($allNotifs as $n): $iconMap=['info'=>'bi-info-circle','success'=>'bi-check-circle','warning'=>'bi-bell','danger'=>'bi-exclamation-circle']; $icon=$iconMap[$n['notif_type']]??'bi-bell'; ?>
          <a href="?read=<?= $n['notification_id'] ?>" class="notif-item <?= $n['is_read']?'':'unread' ?>" style="border-radius:0;display:flex;padding:16px 20px;border-bottom:1px solid rgba(255,255,255,0.04);">
            <div class="notif-icon <?= htmlspecialchars($n['notif_type']) ?>" style="width:40px;height:40px;border-radius:10px;"><i class="bi <?= $icon ?>"></i></div>
            <div style="flex:1;margin-left:14px;">
              <div style="font-size:0.88rem;font-weight:<?= $n['is_read']?'400':'600' ?>;color:<?= $n['is_read']?'var(--text-muted)':'var(--text-primary)' ?>;"><?= htmlspecialchars($n['message']) ?></div>
              <?php if($n['issue_title']): ?><div style="font-size:0.75rem;color:var(--text-primary);margin-top:3px;"><i class="bi bi-link-45deg"></i> <?= htmlspecialchars($n['issue_title']) ?></div><?php endif; ?>
              <div style="font-size:0.73rem;color:var(--text-muted);margin-top:4px;"><?= date('d M Y, h:i A',strtotime($n['created_at'])) ?></div>
            </div>
            <?php if(!$n['is_read']): ?><div style="width:8px;height:8px;background:var(--primary);border-radius:50%;flex-shrink:0;margin-top:6px;"></div><?php endif; ?>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
