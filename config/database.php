<?php
$host     = getenv('MYSQLHOST')     ?: 'localhost';
$user     = getenv('MYSQLUSER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'skontorpiWoYZBczmgBJadkgYtmolyJd';
$database = getenv('MYSQLDATABASE') ?: 'railway';
$port     = getenv('MYSQLPORT')     ?: '3306';

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>