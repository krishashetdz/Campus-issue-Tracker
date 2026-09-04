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
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$proto = $isHttps ? 'https' : 'http';
$detectedBaseUrl = (isset($_SERVER['HTTP_HOST']) ? ($proto . '://' . $_SERVER['HTTP_HOST'] . $appSubdir) : 'http://localhost/fixmycampus/');

if (!file_exists(__DIR__ . '/../uploads/')) {
    @mkdir(__DIR__ . '/../uploads/', 0777, true);
}
if (!file_exists(__DIR__ . '/../uploads/issues/')) {
    @mkdir(__DIR__ . '/../uploads/issues/', 0777, true);
}
if (!defined('BASE_URL')) define('BASE_URL', getenv('BASE_URL') ?: $detectedBaseUrl);
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../uploads/issues/');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', BASE_URL . 'uploads/issues/');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Cloudinary Configuration & Helper
if (!defined('CLOUDINARY_CLOUD_NAME'))   define('CLOUDINARY_CLOUD_NAME',   getenv('CLOUDINARY_CLOUD_NAME')   ?: 'djhubcw7');
if (!defined('CLOUDINARY_API_KEY'))      define('CLOUDINARY_API_KEY',      getenv('CLOUDINARY_API_KEY')      ?: '267922663126137');
if (!defined('CLOUDINARY_API_SECRET'))   define('CLOUDINARY_API_SECRET',   getenv('CLOUDINARY_API_SECRET')   ?: 'KdFVfve-8K6sKgiYnxTHqprT1zU');
if (!defined('CLOUDINARY_UPLOAD_PRESET')) define('CLOUDINARY_UPLOAD_PRESET', getenv('CLOUDINARY_UPLOAD_PRESET') ?: 'fixmycampus_preset');

/**
 * Upload image file to Cloudinary REST API with authentication
 * 
 * @param string $fileTmpPath Path to temporary file
 * @return string|null Secure HTTPS URL of uploaded image or null on failure
 */
if (!function_exists('uploadToCloudinary')) {
    function uploadToCloudinary($fileTmpPath) {
        if (empty($fileTmpPath) || !file_exists($fileTmpPath)) {
            return null;
        }

        $cloudName    = defined('CLOUDINARY_CLOUD_NAME') ? CLOUDINARY_CLOUD_NAME : 'djhubcw7';
        $apiKey       = defined('CLOUDINARY_API_KEY') ? CLOUDINARY_API_KEY : '267922663126137';
        $apiSecret    = defined('CLOUDINARY_API_SECRET') ? CLOUDINARY_API_SECRET : 'KdFVfve-8K6sKgiYnxTHqprT1zU';
        $uploadPreset = defined('CLOUDINARY_UPLOAD_PRESET') ? CLOUDINARY_UPLOAD_PRESET : 'fixmycampus_preset';

        $url = "https://api.cloudinary.com/v1_1/" . $cloudName . "/image/upload";
        $timestamp = time();
        $cfile = new CURLFile($fileTmpPath);

        // 1. Signed request with upload_preset
        $signatureStr = "timestamp=" . $timestamp . "&upload_preset=" . $uploadPreset . $apiSecret;
        $signature = sha1($signatureStr);

        $postData = [
            'file'          => $cfile,
            'api_key'       => $apiKey,
            'timestamp'     => $timestamp,
            'signature'     => $signature,
            'upload_preset' => $uploadPreset,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $jsonResponse = json_decode($response, true);
            if (isset($jsonResponse['secure_url'])) {
                return $jsonResponse['secure_url'];
            }
        }

        // 2. Signed request without upload_preset
        $signatureStrSimple = "timestamp=" . $timestamp . $apiSecret;
        $signatureSimple = sha1($signatureStrSimple);
        $postDataFallback = [
            'file'      => $cfile,
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signatureSimple,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataFallback);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $jsonResponse = json_decode($response, true);
            if (isset($jsonResponse['secure_url'])) {
                return $jsonResponse['secure_url'];
            }
        }

        // 3. Unsigned request with upload_preset
        $postDataUnsigned = [
            'file'          => $cfile,
            'upload_preset' => $uploadPreset,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataUnsigned);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $jsonResponse = json_decode($response, true);
            if (isset($jsonResponse['secure_url'])) {
                return $jsonResponse['secure_url'];
            }
        }

        return null;
    }
}

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

    // Auto-migrate Duplicate Complaint Clustering columns if missing
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM issues")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('parent_id', $cols)) {
            $pdo->exec("ALTER TABLE issues ADD COLUMN parent_id INT(11) DEFAULT NULL AFTER assigned_to, ADD KEY fk_parent_issue (parent_id), ADD CONSTRAINT fk_parent_issue FOREIGN KEY (parent_id) REFERENCES issues(issue_id) ON DELETE SET NULL");
        }
        if (!in_array('is_parent', $cols)) {
            $pdo->exec("ALTER TABLE issues ADD COLUMN is_parent TINYINT(1) DEFAULT 0 AFTER parent_id");
        }
        if (!in_array('affected_count', $cols)) {
            $pdo->exec("ALTER TABLE issues ADD COLUMN affected_count INT(11) DEFAULT 1 AFTER is_parent");
        }
        if (!in_array('reopen_count', $cols)) {
            $pdo->exec("ALTER TABLE issues ADD COLUMN reopen_count INT(11) DEFAULT 0 AFTER affected_count");
        }
    } catch (Exception $ex) {
        // Continue if columns exist or ALTER is restricted
    }

} catch (PDOException $e) {
    die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
}