<?php
// Temporary diagnostic page — will be removed after debugging
header('Content-Type: text/plain; charset=utf-8');

// Simple token check
$secret = getenv('BOT_TOKEN') ?: '8446137046:AAFfhP-O652Awf5OCmG1K6nQS7AehYLZ9BI';
if (($_GET['k'] ?? '') !== substr($secret, -10)) {
    http_response_code(403);
    exit("forbidden\n");
}

echo "=== Railway Diagnostic ===\n\n";
echo "MYSQLHOST: "    . (getenv('MYSQLHOST')     ?: '(not set)') . "\n";
echo "MYSQLPORT: "    . (getenv('MYSQLPORT')     ?: '(not set)') . "\n";
echo "MYSQLUSER: "    . (getenv('MYSQLUSER')     ?: '(not set)') . "\n";
echo "MYSQLDATABASE: ". (getenv('MYSQLDATABASE') ?: '(not set)') . "\n";
echo "DB_HOST: "      . (getenv('DB_HOST')       ?: '(not set)') . "\n";
echo "DB_NAME: "      . (getenv('DB_NAME')       ?: '(not set)') . "\n";
echo "RAILWAY_PUBLIC_DOMAIN: " . (getenv('RAILWAY_PUBLIC_DOMAIN') ?: '(not set)') . "\n\n";

$host = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: '127.0.0.1';
$port = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);
$user = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'shaheen';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'shaheen_bot';

echo "Connecting → $host:$port / $name as $user\n";
$db = @new mysqli($host, $user, $pass, $name, $port);
if ($db->connect_error) {
    echo "DB ERROR: " . $db->connect_error . "\n";
} else {
    echo "DB: OK\n";
    $r  = $db->query("SHOW TABLES");
    echo "Tables: " . ($r ? $r->num_rows : 0) . "\n";
    // Test includes.php chain
    try {
        require __DIR__ . '/config.php';
        echo "config.php: OK\n";
        echo "config DB_HOST: " . DB_HOST . "\n";
        echo "config DB_NAME: " . DB_NAME . "\n";
    } catch (Throwable $e) {
        echo "config.php ERROR: " . $e->getMessage() . "\n";
    }
}
