<?php 
// Require login before accessing upload page
include('includes/session.php');
require_login();

include('includes/header.php'); 
?>

<!-- Background wrapper with gradient and image -->
<!-- <div class="fixed inset-0 bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 overflow-hidden z-0"> -->
  <!-- Background Image -->
  <div class="fixed inset-0 -z-10 bg-cover bg-center bg-no-repeat opacity-25"
     style="background-image: url('assets/img/homepage5.jpg');">
  </div>
  
  <!-- Gradient Overlay -->
  <div class="absolute inset-0 bg-gradient-to-br from-green-50/80 via-emerald-50/80 to-teal-50/80"></div>
  
  <!-- Decorative background elements -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-80 h-80 bg-green-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
    <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-emerald-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-teal-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
  </div>
</div>

<div class="mx-auto max-w-6xl relative z-10">
  <!-- Hero Section -->
  <section class="text-center mt-10 sm:mt-12 px-2">
    <span class="inline-flex items-center gap-2 rounded-full bg-green-700/10 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-green-700">
      Philippine Agriculture · 2020–2025
    </span>
    <h1 class="mt-4 text-3xl sm:text-4xl font-extrabold text-green-900 leading-tight">
      🌾 AI-AgriLink PH
    </h1>
    <p class="mt-3 text-base sm:text-lg text-gray-700 max-w-2xl mx-auto">
      A Smart Agriculture Visualizer with AI-powered insights to guide data-driven decisions for sustainable farming and inclusive regional development.
    </p>
  </section>

  <!-- Upload Form Card -->
  <div class="mt-10 bg-white/90 backdrop-blur-sm px-5 py-7 sm:px-8 sm:py-9 rounded-3xl shadow-xl border border-green-100">
    <h2 class="text-2xl font-bold text-green-800 text-center mb-4">📊 Upload Agricultural Dataset</h2>
    <p class="text-sm sm:text-base text-gray-600 mb-6 text-center leading-relaxed">
      Supported formats: <span class="font-semibold text-green-700">.csv, .xlsx, .xls</span> (e.g., Palay or Corn production — 2020 to 2025).<br class="hidden sm:block" />
      Recommended headers: <code class="bg-gray-100 px-2 py-0.5 rounded text-gray-700">Year, Region, Crop, Production</code>
    </p>

    <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="space-y-5">
      <div>
        <label for="csvFile" class="block text-sm font-semibold text-gray-700 mb-1.5">Select your file (CSV or Excel)</label>
        <input
          type="file"
          id="csvFile"
          name="csvFile"
          accept=".csv,.xlsx,.xls"
          required
          class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
        />
        <p class="mt-2 text-xs text-gray-500">Supported formats: CSV, Excel (.xlsx, .xls). Preview up to 8 rows before submitting.</p>
      </div>

      <button
        type="submit"
        class="flex w-full items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-green-800 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-white"
      >
        <span>Generate Dashboard</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h8m0 0-3-3m3 3-3 3m3 7H8m0 0 3 3m-3-3 3-3" />
        </svg>
      </button>
    </form>

    <div class="mt-6 rounded-xl bg-green-50 p-4 text-xs sm:text-sm text-green-900">
      <p><span class="font-semibold">Aligned SDGs:</span> SDG 2 — Zero Hunger · SDG 17 — Partnerships for the Goals</p>
      <p class="mt-1">Your data fuels AI-assisted analytics to spotlight regional opportunities and resilience.</p>
    </div>
  </div>

  <!-- CSV Preview -->
  <div id="csvPreviewCard" class="mt-10 hidden">
    <div class="rounded-3xl border border-gray-200 bg-white shadow-lg">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 px-5 py-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-800">📄 CSV Quick Preview</h3>
          <p id="csvPreviewMeta" class="text-sm text-gray-500"></p>
        </div>
        <span id="csvPreviewChip" class="inline-flex h-7 items-center rounded-full bg-green-100 px-3 text-xs font-semibold uppercase tracking-wide text-green-700"></span>
      </div>

      <div class="px-5 py-4">
        <div id="csvPreviewAlert" class="hidden rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800"></div>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200">
          <table class="min-w-full divide-y divide-gray-200 text-left text-xs sm:text-sm">
            <thead class="bg-gray-50" id="csvPreviewHead"></thead>
            <tbody class="bg-white" id="csvPreviewBody"></tbody>
          </table>
        </div>
        <p id="csvPreviewFootnote" class="mt-3 text-xs text-gray-500"></p>
      </div>
    </div>
  </div>

  <!-- Info Section -->
  <section class="mt-12 px-3 text-center text-sm leading-relaxed text-gray-600 sm:text-base">
    <p>
      AI-AgriLink PH visualizes agricultural performance across Philippine regions using authoritative datasets
      (PSA OpenStat and World Bank, 2020–2025). Generate interactive dashboards, monitor production shifts,
      and surface collaborative opportunities that advance sustainable food systems.
    </p>
  </section>
</div>

<script>
(function () {
  const fileInput = document.getElementById('csvFile');
  const previewCard = document.getElementById('csvPreviewCard');
  const previewHead = document.getElementById('csvPreviewHead');
  const previewBody = document.getElementById('csvPreviewBody');
  const previewMeta = document.getElementById('csvPreviewMeta');
  const previewFootnote = document.getElementById('csvPreviewFootnote');
  const previewAlert = document.getElementById('csvPreviewAlert');
  const previewChip = document.getElementById('csvPreviewChip');

  if (!fileInput) return;

  const PREVIEW_ROWS = 8;

  const resetPreview = () => {
    previewCard.classList.add('hidden');
    previewHead.innerHTML = '';
    previewBody.innerHTML = '';
    previewMeta.textContent = '';
    previewFootnote.textContent = '';
    previewAlert.classList.add('hidden');
    previewAlert.textContent = '';
    previewChip.textContent = '';
  };

  const escapeHtml = (value) => {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  };

  const parseCsv = (text, maxRows = null) => {
    const rows = [];
    let currentRow = [];
    let currentValue = '';
    let inQuotes = false;

    for (let i = 0; i < text.length; i++) {
      const char = text[i];
      const nextChar = text[i + 1];

      if (char === '"') {
        if (inQuotes && nextChar === '"') {
          currentValue += '"';
          i++;
        } else {
          inQuotes = !inQuotes;
        }
      } else if (char === ',' && !inQuotes) {
        currentRow.push(currentValue);
        currentValue = '';
      } else if ((char === '\n' || char === '\r') && !inQuotes) {
        if (char === '\r' && nextChar === '\n') i++;
        currentRow.push(currentValue);
        if (currentRow.some(value => value.trim() !== '')) {
          rows.push(currentRow);
          if (maxRows && rows.length >= maxRows) break;
        }
        currentRow = [];
        currentValue = '';
      } else {
        currentValue += char;
      }
    }

    if (!maxRows || rows.length < maxRows) {
      currentRow.push(currentValue);
      if (currentRow.some(value => value.trim() !== '')) {
        rows.push(currentRow);
      }
    }

    return rows;
  };

  const formatBytes = (bytes) => {
    if (!bytes && bytes !== 0) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
      size /= 1024;
      unitIndex++;
    }
    return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
  };

  fileInput.addEventListener('change', (event) => {
    const file = event.target.files[0];

    if (!file) {
      resetPreview();
      return;
    }

    const isValidFile = /\.(csv|xlsx|xls)$/i.test(file.name);
    if (!isValidFile) {
      resetPreview();
      previewCard.classList.remove('hidden');
      previewAlert.classList.remove('hidden');
      previewAlert.textContent = 'Only CSV and Excel files (.csv, .xlsx, .xls) are supported.';
      previewChip.textContent = 'invalid file';
      return;
    }

    const reader = new FileReader();
    reader.onload = (loadEvent) => {
      const text = loadEvent.target?.result ?? '';
      if (!text) {
        resetPreview();
        previewCard.classList.remove('hidden');
        previewAlert.classList.remove('hidden');
        previewAlert.textContent = 'We could not read that file. Please try again or choose a different CSV.';
        previewChip.textContent = 'load error';
        return;
      }

      const rowCount = text.split(/\r\n|\n|\r/).filter(line => line.trim() !== '').length;
      const parsedRows = parseCsv(text, PREVIEW_ROWS + 1);

      if (!parsedRows.length) {
        resetPreview();
        previewCard.classList.remove('hidden');
        previewAlert.classList.remove('hidden');
        previewAlert.textContent = 'The file seems empty. Please ensure it contains headers and data rows.';
        previewChip.textContent = 'empty file';
        return;
      }

      const headers = parsedRows[0];
      const bodyRows = parsedRows.slice(1);

      previewHead.innerHTML = `<tr>${headers.map(header => {
        const label = header && String(header).trim() !== '' ? escapeHtml(header) : 'Column';
        return `<th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">${label}</th>`;
      }).join('')}</tr>`;

      previewBody.innerHTML = bodyRows.length
        ? bodyRows.map(row => `<tr class="even:bg-gray-50 text-gray-700">${headers.map((_, cellIndex) => {
            const cellValue = row[cellIndex] ?? '';
            const trimmed = typeof cellValue === 'string' ? cellValue.trim() : String(cellValue).trim();
            const safeValue = trimmed === '' ? '<span class="text-gray-400 italic">—</span>' : escapeHtml(cellValue);
            return `<td class="px-4 py-2 align-top">${safeValue}</td>`;
          }).join('')}</tr>`).join('')
        : `<tr><td colspan="${headers.length}" class="px-4 py-4 text-sm text-gray-500 text-center">No data rows detected yet — please verify your CSV content.</td></tr>`;

      previewMeta.textContent = `${headers.length} column${headers.length === 1 ? '' : 's'} · previewing up to ${Math.min(bodyRows.length, PREVIEW_ROWS)} row${bodyRows.length === 1 ? '' : 's'}`;
      previewFootnote.textContent = rowCount > PREVIEW_ROWS + 1
        ? `Showing the first ${PREVIEW_ROWS} rows out of approximately ${rowCount - 1} data rows.`
        : `Showing all ${Math.max(rowCount - 1, 0)} data rows available.`;

      previewCard.classList.remove('hidden');
      previewAlert.classList.add('hidden');
      previewChip.textContent = `${formatBytes(file.size)} · ${file.name}`;
    };

    reader.onerror = () => {
      resetPreview();
      previewCard.classList.remove('hidden');
      previewAlert.classList.remove('hidden');
      previewAlert.textContent = 'Something went wrong while reading the file. Please try again.';
      previewChip.textContent = 'read error';
    };

    reader.readAsText(file);
  });
})();
</script>

</div>
<!-- End background wrapper -->

<?php include('includes/footer.php'); ?>
