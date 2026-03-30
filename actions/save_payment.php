<?php
include '../includes/auth.php';
include '../db.php';

$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_SERVER['HTTP_ACCEPT']) && strpos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

function respondJson(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/payment.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $monthName = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

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

    // Enforce oldest-first payment: no paying later months while earlier due months are unpaid.
    $today = new DateTime();
    $currentDueYear = (int)$today->format('Y');
    $currentDueMonth = (int)$today->format('n');

    $requestedStartIndex = ($period_year * 12) + $period_month;

    $paidStmt = $pdo->prepare("\n        SELECT period_year, period_month, period_to_year, period_to_month\n        FROM payments\n        WHERE household_id = ? AND deleted_at IS NULL\n    ");
    $paidStmt->execute([$household_id]);
    $existingPayments = $paidStmt->fetchAll();

    $minPaidYear = null;
    foreach ($existingPayments as $p) {
        $py = (int)$p['period_year'];
        if ($py > 0 && ($minPaidYear === null || $py < $minPaidYear)) {
            $minPaidYear = $py;
        }
    }

    // Start from January of the earliest relevant year.
    $dueStartYear = $minPaidYear !== null ? min($minPaidYear, $period_year) : $period_year;
    $dueStartMonth = 1;

    $covered = [];
    foreach ($existingPayments as $p) {
        $startY = (int)$p['period_year'];
        $startM = (int)$p['period_month'];
        $endY = $p['period_to_year'] !== null ? (int)$p['period_to_year'] : $startY;
        $endM = $p['period_to_month'] !== null ? (int)$p['period_to_month'] : $startM;

        if ($endY < $startY || ($endY === $startY && $endM < $startM)) {
            continue;
        }

        for ($y = $startY; $y <= $endY; $y++) {
            $mFrom = ($y === $startY) ? $startM : 1;
            $mTo = ($y === $endY) ? $endM : 12;
            for ($m = $mFrom; $m <= $mTo; $m++) {
                $covered[($y * 100) + $m] = true;
            }
        }
    }

    $earliestUnpaidYear = null;
    $earliestUnpaidMonth = null;
    for ($y = $dueStartYear; $y <= $currentDueYear; $y++) {
        $mFrom = ($y === $dueStartYear) ? $dueStartMonth : 1;
        $mTo = ($y === $currentDueYear) ? $currentDueMonth : 12;
        for ($m = $mFrom; $m <= $mTo; $m++) {
            $key = ($y * 100) + $m;
            if (!isset($covered[$key])) {
                $earliestUnpaidYear = $y;
                $earliestUnpaidMonth = $m;
                break 2;
            }
        }
    }

    if ($earliestUnpaidYear !== null) {
        $earliestUnpaidIndex = ($earliestUnpaidYear * 12) + $earliestUnpaidMonth;
        if ($requestedStartIndex > $earliestUnpaidIndex) {
            $firstDueLabel = $monthName[$earliestUnpaidMonth] . ' ' . $earliestUnpaidYear;
            throw new Exception("Please pay overdue/current dues first. Earliest unpaid month is $firstDueLabel.");
        }
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

    $successRedirect = "../actions/view.php?id=$household_id&msg=Payment recorded successfully";
    if ($isAjax) {
        respondJson(200, [
            'success' => true,
            'redirect' => $successRedirect,
            'message' => 'Payment recorded successfully.'
        ]);
    }

    header("Location: $successRedirect");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($isAjax) {
        respondJson(422, [
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }

    $errorMessage = urlencode($e->getMessage());
    $redirectId = (int)($household_id ?? 0);
    header("Location: ../pages/payment.php?error={$errorMessage}&household_id={$redirectId}");
    exit;
}
?>