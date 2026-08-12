<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/notification_helper.php';
requireRole('admin');
$u = currentUser();

// === Stats ===
$totalIssues    = $pdo->query("SELECT COUNT(*) FROM issues")->fetchColumn();
$resolvedIssues = $pdo->query("SELECT COUNT(*) FROM issues WHERE status IN ('resolved','closed')")->fetchColumn();
$pendingIssues  = $pdo->query("SELECT COUNT(*) FROM issues WHERE status='pending'")->fetchColumn();
$totalUsers     = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('student','staff')")->fetchColumn();
$resolutionRate = $totalIssues > 0 ? round(($resolvedIssues / $totalIssues) * 100) : 0;

// Issues by category
$catData = $pdo->query("SELECT c.category_name, COUNT(i.issue_id) AS cnt FROM categories c LEFT JOIN issues i ON i.category_id=c.category_id GROUP BY c.category_id ORDER BY cnt DESC")->fetchAll();

// Issues by status
$statusData = $pdo->query("SELECT status, COUNT(*) AS cnt FROM issues GROUP BY status")->fetchAll();

// Issues by priority
$priData = $pdo->query("SELECT priority, COUNT(*) AS cnt FROM issues GROUP BY priority ORDER BY FIELD(priority,'critical','high','medium','low')")->fetchAll();

// Monthly issues (last 6 months)
$monthlyData = $pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') AS month, COUNT(*) AS cnt FROM issues WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY MIN(created_at) ASC")->fetchAll();

// Top reporters
$topReporters = $pdo->query("SELECT u.full_name, u.role, COUNT(i.issue_id) AS cnt FROM users u LEFT JOIN issues i ON i.reported_by=u.user_id GROUP BY u.user_id ORDER BY cnt DESC LIMIT 5")->fetchAll();

$pageTitle='Reports & Analytics'; $pageSubtitle='Issue statistics and system overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Reports – FixMyCampus Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../includes/topbar.php'; ?>
    <div class="page-content">

      <!-- Summary Stats -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px;">
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-card-list"></i></div><div class="stat-value"><?= $totalIssues ?></div><div class="stat-label">Total Issues</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:#34d399;border-color:rgba(16,185,129,.2);background:rgba(16,185,129,.1);"><i class="bi bi-check-circle"></i></div><div class="stat-value"><?= $resolvedIssues ?></div><div class="stat-label">Resolved</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:#fbbf24;border-color:rgba(245,158,11,.2);background:rgba(245,158,11,.1);"><i class="bi bi-clock"></i></div><div class="stat-value"><?= $pendingIssues ?></div><div class="stat-label">Pending</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:#60a5fa;border-color:rgba(59,130,246,.2);background:rgba(59,130,246,.1);"><i class="bi bi-people"></i></div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Active Users</div></div>
        <div class="stat-card"><div class="stat-icon" style="color:#38bdf8;border-color:rgba(56,189,248,.2);background:rgba(56,189,248,.1);"><i class="bi bi-graph-up-arrow"></i></div><div class="stat-value"><?= $resolutionRate ?>%</div><div class="stat-label">Resolution Rate</div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">

        <!-- Issues by Category -->
        <div class="panel">
          <div class="panel-header"><i class="bi bi-pie-chart me-2"></i>Issues by Category</div>
          <div class="panel-body">
            <?php $maxCat = max(array_column($catData,'cnt') ?: [1]);
            foreach($catData as $cd): $pct = $maxCat>0?round(($cd['cnt']/$maxCat)*100):0; ?>
            <div style="margin-bottom:14px;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                <span style="font-size:0.82rem;font-weight:600;"><?= htmlspecialchars($cd['category_name']) ?></span>
                <span style="font-size:0.78rem;color:var(--text-muted);"><?= $cd['cnt'] ?> issue<?= $cd['cnt']!=1?'s':'' ?></span>
              </div>
              <div style="height:8px;background:var(--border-color);border-radius:8px;overflow:hidden;">
                <div style="width:<?= $pct ?>%;height:100%;background:var(--primary);border-radius:8px;"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Issues by Status + Priority -->
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div class="panel">
            <div class="panel-header"><i class="bi bi-bar-chart me-2"></i>By Status</div>
            <div class="panel-body">
              <?php 
              $statusColors2 = ['pending'=>'#f59e0b','in_progress'=>'#8b5cf6','resolved'=>'#10b981','closed'=>'#94a3b8','rejected'=>'#ef4444'];
              $maxStat = max(array_column($statusData,'cnt') ?: [1]);
              foreach($statusData as $sd): $pct=round(($sd['cnt']/$maxStat)*100); $col=$statusColors2[$sd['status']]??'#94a3b8'; ?>
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div style="width:80px;font-size:0.78rem;text-align:right;color:var(--text-muted);"><?= ucwords(str_replace('_',' ',$sd['status'])) ?></div>
                <div style="flex:1;height:8px;background:var(--border-color);border-radius:8px;overflow:hidden;"><div style="width:<?= $pct ?>%;height:100%;background:<?= $col ?>;border-radius:8px;"></div></div>
                <div style="width:24px;font-size:0.78rem;font-weight:700;color:<?= $col ?>;"><?= $sd['cnt'] ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="panel">
            <div class="panel-header"><i class="bi bi-exclamation-diamond me-2"></i>By Priority</div>
            <div class="panel-body">
              <?php 
              $priColors = ['low'=>'#10b981','medium'=>'#6366f1','high'=>'#f59e0b','critical'=>'#ef4444'];
              $maxPri = max(array_column($priData,'cnt') ?: [1]);
              foreach($priData as $pd): $pct=round(($pd['cnt']/$maxPri)*100); $col=$priColors[$pd['priority']]??'#94a3b8'; ?>
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div style="width:60px;font-size:0.78rem;text-align:right;color:var(--text-muted);"><?= ucfirst($pd['priority']) ?></div>
                <div style="flex:1;height:8px;background:var(--border-color);border-radius:8px;overflow:hidden;"><div style="width:<?= $pct ?>%;height:100%;background:<?= $col ?>;border-radius:8px;"></div></div>
                <div style="width:24px;font-size:0.78rem;font-weight:700;color:<?= $col ?>;"><?= $pd['cnt'] ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Reporters + Monthly Trend -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
        <div class="panel">
          <div class="panel-header"><i class="bi bi-person-lines-fill me-2"></i>Top Reporters</div>
          <table class="table-dark-custom">
            <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Issues Reported</th></tr></thead>
            <tbody>
            <?php foreach($topReporters as $idx=>$tr): ?>
            <tr>
              <td style="color:var(--text-muted);"><?= $idx+1 ?></td>
              <td style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($tr['full_name']) ?></td>
              <td><span style="font-size:0.75rem;text-transform:capitalize;color:#ec4899;"><?= $tr['role'] ?></span></td>
              <td><span style="font-weight:700;color:#ec4899;"><?= $tr['cnt'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="panel">
          <div class="panel-header"><i class="bi bi-graph-up me-2"></i>Monthly Trend (Last 6 Months)</div>
          <div class="panel-body">
            <div style="position:relative;height:210px;width:100%;">
              <canvas id="monthlyTrendChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const ctx = document.getElementById('monthlyTrendChart');
  if (ctx) {
    const monthLabels = <?= json_encode(array_column($monthlyData, 'month') ?: ['No Data']) ?>;
    const monthCounts = <?= json_encode(array_column($monthlyData, 'cnt') ?: [0]) ?>;
    
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: monthLabels,
        datasets: [{
          label: 'Issues Reported',
          data: monthCounts,
          backgroundColor: 'rgba(74, 14, 23, 0.85)',
          borderColor: '#4A0E17',
          borderWidth: 1.5,
          borderRadius: 6,
          hoverBackgroundColor: '#7B1E2B'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#4A0E17',
            titleColor: '#FFD8BE',
            bodyColor: '#FFFFFF',
            padding: 10,
            cornerRadius: 6
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              color: '#2b0d0d',
              font: { family: "'DM Sans', sans-serif", size: 11, weight: '600' }
            },
            grid: {
              color: 'rgba(74, 14, 23, 0.08)'
            }
          },
          x: {
            ticks: {
              color: '#2b0d0d',
              font: { family: "'DM Sans', sans-serif", size: 11, weight: '600' }
            },
            grid: {
              display: false
            }
          }
        }
      }
    });
  }
});
</script>
</body>
</html>
