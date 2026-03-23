<?php
include '../includes/auth.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/payment.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $household_id = (int)($_POST['household_id'] ?? 0);
    if ($household_id <= 0) {
        throw new Exception("No household selected.");
    }

    $or_no   = trim($_POST['or_no'] ?? '');
    $amount_raw = trim($_POST['amount'] ?? '0');
    $amount  = filter_var($amount_raw, FILTER_VALIDATE_FLOAT);
    $remarks = trim($_POST['remarks'] ?? '');
    $is_promo = isset($_POST['is_promo']) && $_POST['is_promo'] == '1' ? 1 : 0;

    if ($or_no === '') {
        throw new Exception("OR number is required.");
    }

    if ($amount === false || $amount <= 0) {
        throw new Exception("Amount must be a valid number greater than zero. Received: '$amount_raw'");
    }

    $payment_type = $_POST['payment_type'] ?? 'single';

    if ($payment_type === 'single') {
        $month = $_POST['single_month'] ?? '';
        $year  = (int)($_POST['single_year'] ?? 0);

        if (!preg_match('/^(0[1-9]|1[0-2])$/', $month) || $year < 2000) {
            throw new Exception("Invalid month/year.");
        }

        $period_year      = $year;
        $period_month     = (int)$month;
        $period_to_year   = null;
        $period_to_month  = null;
    } else {
        $from_month = $_POST['from_month'] ?? '';
        $from_year  = (int)($_POST['from_year'] ?? 0);
        $to_month   = $_POST['to_month'] ?? '';
        $to_year    = (int)($_POST['to_year'] ?? 0);

        if (!preg_match('/^(0[1-9]|1[0-2])$/', $from_month) ||
            !preg_match('/^(0[1-9]|1[0-2])$/', $to_month) ||
            $from_year < 2000 || $to_year < 2000) {
            throw new Exception("Invalid from/to dates.");
        }

        $from = mktime(0,0,0, $from_month, 1, $from_year);
        $to   = mktime(0,0,0, $to_month,   1, $to_year);
        if ($from > $to) {
            throw new Exception("Start date cannot be after end date.");
        }

        $period_year      = $from_year;
        $period_month     = (int)$from_month;
        $period_to_year   = $to_year;
        $period_to_month  = (int)$to_month;
    }

    // If promo is checked, force full year (safety net)
    if ($is_promo) {
        $period_year      = $period_year ?? $currentYear;
        $period_month     = 1;
        $period_to_year   = $period_year;
        $period_to_month  = 12;
        $amount           = 1000.00; // enforce promo price
    }

    $stmt = $pdo->prepare("
        INSERT INTO payments 
        (household_id, or_no, period_year, period_month, period_to_year, period_to_month, amount, remarks, is_promo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $household_id,
        $or_no,
        $period_year,
        $period_month,
        $period_to_year,
        $period_to_month,
        $amount,
        $remarks ?: null,
        $is_promo
    ]);

    $pdo->commit();

    header("Location: ../actions/view.php?id=$household_id&msg=Payment recorded successfully");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg mx-auto max-w-4xl'>
            Error: " . htmlspecialchars($e->getMessage()) . "
          </div>";
}
?>