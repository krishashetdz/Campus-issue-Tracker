<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole(['student','staff']);
$u = currentUser();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT i.*,c.category_name,rb.full_name AS reporter_name,rb.email AS reporter_email,
    ab.full_name AS assigned_name FROM issues i
    LEFT JOIN categories c ON i.category_id=c.category_id
    LEFT JOIN users rb ON i.reported_by=rb.user_id
    LEFT JOIN users ab ON i.assigned_to=ab.user_id
    WHERE i.issue_id=? AND i.reported_by=?");
$stmt->execute([$id, $u['id']]);
$issue = $stmt->fetch();

if (!$issue) {
    header('Location: my_issues.php?error=Issue not found');
    exit();
}

$images = $pdo->prepare("SELECT * FROM issue_images WHERE issue_id=?");
$images->execute([$id]);
$images = $images->fetchAll();

$history = $pdo->prepare("SELECT sh.*, u.full_name FROM status_history sh LEFT JOIN users u ON sh.changed_by=u.user_id WHERE sh.issue_id=? ORDER BY sh.changed_at ASC");
$history->execute([$id]);
$history = $history->fetchAll();

$statusColors = ['pending'=>'#f59e0b','in_progress'=>'#7c3aed','resolved'=>'#10b981','closed'=>'#64748b','rejected'=>'#ec4899'];
$pageTitle = 'Issue #' . $id;
$pageSubtitle = htmlspecialchars($issue['title']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Issue #<?= $id ?> – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">
      <div style="margin-bottom:16px;">
        <a href="my_issues.php" style="color:var(--text-muted);text-decoration:none;font-size:0.85rem;"><i class="bi bi-arrow-left me-1"></i>Back to My Issues</a>
      </div>

      <?php if(isset($_SESSION['flash_success'])): ?>
        <div class="alert-banner alert-success" style="margin-bottom:14px;"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>
      <?php if(isset($_SESSION['flash_error'])): ?>
        <div class="alert-banner alert-danger" style="margin-bottom:14px;"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">

        <!-- Left: Issue Details -->
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div class="panel fade-in-up">
            <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;">
              <div>
                <span style="font-size:1rem;font-weight:700;"><?= htmlspecialchars($issue['title']) ?></span>
                <?php if(!empty($issue['is_parent']) || (!empty($issue['affected_count']) && $issue['affected_count']>1)): ?>
                  <span class="inline-flex items-center gap-1 font-mono text-xs bg-amber-500/10 text-amber-600 border border-amber-500/20 px-2 py-0.5 rounded font-semibold ml-2">
                    👥 <?= $issue['affected_count'] ?> Affected | Incident #FM<?= $issue['issue_id'] ?>
                  </span>
                <?php elseif(!empty($issue['parent_id'])): ?>
                  <span class="inline-flex items-center gap-1 font-mono text-xs bg-purple-500/10 text-purple-600 border border-purple-500/20 px-2 py-0.5 rounded font-medium ml-2">
                    🔗 Merged → #FM<?= $issue['parent_id'] ?>
                  </span>
                <?php endif; ?>
              </div>
              <div style="display:flex;gap:8px;"><?= getPriorityBadge($issue['priority']) ?> <?= getStatusBadge($issue['status']) ?></div>
            </div>
            <div class="panel-body">
              <?php if(!empty($issue['parent_id'])): ?>
              <div style="background:rgba(124,58,237,0.08);border:1px solid rgba(124,58,237,0.2);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:0.8rem;color:#6b21a8;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-info-circle-fill" style="font-size:1.1rem;"></i>
                <div>Your report has been automatically linked to <b>Parent Incident #FM<?= $issue['parent_id'] ?></b>. Updates and status changes to the main incident will automatically sync to your report.</div>
              </div>
              <?php endif; ?>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
                <div style="background:var(--bg-body);border:1px solid var(--border-color);border-radius:var(--radius);padding:12px;">
                  <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;letter-spacing:0.1em;">Category</div>
                  <div style="font-size:0.88rem;font-weight:600;"><?= htmlspecialchars($issue['category_name'] ?? 'N/A') ?></div>
                </div>
                <div style="background:var(--bg-body);border:1px solid var(--border-color);border-radius:var(--radius);padding:12px;">
                  <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;letter-spacing:0.1em;">Location</div>
                  <div style="font-size:0.88rem;font-weight:600;"><?= htmlspecialchars($issue['location']) ?></div>
                </div>
                <div style="background:var(--bg-body);border:1px solid var(--border-color);border-radius:var(--radius);padding:12px;">
                  <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;letter-spacing:0.1em;">Reported On</div>
                  <div style="font-size:0.88rem;font-weight:600;"><?= date('d M Y, h:i A', strtotime($issue['created_at'])) ?></div>
                </div>
                <div style="background:var(--bg-body);border:1px solid var(--border-color);border-radius:var(--radius);padding:12px;">
                  <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;letter-spacing:0.1em;">Assigned To</div>
                  <div style="font-size:0.88rem;font-weight:600;"><?= $issue['assigned_name'] ? htmlspecialchars($issue['assigned_name']) : '<span style="color:var(--text-muted)">Not yet assigned</span>' ?></div>
                </div>
              </div>
              <div style="margin-bottom:18px;">
                <div style="font-size:0.78rem;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.1em;margin-bottom:8px;">Description</div>
                <div style="font-size:0.9rem;line-height:1.75;color:var(--text-primary);"><?= nl2br(htmlspecialchars($issue['description'])) ?></div>
              </div>
              <?php if($issue['admin_remark']): ?>
              <div style="background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.3);border-radius:10px;padding:14px;">
                <div style="font-size:0.75rem;font-weight:700;color:var(--text-primary);margin-bottom:6px;"><i class="bi bi-chat-left-quote me-1"></i>Admin Remark</div>
                <div style="font-size:0.88rem;color:var(--text-muted);"><?= nl2br(htmlspecialchars($issue['admin_remark'])) ?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Images -->
          <?php if(!empty($images)): ?>
          <div class="panel fade-in-up">
            <div class="panel-header"><i class="bi bi-images me-2"></i>Uploaded Images (<?= count($images) ?>)</div>
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

          <!-- Status Timeline -->
          <div class="panel fade-in-up">
            <div class="panel-header"><i class="bi bi-activity me-2"></i>Status History</div>
            <div class="panel-body">
              <?php if(empty($history)): ?>
                <p style="color:var(--text-muted);font-size:0.85rem;">No history yet.</p>
              <?php else: ?>
              <div class="timeline">
                <?php foreach($history as $h): 
                  $col = $statusColors[$h['new_status']] ?? '#64748b';
                ?>
                <div class="timeline-item">
                  <div class="timeline-dot" style="background:<?= $col ?>;"></div>
                  <div class="timeline-content">
                    <div class="t-title">
                      <?php if($h['old_status']): ?>
                        <?= getStatusBadge($h['old_status']) ?> <i class="bi bi-arrow-right mx-1" style="font-size:0.7rem;"></i>
                      <?php endif; ?>
                      <?= getStatusBadge($h['new_status']) ?>
                    </div>
                    <div class="t-meta">
                      By <b><?= htmlspecialchars($h['full_name']) ?></b> &bull; <?= date('d M Y, h:i A', strtotime($h['changed_at'])) ?>
                    </div>
                    <?php if($h['remarks']): ?>
                      <div class="t-remark">"<?= htmlspecialchars($h['remarks']) ?>"</div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Right Sidebar -->
        <div style="display:flex;flex-direction:column;gap:16px;">
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
                <?php if(!empty($issue['reopen_count']) && $issue['reopen_count'] > 0): ?>
                  <div style="margin-top:8px;">
                    <span class="inline-flex items-center gap-1 font-mono text-xs bg-rose-500/10 text-rose-600 border border-rose-500/20 px-2 py-0.5 rounded font-semibold">
                      ⚠️ Re-opened (x<?= $issue['reopen_count'] ?>)
                    </span>
                  </div>
                <?php endif; ?>
                <div style="margin-top:12px;font-size:0.8rem;color:var(--text-muted);">Last updated: <?= timeAgo($issue['updated_at'] ?? $issue['created_at']) ?></div>

                <?php if($issue['status'] === 'resolved'): ?>
                  <div style="margin-top:14px;border-top:1px solid rgba(74,14,23,0.1);padding-top:12px;">
                    <button type="button" onclick="openReopenModal()" class="bg-amber-600 hover:bg-amber-700 text-white font-sans text-xs font-semibold px-3 py-2 rounded-md transition-colors shadow-none w-full flex items-center justify-center gap-1.5 cursor-pointer">
                      <i class="bi bi-exclamation-triangle-fill"></i> Problem Not Fixed? Re-Open Issue
                    </button>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="panel fade-in-up">
            <div class="panel-header"><i class="bi bi-person me-2"></i>Reporter</div>
            <div class="panel-body">
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="user-avatar-sm"><?= strtoupper(substr($issue['reporter_name'],0,1)) ?></div>
                <div>
                  <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($issue['reporter_name']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($issue['reporter_email']) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Re-Open Issue Modal -->
<div id="reopenModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:16px;">
  <div style="background:#ffffff;border:1px solid #cbbba8;border-radius:12px;width:100%;max-width:440px;padding:20px;box-shadow:0 10px 25px rgba(0,0,0,0.2);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;border-bottom:1px solid rgba(74,14,23,0.1);padding-bottom:10px;">
      <span style="font-weight:700;color:#3c1515;font-size:0.95rem;"><i class="bi bi-arrow-counterclockwise me-1.5"></i>Re-Open Issue #<?= $issue['issue_id'] ?></span>
      <button type="button" onclick="closeReopenModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#8a7575;">&times;</button>
    </div>
    <form action="reopen_issue.php" method="POST">
      <input type="hidden" name="issue_id" value="<?= $issue['issue_id'] ?>">
      <div style="margin-bottom:14px;">
        <label style="display:block;font-size:0.8rem;font-weight:600;color:#3c1515;margin-bottom:6px;">Reason for Re-Opening *</label>
        <textarea name="reason" rows="3" required placeholder="Describe why the problem is not resolved (e.g. 'Light is still flickering', 'AC is still leaking water')..." class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 w-full"></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" onclick="closeReopenModal()" style="background:transparent;border:1px solid #cbbba8;color:#3c1515;font-size:0.8rem;font-weight:600;padding:8px 14px;border-radius:6px;cursor:pointer;">Cancel</button>
        <button type="submit" style="background:#3c1515;border:none;color:#ffffff;font-size:0.8rem;font-weight:600;padding:8px 16px;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:6px;">
          <i class="bi bi-send-fill" style="color:#cbbba8;"></i> Submit Re-Open Request
        </button>
      </div>
    </form>
  </div>
</div>
<script>
function openReopenModal() { document.getElementById('reopenModal').style.display = 'flex'; }
function closeReopenModal() { document.getElementById('reopenModal').style.display = 'none'; }
</script>
</body>
</html>
