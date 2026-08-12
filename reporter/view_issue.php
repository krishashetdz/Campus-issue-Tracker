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

      <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">

        <!-- Left: Issue Details -->
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div class="panel fade-in-up">
            <div class="panel-header">
              <span style="font-size:1rem;font-weight:700;"><?= htmlspecialchars($issue['title']) ?></span>
              <div style="display:flex;gap:8px;"><?= getPriorityBadge($issue['priority']) ?> <?= getStatusBadge($issue['status']) ?></div>
            </div>
            <div class="panel-body">
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
                  $filename = basename($img['image_path']);
                  $webPath = '';
                  if (!empty($filename) && file_exists(__DIR__ . '/../uploads/issues/' . $filename)) {
                    $webPath = '../uploads/issues/' . $filename;
                  } elseif (!empty($filename) && file_exists(__DIR__ . '/../uploads/' . $filename)) {
                    $webPath = '../uploads/' . $filename;
                  } elseif (!empty($img['image_path']) && file_exists(__DIR__ . '/../' . ltrim($img['image_path'], '/'))) {
                    $webPath = '../' . ltrim($img['image_path'], '/');
                  }
                  if ($webPath):
                    $validImgCount++;
                ?>
                  <a href="<?= htmlspecialchars($webPath) ?>" target="_blank" class="block border border-stone-300 rounded-md overflow-hidden hover:opacity-90 transition-opacity">
                    <img src="<?= htmlspecialchars($webPath) ?>" alt="Attached Image" class="w-32 h-32 object-cover" />
                  </a>
                <?php 
                  endif;
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
                <?php $sc = $statusColors[$issue['status']] ?? '#64748b'; ?>
                <div style="width:70px;height:70px;border-radius:50%;background:<?= $sc ?>22;border:3px solid <?= $sc ?>;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;">
                  <?= ['pending'=>'⏳','in_progress'=>'🔧','resolved'=>'✅','closed'=>'🔒','rejected'=>'❌'][$issue['status']] ?? '📋' ?>
                </div>
                <?= getStatusBadge($issue['status']) ?>
                <div style="margin-top:12px;font-size:0.8rem;color:var(--text-muted);">Last updated: <?= timeAgo($issue['updated_at'] ?? $issue['created_at']) ?></div>
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
</body>
</html>
