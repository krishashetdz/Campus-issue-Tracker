<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('admin');
$u=currentUser();
$msg=$err='';
$sf=trim($_GET['status']??'');$pf=trim($_GET['priority']??'');$search=trim($_GET['search']??'');
$where=['i.parent_id IS NULL'];$params=[];
if($sf){$where[]="i.status=?";$params[]=$sf;}
if($pf){$where[]="i.priority=?";$params[]=$pf;}
if($search){$where[]="(i.title LIKE ? OR i.location LIKE ? OR u.full_name LIKE ?)";$params[]="%$search%";$params[]="%$search%";$params[]="%$search%";}
$sql="SELECT i.*,c.category_name,u.full_name AS reporter FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id LEFT JOIN users u ON i.reported_by=u.user_id WHERE ".implode(' AND ',$where)." ORDER BY FIELD(i.priority,'critical','high','medium','low'),i.created_at DESC";

if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??'';
  if($action==='merge_selected'){
    $selected=$_POST['selected_issues']??[];
    if(is_array($selected)&&count($selected)>=2){
      $selected=array_map('intval',$selected);
      sort($selected);
      $parent_id=$selected[0];
      $child_ids=array_slice($selected,1);

      $pdo->prepare("UPDATE issues SET is_parent=1 WHERE issue_id=?")->execute([$parent_id]);
      foreach($child_ids as $cId){
        $pdo->prepare("UPDATE issues SET parent_id=? WHERE issue_id=?")->execute([$parent_id,$cId]);
        logStatusChange($pdo,$cId,$u['id'],'','',"Manually merged into Parent Incident #{$parent_id}");
        $cStmt=$pdo->prepare("SELECT reported_by FROM issues WHERE issue_id=?");
        $cStmt->execute([$cId]);
        $cRow=$cStmt->fetch();
        if($cRow){
          sendNotification($pdo,$cRow['reported_by'],$cId,"Your report #{$cId} was merged into Parent Incident #{$parent_id}.",'info');
        }
      }

      $pdo->prepare("UPDATE issues SET affected_count = (SELECT COUNT(*) + 1 FROM (SELECT issue_id FROM issues WHERE parent_id = ?) AS tmp) WHERE issue_id = ?")->execute([$parent_id,$parent_id]);
      logStatusChange($pdo,$parent_id,$u['id'],'','',"Merged ".count($child_ids)." duplicate report(s) into this incident.");
      $msg="Successfully merged ".count($child_ids)." issue(s) into Parent Incident #{$parent_id}.";
    } else {
      $err="Please select at least 2 issues to perform a merge.";
    }
  }
}

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
      <?php if($msg):?><div class="alert-banner alert-success"><i class="bi bi-check-circle me-2"></i><?=htmlspecialchars($msg)?></div><?php endif;?>
      <?php if($err):?><div class="alert-banner alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?=htmlspecialchars($err)?></div><?php endif;?>

      <form method="GET" class="filter-bar">
        <i class="bi bi-search" style="color:var(--text-dim);"></i>
        <input type="text" name="search" placeholder="Search title, location, reporter..." value="<?=htmlspecialchars($search)?>" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" style="flex:1;min-width:180px;">
        <select name="status" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700">
          <option value="">All Status</option>
          <?php foreach(['pending','in_progress','resolved','closed','rejected'] as $s):?><option value="<?=$s?>"<?=$sf===$s?' selected':''?>><?=ucwords(str_replace('_',' ',$s))?></option><?php endforeach;?>
        </select>
        <select name="priority" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700">
          <option value="">All Priority</option>
          <?php foreach(['low','medium','high','critical'] as $p):?><option value="<?=$p?>"<?=$pf===$p?' selected':''?>><?=ucfirst($p)?></option><?php endforeach;?>
        </select>
        <button type="submit" class="bg-zinc-800 text-zinc-300 border border-zinc-700/60 hover:bg-zinc-700/80 hover:text-zinc-100 font-sans text-xs font-semibold px-3 py-1.5 rounded-md transition-colors shadow-none focus:outline-none focus:ring-1 focus:ring-zinc-600">Filter</button>
        <a href="issues.php" style="font-size:.75rem;color:var(--text-dim);align-self:center;">Clear</a>
      </form>

      <form method="POST">
        <input type="hidden" name="action" value="merge_selected">
        <div style="display:flex;align-items:center;justify-space-between;margin-bottom:12px;background:var(--cream);padding:10px 14px;border-radius:var(--r-sm);border:1px solid rgba(74,14,23,.1);">
          <div style="display:flex;align-items:center;gap:8px;">
            <i class="bi bi-diagram-3-fill" style="color:var(--burg2);"></i>
            <span style="font-size:0.8rem;font-weight:600;color:var(--burg);">Incident Clustering:</span>
            <span style="font-size:0.75rem;color:var(--burg2);">(Select 2+ issues to merge into a single Parent Incident)</span>
          </div>
          <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-sans text-xs font-semibold px-3 py-1.5 rounded-md transition-colors shadow-none flex items-center gap-1.5 cursor-pointer ml-auto">
            <i class="bi bi-intersect"></i> Merge Selected into Incident
          </button>
        </div>

        <div class="panel">
          <div class="panel-header"><span><i class="bi bi-list-ul me-2"></i>Issues <span class="issue-id" style="margin-left:4px;">(<?=count($issues)?>)</span></span></div>
          <?php if(empty($issues)):?>
            <div class="empty-state"><i class="bi bi-inbox"></i><h5>No issues found</h5><p>Try adjusting your filters.</p></div>
          <?php else:?>
            <table class="table-dark-custom">
              <thead>
                <tr>
                  <th style="width:36px;text-align:center;"><input type="checkbox" onclick="toggleSelectAll(this)" title="Select All"></th>
                  <th>#</th>
                  <th>Title & Cluster Info</th>
                  <th>Reporter</th>
                  <th>Category</th>
                  <th>Location</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach($issues as $iss):?>
              <tr>
                <td style="text-align:center;"><input type="checkbox" name="selected_issues[]" value="<?=$iss['issue_id']?>" class="issue-cb"></td>
                <td><span class="issue-id">#<?=$iss['issue_id']?></span></td>
                <td style="max-width:240px;">
                  <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=htmlspecialchars($iss['title'])?></div>
                  <?php if(!empty($iss['is_parent']) || !empty($iss['affected_count']) && $iss['affected_count']>1):?>
                    <div style="margin-top:2px;">
                      <span class="inline-flex items-center gap-1 font-mono text-xs bg-amber-500/10 text-amber-600 border border-amber-500/20 px-2 py-0.5 rounded font-semibold">
                        👥 <?=$iss['affected_count']??1?> Affected | Incident #FM<?=$iss['issue_id']?>
                      </span>
                    </div>
                  <?php elseif(!empty($iss['parent_id'])):?>
                    <div style="margin-top:2px;">
                      <span class="inline-flex items-center gap-1 font-mono text-xs bg-purple-500/10 text-purple-600 border border-purple-500/20 px-2 py-0.5 rounded font-medium">
                        🔗 Merged → #FM<?=$iss['parent_id']?>
                      </span>
                    </div>
                  <?php endif;?>
                </td>
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
      </form>
    </div>
  </div>
</div>
<script>
function toggleSelectAll(master) {
  const checkboxes = document.querySelectorAll('.issue-cb');
  checkboxes.forEach(cb => cb.checked = master.checked);
}
</script>
</body></html>
