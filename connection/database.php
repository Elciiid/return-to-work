<?php
date_default_timezone_set('Asia/Manila');

$databaseUrl = getenv('DATABASE_URL');

// For local testing if .env is not automatically loaded by the environment
if (!$databaseUrl && file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . "=" . trim($value));
    }
    $databaseUrl = getenv('DATABASE_URL');
}

if (!$databaseUrl) {
    die("<div style=\"font-family:sans-serif;padding:50px;text-align:center;\">
        <h2>Configuration Error</h2>
        <p>DATABASE_URL environment variable is not set.</p>
    </div>");
}

$databaseUrl = trim($databaseUrl);
if (strpos($databaseUrl, 'DATABASE_URL=') === 0) {
    $databaseUrl = substr($databaseUrl, 13);
}

$user = $pass = $host = $dbname = null;
$port = 6543; // IMPORTANT: Use Supabase Transaction Pooler port

// Regex parser — handles special chars like [], @, : in passwords
if (preg_match('/^postgres(?:ql)?:\/\/([^:]+):(.*)@([^:\/]+)(?::(\d+))?\/(.+)$/', $databaseUrl, $m)) {
    $user   = $m[1];
    $pass   = $m[2];
    $host   = $m[3];
    $port   = $m[4] ?: 6543;
    $dbname = explode('?', $m[5])[0];
} else {
    $parsed = parse_url($databaseUrl);
    $user   = $parsed['user'] ?? null;
    $pass   = $parsed['pass'] ?? null;
    $host   = $parsed['host'] ?? null;
    $port   = $parsed['port'] ?? 6543;
    $dbname = explode('?', ltrim($parsed['path'] ?? '', '/'))[0];
}

if (!$host || !$user || !$dbname) {
    die("<div style=\"font-family:sans-serif;padding:50px;text-align:center;\">
        <h2>Configuration Error</h2>
        <p>DATABASE_URL is malformed.</p>
    </div>");
}

try {
    $dsn  = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // CRITICAL: Register DB-backed session handler BEFORE session_start()
    require_once __DIR__ . '/../utils/session_handler.php';
    $handler = new PdoSessionHandler($conn);
    session_set_save_handler($handler, true);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die("<div style=\"font-family:sans-serif;padding:50px;text-align:center;\">
        <h2>System Unavailable</h2>
        <p>Database connection failed. Please try again later.</p>
    </div>");
}
?>
