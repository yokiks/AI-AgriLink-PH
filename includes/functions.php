<?php
// Robust CSV parser + metrics generator for AI-AgriLink PH

if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach ($arr as $k => $v) return $k;
        return null;
    }
}
if (!function_exists('array_key_last')) {
    function array_key_last(array $arr) {
        $k = null;
        foreach ($arr as $key => $value) $k = $key;
        return $k;
    }
}

function parse_csv($filepath) {
    $rows = [];
    $headers = [];

    if (!file_exists($filepath)) return [$headers, $rows];

    if (($handle = fopen($filepath, "r")) !== false) {
        $rawHeaders = fgetcsv($handle);
        if ($rawHeaders === false) { fclose($handle); return [$headers, $rows]; }

        $headers = array_map(function($h) {
            $h = trim((string)$h);
            return $h === '' ? 'Column' : $h;
        }, $rawHeaders);

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || count(array_filter($data, fn($v)=> trim((string)$v) !== '')) === 0) continue;

            $assoc = [];
            for ($i = 0; $i < count($headers); $i++) {
                $assoc[$headers[$i]] = isset($data[$i]) ? trim((string)$data[$i]) : null;
            }
            $rows[] = $assoc;
        }
        fclose($handle);
    }

    return [$headers, $rows];
}

function find_key_in_row(array $row, string $needle) {
    $needle = strtolower($needle);
    foreach ($row as $k => $v) {
        if (stripos($k, $needle) !== false) return $k;
    }
    return null;
}

function parse_number($value) {
    if ($value === null || $value === '') return 0.0;
    $clean = preg_replace('/[^\d\.\-]/', '', strval($value));
    return $clean === '' ? 0.0 : floatval($clean);
}

function compute_metrics($headers, $rows) {
    $regionTotals = [];
    $cropTotals = [];
    $yearlyTotals = [];

    foreach ($rows as $row) {
        $regionKey = find_key_in_row($row, 'region');
        $cropKey = find_key_in_row($row, 'crop');
        $yearKey = find_key_in_row($row, 'year');
        $prodKey = find_key_in_row($row, 'production') ?? find_key_in_row($row, 'volume') ?? find_key_in_row($row, 'value');

        $region = $regionKey ? ($row[$regionKey] ?? 'Unknown') : 'Unknown';
        $crop = $cropKey ? ($row[$cropKey] ?? 'Unknown Crop') : 'Unknown Crop';
        $year = $yearKey ? ($row[$yearKey] ?? 'Unknown Year') : 'Unknown Year';
        $prod = $prodKey ? parse_number($row[$prodKey] ?? 0) : 0.0;

        if (!isset($regionTotals[$region])) $regionTotals[$region] = 0.0;
        $regionTotals[$region] += $prod;

        if (!isset($cropTotals[$crop])) $cropTotals[$crop] = 0.0;
        $cropTotals[$crop] += $prod;

        if (!isset($yearlyTotals[$year])) $yearlyTotals[$year] = 0.0;
        $yearlyTotals[$year] += $prod;
    }

    arsort($regionTotals);
    arsort($cropTotals);
    ksort($yearlyTotals, SORT_NUMERIC);

    $highest = $regionTotals ? array_key_first($regionTotals) : 'N/A';
    $lowest = $regionTotals ? array_key_last($regionTotals) : 'N/A';

    return [
        'region_totals' => $regionTotals,
        'crop_totals' => $cropTotals,
        'yearly_totals' => $yearlyTotals,
        'highest_region' => $highest,
        'lowest_region' => $lowest
    ];
}

function generate_insight_text($metrics, $rows = []) {
    $highest = $metrics['highest_region'] ?? 'N/A';
    $lowest = $metrics['lowest_region'] ?? 'N/A';
    $regionCount = count($metrics['region_totals'] ?? []);
    $cropCount = count($metrics['crop_totals'] ?? []);
    $yearCount = count($metrics['yearly_totals'] ?? []);

    $totalProduction = array_sum($metrics['region_totals'] ?? []);
    $averageRegional = $regionCount ? $totalProduction / $regionCount : 0;

    $years = array_keys($metrics['yearly_totals'] ?? []);
    $trendPhrase = 'no clear trend';
    if (count($years) >= 2) {
        $first = reset($metrics['yearly_totals']);
        $last = end($metrics['yearly_totals']);
        $pct = $first > 0 ? (($last - $first) / $first) * 100 : 0;
        if ($pct > 10) $trendPhrase = 'an upward trend';
        elseif ($pct < -10) $trendPhrase = 'a downward trend';
        else $trendPhrase = 'a relatively stable trend';
    }

    $topCrop = $metrics['crop_totals'] ? array_key_first($metrics['crop_totals']) : 'N/A';
    $topCropShare = $topCrop !== 'N/A' && $totalProduction > 0 ? round((($metrics['crop_totals'][$topCrop] ?? 0) / $totalProduction) * 100, 1) : 0;

    $insight = "<strong>Summary:</strong> Between the selected years, total reported production across {$regionCount} region(s) and {$cropCount} crop(s) is <strong>" . number_format($totalProduction) . "</strong> units. ";
    $insight .= "Overall the data shows {$trendPhrase}, with <strong>{$highest}</strong> leading production and <strong>{$lowest}</strong> trailing. ";
    $insight .= "The top crop is <strong>{$topCrop}</strong> (≈{$topCropShare}% of combined production). ";
    $insight .= "Average regional production is approximately <strong>" . round($averageRegional, 1) . "</strong> units. ";
    $insight .= "Recommendation: foster targeted partnerships between high-performing regions (like <em>{$highest}</em>) and lower-performing ones (such as <em>{$lowest}</em>) to balance supply chains — aligning with <strong>SDG 2</strong> and <strong>SDG 17</strong>.";

    return $insight;
}

/**
 * Parse Excel file (.xlsx) - requires PhpSpreadsheet library
 * Falls back to CSV parsing if library not available
 */
function parse_excel($filepath) {
    $headers = [];
    $rows = [];
    
    // Check if PhpSpreadsheet is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        // Fallback: try to read as CSV if it's actually a CSV file
        if (pathinfo($filepath, PATHINFO_EXTENSION) === 'csv') {
            return parse_csv($filepath);
        }
        return [$headers, $rows];
    }
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();
        
        if (empty($data)) {
            return [$headers, $rows];
        }
        
        // First row as headers
        $rawHeaders = array_shift($data);
        $headers = array_map(function($h) {
            $h = trim((string)$h);
            return $h === '' ? 'Column' : $h;
        }, $rawHeaders);
        
        // Process data rows
        foreach ($data as $rowData) {
            if (!is_array($rowData) || count(array_filter($rowData, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            
            $assoc = [];
            for ($i = 0; $i < count($headers); $i++) {
                $assoc[$headers[$i]] = isset($rowData[$i]) ? trim((string)$rowData[$i]) : null;
            }
            $rows[] = $assoc;
        }
    } catch (Exception $e) {
        // Error reading Excel file
        return [$headers, $rows];
    }
    
    return [$headers, $rows];
}

/**
 * Validate uploaded file (CSV or Excel)
 */
function validate_uploaded_file($file) {
    $errors = [];
    
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error occurred.';
        return $errors;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['csv', 'xlsx', 'xls'];
    
    if (!in_array($extension, $allowed)) {
        $errors[] = 'Only CSV and Excel (.xlsx, .xls) files are supported.';
        return $errors;
    }
    
    // Check file size (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds 10MB limit.';
        return $errors;
    }
    
    if (!is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'File upload verification failed.';
        return $errors;
    }
    
    return $errors;
}

/**
 * Generate PDF report from dashboard data
 */
function generate_pdf_report($datasetMeta, $metrics, $headers, $previewRows, $insight) {
    // Check if TCPDF is available, otherwise use simple HTML to PDF
    if (class_exists('TCPDF')) {
        return generate_pdf_tcpdf($datasetMeta, $metrics, $headers, $previewRows, $insight);
    } else {
        // Fallback: Generate HTML that can be printed as PDF
        return generate_pdf_html($datasetMeta, $metrics, $headers, $previewRows, $insight);
    }
}

/**
 * Generate PDF using TCPDF
 */
function generate_pdf_tcpdf($datasetMeta, $metrics, $headers, $previewRows, $insight) {
    require_once(__DIR__ . '/../vendor/autoload.php');
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('AI-AgriLink PH');
    $pdf->SetAuthor('AI-AgriLink PH');
    $pdf->SetTitle('Agriculture Dashboard Report');
    $pdf->SetSubject('Agricultural Data Analysis Report');
    
    $pdf->SetHeaderData('', 0, 'AI-AgriLink PH Dashboard Report', date('F d, Y H:i:s'));
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Agriculture Dashboard Report', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Dataset Info
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Dataset Information', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'File: ' . $datasetMeta['fileName'], 0, 1);
    $pdf->Cell(0, 6, 'Rows: ' . number_format($datasetMeta['rowCount']), 0, 1);
    $pdf->Cell(0, 6, 'Columns: ' . $datasetMeta['columnCount'], 0, 1);
    $pdf->Cell(0, 6, 'Regions: ' . $datasetMeta['regionCount'], 0, 1);
    $pdf->Ln(5);
    
    // Metrics
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Key Metrics', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Top Region: ' . ($metrics['highest_region'] ?? 'N/A'), 0, 1);
    $pdf->Cell(0, 6, 'Region to Watch: ' . ($metrics['lowest_region'] ?? 'N/A'), 0, 1);
    $pdf->Ln(5);
    
    // Data Preview Table
    if (!empty($headers) && !empty($previewRows)) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Data Preview', 0, 1);
        
        $colWidth = 180 / count($headers);
        $pdf->SetFont('helvetica', 'B', 8);
        foreach ($headers as $header) {
            $pdf->Cell($colWidth, 6, substr($header, 0, 15), 1, 0, 'C');
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 7);
        $rowCount = 0;
        foreach ($previewRows as $row) {
            if ($rowCount >= 10) break; // Limit rows in PDF
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                $pdf->Cell($colWidth, 5, substr($value, 0, 12), 1, 0, 'L');
            }
            $pdf->Ln();
            $rowCount++;
        }
        $pdf->Ln(5);
    }
    
    // AI Insight
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'AI Insights', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, strip_tags($insight), 0, 'L');
    
    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on ' . date('F d, Y H:i:s'), 0, 0, 'C');
    
    return $pdf;
}

/**
 * Generate HTML version for PDF (fallback)
 */
function generate_pdf_html($datasetMeta, $metrics, $headers, $previewRows, $insight) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Agriculture Dashboard Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #166534; text-align: center; }
            h2 { color: #15803d; border-bottom: 2px solid #15803d; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #15803d; color: white; }
            .info-box { background: #f0fdf4; padding: 15px; border-left: 4px solid #15803d; margin: 15px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <h1>🌾 AI-AgriLink PH Dashboard Report</h1>
        <p style="text-align: center; color: #666;">Generated on <?= date('F d, Y H:i:s') ?></p>
        
        <h2>Dataset Information</h2>
        <div class="info-box">
            <p><strong>File Name:</strong> <?= htmlspecialchars($datasetMeta['fileName']) ?></p>
            <p><strong>Total Rows:</strong> <?= number_format($datasetMeta['rowCount']) ?></p>
            <p><strong>Total Columns:</strong> <?= $datasetMeta['columnCount'] ?></p>
            <p><strong>Regions Processed:</strong> <?= $datasetMeta['regionCount'] ?></p>
        </div>
        
        <h2>Key Metrics</h2>
        <div class="info-box">
            <p><strong>Top Region:</strong> <?= htmlspecialchars($metrics['highest_region'] ?? 'N/A') ?></p>
            <p><strong>Region to Watch:</strong> <?= htmlspecialchars($metrics['lowest_region'] ?? 'N/A') ?></p>
        </div>
        
        <?php if (!empty($headers) && !empty($previewRows)): ?>
        <h2>Data Preview</h2>
        <table>
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($previewRows, 0, 10) as $row): ?>
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <td><?= htmlspecialchars($row[$header] ?? '') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <h2>AI Insights</h2>
        <div class="info-box">
            <?= $insight ?>
        </div>
        
        <div class="footer">
            <p>© <?= date('Y') ?> AI-AgriLink PH · Sustainable Agriculture Dashboard</p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}


