<?php
/**
 * PDF Export Handler for AI-AgriLinkPH
 */
include('includes/session.php');
require_login();

include('includes/functions.php');

// Get data from session (stored after upload)
if (!isset($_SESSION['dashboard_data'])) {
    header('Location: index.php?error=no_data');
    exit;
}

$data = $_SESSION['dashboard_data'];
$datasetMeta = $data['datasetMeta'] ?? [];
$metrics = $data['metrics'] ?? [];
$headers = $data['headers'] ?? [];
$previewRows = $data['previewRows'] ?? [];
$allRows = $data['allRows'] ?? $previewRows; // Use all rows if available
$insight = $data['insight'] ?? '';

// Check if TCPDF is available
$useTCPDF = class_exists('TCPDF');

if ($useTCPDF) {
    // Generate PDF using TCPDF
    $pdf = generate_pdf_tcpdf($datasetMeta, $metrics, $headers, $previewRows, $insight);
    $pdf->Output('agrilink_report_' . date('Ymd_His') . '.pdf', 'D');
} else {
    // Fallback: Output HTML (user can print as PDF using browser)
    $pdfContent = generate_pdf_html($datasetMeta, $metrics, $headers, $previewRows, $insight);
    header('Content-Type: text/html; charset=UTF-8');
    echo $pdfContent;
}

