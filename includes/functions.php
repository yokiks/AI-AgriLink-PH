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


