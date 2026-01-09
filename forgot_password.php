<?php
include('includes/session.php');

$error = '';
$success = '';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Handle forgot password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = request_password_reset($email);
        if ($result === true) {
            $success = 'If an account exists with this email, you will receive password reset instructions shortly.';
        } else {
            // For security, we show the same message even if email doesn't exist
            $success = 'If an account exists with this email, you will receive password reset instructions shortly.';
        }
    }
}

// Set flag for forgot password page styling
$is_forgot_password_page = true;
include('includes/header.php');
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

<!-- Forgot Password Page Content - Override main styling -->
<style>
  body.forgot-password-page main {
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
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-green-900 mb-2">Forgot Password?</h1>
      <p class="text-sm text-gray-600">Enter your email to reset your password</p>
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
      
      <div class="mt-4 text-center">
        <form method="POST" action="" class="inline">
          <input type="hidden" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <button
            type="submit"
            name="reset_password"
            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Resend Reset Link
          </button>
        </form>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="" class="space-y-5">
      <div>
        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Email Address
        </label>
        <input
          type="email"
          id="email"
          name="email"
          required
          autofocus
          class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
          placeholder="Enter your registered email"
        />
      </div>

      <button
        type="submit"
        name="reset_password"
        class="w-full flex items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-green-800 focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
      >
        <span>Send Reset Link</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
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

     <?php if ($success): ?>
     <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
       <p class="text-xs text-blue-800">
         <strong>Note:</strong> 
         <?php if (defined('EMAIL_METHOD') && EMAIL_METHOD === 'file'): ?>
           <strong>Development Mode:</strong> Email has been saved to <code>email_logs/</code> folder. Check that folder for the reset link.
         <?php else: ?>
           Check your spam folder if you don't see the email. The reset link will expire in 1 hour.
         <?php endif; ?>
       </p>
     </div>
     <?php endif; ?>
  </div>
</div>

<?php include('includes/footer.php'); ?>