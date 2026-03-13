<?php
// actions/export_dues_excel.php

require_once '../vendor/autoload.php'; // Adjust this path to your vendor folder

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? [];

if (empty($data)) {
    http_response_code(400);
    exit;
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$headers = ['Block', 'Lot', 'Household', 'Total Unpaid', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$sheet->fromArray($headers, null, 'A1');

// Style headers: bold, centered, borders
$headerStyle = [
    'font' => ['bold' => true, 'size' => 12],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

// Add data rows
$rowNum = 2;
foreach ($data as $index => $row) {
    // Set block
    $sheet->setCellValue('A' . $rowNum, $row['block']);
    // Set lot
    $sheet->setCellValue('B' . $rowNum, $row['lot']);
    // Set household name
    $sheet->setCellValue('C' . $rowNum, $row['household_name']);
    // Set total_unpaid
    $totalValue = is_numeric($row['total_unpaid']) ? (float)$row['total_unpaid'] : $row['total_unpaid'];
    if (is_numeric($totalValue)) {
        $sheet->setCellValueExplicit('D' . $rowNum, $totalValue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    } else {
        $sheet->setCellValueExplicit('D' . $rowNum, $totalValue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
    // Set months
    $months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    foreach ($months as $i => $month) {
        $col = chr(69 + $i); // E to P
        $value = $row[$month];
        if (is_numeric($value)) {
            $sheet->setCellValueExplicit($col . $rowNum, (float)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        } else {
            $sheet->setCellValueExplicit($col . $rowNum, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }

    // Apply currency format to numeric columns (D to P)
    $sheet->getStyle("D$rowNum:P$rowNum")->getNumberFormat()->setFormatCode('_(* #,##0.00_);_(* (#,##0.00);_(* "-"_);_(@_)');

    // If this is the total row, make it bold + light gray background
    if ($index === count($data) - 1) {
        $sheet->getStyle("A$rowNum:P$rowNum")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }

    $rowNum++;
}

// Auto-size columns
foreach (range('A', 'P') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Freeze header row
$sheet->freezePane('A2');

// Send as .xlsx
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="dues_overview_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;