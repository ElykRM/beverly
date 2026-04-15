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

    // Row 1: Green header banner
    $sheet->setCellValue('A1', 'HOUSEHOLD REPORTS');
    $sheet->mergeCells('A1:H1');
    $sheet->getRowDimension(1)->setRowHeight(28);
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F8449']]
    ]);

    // Row 2: Spacer
    $sheet->getRowDimension(2)->setRowHeight(6);

    // Row 3: Column headers with green background
    $headers = ['Block', 'Lot', 'Last Name', 'First Name', 'Middle Name', 'Street', 'Home Status', 'Dues Status'];
    $sheet->fromArray($headers, null, 'A3');
    $sheet->getRowDimension(3)->setRowHeight(20);
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F8449']]
    ];
    $sheet->getStyle('A3:H3')->applyFromArray($headerStyle);

    // Add data rows (starting from row 4)
    $rowNum = 4;
    foreach ($dataByStatus[$status] as $row) {
        $sheet->fromArray([
            $row['block'] ?? '',
            $row['lot'] ?? '',
            $row['last_name'] ?? '',
            $row['first_name'] ?? '',
            $row['middle_name'] ?? '',
            $row['street'] ?? '',
            $row['status'] ?? '',
            $status
        ], null, 'A' . $rowNum);

        $sheet->getRowDimension($rowNum)->setRowHeight(18);
        // Apply borders to this row
        $sheet->getStyle("A{$rowNum}:H{$rowNum}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $rowNum++;
    }

    // Row before total: Spacer
    $sheet->getRowDimension($rowNum)->setRowHeight(4);
    $rowNum++;

    // Add total row with gray background and medium borders
    $sheet->setCellValue('G' . $rowNum, 'TOTAL');
    $sheet->setCellValue('H' . $rowNum, count($dataByStatus[$status]));
    $sheet->getRowDimension($rowNum)->setRowHeight(20);
    $sheet->getStyle("A{$rowNum}:H{$rowNum}")->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD3D3D3']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
    ]);

    // Freeze header row
    $sheet->freezePane('A4');

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(12);
    $sheet->getColumnDimension('B')->setWidth(10);
    $sheet->getColumnDimension('C')->setWidth(18);
    $sheet->getColumnDimension('D')->setWidth(16);
    $sheet->getColumnDimension('E')->setWidth(16);
    $sheet->getColumnDimension('F')->setWidth(14);
    $sheet->getColumnDimension('G')->setWidth(20);
    $sheet->getColumnDimension('H')->setWidth(14);

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
