<?php
/**
 * CSV Export Handler for AI-AgriLinkPH
 */
include('includes/session.php');
require_login();

// Get data from session
if (!isset($_SESSION['dashboard_data'])) {
    header('Location: index.php?error=no_data');
    exit;
}

$data = $_SESSION['dashboard_data'];
$headers = $data['headers'] ?? [];
$allRows = $data['allRows'] ?? $data['previewRows'] ?? [];
$datasetMeta = $data['datasetMeta'] ?? [];

// Set headers for CSV download
$filename = 'agrilink_data_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output BOM for UTF-8 (helps Excel display special characters correctly)
echo "\xEF\xBB\xBF";

// Open output stream
$output = fopen('php://output', 'w');

// Write headers
if (!empty($headers)) {
    fputcsv($output, $headers);
}

// Write data rows
foreach ($allRows as $row) {
    $csvRow = [];
    foreach ($headers as $header) {
        $csvRow[] = $row[$header] ?? '';
    }
    fputcsv($output, $csvRow);
}

fclose($output);
exit;

