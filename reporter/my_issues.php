<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole(['student','staff']);
$u=currentUser();
$sf=trim($_GET['status']??'');$pf=trim($_GET['priority']??'');$search=trim($_GET['search']??'');
$where=["i.reported_by=?"];$params=[$u['id']];
if($sf){$where[]="i.status=?";$params[]=$sf;}
if($pf){$where[]="i.priority=?";$params[]=$pf;}
if($search){$where[]="(i.title LIKE ? OR i.location LIKE ?)";$params[]="%$search%";$params[]="%$search%";}
$sql="SELECT i.*,c.category_name FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id WHERE ".implode(' AND ',$where)." ORDER BY i.created_at DESC";
$stmt=$pdo->prepare($sql);$stmt->execute($params);$issues=$stmt->fetchAll();
$pageTitle='My Issues';$pageSubtitle='Track all your submitted issues';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Issues – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php';?>
  <div class="main-content">
    <?php include '../includes/topbar.php';?>
    <div class="page-content">
      <form method="GET" class="filter-bar">
        <i class="bi bi-search" style="color:var(--text-dim);"></i>
        <input type="text" name="search" placeholder="Search title or location..." value="<?=htmlspecialchars($search)?>" style="flex:1;min-width:150px;">
        <select name="status">
          <option value="">All Status</option>
          <?php foreach(['pending','in_progress','resolved','closed','rejected'] as $s):?><option value="<?=$s?>"<?=$sf===$s?' selected':''?>><?=ucwords(str_replace('_',' ',$s))?></option><?php endforeach;?>
        </select>
        <select name="priority">
          <option value="">All Priority</option>
          <?php foreach(['low','medium','high','critical'] as $p):?><option value="<?=$p?>"<?=$pf===$p?' selected':''?>><?=ucfirst($p)?></option><?php endforeach;?>
        </select>
        <button type="submit" class="btn btn-secondary" style="padding:5px 12px;">Filter</button>
        <a href="my_issues.php" style="font-size:.75rem;color:var(--text-dim);align-self:center;">Clear</a>
        <a href="report_issue.php" class="btn btn-primary" style="margin-left:auto;"><i class="bi bi-plus me-1"></i>New Issue</a>
      </form>
      <div class="panel">
        <div class="panel-header"><span><i class="bi bi-list-ul me-2"></i>My Issues <span class="issue-id" style="margin-left:4px;">(<?=count($issues)?>)</span></span></div>
        <?php if(empty($issues)):?>
          <div class="empty-state"><i class="bi bi-inbox"></i><h5>No issues found</h5><p><?=$search||$sf||$pf?'Try adjusting your filters.':'You haven\'t reported any issues yet.'?></p><?php if(!$search&&!$sf&&!$pf):?><a href="report_issue.php" class="btn btn-primary" style="margin-top:12px;display:inline-flex;"><i class="bi bi-plus me-1"></i>Report Your First Issue</a><?php endif;?></div>
        <?php else:?>
          <table class="table-dark-custom">
            <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Location</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach($issues as $iss):?>
            <tr>
              <td><span class="issue-id">#<?=$iss['issue_id']?></span></td>
              <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;"><?=htmlspecialchars($iss['title'])?></td>
              <td class="text-muted"><?=htmlspecialchars($iss['category_name']??'—')?></td>
              <td class="text-muted" style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($iss['location']??'—')?></td>
              <td><?=getPriorityBadge($iss['priority'])?></td>
              <td><?=getStatusBadge($iss['status'])?></td>
              <td><span class="issue-id"><?=date('d M Y',strtotime($iss['created_at']))?></span></td>
              <td><a href="view_issue.php?id=<?=$iss['issue_id']?>" class="btn-sm-icon" title="View"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach;?>
            </tbody>
          </table>
        <?php endif;?>
      </div>
    </div>
  </div>
</div>
</body></html>
