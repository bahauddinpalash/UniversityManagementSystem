<?php
$host     = getenv('MYSQLHOST');
$user     = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$database = getenv('MYSQLDATABASE');
$port     = getenv('MYSQLPORT');

// Try connecting and print the exact error if it fails
$conn = @new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    echo "<h3>Connection Debug Info:</h3>";
    echo "Host: " . htmlspecialchars($host) . "<br>";
    echo "User: " . htmlspecialchars($user) . "<br>";
    echo "Port: " . htmlspecialchars($port) . "<br>";
    echo "Database: " . htmlspecialchars($database) . "<br>";
    echo "<b>MySQL Error: " . $conn->connect_error . "</b>";
    exit();
} else {
    echo "Database connected successfully!";
}
?>