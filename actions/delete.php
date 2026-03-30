<?php
include '../includes/auth.php';
include '../db.php';

// Admin-only action
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: ../pages/habitants.php');
    exit;
}

$id = (int)$_POST['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM households WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../pages/habitants.php?msg=Household deleted successfully");
    exit;
} catch (Exception $e) {
    die("Error deleting household: " . $e->getMessage());
}