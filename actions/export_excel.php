<?php
// actions/export_excel.php

// Make sure Composer autoload is correct relative to this file
// turn off error output to avoid corrupting excel
ini_set('display_errors', 0);
error_reporting(0);

require_once '../vendor/autoload.php'; // Adjust path if your folder structure is different

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? [];

if (empty($data)) {
    http_response_code(400);
    exit('No data received');
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$headers = ['Block', 'Lot', 'Last Name', 'First Name', 'Middle Name', 'Street', 'Status', 'Dues Status'];
$sheet->fromArray($headers, null, 'A1');

// Style headers: bold, centered, borders
$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Add data rows
$rowNum = 2;
foreach ($data as $row) {
    $sheet->fromArray([
        $row['block'] ?? '',
        $row['lot'] ?? '',
        $row['last_name'] ?? '',
        $row['first_name'] ?? '',
        $row['middle_name'] ?? '',
        $row['street'] ?? '',
        $row['status'] ?? '',
        $row['dues_status'] ?? ''
    ], null, 'A' . $rowNum);
    // apply borders to this row
    $sheet->getStyle("A{$rowNum}:H{$rowNum}")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $rowNum++;
}

// Freeze header row
$sheet->freezePane('A2');

// clear any buffered output
if (ob_get_length()) {
    ob_end_clean();
}

// Auto-size columns
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Send as .xlsx file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="hoa_report_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;