<?php
/**
 * JSON Export Handler for AI-AgriLinkPH
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
$metrics = $data['metrics'] ?? [];
$insight = $data['insight'] ?? '';

// Prepare JSON data
$jsonData = [
    'export_info' => [
        'generated_at' => date('Y-m-d H:i:s'),
        'dataset_name' => $datasetMeta['fileName'] ?? 'Unknown',
        'total_rows' => count($allRows),
        'total_columns' => count($headers)
    ],
    'dataset_metadata' => $datasetMeta,
    'headers' => $headers,
    'data' => $allRows,
    'metrics' => $metrics,
    'ai_insight' => strip_tags($insight)
];

// Set headers for JSON download
$filename = 'agrilink_data_' . date('Ymd_His') . '.json';
header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output JSON with pretty print
echo json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;

