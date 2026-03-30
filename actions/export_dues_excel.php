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

$input = json_decode(file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];

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

function writeDuesSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $data, string $title): void
{
    $safeTitle = preg_replace('/[\\\\\/?*\[\]:]/', '', $title);
    $sheet->setTitle(substr($safeTitle, 0, 31));

    $headers = ['Block', 'Lot', 'Household', 'Total Unpaid', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $sheet->fromArray($headers, null, 'A1');

    $headerStyle = [
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

    $months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    $rowNum = 2;
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

        $sheet->getStyle("D$rowNum:P$rowNum")->getNumberFormat()->setFormatCode('_(* #,##0.00_);_(* (#,##0.00);_(* "-"_);_(@_)');
        $rowNum++;
    }

    $lastDataRow = $rowNum - 1;
    if ($lastDataRow >= 2) {
        $sheet->setCellValue('C' . $rowNum, 'TOTAL');
        $sheet->setCellValue('D' . $rowNum, "=SUM(D2:D{$lastDataRow})");
        foreach (range('E', 'P') as $col) {
            $sheet->setCellValue($col . $rowNum, "=SUM({$col}2:{$col}{$lastDataRow})");
        }

        $sheet->getStyle("A{$rowNum}:P{$rowNum}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $sheet->getStyle("D{$rowNum}:P{$rowNum}")->getNumberFormat()->setFormatCode('_(* #,##0.00_);_(* (#,##0.00);_(* "-"_);_(@_)');
    }

    foreach (range('A', 'P') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $sheet->freezePane('A2');
}

function getPaidYears(PDO $pdo): array
{
    $stmt = $pdo->query("\n        SELECT period_year, period_to_year, amount\n        FROM payments\n    ");
    $payments = $stmt->fetchAll();

    $years = [];
    foreach ($payments as $p) {
        $amount = isset($p['amount']) ? (float)$p['amount'] : 0.0;
        if ($amount <= 0) {
            continue;
        }

        $startYear = isset($p['period_year']) ? (int)$p['period_year'] : 0;
        $endYear = isset($p['period_to_year']) && $p['period_to_year'] !== null
            ? (int)$p['period_to_year']
            : $startYear;

        if ($startYear <= 0 || $endYear <= 0) {
            continue;
        }

        if ($endYear < $startYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }

        for ($y = $startYear; $y <= $endYear; $y++) {
            $years[$y] = true;
        }
    }

    $yearList = array_keys($years);
    sort($yearList, SORT_NUMERIC);
    return $yearList;
}

$spreadsheet = new Spreadsheet();
$baseYear = isset($input['year']) ? (int)$input['year'] : (int)date('Y');

if (isset($input['data']) && !isset($input['year'])) {
    // Legacy fallback path for older front-end requests.
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
    $paidYears = getPaidYears($pdo);
    if (empty($paidYears)) {
        // Fallback: keep export usable even if no paid records exist yet.
        $paidYears = [$baseYear];
    }

    foreach ($paidYears as $index => $year) {
        if ($index === 0) {
            $sheet = $spreadsheet->getActiveSheet();
        } else {
            $sheet = $spreadsheet->createSheet();
        }

        $sheetTitle = 'Year ' . $year;
        $sheetData = buildDuesData($pdo, (int)$year);
        writeDuesSheet($sheet, $sheetData, $sheetTitle);
    }

    $spreadsheet->setActiveSheetIndex(0);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="dues_overview_paid_years_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;