<?php
/**
 * Shared topbar include
 * Variables expected: $pageTitle, $pageSubtitle (optional)
 */
require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/../config/db.php';
$u = currentUser();
$unread = getUnreadCount($pdo, $u['id']);
$base = BASE_URL;
$notifUrl = $base . ($u['role'] === 'admin' ? 'admin' : ($u['role'] === 'maintenance' ? 'maintenance' : 'reporter')) . '/notifications.php';
$notifs = getNotifications($pdo, $u['id'], 5);
// Search destination based on role
$searchUrl = $base . ($u['role'] === 'admin' ? 'admin/issues.php' : 'reporter/my_issues.php');
?>
<header class="topbar">
  <div class="topbar-left">
    <!-- Mobile hamburger -->
    <button class="btn-hamburger" id="menuToggle" onclick="openSidebar()" aria-label="Open menu">
      <i class="bi bi-list"></i>
    </button>
    <div>
      <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
      <?php if(!empty($pageSubtitle)): ?>
        <div class="topbar-sub"><?= $pageSubtitle ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="topbar-right">
    <!-- Search trigger -->
    <button class="search-trigger" id="searchTriggerBtn" onclick="openSearch()" aria-label="Search">
      <i class="bi bi-search"></i>
      <span>Search...</span>
      <kbd>⌘K</kbd>
    </button>

    <!-- Notification Bell -->
    <div style="position:relative;">
      <button class="notif-btn" id="notifBtn" onclick="toggleNotif()" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <?php if($unread > 0): ?>
          <span class="notif-badge"><?= $unread > 9 ? '9+' : $unread ?></span>
        <?php endif; ?>
      </button>
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-head">
          <span>Notifications</span>
          <?php if($unread>0): ?>
            <a href="<?= $notifUrl ?>?mark_all=1">Mark all read</a>
          <?php endif; ?>
        </div>
        <div class="notif-list">
          <?php if(empty($notifs)): ?>
            <div style="padding:20px;text-align:center;font-size:.78rem;color:var(--text-dim);">No notifications yet</div>
          <?php else: foreach($notifs as $n): ?>
            <a href="<?= $notifUrl ?>?read=<?= $n['notification_id'] ?>" class="notif-item <?= $n['is_read']?'':'unread' ?>">
              <div class="notif-icon">
                <i class="bi <?= $n['notif_type']==='success'?'bi-check-circle':($n['notif_type']==='danger'?'bi-exclamation-circle':($n['notif_type']==='warning'?'bi-bell':'bi-info-circle')) ?>"></i>
              </div>
              <div>
                <div class="notif-msg"><?= htmlspecialchars(substr($n['message'],0,80)) . (strlen($n['message'])>80?'...':'') ?></div>
                <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
              </div>
            </a>
          <?php endforeach; endif; ?>
        </div>
        <div class="notif-foot">
          <a href="<?= $notifUrl ?>">View all notifications</a>
        </div>
      </div>
    </div>

    <!-- User badge -->
    <div style="display:flex;align-items:center;gap:6px;padding:4px 10px;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg-raised);">
      <div class="user-avatar-sm" style="width:24px;height:24px;font-size:.68rem;"><?= strtoupper(substr($u['name'],0,1)) ?></div>
      <div class="d-sm-block">
        <div style="font-size:.75rem;font-weight:500;color:var(--text);line-height:1.2;"><?= htmlspecialchars(explode(' ',$u['name'])[0]) ?></div>
        <div style="font-size:.65rem;color:var(--text-dim);text-transform:capitalize;"><?= $u['role'] ?></div>
      </div>
    </div>
  </div>
</header>

<!-- Global Search Modal -->
<div class="search-modal" id="searchModal" onclick="closeSearchOnBg(event)">
  <div class="search-box">
    <form method="GET" action="<?= $searchUrl ?>">
      <div class="search-input-row">
        <i class="bi bi-search"></i>
        <input type="text" name="search" id="searchInput" placeholder="Search issues by title, location, or reporter..." autocomplete="off" autofocus>
        <kbd style="background:var(--bg-raised);border:1px solid var(--border-sub);border-radius:3px;padding:2px 5px;font-size:.65rem;color:var(--text-dim);cursor:pointer;" onclick="closeSearch()">Esc</kbd>
      </div>
    </form>
    <div class="search-hint">Press Enter to search — results open in Issues list.</div>
  </div>
</div>

<script>
// Notification toggle
function toggleNotif() {
  document.getElementById('notifDropdown').classList.toggle('show');
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('#notifBtn') && !e.target.closest('#notifDropdown')) {
    document.getElementById('notifDropdown')?.classList.remove('show');
  }
});

// Search modal
function openSearch()  { document.getElementById('searchModal').classList.add('open');  setTimeout(()=>document.getElementById('searchInput').focus(),50); }
function closeSearch() { document.getElementById('searchModal').classList.remove('open'); }
function closeSearchOnBg(e) { if(e.target === document.getElementById('searchModal')) closeSearch(); }
document.addEventListener('keydown', function(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
  if (e.key === 'Escape') closeSearch();
});
</script>
