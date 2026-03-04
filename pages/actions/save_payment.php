<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/payment.php');
    exit;
}

try {
    $household_id = $_POST['household_id'] ?? null;
    if (!$household_id || !is_numeric($household_id)) {
        throw new Exception("Invalid household selected");
    }

    $year = $_POST['year'] ?? null;
    $month = $_POST['month'] ?? null;
    $payment_period = null;
    if ($year && $month) {
        $payment_period = sprintf("%04d-%02d", $year, $month);
    }

    $stmt = $pdo->prepare("
        INSERT INTO payments (household_id, or_no, payment_period, amount, remarks)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $household_id,
        $_POST['or_no'] ?? '',
        $payment_period,
        $_POST['amount'] ?? 0,
        $_POST['remarks'] ?? null
    ]);

    header("Location: ../actions/view.php?id=$household_id&msg=Payment recorded successfully");
    exit;

} catch (Exception $e) {
    die("Error saving payment: " . $e->getMessage());
}