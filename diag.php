<?php
header('Content-Type: text/plain; charset=utf-8');
$secret = getenv('BOT_TOKEN') ?: '8446137046:AAFfhP-O652Awf5OCmG1K6nQS7AehYLZ9BI';
if (($_GET['k'] ?? '') !== substr($secret, -10)) { http_response_code(403); exit("forbidden\n"); }

echo "=== Railway Diagnostic ===\n";
echo "php: " . phpversion() . "\n";
echo "mysqli_ext: " . (extension_loaded('mysqli') ? 'YES' : 'NO') . "\n";
echo "pdo_mysql: "  . (extension_loaded('pdo_mysql')  ? 'YES' : 'NO') . "\n";
echo "pdo: "        . (extension_loaded('pdo')         ? 'YES' : 'NO') . "\n";
echo "curl: "       . (extension_loaded('curl')         ? 'YES' : 'NO') . "\n\n";

$host = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: '127.0.0.1';
$port = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);
$user = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'railway';

echo "MYSQLHOST: $host | PORT: $port | USER: $user | DB: $name\n\n";

// Test 1: TCP reachability (socket connect with 5s timeout)
echo "TCP test ($host:$port)...\n";
flush();
$sock = @fsockopen($host, $port, $errno, $errstr, 5);
if ($sock) {
    echo "TCP: OPEN\n";
    fclose($sock);
} else {
    echo "TCP: FAILED ($errno: $errstr)\n";
}
flush();

// Test 2: mysqli with timeout
if (extension_loaded('mysqli')) {
    echo "\nMySQL connect (5s timeout)...\n";
    flush();
    $db = mysqli_init();
    mysqli_options($db, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $ok = @mysqli_real_connect($db, $host, $user, $pass, $name, $port);
    if ($ok) {
        echo "MySQL: CONNECTED\n";
        $r = mysqli_query($db, "SHOW TABLES");
        echo "Tables: " . mysqli_num_rows($r) . "\n";
    } else {
        echo "MySQL ERROR: " . mysqli_connect_error() . " (code: " . mysqli_connect_errno() . ")\n";
    }
    flush();
}

// Test 3: Internal Railway hostname
$int_host = 'mysql.railway.internal';
if ($int_host !== $host) {
    echo "\nTCP test (internal: $int_host:3306)...\n";
    flush();
    $sock2 = @fsockopen($int_host, 3306, $errno2, $errstr2, 5);
    if ($sock2) {
        echo "Internal TCP: OPEN\n";
        fclose($sock2);
    } else {
        echo "Internal TCP: FAILED ($errno2: $errstr2)\n";
    }
    flush();
}

echo "\nDone.\n";
