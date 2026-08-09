<?php
// Railway automatically provides MYSQL_URL or MYSQL_PRIVATE_URL
$db_url = getenv('MYSQL_PRIVATE_URL') ?: getenv('MYSQL_URL');

if ($db_url) {
    // Parse the connection URL provided by Railway
    $dbopts = parse_url($db_url);
    $host = $dbopts['host'];
    $port = $dbopts['port'] ?? 3306;
    $user = $dbopts['user'];
    $pass = $dbopts['pass'];
    $db   = ltrim($dbopts['path'], '/');
} else {
    // Local fallback for development (XAMPP/WAMP)
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db   = getenv('DB_NAME') ?: 'unimanage_db';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
}