<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth_check.php';
if(isLoggedIn()){redirectToDashboard();}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='login'){
  $email=trim($_POST['email']??'');$password=$_POST['password']??'';
  if(empty($email)||empty($password)){$error='Please fill in all fields.';}
  else{
    $stmt=$pdo->prepare("SELECT * FROM users WHERE email=?");$stmt->execute([$email]);$user=$stmt->fetch();
    if($user&&password_verify($password,$user['password'])){
      $_SESSION['user_id']=$user['user_id'];$_SESSION['user_name']=$user['full_name'];
      $_SESSION['user_email']=$user['email'];$_SESSION['role']=$user['role'];
      $_SESSION['department']=$user['department'];redirectToDashboard();
    }else{$error='Invalid email or password.';}
  }
}
$error_get=htmlspecialchars($_GET['error']??'');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>FixMyCampus — Sign In</title>
<meta name="description" content="Sign in to FixMyCampus — the campus issue tracking platform.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#D2C4B1;--burg:#4A0E17;--burg2:#7B1E2B;--cream:#EAE0D3;--cream2:#F5F0EB;--r:24px;--g:12px}
html,body{min-height:100vh;background:var(--bg);font-family:'DM Sans',sans-serif;-webkit-font-smoothing:antialiased}
.nav{display:flex;align-items:center;justify-content:space-between;background:var(--burg);border-radius:var(--r);padding:13px 22px;margin:12px 12px 0;gap:12px}
.nav-brand{font-family:'Playfair Display',serif;font-size:1.05rem;color:#fff;letter-spacing:-.01em;text-decoration:none}
.nav-links{display:flex;align-items:center;gap:6px}
.nav-link{font-size:.72rem;font-weight:500;color:rgba(255,255,255,.6);text-decoration:none;padding:5px 12px;border-radius:20px;border:1px solid rgba(255,255,255,.15);transition:all .18s}
.nav-link:hover{background:rgba(255,255,255,.12);color:#fff}
.nav-icon{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.1);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center}
.nav-icon svg{width:15px;height:15px;stroke:#fff;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round}
.bento{display:grid;grid-template-columns:5fr 7fr;grid-template-rows:auto auto;gap:var(--g);padding:var(--g) 12px 20px;max-width:1280px;margin:0 auto}
.card{border-radius:var(--r);overflow:hidden;position:relative}
.c-burg{background:var(--burg);color:#fff}
.c-cream{background:var(--cream);color:var(--burg)}
.c-light{background:var(--cream2);color:var(--burg)}
/* BRAND */
.g-brand{grid-column:1/2;grid-row:1/2;min-height:380px}
.brand-inner{padding:2.5rem 2.2rem;height:100%;display:flex;flex-direction:column;justify-content:space-between}
.brand-tag{font-size:.63rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.45);margin-bottom:1.25rem}
.brand-hero{font-family:'Playfair Display',serif;font-size:clamp(2.4rem,3vw,3.4rem);font-weight:700;line-height:1.06;letter-spacing:-.03em;color:#fff;margin-bottom:1.25rem}
.brand-hero span{display:block}
.brand-dot{color:rgba(255,255,255,.3)}
.brand-sub{font-size:.81rem;color:rgba(255,255,255,.6);line-height:1.75;margin-bottom:1.75rem}
.brand-pill{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.16);border-radius:20px;padding:7px 15px;font-size:.71rem;color:rgba(255,255,255,.75)}
.brand-pill-dot{width:7px;height:7px;border-radius:50%;background:#22c55e;flex-shrink:0}
/* LOGIN */
.g-login{grid-column:2/3;grid-row:1/2}
.login-inner{padding:2.2rem;height:100%;display:flex;flex-direction:column;justify-content:center}
.login-eyebrow{font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:.6rem}
.login-title{font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;color:#fff;letter-spacing:-.02em;margin-bottom:.2rem;line-height:1.2}
.login-sub{font-size:.77rem;color:rgba(255,255,255,.5);margin-bottom:1.4rem}
.lform{display:flex;flex-direction:column;gap:.7rem}
.lgrp{display:flex;flex-direction:column;gap:.28rem}
.llabel{font-size:.69rem;font-weight:500;color:rgba(255,255,255,.5)}
.lwrap{position:relative;display:flex;align-items:center}
.licon{position:absolute;left:12px;pointer-events:none;color:rgba(255,255,255,.28);display:flex;align-items:center}
.licon svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round}
.linput{width:100%;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.11);border-radius:10px;padding:10px 14px 10px 35px;font-family:'DM Sans',sans-serif;font-size:.84rem;color:#fff;outline:none;transition:border-color .18s,box-shadow .18s}
.linput::placeholder{color:rgba(255,255,255,.22)}
.linput:focus{border-color:rgba(255,255,255,.32);box-shadow:0 0 0 3px rgba(255,255,255,.05)}
.leye{position:absolute;right:10px;background:none;border:none;color:rgba(255,255,255,.28);cursor:pointer;display:flex;align-items:center;padding:3px;transition:color .15s}
.leye:hover{color:rgba(255,255,255,.6)}
.leye svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round}
.ldiv{height:1px;background:rgba(255,255,255,.1);margin:.4rem 0}
.lbtn{width:100%;padding:11px;background:var(--cream2);border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.84rem;font-weight:700;color:var(--burg);cursor:pointer;transition:background .18s,transform .16s}
.lbtn:hover{background:#fff;transform:translateY(-1px)}
.lfoot{text-align:center;font-size:.71rem;color:rgba(255,255,255,.38);margin-top:.5rem}
.lfoot a{color:rgba(255,255,255,.6);font-weight:500;text-decoration:none}
.lfoot a:hover{color:#fff}
.lalert{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;font-size:.75rem;margin-bottom:.6rem;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
.lalert svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}
/* FEATURES */
.g-feats{grid-column:1/3;grid-row:2/3;display:grid;grid-template-columns:repeat(3,1fr);gap:var(--g)}
.feat{background:var(--cream);border-radius:var(--r);padding:20px;display:flex;flex-direction:column;gap:9px}
.feat-icon{width:36px;height:36px;border-radius:9px;background:var(--burg);display:flex;align-items:center;justify-content:center}
.feat-icon svg{width:17px;height:17px;stroke:#fff;fill:none;stroke-width:1.75;stroke-linecap:round;stroke-linejoin:round}
.feat-title{font-size:.82rem;font-weight:700;color:var(--burg)}
.feat-desc{font-size:.73rem;color:var(--burg2);line-height:1.6}
/* FOOT */
.g-foot{grid-column:1/3;grid-row:3/4}
.foot-inner{padding:0 22px;height:100%;display:flex;align-items:center;justify-content:space-between;min-height:56px}
.foot-l{font-size:.72rem;color:rgba(255,255,255,.45)}
.foot-links{display:flex;gap:14px}
.foot-links a{font-size:.71rem;color:rgba(255,255,255,.42);text-decoration:none;transition:color .15s}
.foot-links a:hover{color:rgba(255,255,255,.8)}
@media(max-width:860px){
  .bento{grid-template-columns:1fr}
  .g-brand,.g-login{grid-column:1/2}
  .g-brand{grid-row:auto;min-height:260px}
  .g-login{grid-row:auto}
  .g-feats{grid-column:1/2;grid-template-columns:1fr 1fr}
  .g-feats .feat:last-child{grid-column:1/3}
  .g-foot{grid-column:1/2}
}
@media(max-width:520px){
  .nav{margin:8px;padding:11px 16px}
  .nav-links{display:none}
  .bento{padding:8px 8px 16px}
  .g-feats{grid-template-columns:1fr}
  .g-feats .feat:last-child{grid-column:auto}
  .brand-inner,.login-inner{padding:1.75rem 1.5rem}
}
</style>
</head>
<body>
<nav class="nav">
  <a href="#" class="nav-brand">FixMyCampus</a>
  <div class="nav-links">
    <a href="#" class="nav-link">Home</a>
    <a href="register.php" class="nav-link">Register</a>
    <a href="#features" class="nav-link">Features</a>
    <a href="#" class="nav-link">Help</a>
  </div>
  <button class="nav-icon" aria-label="Search">
    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  </button>
</nav>
<main class="bento">
  <!-- BRAND -->
  <div class="card c-burg g-brand">
    <div class="brand-inner">
      <div>
        <div class="brand-tag">Campus Issue Tracker</div>
        <h1 class="brand-hero">
          <span>Report<span class="brand-dot">.</span></span>
          <span>Track<span class="brand-dot">.</span></span>
          <span>Resolve<span class="brand-dot">.</span></span>
        </h1>
        <p class="brand-sub">Submit maintenance requests, monitor progress in real time, and receive status updates until full resolution — built for students, staff, and facilities teams.</p>
      </div>
      <div class="brand-pill"><span class="brand-pill-dot"></span>Platform available now</div>
    </div>
  </div>
  <!-- LOGIN -->
  <div class="card c-burg g-login">
    <div class="login-inner">
      <div class="login-eyebrow">Secure Access</div>
      <div class="login-title">Welcome back</div>
      <div class="login-sub">Sign in to your FixMyCampus account</div>
      <?php if($error): ?><div class="lalert"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?=htmlspecialchars($error)?></div><?php endif; ?>
      <?php if($error_get): ?><div class="lalert"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?=$error_get?></div><?php endif; ?>
      <form method="POST" class="lform" id="lf" novalidate>
        <input type="hidden" name="action" value="login">
        <div class="lgrp">
          <label class="llabel" for="lemail">Email address</label>
          <div class="lwrap">
            <span class="licon"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
            <input type="email" id="lemail" name="email" class="linput" placeholder="you@campus.edu" required autocomplete="email" value="<?=htmlspecialchars($_POST['email']??'')?>">
          </div>
        </div>
        <div class="lgrp">
          <label class="llabel" for="lpwd">Password</label>
          <div class="lwrap">
            <span class="licon"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
            <input type="password" id="lpwd" name="password" class="linput" placeholder="Enter your password" required autocomplete="current-password" style="padding-right:36px">
            <button type="button" class="leye" id="eyeBtn" aria-label="Toggle password">
              <svg id="eyeO" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg id="eyeC" viewBox="0 0 24 24" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
        <div class="ldiv"></div>
        <button type="submit" class="lbtn" id="sbtn">Sign In</button>
        <p class="lfoot">No account? <a href="register.php">Create one</a></p>
      </form>
    </div>
  </div>
  <!-- FEATURES -->
  <div class="g-feats" id="features">
    <div class="feat">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
      <div class="feat-title">Photo Evidence</div>
      <div class="feat-desc">Attach images directly to issues for faster assessment and accurate resolution by facilities teams.</div>
    </div>
    <div class="feat">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
      <div class="feat-title">Live Status Tracking</div>
      <div class="feat-desc">Follow every issue through Submitted → Under Review → In Progress → Resolved in real time.</div>
    </div>
    <div class="feat">
      <div class="feat-icon"><svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
      <div class="feat-title">Admin Analytics</div>
      <div class="feat-desc">Complete oversight with filterable dashboards, priority queues, staff dispatch, and resolution metrics.</div>
    </div>
  </div>
  <!-- FOOTER -->
  <div class="card c-burg g-foot">
    <div class="foot-inner">
      <span class="foot-l">&copy; <?=date('Y')?> FixMyCampus. All rights reserved.</span>
      <div class="foot-links"><a href="#">Privacy</a><a href="#">Terms</a><a href="register.php">Register</a><a href="#">Help</a></div>
    </div>
  </div>
</main>
<script>
const pwd=document.getElementById('lpwd'),eyeBtn=document.getElementById('eyeBtn'),eyeO=document.getElementById('eyeO'),eyeC=document.getElementById('eyeC');
eyeBtn.addEventListener('click',()=>{const h=pwd.type==='password';pwd.type=h?'text':'password';eyeO.style.display=h?'none':'';eyeC.style.display=h?'':'none';});
document.getElementById('lf').addEventListener('submit',()=>{const b=document.getElementById('sbtn');b.disabled=true;b.textContent='Signing in…';b.style.opacity='.7';});
</script>
</body>
</html>
