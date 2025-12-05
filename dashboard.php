<?php
include('includes/header.php');
include('includes/functions.php');

$uploadError = null;
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

if (!isset($_FILES['csvFile'])) {
  echo "<div class='mx-auto max-w-3xl text-center mt-16 rounded-2xl border border-red-200 bg-red-50 px-6 py-10 text-red-800 shadow-sm'>
          <h2 class='text-2xl font-semibold mb-3'>⚠️ No CSV Uploaded</h2>
          <p class='text-sm sm:text-base'>To generate a dashboard, please upload a dataset first.</p>
          <a href='index.php' class='mt-6 inline-flex items-center justify-center rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-green-800'>Return to upload</a>
        </div>";
  include('includes/footer.php');
  exit;
}

$file = $_FILES['csvFile'];

if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
  $uploadError = 'No CSV file was provided. Please try uploading again.';
} elseif (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
  $uploadError = 'We encountered an upload error. Please try again.';
} else {
  $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if ($extension !== 'csv') {
    $uploadError = 'Only CSV files are supported. Please choose a file with a .csv extension.';
  } elseif (!is_uploaded_file($file['tmp_name'])) {
    $uploadError = 'The uploaded file could not be verified. Please try again.';
  } else {
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0777, true)) {
      $uploadError = 'We could not prepare the uploads directory. Please check folder permissions.';
    } else {
      $baseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
      if ($baseName === '') {
        $baseName = 'dataset';
      }
      $timestamp = date('Ymd_His');
      $targetPath = $uploadsDir . '/' . $baseName . '_' . $timestamp . '.csv';

      if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $uploadError = 'We could not save the uploaded CSV. Please try again.';
      } else {
        [$headers, $rows] = parse_csv($targetPath);

        if (empty($headers)) {
          $uploadError = 'We could not detect any headers in your CSV. Please ensure the first row contains column names.';
        } elseif (empty($rows)) {
          $uploadError = 'No data rows were found in your CSV. Please verify the file contents.';
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
  </style>
  <!-- Top Navigation -->
  <nav class="sticky top-[4.5rem] z-30 mb-6 flex flex-col gap-3 rounded-2xl border border-green-100 bg-white/90 px-4 py-3 text-sm shadow-sm backdrop-blur md:flex-row md:items-center md:justify-between lg:px-6">
    <div>
      <p class="text-xs uppercase tracking-wide text-green-600">AI-AgriLink Dashboard</p>
      <h1 class="text-lg font-semibold text-slate-800">Insights Overview</h1>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="#metrics" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white">Metrics</a>
      <a href="#dataset" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white">Dataset</a>
      <a href="#chart-region" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white">Regions</a>
      <a href="#chart-trend" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white">Trend</a>
      <a href="#chart-crop" class="rounded-full border border-green-200 px-3 py-1 font-medium text-green-700 transition hover:bg-green-600 hover:text-white">Crops</a>
      <a href="#insight" class="rounded-full border border-emerald-300 px-3 py-1 font-semibold text-emerald-700 transition hover:bg-emerald-600 hover:text-white">AI Console</a>
      <a href="index.php" class="inline-flex items-center gap-2 rounded-full border border-green-300 bg-green-600 px-4 py-1.5 font-semibold text-white shadow-sm transition hover:bg-green-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2M12 4v12m0 0-4-4m4 4 4-4" />
        </svg>
        Upload new CSV
      </a>
    </div>
  </nav>

  <div class="space-y-10 lg:space-y-14">
    <header class="rounded-3xl border border-green-100 bg-white px-6 py-8 shadow-sm">
      <p class="text-xs uppercase tracking-wide text-green-600">AI-AgriLink PH · 2020–2025 Dataset</p>
      <h1 class="mt-3 text-3xl font-bold text-green-900 sm:text-4xl">🌾 Interactive Agriculture Dashboard</h1>
      <p class="mt-4 max-w-3xl text-sm text-gray-600 sm:text-base">
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
    <section id="metrics" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-green-100 bg-white p-6 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-green-600">Top Region</p>
          <p class="mt-2 text-xl font-bold text-green-900"><?= htmlspecialchars($metrics['highest_region'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs text-green-700">
            <span class="h-2 w-2 rounded-full bg-green-500"></span>
            Highest cumulative production
          </span>
        </article>

        <article class="rounded-3xl border border-rose-100 bg-white p-6 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Region to Watch</p>
          <p class="mt-2 text-xl font-bold text-rose-700"><?= htmlspecialchars($metrics['lowest_region'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs text-rose-600">
            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
            Lowest output in dataset
          </span>
        </article>

        <article class="rounded-3xl border border-amber-100 bg-white p-6 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Coverage</p>
          <p class="mt-2 text-xl font-bold text-amber-700"><?= $datasetMeta['regionCount'] ?></p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs text-amber-600">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
            Regions represented
          </span>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-slate-900 p-6 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">Dataset Size</p>
          <p class="mt-2 text-xl font-bold text-emerald-100">
            <?= number_format($datasetMeta['rowCount']) ?> row<?= $datasetMeta['rowCount'] === 1 ? '' : 's' ?>
          </p>
          <span class="mt-4 inline-flex items-center gap-2 text-xs text-emerald-200">
            <span class="h-2 w-2 rounded-full bg-emerald-300/80"></span>
            <?= number_format($datasetMeta['columnCount']) ?> column<?= $datasetMeta['columnCount'] === 1 ? '' : 's' ?>
          </span>
        </article>
      </section>

      <!-- Dataset Snapshot -->
      <section id="dataset" class="mt-10 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
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
      <section class="mt-10 space-y-6">
        <article id="chart-region" class="chart-card rounded-3xl border border-green-100 bg-white p-6 shadow-sm xl:p-7">
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
          <article id="chart-trend" class="chart-card rounded-3xl border border-blue-100 bg-white p-6 shadow-sm xl:p-7">
            <div class="flex flex-col gap-3">
              <h2 class="text-xl font-semibold text-blue-900">📈 Yearly Production Trend</h2>
              <p class="text-sm text-gray-500">Track aggregate production shifts across the covered years.</p>
            </div>
            <div class="chart-wrapper mt-6 h-72">
              <canvas id="yearlyTrendChart" class="responsive-chart"></canvas>
            </div>
          </article>

          <article id="chart-crop" class="chart-card rounded-3xl border border-emerald-100 bg-gradient-to-br from-white via-white to-green-50 p-6 shadow-sm xl:p-7">
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
      <section id="insight" class="mt-10 rounded-3xl border border-emerald-400/40 bg-slate-950/95 p-6 text-slate-100 shadow-[0_20px_45px_-25px_rgba(16,185,129,0.75)] sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 via-green-500 to-sky-500 text-lg font-semibold text-slate-900 shadow-lg ring-2 ring-emerald-300/70">
              AI
            </span>
            <div>
              <p class="text-xs uppercase tracking-[0.3em] text-emerald-200/70">Neural Insight Engine</p>
              <h2 class="text-2xl font-semibold text-emerald-200">AI Insight Console</h2>
            </div>
          </div>
          <div class="flex items-center gap-2 rounded-full border border-emerald-400/60 bg-emerald-500/10 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-200">
            <span class="inline-flex h-2 w-2 animate-ping rounded-full bg-emerald-300"></span>
            Live analysis
          </div>
        </div>
        <div class="mt-6 rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-5 shadow-inner">
          <div class="flex items-center justify-between text-xs font-mono uppercase tracking-wide text-slate-400">
            <span>Session ID: <?= date('ymd') ?>-<?= substr(hash('crc32b', $datasetMeta['fileName'] . $datasetMeta['rowCount']), 0, 6) ?></span>
            <span>Model: AGRI-COG v2.4</span>
          </div>
          <div id="aiInsightBox" class="mt-4 space-y-3 rounded-xl border border-slate-800 bg-slate-950/70 px-4 py-5 font-mono text-sm leading-relaxed text-emerald-100 shadow-[inset_0_0_40px_rgba(15,118,110,0.25)]">
            <div class="flex items-center gap-2 text-emerald-400">
              <span class="font-semibold tracking-wide">AIgriLink</span>
            </div>
            <div id="aiInsightText" class="whitespace-pre-wrap"></div>
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
  if (!target) return;

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = aiTextRaw ?? '';
  const aiText = tempDiv.innerHTML;
  let index = 0;

  const typeAI = () => {
    if (index < aiText.length) {
      target.innerHTML = aiText.substring(0, index + 1) + "<span class='ml-1 inline-block h-4 w-1 animate-pulse bg-emerald-400/80 align-baseline rounded-full'></span>";
      index += 1;
      window.setTimeout(typeAI, 12);
    } else {
      target.innerHTML = aiText + "<div class='mt-4 text-center text-xs font-semibold uppercase tracking-[0.4em] text-emerald-300/80'>Analysis complete</div>";
    }
  };

  window.addEventListener('DOMContentLoaded', () => window.setTimeout(typeAI, 400));
})();
</script>

<script src="assets/js/chart.min.js"></script>
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

<?php include('includes/footer.php'); ?>
