<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole(['student','staff']);
$u=currentUser();
$error=$success='';
$categories=$pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $title=trim($_POST['title']??'');$description=trim($_POST['description']??'');
  $category_id=intval($_POST['category_id']??0);$location=trim($_POST['location']??'');
  $priority=$_POST['priority']??'medium';
  if(empty($title)||empty($description)||empty($location)||$category_id<=0){$error='Please fill in all required fields.';}
  elseif(!in_array($priority,['low','medium','high','critical'])){$error='Invalid priority.';}
  else{
    $stmt=$pdo->prepare("INSERT INTO issues (title,description,category_id,location,priority,status,reported_by) VALUES (?,?,?,?,?,'pending',?)");
    $stmt->execute([$title,$description,$category_id,$location,$priority,$u['id']]);
    $issue_id=$pdo->lastInsertId();
    if(!empty($_FILES['images']['name'][0])){
      if(!file_exists(UPLOAD_DIR)){ @mkdir(UPLOAD_DIR, 0777, true); }
      $allowed=['image/jpeg','image/png','image/jpg','image/webp'];
      foreach($_FILES['images']['tmp_name'] as $idx=>$tmp){
        if($_FILES['images']['error'][$idx]!==UPLOAD_ERR_OK)continue;
        $mime=mime_content_type($tmp);if(!in_array($mime,$allowed))continue;
        if($_FILES['images']['size'][$idx]>MAX_FILE_SIZE)continue;
        
        $cloudinaryUrl = uploadToCloudinary($tmp);
        if ($cloudinaryUrl) {
          $pdo->prepare("INSERT INTO issue_images (issue_id,image_path) VALUES (?,?)")->execute([$issue_id,$cloudinaryUrl]);
        } else {
          $ext=pathinfo($_FILES['images']['name'][$idx],PATHINFO_EXTENSION);
          $newName='issue_'.$issue_id.'_'.uniqid().'.'.strtolower($ext);
          if(move_uploaded_file($tmp,UPLOAD_DIR.$newName)){
            $pdo->prepare("INSERT INTO issue_images (issue_id,image_path) VALUES (?,?)")->execute([$issue_id,$newName]);
          }
        }
      }
    } elseif (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $tmp = $_FILES['image']['tmp_name'];
      $cloudinaryUrl = uploadToCloudinary($tmp);
      if ($cloudinaryUrl) {
        $pdo->prepare("INSERT INTO issue_images (issue_id,image_path) VALUES (?,?)")->execute([$issue_id,$cloudinaryUrl]);
      } else {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newName = 'issue_' . $issue_id . '_' . uniqid() . '.' . strtolower($ext);
        if (move_uploaded_file($tmp, UPLOAD_DIR . $newName)) {
          $pdo->prepare("INSERT INTO issue_images (issue_id,image_path) VALUES (?,?)")->execute([$issue_id,$newName]);
        }
      }
    }
    logStatusChange($pdo,$issue_id,$u['id'],'','pending','Issue submitted by '.$u['name']);
    $admins=$pdo->query("SELECT user_id FROM users WHERE role='admin'")->fetchAll();
    foreach($admins as $admin){sendNotification($pdo,$admin['user_id'],$issue_id,"New issue #{$issue_id} submitted by {$u['name']}: {$title}",'warning');}
    $success="Issue #{$issue_id} submitted successfully!";
  }
}
$pageTitle='Report an Issue';$pageSubtitle='Submit a campus problem for resolution';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Report Issue – FixMyCampus</title>
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
      <?php if($error):?><div class="alert-banner alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?=htmlspecialchars($error)?></div><?php endif;?>
      <?php if($success):?><div class="alert-banner alert-success"><i class="bi bi-check-circle me-1"></i><?=htmlspecialchars($success)?><a href="my_issues.php" style="color:inherit;font-weight:600;margin-left:6px;">Track issue</a></div><?php endif;?>
      <form method="POST" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:1fr 260px;gap:14px;">
          <div>
            <div class="panel">
              <div class="panel-header"><i class="bi bi-pencil-square me-2"></i>Issue Details</div>
              <div class="panel-body">
                <div class="field-group"><label class="form-label" for="title">Issue Title *</label><input type="text" id="title" name="title" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" placeholder="e.g. Broken light in Lab 3" maxlength="200" required value="<?=htmlspecialchars($_POST['title']??'')?>"></div>
                <div class="field-group"><label class="form-label" for="description">Description *</label><textarea id="description" name="description" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" rows="5" placeholder="Describe the issue in detail — what's wrong, how long it has been happening, and its impact..." required><?=htmlspecialchars($_POST['description']??'')?></textarea><div style="font-size:.7rem;color:var(--text-dim);margin-top:4px;">Be as descriptive as possible for faster resolution.</div></div>
                <div class="field-group" style="margin-bottom:0"><label class="form-label" for="location">Campus Location *</label><input type="text" id="location" name="location" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" placeholder="e.g. Block A – Computer Lab 3, 2nd Floor" required value="<?=htmlspecialchars($_POST['location']??'')?>"></div>
              </div>
            </div>
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
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div class="panel">
              <div class="panel-header"><i class="bi bi-sliders me-2"></i>Classification</div>
              <div class="panel-body">
                <div class="field-group"><label class="form-label" for="category_id">Category *</label>
                  <select id="category_id" name="category_id" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach($categories as $cat):?><option value="<?=$cat['category_id']?>"<?=($_POST['category_id']??'')==$cat['category_id']?' selected':''?>><?=htmlspecialchars($cat['category_name'])?></option><?php endforeach;?>
                  </select>
                </div>
                <div class="field-group" style="margin-bottom:0"><label class="form-label" for="priority">Priority *</label>
                  <select id="priority" name="priority" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700">
                    <option value="low"<?=($_POST['priority']??'medium')==='low'?' selected':''?>>Low — Minor inconvenience</option>
                    <option value="medium"<?=($_POST['priority']??'medium')==='medium'?' selected':''?>>Medium — Needs attention</option>
                    <option value="high"<?=($_POST['priority']??'medium')==='high'?' selected':''?>>High — Affects operations</option>
                    <option value="critical"<?=($_POST['priority']??'medium')==='critical'?' selected':''?>>Critical — Safety hazard</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="panel">
              <div class="panel-header"><i class="bi bi-info-circle me-2"></i>Priority Guide</div>
              <div class="panel-body" style="padding:12px 14px;">
                <div style="font-size:.75rem;line-height:2.3;color:var(--text-muted);">
                  <div><span class="badge badge-emerald" style="margin-right:6px;">Low</span>Aesthetic or minor comfort</div>
                  <div><span class="badge badge-blue" style="margin-right:6px;">Medium</span>Functional, moderate impact</div>
                  <div><span class="badge badge-amber" style="margin-right:6px;">High</span>Class or work disruption</div>
                  <div><span class="badge badge-rose" style="margin-right:6px;">Critical</span>Safety risk, urgent</div>
                </div>
              </div>
            </div>
            <button type="submit" class="bg-zinc-100 text-zinc-950 hover:bg-zinc-200 font-sans text-xs font-semibold px-3 py-1.5 rounded-md transition-colors shadow-none focus:outline-none focus:ring-1 focus:ring-zinc-400 w-full justify-center flex items-center" style="padding:10px;"><i class="bi bi-send me-2"></i>Submit Issue Report</button>
            <a href="dashboard.php" style="text-align:center;font-size:.75rem;color:var(--text-dim);">Back to Dashboard</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function previewImages(input){
  const preview=document.getElementById('imgPreview');preview.innerHTML='';
  Array.from(input.files).slice(0,5).forEach(file=>{
    const reader=new FileReader();
    reader.onload=e=>{const img=document.createElement('img');img.src=e.target.result;preview.appendChild(img);};
    reader.readAsDataURL(file);
  });
}
</script>
</body></html>
