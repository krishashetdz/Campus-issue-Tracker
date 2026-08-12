<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('admin');
$u = currentUser();

$msg = $err = '';

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $did = intval($_GET['delete']);
    if ($did !== $u['id']) {
        $pdo->prepare("DELETE FROM users WHERE user_id=? AND role != 'admin'")->execute([$did]);
        $msg = 'User deleted.';
    } else { $err = 'Cannot delete yourself.'; }
}

$roleFilter = $_GET['role'] ?? '';
$search     = trim($_GET['search'] ?? '');
$where=['1=1']; $params=[];
if ($roleFilter) { $where[]="role=?"; $params[]=$roleFilter; }
if ($search)     { $where[]="(full_name LIKE ? OR email LIKE ? OR department LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; $params[]="%$search%"; }

$sql="SELECT u.*,(SELECT COUNT(*) FROM issues i WHERE i.reported_by=u.user_id) AS issue_count FROM users u WHERE ".implode(' AND ',$where)." ORDER BY u.created_at DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($params);
$users=$stmt->fetchAll();

$pageTitle='Users Management'; $pageSubtitle='View and manage registered users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Users – FixMyCampus Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">
      <?php if($msg): ?><div class="alert-banner alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?></div><?php endif; ?>
      <?php if($err): ?><div class="alert-banner alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Search name, email, department..." value="<?= htmlspecialchars($search) ?>" style="flex:1;min-width:200px;">
        <select name="role">
          <option value="">All Roles</option>
          <?php foreach(['student','staff','admin','maintenance'] as $r): ?>
            <option value="<?= $r ?>" <?= $roleFilter===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-glow" style="padding:8px 18px;">Filter</button>
        <a href="users.php" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;align-self:center;">Clear</a>
      </form>

      <div class="panel">
        <div class="panel-header"><span><i class="bi bi-people me-2"></i>Users (<?= count($users) ?>)</span></div>
        <table class="table-dark-custom">
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Phone</th><th>Issues</th><th>Joined</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach($users as $usr): 
            $roleColors = ['admin'=>'#7c3aed','student'=>'#ec4899','staff'=>'#3b82f6','maintenance'=>'#a78bfa'];
            $rc = $roleColors[$usr['role']] ?? '#94a3b8';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;background:<?= $rc ?>22;border:1px solid <?= $rc ?>44;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.78rem;color:<?= $rc ?>;"><?= strtoupper(substr($usr['full_name'],0,1)) ?></div>
                <span style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($usr['full_name']) ?></span>
              </div>
            </td>
            <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars($usr['email']) ?></td>
            <td><span class="badge" style="background:<?= $rc ?>22;color:<?= $rc ?>;border-radius:6px;padding:4px 10px;"><?= ucfirst($usr['role']) ?></span></td>
            <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars($usr['department']??'—') ?></td>
            <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars($usr['phone']??'—') ?></td>
            <td><span style="font-weight:700;color:var(--text-muted);"><?= $usr['issue_count'] ?></span></td>
            <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M Y',strtotime($usr['created_at'])) ?></td>
            <td>
              <?php if($usr['user_id'] !== $u['id'] && $usr['role'] !== 'admin'): ?>
                <a href="?delete=<?= $usr['user_id'] ?>&<?= http_build_query(array_filter(['role'=>$roleFilter,'search'=>$search])) ?>" class="btn-sm-icon danger" onclick="return confirm('Delete this user?')" title="Delete"><i class="bi bi-trash"></i></a>
              <?php else: ?>
                <span style="font-size:0.75rem;color:var(--text-muted);">Protected</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
