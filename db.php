<?php
$defaults = [
    'host' => 'sql211.infinityfree.com',
    'user' => 'if0_39511631',
    'pass' => 'VTRa58jzFaI',
    'db'   => 'if0_39511631_complaints',
];

$localConfigPath = __DIR__ . '/db.local.php';
if (file_exists($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $defaults = array_merge($defaults, array_intersect_key($localConfig, $defaults));
    }
}

$host = getenv('DB_HOST') ?: $defaults['host'];
$user = getenv('DB_USER') ?: $defaults['user'];
$pass = getenv('DB_PASS') ?: $defaults['pass'];
$db   = getenv('DB_NAME') ?: $defaults['db'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>