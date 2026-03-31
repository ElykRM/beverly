<?php
// db.php - PDO connection
// InfinityFree Hosting Configuration

$host     = 'sql208.infinityfree.com';
$dbname   = 'if0_41510481_beverly';
$username = 'if0_41510481';
$password = 'F1Iagq6Qs3NM0N';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}