<?php
require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/../config/db.php';
$u=currentUser();
$unread=getUnreadCount($pdo,$u['id']);
$base=BASE_URL;
$role=$u['role'];
$notifUrl=$base.($role==='admin'?'admin':($role==='maintenance'?'maintenance':'reporter')).'/notifications.php';
$searchUrl=$base.($role==='admin'?'admin/issues.php':'reporter/my_issues.php');
$notifs=getNotifications($pdo,$u['id'],5);
?>
<header class="topbar">
  <div class="topbar-left">
    <button class="btn-hamburger" id="menuToggle" onclick="openSidebar()" aria-label="Open menu">
      <i class="bi bi-list" style="color:var(--peach-dim);font-size:1.1rem;"></i>
    </button>
    <div>
      <div class="topbar-title"><?=$pageTitle??'Dashboard'?></div>
      <?php if(!empty($pageSubtitle)):?><div class="topbar-sub"><?=$pageSubtitle?></div><?php endif;?>
    </div>
  </div>
  <div class="topbar-right">
    <button class="search-trigger" onclick="openSearch()" aria-label="Search">
      <i class="bi bi-search" style="font-size:.78rem;"></i>
      <span>Search...</span>
      <kbd>⌘K</kbd>
    </button>
    <div style="position:relative;">
      <button class="notif-btn" id="notifBtn" onclick="toggleNotif()" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <?php if($unread>0):?><span class="notif-badge"><?=$unread>9?'9+':$unread?></span><?php endif;?>
      </button>
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-head">
          <span>Notifications</span>
          <?php if($unread>0):?><a href="<?=$notifUrl?>?mark_all=1">Mark all read</a><?php endif;?>
        </div>
        <div class="notif-list">
          <?php if(empty($notifs)):?>
            <div style="padding:20px;text-align:center;font-size:.76rem;color:var(--peach-dim);">No notifications yet</div>
          <?php else:foreach($notifs as $n):?>
            <a href="<?=$notifUrl?>?read=<?=$n['notification_id']?>" class="notif-item<?=$n['is_read']?'':' unread'?>">
              <div class="notif-icon"><i class="bi bi-<?=$n['notif_type']==='success'?'check-circle':($n['notif_type']==='danger'?'exclamation-circle':($n['notif_type']==='warning'?'exclamation-triangle':'info-circle'))?>"></i></div>
              <div>
                <div class="notif-msg"><?=htmlspecialchars(substr($n['message'],0,80)).(strlen($n['message'])>80?'...':'')?></div>
                <div class="notif-time"><?=timeAgo($n['created_at'])?></div>
              </div>
            </a>
          <?php endforeach;endif;?>
        </div>
        <div class="notif-foot"><a href="<?=$notifUrl?>">View all</a></div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:7px;padding:5px 12px;border:1px solid var(--peach-dimmer);border-radius:var(--r-sm);background:rgba(255,216,190,.08);">
      <div class="user-avatar-sm" style="width:24px;height:24px;font-size:.65rem;"><?=strtoupper(substr($u['name'],0,1))?></div>
      <div class="d-sm-block">
        <div style="font-size:.74rem;font-weight:600;color:var(--peach);line-height:1.2;"><?=htmlspecialchars(explode(' ',$u['name'])[0])?></div>
        <div style="font-size:.63rem;color:var(--peach-dim);text-transform:capitalize;"><?=$role?></div>
      </div>
    </div>
  </div>
</header>
<div class="search-modal" id="searchModal" onclick="closeSearchOnBg(event)">
  <div class="search-box">
    <form method="GET" action="<?=$searchUrl?>">
      <div class="search-input-row">
        <i class="bi bi-search"></i>
        <input type="text" name="search" id="searchInput" placeholder="Search issues by title, location, or reporter..." autocomplete="off">
        <kbd onclick="closeSearch()" style="cursor:pointer;background:rgba(255,216,190,.1);border:1px solid var(--peach-dimmer);border-radius:4px;padding:2px 6px;font-size:.63rem;color:var(--peach-dim);">Esc</kbd>
      </div>
    </form>
    <div class="search-hint">Press Enter to search — results open in Issues list.</div>
  </div>
</div>
<script>
function toggleNotif(){document.getElementById('notifDropdown').classList.toggle('show');}
document.addEventListener('click',function(e){if(!e.target.closest('#notifBtn')&&!e.target.closest('#notifDropdown'))document.getElementById('notifDropdown')?.classList.remove('show');});
function openSearch(){document.getElementById('searchModal').classList.add('open');setTimeout(()=>document.getElementById('searchInput').focus(),50);}
function closeSearch(){document.getElementById('searchModal').classList.remove('open');}
function closeSearchOnBg(e){if(e.target===document.getElementById('searchModal'))closeSearch();}
document.addEventListener('keydown',function(e){if((e.metaKey||e.ctrlKey)&&e.key==='k'){e.preventDefault();openSearch();}if(e.key==='Escape')closeSearch();});
</script>
