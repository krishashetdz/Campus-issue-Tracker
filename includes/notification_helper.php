<?php
/**
 * FixMyCampus - Notification Helper
 * Call these functions to create notifications in the DB
 */
require_once __DIR__ . '/../config/db.php';

function sendNotification($pdo, $user_id, $issue_id, $message, $type = 'info') {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, issue_id, message, notif_type) VALUES (?,?,?,?)");
    $stmt->execute([$user_id, $issue_id, $message, $type]);
}

function getUnreadCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function getNotifications($pdo, $user_id, $limit = 10) {
    $stmt = $pdo->prepare("SELECT n.*, i.title as issue_title FROM notifications n LEFT JOIN issues i ON n.issue_id = i.issue_id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

function markAllRead($pdo, $user_id) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

function logStatusChange($pdo, $issue_id, $changed_by, $old_status, $new_status, $remarks = '') {
    $stmt = $pdo->prepare("INSERT INTO status_history (issue_id, changed_by, old_status, new_status, remarks) VALUES (?,?,?,?,?)");
    $stmt->execute([$issue_id, $changed_by, $old_status, $new_status, $remarks]);
}

function getPriorityBadge($priority) {
    $map = [
        'low'      => '<span class="badge badge-emerald">Low</span>',
        'medium'   => '<span class="badge badge-blue">Medium</span>',
        'high'     => '<span class="badge badge-amber">High</span>',
        'critical' => '<span class="badge badge-rose">Critical</span>',
    ];
    return $map[$priority] ?? '<span class="badge badge-zinc">' . ucfirst($priority) . '</span>';
}

function getStatusBadge($status) {
    $map = [
        'pending'     => '<span class="badge badge-amber">Pending</span>',
        'in_progress' => '<span class="badge badge-blue">In Progress</span>',
        'resolved'    => '<span class="badge badge-emerald">Resolved</span>',
        'closed'      => '<span class="badge badge-zinc">Closed</span>',
        'rejected'    => '<span class="badge badge-rose">Rejected</span>',
    ];
    return $map[$status] ?? '<span class="badge badge-zinc">' . ucfirst($status) . '</span>';
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hrs ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    return date('d M Y', $time);
}
