<?php
/**
 * Excel Export Handler for AI-AgriLinkPH
 */
include('includes/session.php');
require_login();

include('includes/functions.php');

// Get data from session
if (!isset($_SESSION['dashboard_data'])) {
    header('Location: index.php?error=no_data');
    exit;
}

$data = $_SESSION['dashboard_data'];
$headers = $data['headers'] ?? [];
$allRows = $data['allRows'] ?? $data['previewRows'] ?? [];
$datasetMeta = $data['datasetMeta'] ?? [];

// Check if PhpSpreadsheet is available
if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    try {
        require_once(__DIR__ . '/../vendor/autoload.php');
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet title
        $sheet->setTitle('Agricultural Data');
        
        // Write headers
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $sheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(true);
            $col++;
        }
        
        // Write data rows
        $rowNum = 2;
        foreach ($allRows as $row) {
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($col, $rowNum, $row[$header] ?? '');
                $col++;
            }
            $rowNum++;
        }
        
        // Auto-size columns
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        
        // Set headers for download
        $filename = 'agrilink_data_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        // Fallback to CSV if Excel generation fails
        header('Location: export_csv.php');
        exit;
    }
} else {
    // Fallback to CSV if PhpSpreadsheet not available
    header('Location: export_csv.php');
    exit;
}

