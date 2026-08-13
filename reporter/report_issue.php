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
    
    // Automated Duplicate Complaint Detection & Incident Clustering
    $duplicateCheck = $pdo->prepare("SELECT issue_id, parent_id, is_parent FROM issues WHERE category_id = ? AND LOWER(location) = LOWER(?) AND status IN ('pending','in_progress') AND parent_id IS NULL AND issue_id != ? AND created_at >= NOW() - INTERVAL 24 HOUR ORDER BY issue_id ASC LIMIT 1");
    $duplicateCheck->execute([$category_id, $location, $issue_id]);
    $activeMatch = $duplicateCheck->fetch();

    $mergedParentId = null;
    if ($activeMatch) {
      $mergedParentId = $activeMatch['issue_id'];
      
      // Set child report's parent_id
      $pdo->prepare("UPDATE issues SET parent_id = ? WHERE issue_id = ?")->execute([$mergedParentId, $issue_id]);
      
      // Mark primary issue as parent and update affected_count
      $pdo->prepare("UPDATE issues SET is_parent = 1, affected_count = (SELECT COUNT(*) + 1 FROM (SELECT issue_id FROM issues WHERE parent_id = ?) AS tmp) WHERE issue_id = ?")->execute([$mergedParentId, $mergedParentId]);
      
      // Log status timeline on parent issue
      logStatusChange($pdo, $mergedParentId, $u['id'], '', '', "Duplicate report merged from " . $u['name'] . " (Issue #" . $issue_id . ")");
      
      sendNotification($pdo, $u['id'], $issue_id, "Your report has been linked to existing Parent Incident #{$mergedParentId}.", 'info');
    }

    $admins=$pdo->query("SELECT user_id FROM users WHERE role='admin'")->fetchAll();
    foreach($admins as $admin){sendNotification($pdo,$admin['user_id'],$issue_id,"New issue #{$issue_id} submitted by {$u['name']}: {$title}",'warning');}
    
    if ($mergedParentId) {
      $success="Issue #{$issue_id} submitted successfully and auto-merged into Parent Incident #{$mergedParentId}!";
    } else {
      $success="Issue #{$issue_id} submitted successfully!";
    }
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

      <!-- Mode Switcher Tabs -->
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;background:var(--cream);padding:6px;border-radius:10px;border:1px solid rgba(74,14,23,0.15);width:fit-content;">
        <button type="button" id="tabStandard" onclick="switchReportMode('standard')" style="padding:8px 16px;border-radius:8px;font-size:0.8rem;font-weight:600;border:none;cursor:pointer;background:#3c1515;color:#ffffff;transition:all 0.2s ease;">
          <i class="bi bi-pencil-square me-1.5"></i>Standard Form
        </button>
        <button type="button" id="tabAi" onclick="switchReportMode('ai')" style="padding:8px 16px;border-radius:8px;font-size:0.8rem;font-weight:600;border:none;cursor:pointer;background:transparent;color:#3c1515;transition:all 0.2s ease;">
          <i class="bi bi-robot me-1.5" style="color:#cbbba8;"></i>AI Assist Mode <span style="font-size:0.65rem;background:#3c1515;color:#ffffff;padding:2px 6px;border-radius:10px;margin-left:4px;">NEW</span>
        </button>
      </div>

      <!-- AI Chat Assistant Mode Panel -->
      <div id="aiReportPanel" class="panel fade-in-up" style="display:none;background:#ffffff;border:1px solid #cbbba8;border-radius:12px;overflow:hidden;margin-bottom:16px;">
        <div class="panel-header" style="background:#3c1515;color:#ffffff;display:flex;align-items:center;justify-content:space-between;padding:12px 18px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:50%;background:rgba(203,187,168,0.2);display:flex;align-items:center;justify-content:center;color:#cbbba8;">
              <i class="bi bi-robot" style="font-size:1.2rem;"></i>
            </div>
            <div>
              <div style="font-weight:700;font-size:0.9rem;color:#ffffff;">FixMyCampus AI Assistant</div>
              <div style="font-size:0.7rem;color:#cbbba8;">Describe your issue naturally & I'll structure the ticket automatically</div>
            </div>
          </div>
          <span class="badge" style="background:rgba(203,187,168,0.2);color:#cbbba8;border:1px solid rgba(203,187,168,0.4);">Smart Assistant</span>
        </div>

        <div class="panel-body" style="padding:18px;">
          <!-- Chat Messages Window -->
          <div id="chatWindow" style="min-height:200px;max-height:320px;overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding:12px;background:#fcfbfa;border:1px solid #e8e2d8;border-radius:8px;margin-bottom:14px;">
            <!-- Welcome Message -->
            <div style="display:flex;gap:10px;align-items:flex-start;">
              <div style="width:28px;height:28px;border-radius:50%;background:#3c1515;color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;">🤖</div>
              <div style="background:#ffffff;border:1px solid #cbbba8;color:#2b0d0d;padding:10px 14px;border-radius:0 12px 12px 12px;font-size:0.82rem;line-height:1.5;max-width:85%;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                Hi <b><?= htmlspecialchars($u['name']) ?></b>! Describe the issue on campus in your own words (e.g. <i>"The AC in C2 classroom is making a loud buzzing noise and isn't cooling"</i>), and I'll extract all details and structure the report for you!
              </div>
            </div>
          </div>

          <!-- Structured Card Summary Result Area -->
          <div id="aiSummaryCard" style="display:none;background:#fcf9f4;border:1px solid #cbbba8;border-radius:10px;padding:16px;margin-bottom:14px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;border-bottom:1px solid rgba(74,14,23,0.1);padding-bottom:8px;">
              <span style="font-weight:700;color:#3c1515;font-size:0.85rem;"><i class="bi bi-file-earmark-check me-1.5"></i>Extracted Ticket Summary</span>
              <span id="aiPriorityBadge" class="badge badge-amber">Medium Priority</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:0.8rem;margin-bottom:12px;">
              <div><b style="color:#3c1515;">📍 Location:</b> <span id="aiSummaryLocation" style="color:#2b0d0d;font-weight:600;">-</span></div>
              <div><b style="color:#3c1515;">🔧 Category:</b> <span id="aiSummaryCategory" style="color:#2b0d0d;font-weight:600;">-</span></div>
              <div style="grid-column: span 2;"><b style="color:#3c1515;">📝 Issue Title:</b> <span id="aiSummaryTitle" style="color:#2b0d0d;font-weight:600;">-</span></div>
              <div style="grid-column: span 2;"><b style="color:#3c1515;">📄 Description:</b> <div id="aiSummaryDescription" style="color:#555;margin-top:2px;line-height:1.4;">-</div></div>
            </div>
            <div style="display:flex;gap:10px;align-items:center;justify-content:flex-end;margin-top:14px;border-top:1px solid rgba(74,14,23,0.1);padding-top:12px;">
              <button type="button" onclick="editAiDetails()" style="background:transparent;border:1px solid #3c1515;color:#3c1515;font-size:0.78rem;font-weight:600;padding:8px 14px;border-radius:6px;cursor:pointer;">
                <i class="bi bi-pencil me-1"></i>Edit Details
              </button>
              <button type="button" onclick="confirmAndCreateTicket()" style="background:#3c1515;border:none;color:#ffffff;font-size:0.78rem;font-weight:600;padding:8px 18px;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:6px;">
                <i class="bi bi-check-circle-fill" style="color:#cbbba8;"></i>Confirm & Create Ticket
              </button>
            </div>
          </div>

          <!-- User Input Controls -->
          <div style="display:flex;gap:10px;" id="aiInputControls">
            <input type="text" id="aiUserInput" placeholder="Type campus issue details here... (e.g. 'AC in C2 class is making a loud buzzing noise')" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2.5 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" style="flex:1;" onkeydown="if(event.key==='Enter'){event.preventDefault();sendAiMessage();}">
            <button type="button" id="btnSendAi" onclick="sendAiMessage()" style="background:#3c1515;color:#ffffff;border:none;padding:0 16px;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
              <span>Analyze</span> <i class="bi bi-send-fill" style="color:#cbbba8;"></i>
            </button>
          </div>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <div style="display:grid;grid-template-columns:1fr 260px;gap:14px;">
          <div>
            <!-- Multi-Language Voice Dictation Bar -->
            <div style="background:var(--cream);border:1px solid rgba(74,14,23,0.15);border-radius:10px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
              <div style="display:flex;align-items:center;gap:8px;">
                <button type="button" id="btnVoiceRecord" onclick="toggleVoiceRecording()" style="background:#3c1515;color:#ffffff;border:none;padding:7px 14px;border-radius:8px;font-size:0.78rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.2s ease;">
                  <i class="bi bi-mic-fill" id="micIcon" style="color:#cbbba8;"></i>
                  <span id="micText">Speak & Auto-Fill</span>
                </button>
                <select id="voiceLangSelect" class="bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] text-xs rounded-md px-2.5 py-1.5 focus:outline-none focus:border-amber-700">
                  <option value="en-IN">English</option>
                  <option value="hi-IN">Hindi - हिन्दी</option>
                  <option value="mr-IN">Marathi - मराठी</option>
                  <option value="kok-IN">Konkani - कोंकणी</option>
                </select>
              </div>
              <div id="voiceStatus" style="font-size:0.75rem;color:#8a7575;display:flex;align-items:center;gap:6px;">
                <span><i class="bi bi-translate me-1"></i>Speak in your language — auto-translated & filled to form</span>
              </div>
            </div>

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
let currentAiData = null;

function switchReportMode(mode) {
  const tabStd = document.getElementById('tabStandard');
  const tabAi = document.getElementById('tabAi');
  const aiPanel = document.getElementById('aiReportPanel');

  if (mode === 'ai') {
    tabAi.style.background = '#3c1515';
    tabAi.style.color = '#ffffff';
    tabStd.style.background = 'transparent';
    tabStd.style.color = '#3c1515';
    aiPanel.style.display = 'block';
  } else {
    tabStd.style.background = '#3c1515';
    tabStd.style.color = '#ffffff';
    tabAi.style.background = 'transparent';
    tabAi.style.color = '#3c1515';
    aiPanel.style.display = 'none';
  }
}

async function sendAiMessage() {
  const inputEl = document.getElementById('aiUserInput');
  const msg = inputEl.value.trim();
  if (!msg) return;

  const chatWin = document.getElementById('chatWindow');
  const btnSend = document.getElementById('btnSendAi');

  const userMsgHtml = `
    <div style="display:flex;gap:10px;align-items:flex-start;justify-content:flex-end;">
      <div style="background:#3c1515;color:#ffffff;padding:10px 14px;border-radius:12px 0 12px 12px;font-size:0.82rem;line-height:1.5;max-width:80%;">
        ${escapeHtml(msg)}
      </div>
      <div style="width:28px;height:28px;border-radius:50%;background:#cbbba8;color:#3c1515;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0;">You</div>
    </div>`;
  chatWin.insertAdjacentHTML('beforeend', userMsgHtml);
  inputEl.value = '';
  chatWin.scrollTop = chatWin.scrollHeight;

  const loadingId = 'aiLoading_' + Date.now();
  const loadingHtml = `
    <div id="${loadingId}" style="display:flex;gap:10px;align-items:flex-start;">
      <div style="width:28px;height:28px;border-radius:50%;background:#3c1515;color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;">🤖</div>
      <div style="background:#ffffff;border:1px solid #cbbba8;color:#8a7575;padding:10px 14px;border-radius:0 12px 12px 12px;font-size:0.82rem;font-style:italic;">
        <i class="bi bi-cpu me-1 animate-spin"></i> Analyzing your issue details with AI...
      </div>
    </div>`;
  chatWin.insertAdjacentHTML('beforeend', loadingHtml);
  chatWin.scrollTop = chatWin.scrollHeight;

  btnSend.disabled = true;

  try {
    const response = await fetch('../api/ai_chat_reporter.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg })
    });
    const resData = await response.json();
    document.getElementById(loadingId)?.remove();

    if (resData.success && resData.data) {
      currentAiData = resData.data;

      const aiReplyHtml = `
        <div style="display:flex;gap:10px;align-items:flex-start;">
          <div style="width:28px;height:28px;border-radius:50%;background:#3c1515;color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;">🤖</div>
          <div style="background:#ffffff;border:1px solid #cbbba8;color:#2b0d0d;padding:10px 14px;border-radius:0 12px 12px 12px;font-size:0.82rem;line-height:1.5;max-width:85%;">
            I've extracted the ticket details! Please review the structured summary below and click <b>Confirm & Create Ticket</b>.
          </div>
        </div>`;
      chatWin.insertAdjacentHTML('beforeend', aiReplyHtml);

      document.getElementById('aiSummaryTitle').innerText = currentAiData.title;
      document.getElementById('aiSummaryLocation').innerText = currentAiData.location;
      document.getElementById('aiSummaryCategory').innerText = currentAiData.category_name;
      document.getElementById('aiSummaryDescription').innerText = currentAiData.description;
      document.getElementById('aiPriorityBadge').innerText = (currentAiData.priority || 'medium').toUpperCase() + ' PRIORITY';

      document.getElementById('aiSummaryCard').style.display = 'block';
    } else {
      const errHtml = `
        <div style="display:flex;gap:10px;align-items:flex-start;">
          <div style="width:28px;height:28px;border-radius:50%;background:#3c1515;color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;">🤖</div>
          <div style="background:#fff2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:0 12px 12px 12px;font-size:0.82rem;">
            ${resData.error || 'Could not parse issue details. Please try again or use the standard form.'}
          </div>
        </div>`;
      chatWin.insertAdjacentHTML('beforeend', errHtml);
    }
  } catch (err) {
    document.getElementById(loadingId)?.remove();
    console.error(err);
  } finally {
    btnSend.disabled = false;
    chatWin.scrollTop = chatWin.scrollHeight;
  }
}

function confirmAndCreateTicket() {
  if (!currentAiData) return;

  document.getElementById('title').value = currentAiData.title || '';
  document.getElementById('description').value = currentAiData.description || '';
  document.getElementById('location').value = currentAiData.location || '';
  
  if (currentAiData.category_id) {
    document.getElementById('category_id').value = currentAiData.category_id;
  }
  if (currentAiData.priority) {
    document.getElementById('priority').value = currentAiData.priority.toLowerCase();
  }

  const form = document.querySelector('form');
  form.submit();
}

function editAiDetails() {
  if (!currentAiData) return;

  document.getElementById('title').value = currentAiData.title || '';
  document.getElementById('description').value = currentAiData.description || '';
  document.getElementById('location').value = currentAiData.location || '';
  if (currentAiData.category_id) {
    document.getElementById('category_id').value = currentAiData.category_id;
  }
  if (currentAiData.priority) {
    document.getElementById('priority').value = currentAiData.priority.toLowerCase();
  }

  switchReportMode('standard');
}

function escapeHtml(text) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

/* Multi-Language Voice Input & Speech Recognition */
let recognition = null;
let isRecording = false;

function initSpeechRecognition() {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    alert('Web Speech API is not supported in your browser. Please use Chrome, Edge, or Safari.');
    return null;
  }
  const rec = new SpeechRecognition();
  rec.continuous = false;
  rec.interimResults = false;

  rec.onstart = function() {
    isRecording = true;
    const btn = document.getElementById('btnVoiceRecord');
    const icon = document.getElementById('micIcon');
    const text = document.getElementById('micText');
    const status = document.getElementById('voiceStatus');

    btn.style.background = '#dc2626';
    btn.style.color = '#ffffff';
    icon.className = 'bi bi-record-fill animate-pulse';
    icon.style.color = '#ffffff';
    text.innerText = 'Listening...';
    status.innerHTML = '<span style="color:#dc2626;font-weight:600;"><i class="bi bi-soundwave me-1 animate-pulse"></i> Recording live audio...</span>';
  };

  rec.onresult = async function(event) {
    const transcript = event.results[0][0].transcript;
    const langSelect = document.getElementById('voiceLangSelect').value;

    const status = document.getElementById('voiceStatus');
    status.innerHTML = '<span style="color:#b45309;font-weight:600;"><i class="bi bi-cpu me-1 animate-spin"></i> Translating to English & extracting fields...</span>';

    try {
      const response = await fetch('../api/translate_voice_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ raw_text: transcript, language: langSelect })
      });
      const resData = await response.json();

      if (resData.success && resData.data) {
        const data = resData.data;

        // Auto-fill form fields
        document.getElementById('title').value = data.title || transcript;
        document.getElementById('description').value = data.description || data.translated_text || transcript;
        document.getElementById('location').value = data.location || '';
        
        if (data.category_id) {
          document.getElementById('category_id').value = data.category_id;
        }
        if (data.priority) {
          document.getElementById('priority').value = data.priority.toLowerCase();
        }

        status.innerHTML = `<span style="color:#059669;font-weight:600;"><i class="bi bi-check-circle-fill me-1"></i> Auto-filled form! Translated: "${escapeHtml(data.title)}"</span>`;
      } else {
        status.innerHTML = `<span style="color:#dc2626;font-weight:600;">Translation failed: ${resData.error || 'Unknown error'}</span>`;
      }
    } catch (err) {
      console.error(err);
      status.innerHTML = '<span style="color:#dc2626;font-weight:600;">Error processing translation request.</span>';
    } finally {
      resetVoiceBtn();
    }
  };

  rec.onerror = function(event) {
    console.error('Speech recognition error:', event.error);
    const status = document.getElementById('voiceStatus');
    status.innerHTML = `<span style="color:#dc2626;font-weight:600;">Mic Error: ${event.error}</span>`;
    resetVoiceBtn();
  };

  rec.onend = function() {
    if (isRecording) {
      resetVoiceBtn();
    }
  };

  return rec;
}

function resetVoiceBtn() {
  isRecording = false;
  const btn = document.getElementById('btnVoiceRecord');
  const icon = document.getElementById('micIcon');
  const text = document.getElementById('micText');

  if (btn) {
    btn.style.background = '#3c1515';
    btn.style.color = '#ffffff';
    icon.className = 'bi bi-mic-fill';
    icon.style.color = '#cbbba8';
    text.innerText = 'Speak & Auto-Fill';
  }
}

function toggleVoiceRecording() {
  if (!recognition) {
    recognition = initSpeechRecognition();
  }
  if (!recognition) return;

  if (isRecording) {
    recognition.stop();
  } else {
    const selectedLang = document.getElementById('voiceLangSelect').value;
    recognition.lang = (selectedLang === 'kok-IN') ? 'hi-IN' : selectedLang;
    recognition.start();
  }
}

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
