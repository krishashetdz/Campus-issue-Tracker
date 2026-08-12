<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth_check.php';

// Redirect if already logged in
if (isLoggedIn()) { redirectToDashboard(); }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']    = $user['user_id'];
                $_SESSION['user_name']  = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['department'] = $user['department'];
                redirectToDashboard();
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        }
    }
}

$error_get = htmlspecialchars($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FixMyCampus – Sign In</title>
<meta name="description" content="Sign in to FixMyCampus — the campus issue tracking platform for students, staff, and facilities teams.">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-split">

    <!-- Left: Hero -->
    <div class="auth-left">
      <div class="auth-wordmark">
        <div class="mark-box">
          <svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M8 1a7 7 0 100 14A7 7 0 008 1zM2 8a6 6 0 1112 0A6 6 0 012 8z"/>
            <path d="M8 5a1 1 0 011 1v1.586l1.707 1.707a1 1 0 01-1.414 1.414L7.586 9H6a1 1 0 010-2h1V6a1 1 0 011-1z"/>
          </svg>
        </div>
        FixMyCampus
      </div>
      <h1 class="auth-hero-title">Report. Track.<br>Resolve.</h1>
      <p class="auth-hero-sub">The campus issue tracking platform — submit maintenance requests, monitor progress, and receive status updates until resolution.</p>

      <div class="auth-feature">
        <i class="bi bi-image"></i>
        <div>
          <div class="auth-feature-title">Photo Evidence</div>
          <div class="auth-feature-sub">Attach images to issues for faster assessment</div>
        </div>
      </div>
      <div class="auth-feature">
        <i class="bi bi-activity"></i>
        <div>
          <div class="auth-feature-title">Live Status Tracking</div>
          <div class="auth-feature-sub">Submitted → Under Review → In Progress → Resolved</div>
        </div>
      </div>
      <div class="auth-feature">
        <i class="bi bi-bar-chart-line"></i>
        <div>
          <div class="auth-feature-title">Admin Analytics</div>
          <div class="auth-feature-sub">Full oversight with reports and resolution metrics</div>
        </div>
      </div>
    </div>

    <!-- Right: Login Card -->
    <div class="auth-card">
      <div class="auth-card-title">Welcome back</div>
      <div class="auth-card-sub">Sign in to your FixMyCampus account</div>

      <?php if ($error): ?>
        <div class="alert-banner alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($error_get): ?>
        <div class="alert-banner alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= $error_get ?></div>
      <?php endif; ?>

      <form method="POST" id="loginForm">
        <input type="hidden" name="action" value="login">
        <div class="mb-3">
          <label class="form-label" for="email">Email address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email" class="form-control" placeholder="you@campus.edu" required autocomplete="email">
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="passwordField">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="passwordField" class="form-control" placeholder="Enter password" required>
            <button type="button" class="eye-toggle" onclick="togglePwd()" aria-label="Toggle password">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn-auth">Sign In</button>
      </form>

      <hr class="auth-divider">
      <p style="text-align:center;font-size:.78rem;color:var(--text-dim);">
        No account? <a href="register.php" style="color:var(--text-muted);font-weight:500;">Create one</a>
      </p>
    </div>

  </div>
</div>
<script>
function togglePwd() {
  const f = document.getElementById('passwordField');
  const i = document.getElementById('eyeIcon');
  if (f.type === 'password') { f.type='text'; i.className='bi bi-eye-slash'; }
  else { f.type='password'; i.className='bi bi-eye'; }
}
</script>
</body>
</html>
