<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole(['student','staff']);
$u = currentUser();

$error = $success = '';
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $location    = trim($_POST['location'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';

    if (empty($title) || empty($description) || empty($location) || $category_id <= 0) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($priority, ['low','medium','high','critical'])) {
        $error = 'Invalid priority selected.';
    } else {
        // Insert issue
        $stmt = $pdo->prepare("INSERT INTO issues (title,description,category_id,location,priority,status,reported_by) VALUES (?,?,?,?,?,'pending',?)");
        $stmt->execute([$title,$description,$category_id,$location,$priority,$u['id']]);
        $issue_id = $pdo->lastInsertId();

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $allowedTypes = ['image/jpeg','image/png','image/jpg','image/webp'];
            foreach ($_FILES['images']['tmp_name'] as $idx => $tmpName) {
                if ($_FILES['images']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $mime = mime_content_type($tmpName);
                if (!in_array($mime,$allowedTypes)) continue;
                if ($_FILES['images']['size'][$idx] > MAX_FILE_SIZE) continue;
                $ext = pathinfo($_FILES['images']['name'][$idx], PATHINFO_EXTENSION);
                $newName = 'issue_' . $issue_id . '_' . uniqid() . '.' . strtolower($ext);
                $dest = UPLOAD_DIR . $newName;
                if (move_uploaded_file($tmpName, $dest)) {
                    $imgStmt = $pdo->prepare("INSERT INTO issue_images (issue_id,image_path) VALUES (?,?)");
                    $imgStmt->execute([$issue_id, $newName]);
                }
            }
        }

        // Log initial status
        logStatusChange($pdo, $issue_id, $u['id'], '', 'pending', 'Issue submitted by '.$u['name']);

        // Notify all admins
        $admins = $pdo->query("SELECT user_id FROM users WHERE role='admin'")->fetchAll();
        foreach ($admins as $admin) {
            sendNotification($pdo, $admin['user_id'], $issue_id,
                "New issue #{$issue_id} submitted by {$u['name']}: {$title}", 'warning');
        }

        $success = "Issue #{$issue_id} submitted successfully!";
    }
}

$pageTitle    = 'Report an Issue';
$pageSubtitle = 'Submit a campus problem for resolution';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Report Issue – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">

      <?php if($error): ?>
        <div class="alert-banner alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if($success): ?>
        <div class="alert-banner alert-success">
          <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
          <a href="my_issues.php" style="color:inherit;font-weight:600;margin-left:6px;">Track issue</a>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:1fr 260px;gap:14px;">

          <!-- Left: Main Form -->
          <div>
            <div class="panel">
              <div class="panel-header"><i class="bi bi-pencil-square me-2"></i>Issue Details</div>
              <div class="panel-body">
                <div class="field-group">
                  <label class="form-label" for="title">Issue Title *</label>
                  <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Broken light in Lab 3" maxlength="200" required value="<?= htmlspecialchars($_POST['title']??'') ?>">
                </div>
                <div class="field-group">
                  <label class="form-label" for="description">Description *</label>
                  <textarea id="description" name="description" class="form-control" rows="5" placeholder="Describe the issue in detail — what's wrong, how long it has been happening, and its impact on students or staff..." required><?= htmlspecialchars($_POST['description']??'') ?></textarea>
                  <div style="font-size:.7rem;color:var(--text-dim);margin-top:4px;">Be as descriptive as possible for faster resolution.</div>
                </div>
                <div class="field-group" style="margin-bottom:0">
                  <label class="form-label" for="location">Campus Location *</label>
                  <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Block A – Computer Lab 3, 2nd Floor" required value="<?= htmlspecialchars($_POST['location']??'') ?>">
                </div>
              </div>
            </div>

            <!-- Photo Evidence -->
            <div class="panel">
              <div class="panel-header"><i class="bi bi-images me-2"></i>Photo Evidence</div>
              <div class="panel-body">
                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imgInput').click()">
                  <i class="bi bi-cloud-upload"></i>
                  <p style="font-weight:500;margin-bottom:2px;">Click to upload images</p>
                  <p>JPG, PNG, WEBP — max 5 MB each, up to 5 images</p>
                </div>
                <input type="file" id="imgInput" name="images[]" multiple accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewImages(this)">
                <div id="imgPreview" class="img-gallery" style="margin-top:10px;"></div>
              </div>
            </div>
          </div>

          <!-- Right: Classification -->
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div class="panel">
              <div class="panel-header"><i class="bi bi-sliders me-2"></i>Classification</div>
              <div class="panel-body">
                <div class="field-group">
                  <label class="form-label" for="category_id">Category *</label>
                  <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach($categories as $cat): ?>
                      <option value="<?= $cat['category_id'] ?>" <?= ($_POST['category_id']??'')==$cat['category_id']?'selected':'' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field-group" style="margin-bottom:0">
                  <label class="form-label" for="priority">Priority *</label>
                  <select id="priority" name="priority" class="form-control">
                    <option value="low"      <?= ($_POST['priority']??'medium')==='low'      ?'selected':'' ?>>Low — Minor inconvenience</option>
                    <option value="medium"   <?= ($_POST['priority']??'medium')==='medium'   ?'selected':'' ?>>Medium — Needs attention</option>
                    <option value="high"     <?= ($_POST['priority']??'medium')==='high'     ?'selected':'' ?>>High — Affects operations</option>
                    <option value="critical" <?= ($_POST['priority']??'medium')==='critical' ?'selected':'' ?>>Critical — Safety hazard</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="panel">
              <div class="panel-header"><i class="bi bi-info-circle me-2"></i>Priority Guide</div>
              <div class="panel-body" style="padding:12px 14px;">
                <div style="font-size:.75rem;line-height:2;color:var(--text-muted);">
                  <div><span class="badge badge-emerald" style="margin-right:6px;">Low</span> Aesthetic or minor comfort</div>
                  <div><span class="badge badge-blue" style="margin-right:6px;">Medium</span> Functional, moderate impact</div>
                  <div><span class="badge badge-amber" style="margin-right:6px;">High</span> Class or work disruption</div>
                  <div><span class="badge badge-rose" style="margin-right:6px;">Critical</span> Safety risk, urgent</div>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:10px;">
              <i class="bi bi-send me-2"></i>Submit Issue Report
            </button>
            <a href="dashboard.php" style="text-align:center;font-size:.75rem;color:var(--text-dim);">Back to Dashboard</a>
          </div>

        </div>
      </form>
    </div>
  </div>
</div>
<script>
function previewImages(input) {
  const preview = document.getElementById('imgPreview');
  preview.innerHTML = '';
  const files = Array.from(input.files).slice(0,5);
  files.forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      preview.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}
</script>
</body>
</html>
