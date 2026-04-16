<?php
// actions/export_dues_excel.php
require_once '../includes/auth.php';
require_once '../db.php';

require_once '../vendor/autoload.php'; // Adjust this path to your vendor folder

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$input = is_array($input) ? $input : [];

// Ensure year parameter is set
if (!isset($input['year']) || !is_numeric($input['year'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing or invalid year parameter']);
    exit;
}

$statusFilter = $input['status'] ?? 'all'; // Default to 'all' if not provided
$validStatuses = ['all', 'paid', 'unpaid', 'overdue'];
if (!in_array($statusFilter, $validStatuses)) {
    $statusFilter = 'all';
}

function buildDuesData(PDO $pdo, int $selectedYear): array
{
    $householdsStmt = $pdo->query("\n        SELECT id, last_name, first_name, block, lot\n        FROM households\n        ORDER BY last_name, first_name\n    ");
    $households = $householdsStmt->fetchAll();

    $paymentsRaw = [];
    if (!empty($households)) {
        $pstmt = $pdo->prepare("\n            SELECT household_id, period_year, period_month, period_to_year, period_to_month, amount, is_promo\n            FROM payments\n            WHERE (period_year = ? OR period_to_year = ? OR\n                   (period_year <= ? AND (period_to_year IS NULL OR period_to_year >= ?)))\n        ");
        $pstmt->execute([$selectedYear, $selectedYear, $selectedYear, $selectedYear]);
        $paymentsRaw = $pstmt->fetchAll();
    }

    $monthlyData = [];
    foreach ($paymentsRaw as $p) {
        $hid = $p['household_id'];
        $startY = (int)$p['period_year'];
        $startM = (int)$p['period_month'];
        $endY = $p['period_to_year'] !== null ? (int)$p['period_to_year'] : $startY;
        $endM = $p['period_to_month'] !== null ? (int)$p['period_to_month'] : $startM;
        $totalAmount = (float)$p['amount'];
        $isPromo = (int)$p['is_promo'];

        $monthCount = 0;
        for ($y = $startY; $y <= $endY; $y++) {
            $mFrom = ($y == $startY) ? $startM : 1;
            $mTo = ($y == $endY) ? $endM : 12;
            $monthCount += $mTo - $mFrom + 1;
        }

        $divisor = $isPromo ? min(10, $monthCount) : $monthCount;
        $perMonth = $divisor > 0 ? $totalAmount / $divisor : 0;

        for ($y = $startY; $y <= $endY; $y++) {
            if ($y !== $selectedYear) {
                continue;
            }

            $mFrom = ($y == $startY) ? $startM : 1;
            $mTo = ($y == $endY) ? $endM : 12;
            for ($m = $mFrom; $m <= $mTo; $m++) {
                $key = "$y-$m";
                $monthlyData[$hid][$key] = [
                    'amount' => round($perMonth, 2),
                    'is_promo' => $isPromo,
                    'month_num' => $m
                ];
            }
        }
    }

    $rows = [];
    $today = new DateTime();
    $monthKeys = [
        1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec'
    ];

    foreach ($households as $h) {
        $hid = $h['id'];
        $row = [
            'block' => $h['block'] ?? '',
            'lot' => $h['lot'] ?? '',
            'household_name' => trim(($h['last_name'] ?? '') . ', ' . ($h['first_name'] ?? '')),
            'total_unpaid' => 0,
            'jan' => '', 'feb' => '', 'mar' => '', 'apr' => '', 'may' => '', 'jun' => '',
            'jul' => '', 'aug' => '', 'sep' => '', 'oct' => '', 'nov' => '', 'dec' => '',
        ];

        for ($m = 1; $m <= 12; $m++) {
            $key = "$selectedYear-$m";
            $monthData = $monthlyData[$hid][$key] ?? null;
            $isPaid = $monthData !== null;

            if ($isPaid) {
                $amount = (float)($monthData['amount'] ?? 0);
                $isPromo = (int)($monthData['is_promo'] ?? 0);
                $monthNum = (int)($monthData['month_num'] ?? $m);

                if ($isPromo && $monthNum > 10) {
                    $row[$monthKeys[$m]] = 'Promo';
                } else {
                    $row[$monthKeys[$m]] = round($amount, 2);
                }
                continue;
            }

            $row['total_unpaid'] += 100.00;

            $dueDate = new DateTime("$selectedYear-$m-01");
            $isOverdue = $dueDate < $today && $dueDate->format('Y-m') < date('Y-m');
            $isCurrent = $dueDate->format('Y-m') === date('Y-m');
            $isFuture = $dueDate > $today;

            if ($isOverdue) {
                $row[$monthKeys[$m]] = 'overdue';
            } elseif ($isCurrent) {
                $row[$monthKeys[$m]] = 'unpaid';
            } elseif ($isFuture) {
                $row[$monthKeys[$m]] = 'future';
            } else {
                $row[$monthKeys[$m]] = '';
            }
        }

        $rows[] = $row;
    }

    return $rows;
}

function filterDuesByStatus(array $rows, string $status): array
{
    if ($status === 'all') {
        return $rows;
    }

    $filtered = [];
    foreach ($rows as $row) {
        $monthKeys = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        $hasPaid = false;
        $hasOverdue = false;
        $hasUnpaid = false;
        
        foreach ($monthKeys as $month) {
            $value = $row[$month] ?? '';
            if (is_numeric($value) || $value === 'Promo') {
                $hasPaid = true;
            }
            if ($value === 'overdue') {
                $hasOverdue = true;
            }
            if ($value === 'unpaid') {
                $hasUnpaid = true;
            }
        }
        
        $include = false;
        if ($status === 'paid' && $hasPaid) {
            $include = true;
        } elseif ($status === 'overdue' && $hasOverdue) {
            $include = true;
        } elseif ($status === 'unpaid' && ($hasUnpaid || (!$hasPaid && !$hasOverdue))) {
            // "Unpaid" means households with unpaid/current month due OR households with no payments and no overdue
            $include = true;
        }
        
        if ($include) {
            $filtered[] = $row;
        }
    }
    
    return $filtered;
}

function writeDuesSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $data, string $title): void
{
    $safeTitle = preg_replace('/[\\\\\/?*\[\]:]/', '', $title);
    $sheet->setTitle(substr($safeTitle, 0, 31));

    // Row 1: Green header banner
    $sheet->setCellValue('A1', 'HOUSEHOLD DUES');
    $sheet->mergeCells('A1:P1');
    $sheet->getRowDimension(1)->setRowHeight(28);
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F8449']]
    ]);

    // Row 2: Spacer
    $sheet->getRowDimension(2)->setRowHeight(6);

    // Row 3: Column headers with green background
    $headers = ['Block', 'Lot', 'Household', 'Total Unpaid', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $sheet->fromArray($headers, null, 'A3');
    $sheet->getRowDimension(3)->setRowHeight(20);
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F8449']]
    ];
    $sheet->getStyle('A3:P3')->applyFromArray($headerStyle);

    $months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    $rowNum = 4;
    foreach ($data as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['block'] ?? '');
        $sheet->setCellValue('B' . $rowNum, $row['lot'] ?? '');
        $sheet->setCellValue('C' . $rowNum, $row['household_name'] ?? '');

        $totalValue = $row['total_unpaid'] ?? 0;
        if (is_numeric($totalValue)) {
            $sheet->setCellValueExplicit('D' . $rowNum, (float)$totalValue, DataType::TYPE_NUMERIC);
        } else {
            $sheet->setCellValueExplicit('D' . $rowNum, (string)$totalValue, DataType::TYPE_STRING);
        }

        foreach ($months as $i => $month) {
            $col = chr(69 + $i);
            $value = $row[$month] ?? '';

            if (is_numeric($value)) {
                $sheet->setCellValueExplicit($col . $rowNum, (float)$value, DataType::TYPE_NUMERIC);
                continue;
            }

            $text = strtolower(trim((string)$value));
            if ($text === 'overdue') {
                $sheet->setCellValue($col . $rowNum, '');
                $sheet->getStyle($col . $rowNum)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFC7CE']]
                ]);
            } elseif ($text === 'unpaid') {
                $sheet->setCellValue($col . $rowNum, '');
                $sheet->getStyle($col . $rowNum)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFF99']]
                ]);
            } elseif ($text === 'future') {
                $sheet->setCellValue($col . $rowNum, '');
                $sheet->getStyle($col . $rowNum)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']]
                ]);
            } else {
                $sheet->setCellValueExplicit($col . $rowNum, (string)$value, DataType::TYPE_STRING);
            }
        }

        $sheet->getRowDimension($rowNum)->setRowHeight(18);
        $sheet->getStyle("D$rowNum:P$rowNum")->getNumberFormat()->setFormatCode('_(* #,##0.00_);_(* (#,##0.00);_(* "-"_);_(@_)');
        $rowNum++;
    }

    // Row before total: Spacer
    $sheet->getRowDimension($rowNum)->setRowHeight(4);
    $rowNum++;

    $lastDataRow = $rowNum - 2;
    if ($lastDataRow >= 4) {
        $sheet->setCellValue('C' . $rowNum, 'TOTAL');
        $sheet->setCellValue('D' . $rowNum, "=SUM(D4:D{$lastDataRow})");
        foreach (range('E', 'P') as $col) {
            $sheet->setCellValue($col . $rowNum, "=SUM({$col}4:{$col}{$lastDataRow})");
        }

        $sheet->getRowDimension($rowNum)->setRowHeight(20);
        $sheet->getStyle("A{$rowNum}:P{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD3D3D3']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
        ]);
        $sheet->getStyle("D{$rowNum}:P{$rowNum}")->getNumberFormat()->setFormatCode('_(* #,##0.00_);_(* (#,##0.00);_(* "-"_);_(@_)');
    }

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(12);
    $sheet->getColumnDimension('B')->setWidth(10);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(14);
    foreach (range('E', 'P') as $col) {
        $sheet->getColumnDimension($col)->setWidth(12);
    }

    $sheet->freezePane('A4');
}

$spreadsheet = new Spreadsheet();
$baseYear = (int)$input['year'];  // Year is now guaranteed to exist and be numeric

if (isset($input['data']) && count($input) === 1) {
    // Legacy fallback path for older front-end requests (with embedded data).
    $data = is_array($input['data']) ? $input['data'] : [];
    $lastIndex = count($data) - 1;
    if ($lastIndex >= 0) {
        $last = $data[$lastIndex];
        if (isset($last['household_name']) && strtoupper($last['household_name']) === 'TOTAL') {
            array_pop($data);
        }
    }
    if (empty($data)) {
        http_response_code(400);
        exit;
    }
    $sheet = $spreadsheet->getActiveSheet();
    writeDuesSheet($sheet, $data, 'Dues Overview');
} else {
    // Export only the selected year, not all years with payments
    $sheet = $spreadsheet->getActiveSheet();
    $sheetTitle = 'Year ' . $baseYear;
    $sheetData = buildDuesData($pdo, (int)$baseYear);
    // Apply status filter
    $sheetData = filterDuesByStatus($sheetData, $statusFilter);
    
    // Prevent export if no data matches the filter
    if (empty($sheetData)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => "No households match the $statusFilter filter for $baseYear"]);
        exit;
    }
    
    writeDuesSheet($sheet, $sheetData, $sheetTitle);

    $spreadsheet->setActiveSheetIndex(0);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$filename = $statusFilter === 'all' ? "dues_overview_{$baseYear}" : "dues_overview_{$baseYear}_{$statusFilter}";
header('Content-Disposition: attachment;filename="' . $filename . '_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;