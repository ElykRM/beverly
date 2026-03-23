<?php
include '../includes/auth.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

$payment_id   = (int)($_POST['payment_id'] ?? 0);
$household_id = (int)($_POST['household_id'] ?? 0);

if ($payment_id <= 0 || $household_id <= 0) {
    header("Location: ../actions/view.php?id=$household_id&msg=Invalid request");
    exit;
}

// Hard delete - remove the record completely
$stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
$stmt->execute([$payment_id]);

header("Location: ../actions/view.php?id=$household_id&msg=Payment record deleted successfully");
exit;
?>