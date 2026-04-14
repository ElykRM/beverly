<?php
include '../includes/auth.php';
include '../db.php';

// Suppress errors to avoid corrupting Excel output
ini_set('display_errors', 0);
error_reporting(0);

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$householdId = (int)($input['household_id'] ?? 0);
$householdName = $input['household_name'] ?? 'Household';
$selectedYear = !empty($input['year']) ? (int)$input['year'] : null;

if (!$householdId) {
    http_response_code(400);
    exit('Invalid household ID');
}

// Verify household exists
$hstmt = $pdo->prepare("SELECT * FROM households WHERE id = ?");
$hstmt->execute([$householdId]);
$household = $hstmt->fetch();
if (!$household) {
    http_response_code(404);
    exit('Household not found');
}

// Fetch payments (filter by year if provided)
if ($selectedYear) {
    $pstmt = $pdo->prepare("
        SELECT * FROM payments 
        WHERE household_id = ? AND deleted_at IS NULL 
        AND (
            (period_to_year IS NULL AND period_year = ?)
            OR (period_to_year IS NOT NULL AND period_year <= ? AND period_to_year >= ?)
        )
        ORDER BY period_year, period_month
    ");
    $pstmt->execute([$householdId, $selectedYear, $selectedYear, $selectedYear]);
} else {
    $pstmt = $pdo->prepare("SELECT * FROM payments WHERE household_id = ? AND deleted_at IS NULL ORDER BY period_year, period_month");
    $pstmt->execute([$householdId]);
}
$payments = $pstmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch exemptions (filter by year if provided)
if ($selectedYear) {
    $estmt = $pdo->prepare("
        SELECT * FROM exemptions 
        WHERE household_id = ? 
        AND (
            exemption_year = ? 
            OR (exemption_to_year IS NOT NULL AND exemption_year <= ? AND exemption_to_year >= ?)
        )
        ORDER BY exemption_year, exemption_month
    ");
    $estmt->execute([$householdId, $selectedYear, $selectedYear, $selectedYear]);
} else {
    $estmt = $pdo->prepare("SELECT * FROM exemptions WHERE household_id = ? ORDER BY exemption_year, exemption_month");
    $estmt->execute([$householdId]);
}
$exemptions = $estmt->fetchAll(PDO::FETCH_ASSOC);

// Determine date range
if ($selectedYear) {
    // For selected year, show all months in that year
    $startYear = $selectedYear;
    $startMonth = 1;
    $currentYear = $selectedYear;
    $currentMonth = 12;
} else {
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('m');
    $startYear = $currentYear;
    $startMonth = $currentMonth;

    if (!empty($payments)) {
        $startYear = (int)$payments[0]['period_year'];
        $startMonth = (int)$payments[0]['period_month'];
        
        // Find the latest date with payment data
        $latestYear = $currentYear;
        $latestMonth = $currentMonth;
        foreach ($payments as $p) {
            $endY = $p['period_to_year'] ?? $p['period_year'];
            $endM = $p['period_to_month'] ?? $p['period_month'];
            if ($endY > $latestYear || ($endY == $latestYear && $endM > $latestMonth)) {
                $latestYear = $endY;
                $latestMonth = $endM;
            }
        }
        $currentYear = $latestYear;
        $currentMonth = $latestMonth;
    }

    if ($household['move_in_date']) {
        $moveIn = new DateTime($household['move_in_date']);
        $moveYear = (int)$moveIn->format('Y');
        $moveMonth = (int)$moveIn->format('m');
        if ($moveYear < $startYear || ($moveYear == $startYear && $moveMonth < $startMonth)) {
            $startYear = $moveYear;
            $startMonth = $moveMonth;
        }
    }
}

// Build month data - same logic as dues.php
$monthData = [];
$totalPaid = 0;
$paymentsByOriginal = []; // Track original payment amounts by year for yearly summary

for ($y = $startYear; $y <= $currentYear; $y++) {
    $endMonth = ($y == $currentYear) ? $currentMonth : 12;
    
    for ($m = 1; $m <= $endMonth; $m++) {
        if ($y == $startYear && $m < $startMonth) continue;
        
        $isExempted = false;
        foreach ($exemptions as $ex) {
            if ($ex['exemption_month'] === null && $ex['exemption_year'] == $y) {
                $isExempted = true;
                break;
            } elseif ($ex['exemption_to_year'] === null && $ex['exemption_year'] == $y && $ex['exemption_month'] == $m) {
                $isExempted = true;
                break;
            } else {
                $exStart = $ex['exemption_year'] * 100 + ($ex['exemption_month'] ?? 1);
                $exEnd = ($ex['exemption_to_year'] ?? $ex['exemption_year']) * 100 + ($ex['exemption_to_month'] ?? 12);
                $current = $y * 100 + $m;
                if ($current >= $exStart && $current <= $exEnd) {
                    $isExempted = true;
                    break;
                }
            }
        }
        
        $paymentFound = false;
        $amount = 0;
        $orNo = '';
        $datePaid = '';
        $remarks = '';
        $isPromo = false;
        $originalAmount = 0;
        $paymentStartYear = $y;
        
        // Find matching payment for this month
        foreach ($payments as $payment) {
            $payStart = $payment['period_year'] * 100 + $payment['period_month'];
            $payEnd = ($payment['period_to_year'] ?? $payment['period_year']) * 100 + ($payment['period_to_month'] ?? $payment['period_month']);
            $current = $y * 100 + $m;
            
            if ($current >= $payStart && $current <= $payEnd) {
                // Calculate per-month amount
                $startY = $payment['period_year'];
                $startM = $payment['period_month'];
                $endY = $payment['period_to_year'] ?? $payment['period_year'];
                $endM = $payment['period_to_month'] ?? $payment['period_month'];
                
                $monthCount = 0;
                for ($py = $startY; $py <= $endY; $py++) {
                    $mFrom = ($py == $startY) ? $startM : 1;
                    $mTo = ($py == $endY) ? $endM : 12;
                    $monthCount += $mTo - $mFrom + 1;
                }
                
                // For promo: divide by 10, not 12
                $divisor = $payment['is_promo'] ? min(10, $monthCount) : $monthCount;
                $perMonth = $divisor > 0 ? (float)$payment['amount'] / $divisor : 0;
                
                $paymentFound = true;
                $amount = $perMonth;
                $orNo = $payment['or_no'];
                $datePaid = $payment['paid_at'];
                $remarks = $payment['remarks'] ?? '';
                $isPromo = (bool)$payment['is_promo'];
                $originalAmount = (float)$payment['amount'];
                $paymentStartYear = $startY;
                $totalPaid += $amount;
                
                // Track original payment amounts by year (only count once per payment)
                $paymentKey = $payment['id'] . '_' . $startY;
                if (!isset($paymentsByOriginal[$paymentKey])) {
                    $paymentsByOriginal[$paymentKey] = [
                        'year' => $startY,
                        'amount' => $originalAmount
                    ];
                }
                break;
            }
        }
        
        // Determine if this is a promo month (Nov-Dec) for marking
        $isPromoMonth = $isPromo && $m > 10;
        
        $monthData[] = [
            'year' => $y,
            'month' => $m,
            'amount' => $paymentFound ? $amount : 0,
            'orNo' => $orNo,
            'datePaid' => $datePaid,
            'remarks' => $remarks,
            'isExempted' => $isExempted,
            'isPromo' => $isPromo,
            'isPromoMonth' => $isPromoMonth,
            'status' => $isExempted ? 'Exempted' : ($paymentFound ? 'Paid' : 'Unpaid')
        ];
    }
}

// Reverse month data so current year appears at top
$monthData = array_reverse($monthData);

// Recalculate totalPaid using original payment amounts (for accurate accounting)
$totalPaid = 0;
foreach ($paymentsByOriginal as $payment) {
    $totalPaid += $payment['amount'];
}

// Calculate expected months (up to today, excluding exempted months)
$todayYear = (int)date('Y');
$todayMonth = (int)date('m');
$exemptedMonths = 0;
$billableMonths = 0;

foreach ($monthData as $month) {
    // Only count months that are today or earlier
    $isCurrentOrPast = ($month['year'] < $todayYear) || ($month['year'] == $todayYear && $month['month'] <= $todayMonth);
    
    // Skip promo months (Nov-Dec showing ₱0.00) from expected billing
    if ($month['isPromoMonth']) {
        continue;
    }
    
    if ($isCurrentOrPast && $month['isExempted']) {
        $exemptedMonths++;
    } elseif ($isCurrentOrPast && !$month['isExempted']) {
        $billableMonths++;
    }
}

$monthlyDue = 100.00; // ₱100 per month
$totalExpected = $billableMonths * $monthlyDue;

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Statement of Account');

// Household info header
$sheet->setCellValue('A1', $household['last_name'] . ', ' . $household['first_name']);
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

$address = trim((($household['block'] ? 'Block ' . $household['block'] : '') . ' ' . ($household['lot'] ? 'Lot ' . $household['lot'] : '')));
if ($household['street']) $address .= ' ' . $household['street'];
$sheet->setCellValue('A2', trim($address) ?: 'No address');
$sheet->mergeCells('A2:F2');

$sheet->setCellValue('A3', $household['subdivision']);
$sheet->mergeCells('A3:F3');

$sheet->setCellValue('A5', 'STATEMENT OF ACCOUNT');
$sheet->mergeCells('A5:F5');
$sheet->getStyle('A5')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Column headers
$headers = ['OR No.', 'Period', 'Amount', 'Date Paid', 'Remarks', 'Status'];
$headerStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => '1F8449'],
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC'],
        ],
    ],
];

$row = 7;
$colLetters = ['A', 'B', 'C', 'D', 'E', 'F'];
foreach ($headers as $col => $header) {
    $sheet->setCellValue($colLetters[$col] . $row, $header);
}
$sheet->getStyle('A7:F7')->applyFromArray($headerStyle);

// Monthly data rows
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$row = 8;

foreach ($monthData as $month) {
    $sheet->setCellValue('A' . $row, $month['orNo'] ?: '-');
    $sheet->setCellValue('B' . $row, $monthNames[$month['month'] - 1] . ' ' . $month['year']);
    
    // Promo months (Nov-Dec) show as ₱0.00
    $displayAmount = $month['isPromoMonth'] ? 0 : $month['amount'];
    $sheet->setCellValue('C' . $row, '₱' . number_format($displayAmount, 2));
    $sheet->setCellValue('D' . $row, $month['datePaid'] ? date('M d, Y H:i', strtotime($month['datePaid'])) : '-');
    
    // Add [PROMO] badge for Nov-Dec of promo payments
    $remarks = $month['remarks'] ?: '-';
    if ($month['isPromoMonth']) {
        $remarks = ($remarks !== '-' ? $remarks . ' ' : '') . '[PROMO]';
    }
    $sheet->setCellValue('E' . $row, $remarks);
    $sheet->setCellValue('F' . $row, $month['status']);
    
    // Row styling
    $rowStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC'],
            ],
        ],
    ];
    
    // Color code rows
    if ($month['isExempted']) {
        $rowStyle['fill'] = [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'FFFF99'],
        ];
    } elseif ($month['amount'] == 0) {
        $rowStyle['fill'] = [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'CCCCCC'],
        ];
    }
    
    $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($rowStyle);
    $row++;
}

// Add yearly summary if viewing all years
if (!$selectedYear && count($monthData) > 0) {
    // Use original payment amounts for yearly summary (not spread-out amounts)
    $yearlyTotals = [];
    foreach ($paymentsByOriginal as $payment) {
        $y = $payment['year'];
        if (!isset($yearlyTotals[$y])) {
            $yearlyTotals[$y] = 0;
        }
        $yearlyTotals[$y] += $payment['amount'];
    }
    
    // Sort in descending order (current year first)
    krsort($yearlyTotals);
    
    // Blank row
    $row++;
    
    // Yearly summary header
    $row++;
    $sheet->setCellValue('E' . $row, 'YEARLY SUMMARY:');
    $sheet->getStyle('E' . $row)->getFont()->setBold(true);
    $row++;
    
    // Yearly totals
    foreach ($yearlyTotals as $year => $yearTotal) {
        $sheet->setCellValue('E' . $row, 'Year ' . $year . ':');
        $sheet->setCellValue('F' . $row, '₱' . number_format($yearTotal, 2));
        $sheet->getStyle('E' . $row . ':F' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);
        $row++;
    }
}

// Total row
$totalRow = $row + 1;
$sheet->setCellValue('E' . $totalRow, 'TOTAL PAID:');
$sheet->setCellValue('F' . $totalRow, '₱' . number_format($totalPaid, 2));
$sheet->getStyle('E' . $totalRow . ':F' . $totalRow)->getFont()->setBold(true);
$sheet->getStyle('E' . $totalRow . ':F' . $totalRow)->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => 'E0E0E0'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC'],
        ],
    ],
]);

// Calculate outstanding balance (using accurate totals)
$outstandingBalance = $totalExpected - $totalPaid;

// Outstanding balance row
$balanceRow = $totalRow + 1;
$sheet->setCellValue('E' . $balanceRow, 'OUTSTANDING BALANCE:');
$sheet->setCellValue('F' . $balanceRow, '₱' . number_format($outstandingBalance, 2));
$sheet->getStyle('E' . $balanceRow . ':F' . $balanceRow)->getFont()->setBold(true);

// Color code balance row
if ($outstandingBalance > 0) {
    $balanceColor = 'FF6B6B'; // Red for unpaid
} else {
    $balanceColor = '51CF66'; // Green for paid up
}

$sheet->getStyle('E' . $balanceRow . ':F' . $balanceRow)->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => $balanceColor],
    ],
    'font' => [
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC'],
        ],
    ],
]);

// Adjust column widths
$sheet->getColumnDimension('A')->setWidth(12);
$sheet->getColumnDimension('B')->setWidth(16);
$sheet->getColumnDimension('C')->setWidth(14);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(12);

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="SOA_' . preg_replace('/[^a-zA-Z0-9]/', '', $householdName) . '_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
