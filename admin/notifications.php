<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('admin');
$uid=$_SESSION['user_id'];
if(isset($_GET['mark_all'])){markAllRead($pdo,$uid);header('Location: notifications.php');exit();}
if(isset($_GET['read'])){
  $nid=intval($_GET['read']);
  $pdo->prepare("UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?")->execute([$nid,$uid]);
  $n=$pdo->prepare("SELECT issue_id FROM notifications WHERE notification_id=?");$n->execute([$nid]);$row=$n->fetch();
  if($row&&$row['issue_id']){header("Location: view_issue.php?id={$row['issue_id']}");}
  else{header('Location: notifications.php');}
  exit();
}
$allNotifs=$pdo->prepare("SELECT n.*,i.title as issue_title FROM notifications n LEFT JOIN issues i ON n.issue_id=i.issue_id WHERE n.user_id=? ORDER BY n.created_at DESC");
$allNotifs->execute([$uid]);$allNotifs=$allNotifs->fetchAll();
$unreadCount=array_reduce($allNotifs,fn($c,$n)=>$c+($n['is_read']?0:1),0);
$u=currentUser();$pageTitle='Notifications';$pageSubtitle='Admin alerts and updates';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Notifications – Admin – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php';?>
  <div class="main-content">
    <?php include '../includes/topbar.php';?>
    <div class="page-content">

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div>
          <div style="font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--burg2);margin-bottom:3px;">Admin Inbox</div>
          <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:var(--burg);letter-spacing:-.02em;">All Notifications</h2>
        </div>
        <?php if($unreadCount>0):?>
          <a href="?mark_all=1" class="notif-mark-btn" style="background:var(--burg);color:var(--peach);border:1px solid rgba(255,216,190,.25);">
            <i class="bi bi-check2-all"></i> Mark all read
            <span style="background:var(--peach2);color:var(--burg);border-radius:10px;padding:1px 7px;font-size:.65rem;font-weight:700;margin-left:4px;"><?=$unreadCount?></span>
          </a>
        <?php endif;?>
      </div>

      <div class="panel-burg" style="border-radius:24px;overflow:hidden;">
        <?php if(empty($allNotifs)):?>
          <div style="text-align:center;padding:56px 20px;">
            <i class="bi bi-bell-slash" style="font-size:2.2rem;color:var(--peach-dimmer);display:block;margin-bottom:14px;"></i>
            <div style="font-size:.95rem;font-weight:600;color:var(--peach);margin-bottom:6px;">All caught up!</div>
            <div style="font-size:.8rem;color:var(--peach-dim);">No notifications yet. New issue alerts will appear here.</div>
          </div>
        <?php else:?>
          <?php
          $iconMap=['info'=>'bi-info-circle','success'=>'bi-check-circle','warning'=>'bi-exclamation-triangle','danger'=>'bi-exclamation-circle'];
          foreach($allNotifs as $n):
            $icon=$iconMap[$n['notif_type']]??'bi-bell';
          ?>
          <a href="?read=<?=$n['notification_id']?>" class="notif-page-item<?=$n['is_read']?'':' unread'?>">
            <div class="notif-page-icon"><i class="bi <?=$icon?>"></i></div>
            <div style="flex:1;min-width:0;">
              <div class="notif-page-msg"><?=htmlspecialchars($n['message'])?></div>
              <?php if($n['issue_title']):?>
                <span class="notif-page-link"><i class="bi bi-link-45deg" style="margin-right:3px;"></i><?=htmlspecialchars($n['issue_title'])?></span>
              <?php endif;?>
              <div class="notif-page-time"><i class="bi bi-clock" style="margin-right:4px;"></i><?=date('d M Y, h:i A',strtotime($n['created_at']))?></div>
            </div>
            <?php if(!$n['is_read']):?><div class="notif-unread-dot"></div><?php endif;?>
          </a>
          <?php endforeach;?>
        <?php endif;?>
      </div>

    </div>
  </div>
</div>
</body></html>
