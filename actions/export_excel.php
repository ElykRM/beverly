<?php
// actions/export_excel.php
require_once '../includes/auth.php';

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
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

// Organize data by dues_status
$dataByStatus = [
    'Paid' => [],
    'Unpaid' => [],
    'Overdue' => []
];

foreach ($data as $row) {
    $status = $row['dues_status'] ?? 'Unknown';
    if (isset($dataByStatus[$status])) {
        $dataByStatus[$status][] = $row;
    }
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();

$statusOrder = ['Paid', 'Unpaid', 'Overdue'];
$sheetIndex = 0;

foreach ($statusOrder as $status) {
    if (empty($dataByStatus[$status])) {
        continue; // Skip if no data for this status
    }

    if ($sheetIndex === 0) {
        $sheet = $spreadsheet->getActiveSheet();
    } else {
        $sheet = $spreadsheet->createSheet();
    }

    // Set sheet title
    $sheet->setTitle($status);

    // Headers
    $headers = ['Block', 'Lot', 'Last Name', 'First Name', 'Middle Name', 'Street', 'Home Status'];
    $sheet->fromArray($headers, null, 'A1');

    // Style headers: bold, centered, borders
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD3D3D3']]
    ];
    $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

    // Add data rows
    $rowNum = 2;
    foreach ($dataByStatus[$status] as $row) {
        $sheet->fromArray([
            $row['block'] ?? '',
            $row['lot'] ?? '',
            $row['last_name'] ?? '',
            $row['first_name'] ?? '',
            $row['middle_name'] ?? '',
            $row['street'] ?? '',
            $row['status'] ?? ''
        ], null, 'A' . $rowNum);

        // Apply borders to this row
        $sheet->getStyle("A{$rowNum}:G{$rowNum}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $rowNum++;
    }

    // Add total row
    $sheet->setCellValue('F' . $rowNum, 'TOTAL');
    $sheet->setCellValue('G' . $rowNum, count($dataByStatus[$status]));
    $sheet->getStyle("A{$rowNum}:G{$rowNum}")->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    // Freeze header row
    $sheet->freezePane('A2');

    // Auto-size columns
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $sheetIndex++;
}

// Set active sheet to first one
$spreadsheet->setActiveSheetIndex(0);

// clear any buffered output
if (ob_get_length()) {
    ob_end_clean();
}

// Send as .xlsx file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="hoa_report_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
