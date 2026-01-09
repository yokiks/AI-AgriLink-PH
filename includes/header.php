<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AI-AgriLink PH Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="<?php echo htmlspecialchars((strpos($_SERVER['PHP_SELF'], '/') === 0 ? '' : './') . 'assets/css/style.css', ENT_QUOTES, 'UTF-8'); ?>" />
</head>

<body class="<?php
              if (isset($is_login_page) && $is_login_page) {
                echo 'login-page';
              } elseif (isset($is_forgot_password_page) && $is_forgot_password_page) {
                echo 'forgot-password-page';
              } elseif (isset($is_reset_password_page) && $is_reset_password_page) {
                echo 'reset-password-page';
              } else {
                echo 'gradient-bg';
              }
              ?> text-gray-800 min-h-screen flex flex-col">

  <nav class="w-full bg-green-700 text-white px-4 py-3 shadow-md relative z-50">
    <div class="flex items-center justify-between gap-3 max-w-7xl mx-auto">
      <div class="font-semibold text-sm sm:text-base truncate">🌾 AI-AgriLink PH: Smart Agriculture Visualizer with AI Insight
      </div>
      <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
          <span class="text-xs sm:text-sm hidden sm:inline">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
          <a href="logout.php" class="inline-flex items-center gap-1 rounded-lg bg-green-800 px-2 sm:px-3 py-1.5 text-xs sm:text-sm font-medium hover:bg-green-900 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="hidden sm:inline">Logout</span>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </nav>

  <main class="flex-1 w-full <?php echo (isset($is_login_page) && $is_login_page) ? '' : 'px-4 py-6 sm:px-6 lg:px-10'; ?>">