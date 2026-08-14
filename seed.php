<?php
/**
 * FixMyCampus - Database Seeder
 * Populates realistic multi-month campus issue data, photo evidence, SLA timelines, and incident clusters.
 * 
 * Usage: Access via browser at http://localhost/fixmycampus/seed.php or http://localhost/fixmycampus/seed.php?key=secret_seed_123
 */
session_start();
require_once __DIR__ . '/config/db.php';

// Security / Access Check
$secretKey = 'secret_seed_123';
$passedKey = $_GET['key'] ?? '';
$isCli = (php_sapi_name() === 'cli');

if (!$isCli && !empty($secretKey) && !empty($passedKey) && $passedKey !== $secretKey) {
    die('<div style="font-family:sans-serif;padding:30px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;max-width:500px;margin:50px auto;text-align:center;">
        <h2>Access Denied</h2>
        <p>Invalid security key provided. Please pass <code>?key=secret_seed_123</code> in the URL.</p>
    </div>');
}

// 1. Ensure Essential Categories Exist
$defaultCategories = [
    ['Electrical Fault', 'bi-lightning-charge', 'Power failures, flickering lights, damaged sockets, or open wiring.'],
    ['HVAC / Cooling', 'bi-fan', 'AC unit breakdowns, excessive noise, water leaks, or inadequate cooling.'],
    ['Furniture Damage', 'bi-shop', 'Broken desks, cracked chairs, damaged whiteboards, or unhinged doors.'],
    ['IT / Network Issue', 'bi-wifi', 'Campus WiFi downtime, dead Ethernet ports, computer lab PC errors, or projector glitches.'],
    ['Plumbing', 'bi-droplet-fill', 'Leaking taps, clogged washroom drains, broken flush valves, or water shortages.'],
    ['Cleanliness', 'bi-trash3-fill', 'Overflowing dustbins, unclean washrooms, uncleaned hallways, or spillages.'],
    ['Safety & Security', 'bi-shield-lock-fill', 'Broken door locks, malfunctioning CCTV cameras, exposed wiring hazards, or damaged handrails.']
];

foreach ($defaultCategories as $cat) {
    $chk = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
    $chk->execute([$cat[0]]);
    if (!$chk->fetch()) {
        $pdo->prepare("INSERT INTO categories (category_name, icon, description) VALUES (?, ?, ?)")->execute($cat);
    }
}

// Fetch Category Map (Name => ID)
$catRows = $pdo->query("SELECT category_id, category_name FROM categories")->fetchAll();
$catMap = [];
foreach ($catRows as $cr) {
    $catMap[$cr['category_name']] = $cr['category_id'];
}

// 2. Ensure Users Exist (Admin, Staff, Students)
$defaultUsers = [
    ['System Admin', 'admin@fixmycampus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.zA0y65XmO', 'admin', 'IT Services', '9876543210'], // password
    ['Maintenance Tech 1', 'staff1@fixmycampus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.zA0y65XmO', 'maintenance', 'Facilities', '9876543211'],
    ['Maintenance Tech 2', 'staff2@fixmycampus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.zA0y65XmO', 'maintenance', 'Electrical Dept', '9876543212'],
    ['Rahul Sharma', 'student1@fixmycampus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.zA0y65XmO', 'student', 'Computer Science', '9876543213'],
    ['Ananya Roy', 'student2@fixmycampus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.zA0y65XmO', 'student', 'Electronics Dept', '9876543214'],
    ['Priya Nair', 'student3@fixmycampus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.zA0y65XmO', 'student', 'Mechanical Dept', '9876543215']
];

foreach ($defaultUsers as $u) {
    $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $chk->execute([$u[1]]);
    if (!$chk->fetch()) {
        $pdo->prepare("INSERT INTO users (full_name, email, password, role, department, phone) VALUES (?, ?, ?, ?, ?, ?)")->execute($u);
    }
}

// Fetch Users for assignment & reporting
$students = $pdo->query("SELECT user_id FROM users WHERE role = 'student'")->fetchAll(PDO::FETCH_COLUMN);
$staff    = $pdo->query("SELECT user_id FROM users WHERE role IN ('maintenance','staff','admin')")->fetchAll(PDO::FETCH_COLUMN);
$adminId  = $staff[0] ?? 1;

if (empty($students)) $students = [1];
if (empty($staff)) $staff = [1];

// Photo Evidence Stock URLs mapped by Category
$categoryImages = [
    'Electrical Fault'   => ['https://images.unsplash.com/photo-1544724569-5f546fd6f2b5?w=600'],
    'HVAC / Cooling'     => ['https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=600'],
    'Furniture Damage'   => ['https://images.unsplash.com/photo-1580481072645-022f9a6d1290?w=600'],
    'IT / Network Issue' => ['https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=600'],
    'Plumbing'           => ['https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=600'],
    'Cleanliness'        => ['https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=600'],
    'Safety & Security'  => ['https://images.unsplash.com/photo-1557597774-9d273605dfa9?w=600']
];

$resolutionImages = [
    'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=600',
    'https://images.unsplash.com/photo-1581092335397-9583fe92d232?w=600'
];

// Seed Ticket Dataset Definition across 3 to 6 months
$issuesToSeed = [
    // Multi-month historical data (90-180 days ago)
    ['title' => 'Projector HDMI port disconnected in Auditorium 1', 'cat' => 'IT / Network Issue', 'loc' => 'Auditorium Block - 1st Floor', 'prio' => 'medium', 'stat' => 'resolved', 'days_ago' => 150],
    ['title' => 'Water leakage near Block A washrooms', 'cat' => 'Plumbing', 'loc' => 'Block A - 2nd Floor Washroom', 'prio' => 'high', 'stat' => 'resolved', 'days_ago' => 140],
    ['title' => 'Main entrance AC unit blowing warm air', 'cat' => 'HVAC / Cooling', 'loc' => 'Admin Block - Ground Floor', 'prio' => 'critical', 'stat' => 'resolved', 'days_ago' => 120],
    ['title' => 'Broken bench legs in Classroom C3', 'cat' => 'Furniture Damage', 'loc' => 'Classroom C3', 'prio' => 'low', 'stat' => 'resolved', 'days_ago' => 110],
    ['title' => 'Uncleaned trash bins near Canteen patio', 'cat' => 'Cleanliness', 'loc' => 'Student Canteen Area', 'prio' => 'low', 'stat' => 'resolved', 'days_ago' => 95],

    // Mid-range historical data (30-90 days ago)
    ['title' => 'Ceiling fan making screeching noise in Lab 2', 'cat' => 'Electrical Fault', 'loc' => 'Block B - Electrical Lab 2', 'prio' => 'medium', 'stat' => 'resolved', 'days_ago' => 85],
    ['title' => 'CCTV camera angle misaligned near Library exit', 'cat' => 'Safety & Security', 'loc' => 'Central Library - Rear Door', 'prio' => 'high', 'stat' => 'resolved', 'days_ago' => 70],
    ['title' => 'Wi-Fi Access Point 4 dropping connections intermittently', 'cat' => 'IT / Network Issue', 'loc' => 'Hostel Block 2 - Floor 3', 'prio' => 'high', 'stat' => 'resolved', 'days_ago' => 60],
    ['title' => 'Flush valve leaking continuously in Boys Washroom', 'cat' => 'Plumbing', 'loc' => 'Block C - 1st Floor Washroom', 'prio' => 'medium', 'stat' => 'resolved', 'days_ago' => 45],
    ['title' => 'Door lock latch jammed in Seminar Hall 2', 'cat' => 'Safety & Security', 'loc' => 'Block B - Seminar Hall 2', 'prio' => 'high', 'stat' => 'resolved', 'days_ago' => 35],

    // Active Recent Issues (0-30 days ago)
    ['title' => 'AC unit stopped cooling completely in Computer Lab 3', 'cat' => 'HVAC / Cooling', 'loc' => 'Block A – Computer Lab 3', 'prio' => 'critical', 'stat' => 'in_progress', 'days_ago' => 12],
    ['title' => 'Flickering LED tube light near Board Room', 'cat' => 'Electrical Fault', 'loc' => 'Block A - 3rd Floor Corridor', 'prio' => 'medium', 'stat' => 'in_progress', 'days_ago' => 8],
    ['title' => 'Broken whiteboard frame in Classroom C8', 'cat' => 'Furniture Damage', 'loc' => 'Classroom C8', 'prio' => 'low', 'stat' => 'pending', 'days_ago' => 5],
    ['title' => 'Main Router offline in Mechanical Workshop', 'cat' => 'IT / Network Issue', 'loc' => 'Mechanical Workshop - Block D', 'prio' => 'critical', 'stat' => 'pending', 'days_ago' => 3],
    ['title' => 'Tap handle broken off in Girls Washroom', 'cat' => 'Plumbing', 'loc' => 'Block A - Ground Floor Washroom', 'prio' => 'high', 'stat' => 'in_progress', 'days_ago' => 2],

    // CLUSTER PARENT 1: Tubelight issue in Classroom C8
    ['title' => 'Tubelight flickering violently in Classroom C8', 'cat' => 'Electrical Fault', 'loc' => 'Classroom C8', 'prio' => 'high', 'stat' => 'in_progress', 'days_ago' => 1, 'is_cluster_parent' => true],
    
    // CLUSTER PARENT 2: WiFi down in Library 2nd Floor
    ['title' => 'Library 2nd Floor WiFi completely unreachable', 'cat' => 'IT / Network Issue', 'loc' => 'Central Library - 2nd Floor', 'prio' => 'critical', 'stat' => 'pending', 'days_ago' => 1, 'is_cluster_parent' => true]
];

$seededIssues = 0;
$seededImages = 0;
$seededClusters = 0;
$parentMap = [];

foreach ($issuesToSeed as $item) {
    $catId = $catMap[$item['cat']] ?? 1;
    $reporterId = $students[array_rand($students)];
    $assigneeId = ($item['stat'] !== 'pending') ? $staff[array_rand($staff)] : null;
    
    $createdTime = date('Y-m-d H:i:s', strtotime("-{$item['days_ago']} days -" . rand(1, 10) . " hours"));
    $updatedTime = date('Y-m-d H:i:s', strtotime($createdTime . " +" . rand(1, 3) . " days"));

    $adminRemark = null;
    if ($item['stat'] === 'resolved') {
        $adminRemark = "Inspection completed and component replaced. Fixed verified by campus maintenance technician.";
    } elseif ($item['stat'] === 'in_progress') {
        $adminRemark = "Assigned to maintenance technician. Replacement parts ordered.";
    }

    $isParentFlag = !empty($item['is_cluster_parent']) ? 1 : 0;
    $affectedCount = $isParentFlag ? 3 : 1;

    $stmt = $pdo->prepare("INSERT INTO issues (title, description, category_id, location, priority, status, reported_by, assigned_to, is_parent, affected_count, admin_remark, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $item['title'],
        "Detailed report: Student reported issue " . strtolower($item['title']) . " at location " . $item['loc'] . ". Please resolve at earliest.",
        $catId,
        $item['loc'],
        $item['prio'],
        $item['stat'],
        $reporterId,
        $assigneeId,
        $isParentFlag,
        $affectedCount,
        $adminRemark,
        $createdTime,
        $updatedTime
    ]);
    
    $issueId = $pdo->lastInsertId();
    $seededIssues++;

    // Track Parents for Cluster Child insertion
    if ($isParentFlag) {
        $parentMap[$item['loc']] = $issueId;
        $seededClusters++;
    }

    // Insert Primary Image
    if (!empty($categoryImages[$item['cat']])) {
        $imgUrl = $categoryImages[$item['cat']][0];
        $pdo->prepare("INSERT INTO issue_images (issue_id, image_path, uploaded_at) VALUES (?, ?, ?)")->execute([$issueId, $imgUrl, $createdTime]);
        $seededImages++;
    }

    // Insert Status History Timeline
    $pdo->prepare("INSERT INTO status_history (issue_id, changed_by, old_status, new_status, remarks, changed_at) VALUES (?, ?, ?, ?, ?, ?)")->execute([
        $issueId, $reporterId, '', 'pending', 'Initial report submitted by student', $createdTime
    ]);

    if ($item['stat'] !== 'pending') {
        $pdo->prepare("INSERT INTO status_history (issue_id, changed_by, old_status, new_status, remarks, changed_at) VALUES (?, ?, ?, ?, ?, ?)")->execute([
            $issueId, $adminId, 'pending', $item['stat'], $adminRemark ?: 'Status updated by admin', $updatedTime
        ]);
    }
}

// 3. Seed Child Duplicate Reports (Incident Clustering)
$childDuplicates = [
    // Children for Classroom C8 Tubelight cluster
    [
        'title' => 'Tube light not working and buzzing in C8 class',
        'cat' => 'Electrical Fault',
        'loc' => 'Classroom C8',
        'prio' => 'high',
        'parent_loc' => 'Classroom C8',
        'days_ago' => 1
    ],
    [
        'title' => 'No light in C8 lecture hall',
        'cat' => 'Electrical Fault',
        'loc' => 'Classroom C8',
        'prio' => 'medium',
        'parent_loc' => 'Classroom C8',
        'days_ago' => 1
    ],
    // Child for Library 2nd Floor WiFi cluster
    [
        'title' => 'No Internet access on 2nd Floor Library WiFi',
        'cat' => 'IT / Network Issue',
        'loc' => 'Central Library - 2nd Floor',
        'prio' => 'critical',
        'parent_loc' => 'Central Library - 2nd Floor',
        'days_ago' => 1
    ]
];

foreach ($childDuplicates as $child) {
    $parentIssueId = $parentMap[$child['parent_loc']] ?? null;
    if ($parentIssueId) {
        $catId = $catMap[$child['cat']] ?? 1;
        $reporterId = $students[array_rand($students)];
        $createdTime = date('Y-m-d H:i:s', strtotime("-{$child['days_ago']} days -3 hours"));

        $stmt = $pdo->prepare("INSERT INTO issues (title, description, category_id, location, priority, status, reported_by, parent_id, is_parent, affected_count, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 1, ?, ?)");
        $stmt->execute([
            $child['title'],
            "Duplicate student report linked to main parent incident #{$parentIssueId}.",
            $catId,
            $child['loc'],
            $child['prio'],
            'in_progress',
            $reporterId,
            $parentIssueId,
            $createdTime,
            $createdTime
        ]);

        $childId = $pdo->lastInsertId();
        $seededIssues++;

        $pdo->prepare("INSERT INTO status_history (issue_id, changed_by, old_status, new_status, remarks, changed_at) VALUES (?, ?, ?, ?, ?, ?)")->execute([
            $childId, $reporterId, '', 'pending', "Duplicate report linked to Parent Incident #{$parentIssueId}", $createdTime
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Database Seeder – FixMyCampus</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background:#f8f6f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#ffffff;border:1px solid #cbbba8;border-radius:16px;width:100%;max-width:560px;padding:28px;box-shadow:0 10px 30px rgba(60,21,21,0.08);">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;border-bottom:1px solid rgba(74,14,23,0.12);padding-bottom:16px;">
      <div style="width:44px;height:44px;border-radius:12px;background:#3c1515;color:#cbbba8;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
        <i class="bi bi-database-check"></i>
      </div>
      <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:#3c1515;margin:0;">Database Successfully Seeded!</h2>
        <div style="font-size:0.8rem;color:#8a7575;">Multi-month analytics, stock images & incident clusters populated</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;">
      <div style="background:#fcfbfa;border:1px solid #e8e2d8;border-radius:10px;padding:14px;text-align:center;">
        <div style="font-size:1.5rem;font-weight:800;color:#3c1515;"><?= $seededIssues ?></div>
        <div style="font-size:0.75rem;color:#8a7575;font-weight:600;">Total Seeded Issues</div>
      </div>
      <div style="background:#fcfbfa;border:1px solid #e8e2d8;border-radius:10px;padding:14px;text-align:center;">
        <div style="font-size:1.5rem;font-weight:800;color:#b45309;"><?= count($catMap) ?></div>
        <div style="font-size:0.75rem;color:#8a7575;font-weight:600;">Active Categories</div>
      </div>
      <div style="background:#fcfbfa;border:1px solid #e8e2d8;border-radius:10px;padding:14px;text-align:center;">
        <div style="font-size:1.5rem;font-weight:800;color:#059669;"><?= $seededImages ?></div>
        <div style="font-size:0.75rem;color:#8a7575;font-weight:600;">Photo Evidence Assets</div>
      </div>
      <div style="background:#fcfbfa;border:1px solid #e8e2d8;border-radius:10px;padding:14px;text-align:center;">
        <div style="font-size:1.5rem;font-weight:800;color:#7c3aed;"><?= $seededClusters ?></div>
        <div style="font-size:0.75rem;color:#8a7575;font-weight:600;">Incident Clusters</div>
      </div>
    </div>

    <div style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);border-radius:10px;padding:12px 14px;margin-bottom:24px;font-size:0.8rem;color:#1e40af;display:flex;align-items:center;gap:8px;">
      <i class="bi bi-info-circle-fill" style="font-size:1.1rem;flex-shrink:0;"></i>
      <div><b>Timestamps Spread:</b> Seeded records cover the last <b>180 days (3–6 months)</b> for natural chart analytics.</div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <a href="admin/dashboard.php" style="flex:1;background:#3c1515;color:#ffffff;text-align:center;padding:10px 16px;border-radius:8px;font-size:0.85rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
        <i class="bi bi-speedometer2" style="color:#cbbba8;"></i> Admin Dashboard
      </a>
      <a href="admin/issues.php" style="flex:1;background:#ffffff;border:1px solid #cbbba8;color:#3c1515;text-align:center;padding:10px 16px;border-radius:8px;font-size:0.85rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
        <i class="bi bi-card-list"></i> View All Issues
      </a>
    </div>
  </div>
</body>
</html>
