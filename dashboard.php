<?php
date_default_timezone_set('Asia/Manila');

// Session protection
include('includes/session.php');
require_login();

include('includes/header.php');
include('includes/functions.php');
?>

<!-- Background wrapper with gradient and image -->
<div class="fixed inset-0 bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 overflow-hidden z-0">
  <!-- Background Image -->
  <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-25" style="background-image: url('assets/img/homepage5.jpg');"></div>
  
  <!-- Gradient Overlay -->
  <div class="absolute inset-0 bg-gradient-to-br from-green-50/80 via-emerald-50/80 to-teal-50/80"></div>
  
  <!-- Decorative background elements -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-80 h-80 bg-green-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
    <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-emerald-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-teal-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
  </div>
</div>

<div class="relative z-10">
<?php

$uploadError = null;
$uploadSuccess = null;
$headers = [];
$rows = [];
$metrics = [];
$insight = '';
$datasetMeta = [
  'fileName' => '',
  'fileSize' => 0,
  'rowCount' => 0,
  'columnCount' => 0,
  'regionCount' => 0,
];
$previewRows = [];
$previewLimit = 6;
$hasMorePreviewRows = false;

// Check if file was uploaded
if (!isset($_FILES['csvFile']) && empty($uploadError) && empty($uploadSuccess)) {
  echo "<div class='mx-auto max-w-3xl text-center mt-16 rounded-2xl border border-red-200 bg-red-50 px-6 py-10 text-red-800 shadow-sm'>
          <h2 class='text-2xl font-semibold mb-3'>⚠️ No File Uploaded</h2>
          <p class='text-sm sm:text-base'>To generate a dashboard, please upload a dataset first.</p>
          <a href='index.php' class='mt-6 inline-flex items-center justify-center rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-green-800'>Return to upload</a>
        </div>";
  include('includes/footer.php');
  exit;
}

$file = $_FILES['csvFile'] ?? null;

if (!$file || !is_array($file)) {
  $uploadError = 'No file was provided. Please try uploading again.';
} else {
  // Validate file
  $validationErrors = validate_uploaded_file($file);
  if (!empty($validationErrors)) {
    $uploadError = implode(' ', $validationErrors);
  } else {
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uploadsDir = __DIR__ . '/uploads';
    
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0777, true)) {
      $uploadError = 'We could not prepare the uploads directory. Please check folder permissions.';
    } else {
      $baseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
      if ($baseName === '') {
        $baseName = 'dataset';
      }
      $timestamp = date('Ymd_His');
      $targetPath = $uploadsDir . '/' . $baseName . '_' . $timestamp . '.' . $extension;

      if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $uploadError = 'We could not save the uploaded file. Please try again.';
      } else {
        // Parse file based on extension
        if ($extension === 'csv') {
          [$headers, $rows] = parse_csv($targetPath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
          [$headers, $rows] = parse_excel($targetPath);
        } else {
          $uploadError = 'Unsupported file format.';
        }

        if (empty($headers)) {
          $uploadError = 'We could not detect any headers in your file. Please ensure the first row contains column names.';
        } elseif (empty($rows)) {
          $uploadError = 'No data rows were found in your file. Please verify the file contents.';
        } else {
          $metrics = compute_metrics($headers, $rows);
          $insight = generate_insight_text($metrics, $rows);

          $datasetMeta = [
            'fileName' => $file['name'],
            'fileSize' => $file['size'],
            'rowCount' => count($rows),
            'columnCount' => count($headers),
            'regionCount' => count($metrics['region_totals'] ?? []),
          ];

          $previewRows = array_slice($rows, 0, $previewLimit);
          $hasMorePreviewRows = $datasetMeta['rowCount'] > $previewLimit;
          
          // Store data in session for export (include all rows for CSV/Excel export)
          $_SESSION['dashboard_data'] = [
            'datasetMeta' => $datasetMeta,
            'metrics' => $metrics,
            'headers' => $headers,
            'previewRows' => $previewRows,
            'allRows' => $rows, // Store all rows for full export
            'insight' => $insight
          ];
          
          $uploadSuccess = 'File uploaded and processed successfully!';
        }
      }
    }
  }
}

if ($uploadError) {
  echo "<div class='mx-auto max-w-3xl text-center mt-16 rounded-2xl border border-red-200 bg-red-50 px-6 py-10 text-red-800 shadow-sm'>
          <h2 class='text-2xl font-semibold mb-3'>⚠️ Upload Issue</h2>
          <p class='text-sm sm:text-base'>" . htmlspecialchars($uploadError, ENT_QUOTES, 'UTF-8') . "</p>
          <a href='index.php' class='mt-6 inline-flex items-center justify-center rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-green-800'>Return to upload</a>
        </div>";
  include('includes/footer.php');
  exit;
}
?>

<div class="relative mx-auto max-w-7xl">
  <style>
    .chart-wrapper {
      width: 100%;
      min-height: 18rem;
    }

    .chart-wrapper canvas {
      width: 100% !important;
      height: 100% !important;
    }

    @media (max-width: 640px) {
      .chart-wrapper {
        min-height: 14rem;
      }

      .chart-card {
        padding: 1.25rem;
      }
    }

    /* Enhanced animations and transitions */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes pulse-glow {
      0%, 100% {
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
      }
      50% {
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.6);
      }
    }

    @keyframes scan-line {
      0% {
        transform: translateY(-100%);
        opacity: 0;
      }
      50% {
        opacity: 1;
      }
      100% {
        transform: translateY(400%);
        opacity: 0;
      }
    }

    .metric-card {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      animation: fadeInUp 0.6s ease-out;
    }

    .metric-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .metric-card:nth-child(1) { animation-delay: 0.1s; }
    .metric-card:nth-child(2) { animation-delay: 0.2s; }
    .metric-card:nth-child(3) { animation-delay: 0.3s; }
    .metric-card:nth-child(4) { animation-delay: 0.4s; }

    .ai-console {
      position: relative;
      overflow: hidden;
    }

    .ai-console::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.5), transparent);
      animation: scan-line 3s linear infinite;
    }

    .typing-cursor {
      display: inline-block;
      width: 2px;
      height: 1em;
      background: #10b981;
      animation: blink 1s infinite;
      margin-left: 2px;
      vertical-align: baseline;
    }

    @keyframes blink {
      0%, 50% { opacity: 1; }
      51%, 100% { opacity: 0; }
    }

    .status-indicator {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);
      animation: pulse-dot 2s ease-in-out infinite;
    }

    @keyframes pulse-dot {
      0%, 100% {
        transform: scale(1);
        opacity: 1;
      }
      50% {
        transform: scale(1.2);
        opacity: 0.8;
      }
    }

    .gradient-text {
      background: linear-gradient(135deg, #10b981, #06b6d4, #8b5cf6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .section-card {
      transition: all 0.3s ease;
    }

    .section-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
    }
  </style>
  <!-- Success/Error Messages -->
  <?php if ($uploadSuccess): ?>
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 shadow-sm">
      <div class="flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <p class="font-semibold"><?= htmlspecialchars($uploadSuccess, ENT_QUOTES, 'UTF-8') ?></p>
          <p class="text-sm mt-1">Your dashboard is ready. Scroll down to explore the insights.</p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Top Navigation -->
  <nav class="sticky top-[4.5rem] z-30 mb-4 flex flex-col gap-2 rounded-2xl border border-green-100 bg-white/90 px-3 py-2 text-sm shadow-sm backdrop-blur md:flex-row md:items-center md:justify-between lg:px-4">
    <div>
      <p class="text-xs uppercase tracking-wide text-green-600">AI-AgriLink Dashboard</p>
      <h1 class="text-lg font-semibold text-slate-800">Insights Overview</h1>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="#metrics" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white text-xs sm:text-sm">Metrics</a>
      <a href="#dataset" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white text-xs sm:text-sm">Dataset</a>
      <a href="#chart-region" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white text-xs sm:text-sm">Regions</a>
      <a href="#chart-trend" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white text-xs sm:text-sm">Trend</a>
      <a href="#chart-crop" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white text-xs sm:text-sm">Crops</a>
      <a href="#insight" class="rounded-full border border-emerald-300 px-3 py-1 font-semibold text-emerald-700 transition hover:bg-emerald-600 hover:text-white text-xs sm:text-sm">AI Console</a>
      <div class="relative inline-block">
        <button onclick="toggleExportMenu()" class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-600 px-4 py-1.5 font-semibold text-white shadow-sm transition hover:bg-blue-700 text-xs sm:text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Download
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div id="exportMenu" class="hidden absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white shadow-lg z-50">
          <a href="export_pdf.php" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-b border-gray-100">
            <span class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              PDF Report
            </span>
          </a>
          <a href="export_csv.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-b border-gray-100">
            <span class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              CSV Data
            </span>
          </a>
          <a href="export_excel.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-b border-gray-100">
            <span class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Excel (.xlsx)
            </span>
          </a>
          <a href="export_json.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
            <span class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
              </svg>
              JSON Data
            </span>
          </a>
        </div>
      </div>
      <script>
        function toggleExportMenu() {
          const menu = document.getElementById('exportMenu');
          menu.classList.toggle('hidden');
        }
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
          const menu = document.getElementById('exportMenu');
          const button = event.target.closest('button');
          if (!menu.contains(event.target) && !button) {
            menu.classList.add('hidden');
          }
        });
      </script>
      <a href="index.php" class="inline-flex items-center gap-2 rounded-full border border-green-300 bg-green-600 px-4 py-1.5 font-semibold text-white shadow-sm transition hover:bg-green-700 text-xs sm:text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2M12 4v12m0 0-4-4m4 4 4-4" />
        </svg>
        Upload New
      </a>
    </div>
  </nav>

  <div class="space-y-6 lg:space-y-8">
    <header class="section-card rounded-2xl border border-green-100 bg-gradient-to-br from-white via-green-50/30 to-white px-4 py-5 shadow-sm">
      <div class="flex items-center gap-2 mb-2">
        <p class="text-xs uppercase tracking-wide text-green-600">AI-AgriLink PH · 2020–2025 Dataset</p>
        <span class="h-1 w-1 rounded-full bg-green-500 animate-pulse"></span>
      </div>
      <h1 class="mt-3 text-3xl font-bold bg-gradient-to-r from-green-900 via-green-700 to-green-900 bg-clip-text text-transparent sm:text-4xl">🌾 Interactive Agriculture Dashboard</h1>
      <p class="mt-4 max-w-3xl text-sm text-gray-600 sm:text-base leading-relaxed">
        Explore production hotspots, monitor yearly performance, and spotlight crop balances to support data-driven,
        sustainable agriculture decisions across Philippine regions.
      </p>
      <div class="mt-6 flex flex-wrap gap-3 text-xs sm:text-sm text-gray-600">
        <span class="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1 text-green-800">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7h18M3 12h18M3 17h18" />
          </svg>
          <?= htmlspecialchars($datasetMeta['fileName'], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <?= number_format($datasetMeta['rowCount']) ?> rows · <?= $datasetMeta['columnCount'] ?> columns
        </span>
        <span class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-sky-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2" />
          </svg>
          Processed <?= number_format($datasetMeta['regionCount']) ?> region<?= $datasetMeta['regionCount'] === 1 ? '' : 's' ?>
        </span>
      </div>
    </header>

    <!-- Metric Cards -->
    <section id="metrics" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="metric-card rounded-2xl border border-green-100 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-green-600">Top Region</p>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
          </div>
          <p class="mt-2 text-2xl font-bold text-green-900"><?= htmlspecialchars($metrics['highest_region'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs text-green-700">
            <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
            Highest cumulative production
          </span>
        </article>

        <article class="metric-card rounded-2xl border border-rose-100 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Region to Watch</p>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </div>
          <p class="mt-2 text-2xl font-bold text-rose-700"><?= htmlspecialchars($metrics['lowest_region'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs text-rose-600">
            <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
            Lowest output in dataset
          </span>
        </article>

        <article class="metric-card rounded-2xl border border-amber-100 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Coverage</p>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 002 2h2.945M9 11V9a2 2 0 012-2h2a2 2 0 012 2v2m-6 4h6a2 2 0 002-2v-1a2 2 0 00-2-2h-2a2 2 0 00-2 2v1a2 2 0 01-2 2z" />
            </svg>
          </div>
          <p class="mt-2 text-2xl font-bold text-amber-700"><?= $datasetMeta['regionCount'] ?></p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs text-amber-600">
            <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
            Regions represented
          </span>
        </article>

        <article class="metric-card rounded-2xl border border-amber-100 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Dataset Size</p>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
            </svg>
          </div>
          <p class="mt-2 text-2xl font-bold text-emerald-100">
            <?= number_format($datasetMeta['rowCount']) ?> row<?= $datasetMeta['rowCount'] === 1 ? '' : 's' ?>
          </p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs text-emerald-200">
            <span class="h-2 w-2 rounded-full bg-emerald-300/80 animate-pulse"></span>
            <?= number_format($datasetMeta['columnCount']) ?> column<?= $datasetMeta['columnCount'] === 1 ? '' : 's' ?>
          </span>
        </article>
      </section>

      <!-- Dataset Snapshot -->
      <section id="dataset" class="section-card mt-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-xl font-semibold text-gray-800">🗃 Dataset Snapshot</h2>
            <p class="text-sm text-gray-500">
              Preview of the first <?= min($previewLimit, $datasetMeta['rowCount']) ?> row<?= $datasetMeta['rowCount'] === 1 ? '' : 's' ?> · <?= number_format($datasetMeta['rowCount']) ?> total rows processed.
            </p>
          </div>
          <span class="inline-flex items-center gap-2 rounded-full bg-green-50 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-green-700">
            <?= htmlspecialchars($datasetMeta['fileName'], ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>

        <div class="mt-5 overflow-x-auto rounded-2xl border border-gray-200">
          <table class="min-w-full divide-y divide-gray-200 text-left text-xs sm:text-sm">
            <thead class="bg-gray-50">
              <tr>
                <?php foreach ($headers as $header): ?>
                  <th class="px-4 py-3 font-semibold uppercase tracking-wide text-gray-600">
                    <?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <?php foreach ($previewRows as $row): ?>
                <tr class="even:bg-gray-50">
                  <?php foreach ($headers as $header): ?>
                    <td class="px-4 py-2 text-gray-700 align-top">
                      <?php
                        $value = $row[$header] ?? '';
                        echo $value === '' ? '<span class="text-gray-400 italic">—</span>' : nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
                      ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($hasMorePreviewRows): ?>
          <p class="mt-3 text-xs text-gray-500">Showing the first <?= $previewLimit ?> records. Additional rows are included in the analysis.</p>
        <?php endif; ?>
      </section>

      <!-- Charts -->
      <section class="mt-6 space-y-4">
        <article id="chart-region" class="section-card chart-card rounded-2xl border border-green-100 bg-white p-4 shadow-sm xl:p-5">
          <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div>
              <h2 class="text-xl font-semibold text-green-900">📊 Total Production by Region</h2>
              <p class="text-sm text-gray-500">Identify leading regions and compare total outputs at a glance.</p>
            </div>
          </div>
          <div class="chart-wrapper mt-6 h-80">
            <canvas id="regionChart" class="responsive-chart"></canvas>
          </div>
        </article>

        <div class="grid gap-6 xl:grid-cols-2">
          <article id="chart-trend" class="section-card chart-card rounded-2xl border border-blue-100 bg-white p-4 shadow-sm xl:p-5">
            <div class="flex flex-col gap-3">
              <h2 class="text-xl font-semibold text-blue-900">📈 Yearly Production Trend</h2>
              <p class="text-sm text-gray-500">Track aggregate production shifts across the covered years.</p>
            </div>
            <div class="chart-wrapper mt-6 h-72">
              <canvas id="yearlyTrendChart" class="responsive-chart"></canvas>
            </div>
          </article>

          <article id="chart-crop" class="section-card chart-card rounded-2xl border border-emerald-100 bg-gradient-to-br from-white via-white to-green-50 p-4 shadow-sm xl:p-5">
            <div class="flex flex-col gap-3">
              <h2 class="text-xl font-semibold text-emerald-900">🥧 Crop Production Share</h2>
              <p class="text-sm text-gray-500">Balance of crop types based on total reported production.</p>
            </div>
            <div class="chart-wrapper mt-6 h-72">
              <canvas id="cropPieChart" class="responsive-chart"></canvas>
            </div>
          </article>
        </div>
      </section>

      <!-- AI Insight -->
      <section id="insight" class="ai-console mt-6 rounded-2xl border border-emerald-400/40 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-4">
            <div class="relative">
              <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 via-green-500 to-sky-500 text-xl font-bold text-black shadow-lg ring-2 ring-emerald-300/70">
                AI
              </span>
              <span class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500"></span>
              </span>
            </div>
            <div>
              <p class="text-xs uppercase tracking-[0.3em] text-emerald-600">Neural Insight Engine</p>
              <h2 class="text-2xl font-semibold text-emerald-900">AI Insight Console</h2>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="status-indicator">
              <span class="status-dot"></span>
              <span class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Processing</span>
            </div>
            <div class="flex items-center gap-2 rounded-full border border-emerald-300 bg-emerald-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Live analysis
            </div>
          </div>
        </div>
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm relative overflow-hidden">
          <div class="relative flex items-center justify-between text-xs font-mono uppercase tracking-wide text-gray-600 mb-4 pb-3 border-b border-gray-200">
            <div class="flex items-center gap-4">
                <span>Session ID: <span class="text-emerald-600"><?= date('ymd') ?>-<?= substr(hash('crc32b', $datasetMeta['fileName'] . $datasetMeta['rowCount']), 0, 6) ?></span></span>
              <span class="text-gray-400">|</span>
              <span>Model: <span class="text-emerald-600">AGRI-COG v2.4</span></span>
            </div>
            <div class="flex items-center gap-2 text-emerald-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span id="aiStatusText">Initializing...</span>
            </div>
          </div>
          
          <div id="aiInsightBox" class="relative mt-4 space-y-4 rounded-xl border border-gray-200 bg-gray-50 px-5 py-6 text-sm leading-relaxed text-gray-700">
            <div class="flex items-center gap-3 mb-3 pb-3 border-b border-gray-200">
              <div class="flex items-center gap-2 text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span class="font-semibold tracking-wide">AIgriLink</span>
              </div>
              <span class="text-gray-400">|</span>
              <span class="text-xs text-gray-500"><?= date('h:i A') ?> PHT</span>
            </div>
            <div id="aiInsightText" class="whitespace-pre-wrap text-gray-700"></div>
            <div id="aiTypingIndicator" class="hidden mt-2 text-xs text-emerald-600">
              <span class="inline-flex items-center gap-1">
                <span>AI is thinking</span>
                <span class="flex gap-1">
                  <span class="w-1 h-1 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0s;"></span>
                  <span class="w-1 h-1 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0.2s;"></span>
                  <span class="w-1 h-1 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0.4s;"></span>
                </span>
              </span>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<script>
(function () {
  const aiTextRaw = <?= json_encode($insight, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const target = document.getElementById('aiInsightText');
  const statusText = document.getElementById('aiStatusText');
  const typingIndicator = document.getElementById('aiTypingIndicator');
  if (!target) return;

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = aiTextRaw ?? '';
  const aiText = tempDiv.innerHTML;
  let index = 0;
  let phase = 0;

  const statusMessages = [
    'Initializing neural network...',
    'Loading dataset...',
    'Analyzing patterns...',
    'Computing insights...',
    'Generating report...',
    'Finalizing analysis...'
  ];

  const updateStatus = () => {
    if (statusText && phase < statusMessages.length) {
      statusText.textContent = statusMessages[phase];
      phase++;
    }
  };

  const typeAI = () => {
    if (index === 0) {
      updateStatus();
      if (typingIndicator) typingIndicator.classList.remove('hidden');
    }

    if (index < aiText.length) {
      const progress = Math.floor((index / aiText.length) * statusMessages.length);
      if (progress < statusMessages.length && statusText) {
        statusText.textContent = statusMessages[progress];
      }

      // Simulate realistic typing with variable speed
      const char = aiText[index];
      const delay = char === '.' || char === '!' || char === '?' ? 50 : 
                    char === ',' || char === ';' ? 30 :
                    char === ' ' ? 15 : 
                    char === '\n' ? 25 : 8;

      target.innerHTML = aiText.substring(0, index + 1) + "<span class='typing-cursor'></span>";
      index += 1;
      window.setTimeout(typeAI, delay);
    } else {
      if (typingIndicator) typingIndicator.classList.add('hidden');
      if (statusText) {
        statusText.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Analysis Complete';
      }
      
      target.innerHTML = aiText + `
      <div class="mt-6 pt-4 border-t border-gray-200 text-center text-xs"><span class="text-emerald-600">✓ Analysis completed successfully</span></div>

      <div class="mt-3 flex flex-wrap justify-center gap-2"><span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>Data Processed</span><span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Insights Generated</span><span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-1 text-xs text-purple-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>AI-Powered</span></div>

        </div>`;
    }
  };

  window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      updateStatus();
      setTimeout(typeAI, 800);
    }, 300);
  });
})();
</script>

<script>
(function () {
  const regionCtxElement = document.getElementById('regionChart');
  const yearlyCtxElement = document.getElementById('yearlyTrendChart');
  const cropCtxElement = document.getElementById('cropPieChart');

  if (!regionCtxElement || !yearlyCtxElement || !cropCtxElement) return;

  const regionCtx = regionCtxElement.getContext('2d');
  const yearlyCtx = yearlyCtxElement.getContext('2d');
  const cropCtx = cropCtxElement.getContext('2d');

  const regionLabels = <?= json_encode(array_keys($metrics['region_totals'] ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const regionData = <?= json_encode(array_values($metrics['region_totals'] ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const yearlyData = <?= json_encode($metrics['yearly_totals'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const cropData = <?= json_encode($metrics['crop_totals'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  if (!regionLabels.length) {
    regionCtxElement.parentElement.innerHTML = "<p class='text-center text-sm text-gray-500'>Regional totals could not be visualized because no regions were detected.</p>";
  } else {
    const regionGradient = regionCtx.createLinearGradient(0, 0, 0, 400);
    regionGradient.addColorStop(0, 'rgba(22, 163, 74, 0.95)');
    regionGradient.addColorStop(1, 'rgba(22, 163, 74, 0.15)');

    new Chart(regionCtx, {
      type: 'bar',
      data: {
        labels: regionLabels,
        datasets: [{
          label: 'Total Production',
          data: regionData,
          backgroundColor: regionGradient,
          borderRadius: 12,
          barPercentage: 0.7,
          categoryPercentage: 0.6,
        }],
      },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 10,
          },
        },
        scales: {
          x: {
            ticks: { color: '#1f2937', font: { family: 'Inter', size: 12 } },
            grid: { display: false },
          },
          y: {
            ticks: { color: '#1f2937', font: { family: 'Inter', size: 12 } },
            grid: { color: 'rgba(148, 163, 184, 0.15)' },
            beginAtZero: true,
          },
        },
      },
    });
  }

  const yearlyLabels = Object.keys(yearlyData);
  if (!yearlyLabels.length) {
    yearlyCtxElement.parentElement.innerHTML = "<p class='text-center text-sm text-gray-500'>Yearly trends cannot be displayed because the dataset has no year information.</p>";
  } else {
    new Chart(yearlyCtx, {
      type: 'line',
      data: {
        labels: yearlyLabels,
        datasets: [{
          label: 'Yearly Production',
          data: Object.values(yearlyData),
          borderColor: '#0ea5e9',
          backgroundColor: 'rgba(14, 165, 233, 0.18)',
          pointBackgroundColor: '#0284c7',
          pointBorderColor: '#fff',
          fill: true,
          tension: 0.35,
          borderWidth: 2,
        }],
      },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 10,
          },
        },
        scales: {
          x: {
            ticks: { color: '#1f2937', font: { family: 'Inter', size: 12 } },
            grid: { color: 'rgba(148, 163, 184, 0.12)' },
          },
          y: {
            ticks: { color: '#1f2937', font: { family: 'Inter', size: 12 } },
            grid: { color: 'rgba(148, 163, 184, 0.15)' },
            beginAtZero: true,
          },
        },
      },
    });
  }

  const cropLabels = Object.keys(cropData);
  if (!cropLabels.length) {
    cropCtxElement.parentElement.innerHTML = "<p class='text-center text-sm text-gray-500'>Crop distribution cannot be shown because crop data is missing.</p>";
  } else {
    const cropColors = ['#15803d', '#16a34a', '#22c55e', '#4ade80', '#86efac', '#bbf7d0'];
    new Chart(cropCtx, {
      type: 'doughnut',
      data: {
        labels: cropLabels,
        datasets: [{
          label: 'Production Share',
          data: Object.values(cropData),
          backgroundColor: cropColors,
          borderWidth: 2,
          borderColor: '#ffffff',
        }],
      },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              font: { family: 'Inter', size: 12 },
              padding: 16,
            },
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: (context) => {
                const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                const current = context.parsed;
                const percentage = total ? ((current / total) * 100).toFixed(1) : 0;
                return `${context.label}: ${percentage}%`;
              },
            },
          },
        },
        cutout: '65%',
      },
    });
  }
})();
</script>

</div>
<!-- End background wrapper -->

<?php include('includes/footer.php'); ?>

