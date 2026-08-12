<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('admin');
$sf=trim($_GET['status']??'');$pf=trim($_GET['priority']??'');$search=trim($_GET['search']??'');
$where=['1=1'];$params=[];
if($sf){$where[]="i.status=?";$params[]=$sf;}
if($pf){$where[]="i.priority=?";$params[]=$pf;}
if($search){$where[]="(i.title LIKE ? OR i.location LIKE ? OR u.full_name LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}
$sql="SELECT i.*,c.category_name,u.full_name AS reporter FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id LEFT JOIN users u ON i.reported_by=u.user_id WHERE ".implode(' AND ',$where)." ORDER BY FIELD(i.priority,'critical','high','medium','low'),i.created_at DESC";
$stmt=$pdo->prepare($sql);$stmt->execute($params);$issues=$stmt->fetchAll();
$pageTitle='All Issues';$pageSubtitle='Manage and assign campus issues';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Issues – FixMyCampus Admin</title>
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
      <form method="GET" class="filter-bar">
        <i class="bi bi-search" style="color:var(--text-dim);"></i>
        <input type="text" name="search" placeholder="Search title, location, reporter..." value="<?=htmlspecialchars($search)?>" class="bg-zinc-900 border border-zinc-800 text-zinc-200 placeholder-zinc-500 text-xs rounded-md px-3 py-2 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700" style="flex:1;min-width:180px;">
        <select name="status" class="bg-zinc-900 border border-zinc-800 text-zinc-200 placeholder-zinc-500 text-xs rounded-md px-3 py-2 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700">
          <option value="">All Status</option>
          <?php foreach(['pending','in_progress','resolved','closed','rejected'] as $s):?><option value="<?=$s?>"<?=$sf===$s?' selected':''?>><?=ucwords(str_replace('_',' ',$s))?></option><?php endforeach;?>
        </select>
        <select name="priority" class="bg-zinc-900 border border-zinc-800 text-zinc-200 placeholder-zinc-500 text-xs rounded-md px-3 py-2 focus:outline-none focus:border-zinc-700 focus:ring-1 focus:ring-zinc-700">
          <option value="">All Priority</option>
          <?php foreach(['low','medium','high','critical'] as $p):?><option value="<?=$p?>"<?=$pf===$p?' selected':''?>><?=ucfirst($p)?></option><?php endforeach;?>
        </select>
        <button type="submit" class="bg-zinc-800 text-zinc-300 border border-zinc-700/60 hover:bg-zinc-700/80 hover:text-zinc-100 font-sans text-xs font-semibold px-3 py-1.5 rounded-md transition-colors shadow-none focus:outline-none focus:ring-1 focus:ring-zinc-600">Filter</button>
        <a href="issues.php" style="font-size:.75rem;color:var(--text-dim);align-self:center;">Clear</a>
      </form>
      <div class="panel">
        <div class="panel-header"><span><i class="bi bi-list-ul me-2"></i>Issues <span class="issue-id" style="margin-left:4px;">(<?=count($issues)?>)</span></span></div>
        <?php if(empty($issues)):?>
          <div class="empty-state"><i class="bi bi-inbox"></i><h5>No issues found</h5><p>Try adjusting your filters.</p></div>
        <?php else:?>
          <table class="table-dark-custom">
            <thead><tr><th>#</th><th>Title</th><th>Reporter</th><th>Category</th><th>Location</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach($issues as $iss):?>
            <tr>
              <td><span class="issue-id">#<?=$iss['issue_id']?></span></td>
              <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;"><?=htmlspecialchars($iss['title'])?></td>
              <td class="text-muted" style="white-space:nowrap;"><?=htmlspecialchars($iss['reporter'])?></td>
              <td class="text-muted"><?=htmlspecialchars($iss['category_name']??'—')?></td>
              <td class="text-muted" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($iss['location'])?></td>
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
