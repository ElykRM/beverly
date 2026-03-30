<?php
// db.php - PDO connection

$host     = 'sql208.infinityfree.com';
$dbname   = 'if0_41510481_beverly';          // InfinityFree database name
$username = 'if0_41510481';                  // InfinityFree username
$password = 'F1Iagq6Qs3NM0N';            // Add your InfinityFree password here

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}