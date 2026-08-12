<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth_check.php';

if (isLoggedIn()) { redirectToDashboard(); }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $role       = $_POST['role'] ?? 'student';
    $department = trim($_POST['department'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($department)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['student','staff'])) {
        $error = 'Invalid role selected.';
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, department, phone) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $email, $hashed, $role, $department, $phone]);
            $success = 'Account created successfully! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account – FixMyCampus</title>
<meta name="description" content="Create a FixMyCampus account to start reporting and tracking campus issues.">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card" style="flex:0 0 460px;max-width:460px;">
    <div class="auth-wordmark" style="margin-bottom:16px;">
      <div class="mark-box">
        <svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
          <path d="M8 1a7 7 0 100 14A7 7 0 008 1zM2 8a6 6 0 1112 0A6 6 0 012 8z"/>
          <path d="M8 5a1 1 0 011 1v1.586l1.707 1.707a1 1 0 01-1.414 1.414L7.586 9H6a1 1 0 010-2h1V6a1 1 0 011-1z"/>
        </svg>
      </div>
      FixMyCampus
    </div>
    <div class="auth-card-title">Create your account</div>
    <div class="auth-card-sub">Join and start reporting campus issues today</div>

    <?php if ($error): ?>
      <div class="alert-banner alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert-banner alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
        <a href="index.php" style="color:inherit;font-weight:600;margin-left:6px;">Sign In</a>
      </div>
    <?php endif; ?>

    <form method="POST" id="regForm">
      <div class="mb-3">
        <label class="form-label" for="full_name">Full Name *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" id="full_name" name="full_name" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" placeholder="John Doe" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="reg_email">Email Address *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" id="reg_email" name="email" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" placeholder="you@campus.edu" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label" for="role">Role *</label>
          <select id="role" name="role" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700">
            <option value="student" <?= ($_POST['role']??'student')==='student'?'selected':'' ?>>Student</option>
            <option value="staff"   <?= ($_POST['role']??'')==='staff'?'selected':'' ?>>Staff</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="department">Department *</label>
          <input type="text" id="department" name="department" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" placeholder="e.g. Computer Science" required value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="phone">Phone Number</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-phone"></i></span>
          <input type="tel" id="phone" name="phone" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" placeholder="9876543210" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
      </div>
      <div class="row mb-4">
        <div class="col-md-6">
          <label class="form-label" for="password">Password *</label>
          <input type="password" id="password" name="password" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" placeholder="Min. 6 characters" required>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="confirm_password">Confirm Password *</label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-control bg-[#f8f6f0] border border-[#d4c8b8] text-[#2b0d0d] placeholder-[#8a7575] text-xs rounded-md px-3 py-2 focus:outline-none focus:border-amber-700 focus:ring-1 focus:ring-amber-700" placeholder="Repeat password" required>
        </div>
      </div>
      <button type="submit" class="bg-zinc-100 text-zinc-950 hover:bg-zinc-200 font-sans text-xs font-semibold px-3 py-2 rounded-md transition-colors shadow-none focus:outline-none focus:ring-1 focus:ring-zinc-400 w-full">Create Account</button>
    </form>

    <hr class="auth-divider">
    <p style="text-align:center;font-size:.78rem;color:var(--text-dim);">
      Already have an account? <a href="index.php" style="color:var(--text-muted);font-weight:500;">Sign In</a>
    </p>
  </div>
</div>
</body>
</html>
