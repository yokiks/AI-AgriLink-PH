<?php
include('includes/session.php');

$error = '';
$success = '';
$token_valid = false;
$token = '';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Get token from URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $token_valid = verify_reset_token($token);
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $result = reset_password($token, $password);
        if ($result === true) {
            $success = 'Password reset successfully! You can now log in with your new password.';
            $token_valid = false; // Hide form after success
        } else {
            $error = $result;
        }
    }
}

// Set flag for reset password page styling
$is_reset_password_page = true;
include('includes/header.php');
?>

<!-- Background wrapper with gradient and image -->
<div class="fixed inset-0 -z-10 overflow-hidden">
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

<!-- Reset Password Page Content - Override main styling -->
<style>
  body.reset-password-page main {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 140px);
    padding: 0;
    position: relative;
    z-index: 10;
  }
</style>

<!-- Form container - centered -->
<div class="w-full max-w-md mx-auto px-4 py-6 sm:py-8 relative z-10">
  <div class="bg-white/95 backdrop-blur-sm rounded-3xl border border-green-200/50 shadow-2xl p-6 sm:p-8 md:p-10">
    <div class="text-center mb-6">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-green-900 mb-2">Reset Password</h1>
      <p class="text-sm text-gray-600">Enter your new password</p>
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
        <div class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
      
      <div class="mt-6 text-center">
        <a href="login.php" class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-green-800 focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
          <span>Go to Login</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
        </a>
      </div>
    <?php elseif (!$token_valid): ?>
      <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
        <div class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div>
            <strong>Invalid or Expired Link</strong>
            <p class="mt-1">This password reset link is invalid or has expired. Please request a new one.</p>
          </div>
        </div>
      </div>
      
      <div class="mt-6 text-center space-y-3">
        <a href="forgot_password.php" class="inline-flex items-center justify-center gap-2 w-full rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-green-800 focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
          <span>Request New Reset Link</span>
        </a>
        <a href="login.php" class="block text-sm font-medium text-green-700 hover:text-green-800 hover:underline">
          Back to Login
        </a>
      </div>
    <?php else: ?>
    <form method="POST" action="" class="space-y-5">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
      
      <div>
        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
          New Password
        </label>
        <div class="relative">
          <input
            type="password"
            id="password"
            name="password"
            required
            autofocus
            minlength="6"
            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-12 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
            placeholder="At least 6 characters"
          />
          <button
            type="button"
            onclick="togglePassword('password', 'password_toggle')"
            id="password_toggle"
            class="password-toggle-btn absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-green-700 focus:outline-none transition"
            aria-label="Toggle password visibility"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <div>
        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Confirm New Password
        </label>
        <div class="relative">
          <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            required
            minlength="6"
            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-12 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
            placeholder="Re-enter your new password"
          />
          <button
            type="button"
            onclick="togglePassword('confirm_password', 'confirm_password_toggle')"
            id="confirm_password_toggle"
            class="password-toggle-btn absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-green-700 focus:outline-none transition"
            aria-label="Toggle password visibility"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <button
        type="submit"
        name="update_password"
        class="w-full flex items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-green-800 focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
      >
        <span>Update Password</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </button>
    </form>
    <?php endif; ?>

    <div class="mt-6 pt-6 border-t border-gray-200 text-center">
      <a href="login.php" class="text-sm font-medium text-green-700 hover:text-green-800 hover:underline inline-flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Login
      </a>
    </div>
  </div>
</div>

<script>
  // Password toggle function
  function togglePassword(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    const svg = button.querySelector('svg');
    
    if (input.type === 'password') {
      input.type = 'text';
      // Change to eye-slash icon
      svg.innerHTML = `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.29 3.29m0 0A9.97 9.97 0 015.12 5.12m3.17 3.17L3 3m3.29 3.29l3.29 3.29M12 12l.01.01M21 21l-3.29-3.29m0 0a9.97 9.97 0 01-1.563 3.029M15.12 15.12l-3.29-3.29" />
      `;
    } else {
      input.type = 'password';
      // Change back to eye icon
      svg.innerHTML = `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      `;
    }
  }
</script>

<?php include('includes/footer.php'); ?>