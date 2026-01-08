<?php
include('includes/session.php');

$error = '';
$success = '';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if (login($username, $password)) {
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

include('includes/header.php');
?>

<div class="mx-auto max-w-md mt-10 sm:mt-16 px-4">
  <div class="bg-white rounded-3xl border border-green-100 shadow-xl p-8">
    <div class="text-center mb-6">
      <h1 class="text-3xl font-bold text-green-900 mb-2">🌾 AI-AgriLink PH</h1>
      <p class="text-sm text-gray-600">Sign in to access the dashboard</p>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <div class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-5">
      <div>
        <label for="username" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Username
        </label>
        <input
          type="text"
          id="username"
          name="username"
          required
          autofocus
          class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
          placeholder="Enter your username"
        />
      </div>

      <div>
        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Password
        </label>
        <input
          type="password"
          id="password"
          name="password"
          required
          class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
          placeholder="Enter your password"
        />
      </div>

      <button
        type="submit"
        name="login"
        class="w-full flex items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-green-800 focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
      >
        <span>Sign In</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
        </svg>
      </button>
    </form>

    <div class="mt-6 pt-6 border-t border-gray-200">
      <p class="text-xs text-center text-gray-500">
        Default credentials:<br>
        <!-- <span class="font-mono">admin / admin123</span> or <span class="font-mono">user / user123</span> -->
        <span class="font-mono">user / user123</span>
      </p>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>

