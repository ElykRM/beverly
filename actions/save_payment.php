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
    $exemption_year = isset($_POST['exemption_year']) && $_POST['exemption_year'] !== '' ? (int)$_POST['exemption_year'] : null;

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

    // Fetch exemptions for this household
    $exemptStmt = $pdo->prepare("\n        SELECT exemption_year, exemption_month, exemption_to_year, exemption_to_month\n        FROM exemptions\n        WHERE household_id = ?\n    ");
    $exemptStmt->execute([$household_id]);
    $exemptions = $exemptStmt->fetchAll();

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
        // Check if this year is exempted
        $yearIsExempted = false;
        foreach ($exemptions as $ex) {
            $exYear = (int)$ex['exemption_year'];
            $exMonth = $ex['exemption_month'] ? (int)$ex['exemption_month'] : null;
            $exToYear = $ex['exemption_to_year'] ? (int)$ex['exemption_to_year'] : null;
            $exToMonth = $ex['exemption_to_month'] ? (int)$ex['exemption_to_month'] : null;
            
            // Full year exemption
            if ($exMonth === null && $exToYear === null) {
                if ($y == $exYear) {
                    $yearIsExempted = true;
                    break;
                }
            }
            // Range exemption - check if year falls in range
            else if ($exToYear !== null) {
                if ($y >= $exYear && $y <= $exToYear) {
                    $yearIsExempted = true;
                    break;
                }
            }
        }
        
        if ($yearIsExempted) {
            continue; // Skip this year, it's fully exempted
        }
        
        $mFrom = ($y === $dueStartYear) ? $dueStartMonth : 1;
        $mTo = ($y === $currentDueYear) ? $currentDueMonth : 12;
        for ($m = $mFrom; $m <= $mTo; $m++) {
            // Check if this specific month is exempted
            $monthIsExempted = false;
            foreach ($exemptions as $ex) {
                $exYear = (int)$ex['exemption_year'];
                $exMonth = $ex['exemption_month'] ? (int)$ex['exemption_month'] : null;
                $exToYear = $ex['exemption_to_year'] ? (int)$ex['exemption_to_year'] : null;
                $exToMonth = $ex['exemption_to_month'] ? (int)$ex['exemption_to_month'] : null;
                
                // Single month exemption
                if ($exMonth !== null && $exToYear === null) {
                    if ($y == $exYear && $m == $exMonth) {
                        $monthIsExempted = true;
                        break;
                    }
                }
                // Range exemption
                else if ($exToYear !== null) {
                    $startKey = ($exYear * 100) + ($exMonth ?? 1);
                    $endKey = ($exToYear * 100) + ($exToMonth ?? 12);
                    $currentKey = ($y * 100) + $m;
                    if ($currentKey >= $startKey && $currentKey <= $endKey) {
                        $monthIsExempted = true;
                        break;
                    }
                }
            }
            
            if ($monthIsExempted) continue; // Skip this month, it's exempted
            
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

    // Check if any of the months being paid for are already paid
    $requestedMonths = [];
    if ($is_promo) {
        // Promo covers Jan-Dec of the selected year
        for ($m = 1; $m <= 12; $m++) {
            $requestedMonths[] = ($period_year * 100) + $m;
        }
    } else {
        // Single or range payment
        for ($y = $period_year; $y <= ($period_to_year ?? $period_year); $y++) {
            $mFrom = ($y === $period_year) ? $period_month : 1;
            $mTo = ($y === ($period_to_year ?? $period_year)) ? ($period_to_month ?? $period_month) : 12;
            for ($m = $mFrom; $m <= $mTo; $m++) {
                $requestedMonths[] = ($y * 100) + $m;
            }
        }
    }

    // Check if trying to pay for an exempted month/year/range
    $exemptedMonthsList = [];
    foreach ($requestedMonths as $monthKey) {
        $year = intdiv($monthKey, 100);
        $month = $monthKey % 100;
        
        foreach ($exemptions as $ex) {
            $exYear = (int)$ex['exemption_year'];
            $exMonth = $ex['exemption_month'] ? (int)$ex['exemption_month'] : null;
            $exToYear = $ex['exemption_to_year'] ? (int)$ex['exemption_to_year'] : null;
            $exToMonth = $ex['exemption_to_month'] ? (int)$ex['exemption_to_month'] : null;
            
            $isExempt = false;
            
            // Full year exemption
            if ($exMonth === null && $exToYear === null) {
                if ($year == $exYear) {
                    $isExempt = true;
                }
            }
            // Single month exemption
            else if ($exMonth !== null && $exToYear === null) {
                if ($year == $exYear && $month == $exMonth) {
                    $isExempt = true;
                }
            }
            // Range exemption
            else if ($exToYear !== null) {
                $startKey = ($exYear * 100) + ($exMonth ?? 1);
                $endKey = ($exToYear * 100) + ($exToMonth ?? 12);
                $currentKey = ($year * 100) + $month;
                if ($currentKey >= $startKey && $currentKey <= $endKey) {
                    $isExempt = true;
                }
            }
            
            if ($isExempt) {
                $exemptedMonthsList[] = $monthName[$month] . ' ' . $year;
                break;
            }
        }
    }
    
    if (!empty($exemptedMonthsList)) {
        $monthList = implode(', ', $exemptedMonthsList);
        throw new Exception("Cannot record payment for $monthList - exemption is active.");
    }

    // Find overlap with already paid months
    $alreadyPaidMonths = [];
    foreach ($requestedMonths as $monthKey) {
        if (isset($covered[$monthKey])) {
            $year = intdiv($monthKey, 100);
            $month = $monthKey % 100;
            $alreadyPaidMonths[] = $monthName[$month] . ' ' . $year;
        }
    }

    // If any months are already paid, throw error
    if (!empty($alreadyPaidMonths)) {
        if ($is_promo) {
            // For yearly promo, just show a simple message
            throw new Exception("Cannot apply yearly promo. Some months are already paid this year.");
        } else {
            // For single or range, show which months are already paid
            $monthList = implode(', ', array_map(function($m) {
                return explode(' ', $m)[0]; // Extract just the month name
            }, $alreadyPaidMonths));
            throw new Exception("Already paid: " . $monthList);
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