<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AI-AgriLink PH Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">
  <nav class="bg-green-700 text-white px-4 py-3 shadow-md">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 max-w-7xl mx-auto">
        <div class="text-center font-semibold">🌾 AI-AgriLink PH: Smart Agriculture Visualizer with AI Insight
 </div>
      <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <div class="flex items-center gap-3">
          <span class="text-sm">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
          <a href="logout.php" class="inline-flex items-center gap-1 rounded-lg bg-green-800 px-3 py-1.5 text-sm font-medium hover:bg-green-900 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Logout
          </a>
        </div>
      <?php endif; ?>
    </div>
  </nav>
  <main class="flex-1 w-full px-4 py-6 sm:px-6 lg:px-10">
