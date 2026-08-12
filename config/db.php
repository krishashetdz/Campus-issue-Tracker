<?php
/**
 * FixMyCampus - Database Configuration
 * Reads environment variables on Render, or falls back to Aiven / XAMPP defaults
 */
$host = getenv('DB_HOST') ?: 'mysql-64723e2-campusissuetracker.g.aivencloud.com';
$port = getenv('DB_PORT') ?: '21975';
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASSWORD');

if (!defined('DB_HOST')) define('DB_HOST', $host);
if (!defined('DB_PORT')) define('DB_PORT', $port);
if (!defined('DB_USER')) define('DB_USER', $user);
if (!defined('DB_PASS')) define('DB_PASS', $pass);
if (!defined('DB_NAME')) define('DB_NAME', $db);

// Dynamic BASE_URL detection for local XAMPP subfolder vs Render root deployment
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$appSubdir = (strpos($scriptDir, '/fixmycampus') !== false) ? '/fixmycampus/' : '/';
$detectedBaseUrl = (isset($_SERVER['HTTP_HOST']) ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $appSubdir) : 'http://localhost/fixmycampus/');

if (!defined('BASE_URL')) define('BASE_URL', getenv('BASE_URL') ?: $detectedBaseUrl);
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads/issues/');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', BASE_URL . 'uploads/issues/');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

try {
    // Explicitly using host and port forces TCP connection instead of unix socket
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Check if database needs initial setup
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        $sqlPath = __DIR__ . '/../database.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Clean CREATE DATABASE / USE statements so schema imports directly into active $db
            $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS [^;]+;/i', '', $sql);
            $sql = preg_replace('/USE [^;]+;/i', '', $sql);
            $pdo->exec($sql);
        }
    }

} catch (PDOException $e) {
    die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
}