<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('maintenance');
$u = currentUser();

$id = intval($_GET['id'] ?? 0);
$msg = $err = '';

$stmt = $pdo->prepare("SELECT i.*,c.category_name,rb.full_name AS reporter_name,rb.email AS reporter_email FROM issues i LEFT JOIN categories c ON i.category_id=c.category_id LEFT JOIN users rb ON i.reported_by=rb.user_id WHERE i.issue_id=? AND i.assigned_to=?");
$stmt->execute([$id,$u['id']]);
$issue = $stmt->fetch();
if (!$issue) { header('Location: my_assignments.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['new_status'] ?? '';
    $remarks    = trim($_POST['remarks'] ?? '');
    $valid = ['in_progress','resolved'];
    if (!in_array($new_status,$valid)) { $err = 'Invalid status.'; }
    elseif (empty($remarks)) { $err = 'Please provide a remark.'; }
    else {
        $old_status = $issue['status'];
        $pdo->prepare("UPDATE issues SET status=?,updated_at=NOW() WHERE issue_id=?")->execute([$new_status,$id]);
        logStatusChange($pdo,$id,$u['id'],$old_status,$new_status,$remarks);
        $type = $new_status==='resolved'?'success':'info';
        sendNotification($pdo,$issue['reported_by'],$id,"Your issue #{$id} ({$issue['title']}) status is now '".ucwords(str_replace('_',' ',$new_status))."'. Note: {$remarks}",$type);
        // Also notify admin
        $admins=$pdo->query("SELECT user_id FROM users WHERE role='admin'")->fetchAll();
        foreach($admins as $admin) { sendNotification($pdo,$admin['user_id'],$id,"Issue #{$id} marked as '{$new_status}' by {$u['name']}. Remark: {$remarks}",'info'); }
        
        // Mass propagate status update to clustered reports
        $rootParentId = !empty($issue['is_parent']) ? $id : (!empty($issue['parent_id']) ? $issue['parent_id'] : 0);
        if ($rootParentId > 0) {
            $cStmt = $pdo->prepare("SELECT issue_id, reported_by, status FROM issues WHERE (parent_id = ? OR issue_id = ?) AND issue_id != ?");
            $cStmt->execute([$rootParentId, $rootParentId, $id]);
            foreach ($cStmt->fetchAll() as $ch) {
                $pdo->prepare("UPDATE issues SET status=?, updated_at=NOW() WHERE issue_id=?")->execute([$new_status, $ch['issue_id']]);
                logStatusChange($pdo, $ch['issue_id'], $u['id'], $ch['status'], $new_status, "Status sync from Parent Incident #{$rootParentId}. {$remarks}");
                sendNotification($pdo, $ch['reported_by'], $ch['issue_id'], "Your report #{$ch['issue_id']} (linked to Parent Incident #{$rootParentId}) status changed to '".ucwords(str_replace('_',' ',$new_status))."'. Note: {$remarks}", $type);
            }
        }

        $msg = 'Status updated to ' . ucwords(str_replace('_',' ',$new_status)) . ' and synced across incident cluster.';
        $stmt->execute([$id,$u['id']]); $issue=$stmt->fetch();
    }
}

$images = $pdo->prepare("SELECT * FROM issue_images WHERE issue_id=?"); $images->execute([$id]); $images=$images->fetchAll();
$history = $pdo->prepare("SELECT sh.*,u.full_name FROM status_history sh LEFT JOIN users u ON sh.changed_by=u.user_id WHERE sh.issue_id=? ORDER BY sh.changed_at ASC"); $history->execute([$id]); $history=$history->fetchAll();
$statusColors=['pending'=>'#f59e0b','in_progress'=>'#7c3aed','resolved'=>'#10b981','closed'=>'#64748b','rejected'=>'#ec4899'];

$pageTitle='Update Issue #'.$id; $pageSubtitle=htmlspecialchars($issue['title']);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Update Issue – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">
      <div style="margin-bottom:14px;"><a href="my_assignments.php" style="color:var(--text-muted);text-decoration:none;font-size:0.85rem;"><i class="bi bi-arrow-left me-1"></i>Back to Assignments</a></div>
      <?php if($msg): ?><div class="alert-banner alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
      <?php if($err): ?><div class="alert-banner alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div class="panel">
            <div class="panel-header">
              <span style="font-weight:700;"><?= htmlspecialchars($issue['title']) ?></span>
              <div style="display:flex;gap:8px;"><?= getPriorityBadge($issue['priority']) ?><?= getStatusBadge($issue['status']) ?></div>
            </div>
            <div class="panel-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div style="background:var(--bg-body);border:1px solid var(--border-color);border-radius:var(--radius);padding:12px;"><div style="font-size:0.68rem;text-transform:uppercase;color:var(--text-muted);margin-bottom:3px;">Category</div><div style="font-size:0.85rem;font-weight:600;color:var(--text-dark);"><?= htmlspecialchars($issue['category_name']??'N/A') ?></div></div>
                <div style="background:var(--bg-body);border:1px solid var(--border-color);border-radius:var(--radius);padding:12px;"><div style="font-size:0.68rem;text-transform:uppercase;color:var(--text-muted);margin-bottom:3px;">Location</div><div style="font-size:0.85rem;font-weight:600;color:var(--text-dark);"><?= htmlspecialchars($issue['location']) ?></div></div>
              </div>
              <div style="margin-bottom:16px;"><div style="font-size:0.72rem;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.1em;margin-bottom:8px;">Description</div><div style="font-size:0.9rem;line-height:1.75;"><?= nl2br(htmlspecialchars($issue['description'])) ?></div></div>
              <?php if($issue['admin_remark']): ?><div style="background:var(--bg-body);border:1px solid var(--border-color);border-radius:var(--radius);padding:12px;"><div style="font-size:0.72rem;font-weight:700;color:var(--text-primary);margin-bottom:5px;"><i class="bi bi-chat-left-quote me-1"></i>Admin Instruction</div><div style="font-size:0.85rem;color:var(--text-muted);"><?= nl2br(htmlspecialchars($issue['admin_remark'])) ?></div></div><?php endif; ?>
            </div>
          </div>
          <!-- Images -->
          <?php if(!empty($images)): ?>
          <div class="panel">
            <div class="panel-header"><i class="bi bi-images me-2"></i>Images (<?= count($images) ?>)</div>
            <div class="panel-body">
              <div class="mt-3 flex flex-wrap gap-3">
                <?php 
                $validImgCount = 0;
                foreach($images as $img):
                  $raw_path = trim($img['image_path'] ?? '');
                  if (empty($raw_path)) continue;
                  if (filter_var($raw_path, FILTER_VALIDATE_URL) || strpos($raw_path, 'http://') === 0 || strpos($raw_path, 'https://') === 0) {
                      $webPath = $raw_path;
                  } elseif (strpos($raw_path, '../') === 0) {
                      $webPath = $raw_path;
                  } elseif (strpos($raw_path, 'uploads/') === 0) {
                      $webPath = '../' . $raw_path;
                  } else {
                      $webPath = '../uploads/issues/' . ltrim($raw_path, '/');
                  }
                  $validImgCount++;
                ?>
                  <a href="<?= htmlspecialchars($webPath) ?>" target="_blank" class="block border border-stone-300 rounded-md overflow-hidden hover:opacity-90 transition-opacity">
                    <img src="<?= htmlspecialchars($webPath) ?>" alt="Issue Evidence" class="w-32 h-32 object-cover" onerror="this.onerror=null; this.src='https://via.placeholder.com/150?text=Image+Not+Found';" />
                  </a>
                <?php 
                endforeach;
                if ($validImgCount === 0):
                ?>
                  <p class="text-xs text-stone-500 italic">No image preview available</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <div class="panel"><div class="panel-header"><i class="bi bi-activity me-2"></i>Status Timeline</div><div class="panel-body">
            <?php if(empty($history)): ?><p style="color:var(--text-muted);font-size:0.85rem;">No changes yet.</p>
            <?php else: ?><div class="timeline"><?php foreach($history as $h): $col=$statusColors[$h['new_status']]??'#64748b'; ?><div class="timeline-item"><div class="timeline-dot" style="background:<?= $col ?>;"></div><div class="timeline-content"><div class="t-title"><?php if($h['old_status']): ?><?= getStatusBadge($h['old_status']) ?> <i class="bi bi-arrow-right" style="font-size:0.7rem;"></i><?php endif; ?><?= getStatusBadge($h['new_status']) ?></div><div class="t-meta">By <b><?= htmlspecialchars($h['full_name']) ?></b> &bull; <?= date('d M Y, h:i A',strtotime($h['changed_at'])) ?></div><?php if($h['remarks']): ?><div class="t-remark">"<?= htmlspecialchars($h['remarks']) ?>"</div><?php endif; ?></div></div><?php endforeach; ?></div>
            <?php endif; ?>
          </div></div>
        </div>

        <!-- Right: Update Form -->
        <div style="display:flex;flex-direction:column;gap:14px;">
          <?php if(!in_array($issue['status'],['resolved','closed','rejected'])): ?>
          <div class="panel">
            <div class="panel-header"><i class="bi bi-arrow-repeat me-2"></i>Update Status</div>
            <div class="panel-body">
              <form method="POST">
                <div class="field-group">
                  <label>Set Status To</label>
                  <select name="new_status" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700 w-full">
                    <option value="in_progress" <?= $issue['status']==='in_progress'?'selected':'' ?>>🔧 In Progress</option>
                    <option value="resolved">✅ Mark as Resolved</option>
                  </select>
                </div>
                <div class="field-group">
                  <label>Work Notes / Remark *</label>
                  <textarea name="remarks" rows="4" placeholder="Describe the work done, parts replaced, or reason if not yet complete..." required class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700 w-full"></textarea>
                </div>
                <button type="submit" class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 font-sans text-xs font-semibold px-3 py-1.5 rounded-md transition-colors shadow-none focus:outline-none focus:ring-1 focus:ring-emerald-500 w-full justify-center flex items-center" style="padding:12px;">
                  <i class="bi bi-check-circle me-2"></i>Submit Update
                </button>
              </form>
            </div>
          </div>
          <?php else: ?>
          <div class="panel"><div class="panel-body" style="text-align:center;padding:24px;">
            <div style="font-size:2rem;margin-bottom:8px;">✅</div>
            <div style="font-weight:700;margin-bottom:6px;">Issue <?= ucfirst($issue['status']) ?></div>
            <div style="font-size:0.82rem;color:var(--text-muted);">This issue has been closed. No further action required.</div>
          </div></div>
          <!-- Issue Status Card -->
          <div class="panel fade-in-up">
            <div class="panel-header"><i class="bi bi-info-circle me-2"></i>Issue Status</div>
            <div class="panel-body">
              <div style="text-align:center;padding:20px 0;">
                <?php 
                $st = $issue['status'] ?? 'pending';
                $statusIconMap = [
                  'pending'     => 'bi-hourglass-split',
                  'in_progress' => 'bi-wrench',
                  'resolved'    => 'bi-check-circle-fill',
                  'closed'      => 'bi-lock-fill',
                  'rejected'    => 'bi-x-circle-fill'
                ];
                $iconClass = $statusIconMap[$st] ?? 'bi-info-circle-fill';
                ?>
                <div class="status-badge-circle status-<?= htmlspecialchars($st) ?>">
                  <i class="bi <?= $iconClass ?> status-badge-icon"></i>
                </div>
                <?= getStatusBadge($issue['status']) ?>
                <div style="margin-top:12px;font-size:0.8rem;color:var(--text-muted);">Last updated: <?= timeAgo($issue['updated_at'] ?? $issue['created_at']) ?></div>
              </div>
            </div>
          </div>

          <div class="panel"><div class="panel-header"><i class="bi bi-info-circle me-2"></i>Issue Details</div>
            <div class="panel-body" style="font-size:0.82rem;color:var(--text-muted);line-height:2;">
              <div><b style="color:var(--text-primary);">Issue ID:</b> #<?= $id ?></div>
              <div><b style="color:var(--text-primary);">Reporter:</b> <?= htmlspecialchars($issue['reporter_name']) ?></div>
              <div><b style="color:var(--text-primary);">Email:</b> <?= htmlspecialchars($issue['reporter_email']) ?></div>
              <div><b style="color:var(--text-primary);">Submitted:</b> <?= date('d M Y',strtotime($issue['created_at'])) ?></div>
              <div><b style="color:var(--text-primary);">Last Updated:</b> <?= timeAgo($issue['updated_at']??$issue['created_at']) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
