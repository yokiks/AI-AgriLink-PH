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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
  $username = trim($_POST['reg_username'] ?? '');
  $email = trim($_POST['reg_email'] ?? '');
  $password = $_POST['reg_password'] ?? '';
  $confirm_password = $_POST['reg_confirm_password'] ?? '';

  // Validation
  if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    $error = 'All fields are required for registration.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
  } elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters long.';
  } elseif ($password !== $confirm_password) {
    $error = 'Passwords do not match.';
  } else {
    $result = register_user($username, $email, $password);
    if ($result === true) {
      $success = 'Account created successfully! You can now log in.';
    } else {
      $error = $result; // Error message from register_user function
    }
  }
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
// Set flag for login page styling
$is_login_page = true;
include('includes/header.php');
?>

<!-- Full viewport background (fixed to cover entire screen) -->
<div class="fixed inset-0 bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 overflow-hidden -z-10 pointer-events-none">
  <!-- Background Image -->
  <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-25" style="background-image: url('<?php echo htmlspecialchars((strpos($_SERVER['PHP_SELF'], '/') === 0 ? '' : './') . 'assets/img/homepage5.jpg', ENT_QUOTES, 'UTF-8'); ?>');"></div>

  <!-- Gradient Overlay -->
  <div class="absolute inset-0 bg-gradient-to-br from-green-50/80 via-emerald-50/80 to-teal-50/80"></div>

  <!-- Decorative background elements -->
  <div class="absolute inset-0 overflow-hidden">
    <div class="absolute -top-40 -right-40 w-80 h-80 bg-green-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
    <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-emerald-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-teal-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
  </div>
</div>

<!-- Form container - centered -->
<div class="w-full px-4 py-6 sm:py-8 relative z-10 flex items-center justify-center">
  <div class="w-full max-w-md bg-white/95 backdrop-blur-sm rounded-3xl border border-green-200/50 shadow-2xl p-6 sm:p-8 md:p-10">
    <div class="text-center mb-6">
      <h1 class="text-2xl sm:text-3xl font-bold text-green-900 mb-2">🌾 AI-AgriLink PH</h1>
      <p class="text-xs sm:text-sm text-gray-600" id="formTitle">Create an account to get started</p>
    </div>

    <!-- Tab Navigation -->
    <div class="flex border-b border-gray-200 mb-6">
      <button
        type="button"
        id="registerTab"
        class="flex-1 py-2 px-4 text-sm font-semibold text-center border-b-2 border-green-700 text-green-700 transition">
        Create Account
      </button>
      <button
        type="button"
        id="loginTab"
        class="flex-1 py-2 px-4 text-sm font-semibold text-center border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition">
        Sign In
      </button>
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
    <?php endif; ?>

    <!-- Registration Form (Shown by default) -->
    <form method="POST" action="" class="space-y-4 sm:space-y-5" id="registerForm">
      <div>
        <label for="reg_username" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Username
        </label>
        <input
          type="text"
          id="reg_username"
          name="reg_username"
          required
          minlength="3"
          maxlength="50"
          pattern="[a-zA-Z0-9_]+"
          title="Username can only contain letters, numbers, and underscores"
          class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
          placeholder="Choose a username (min 3 characters)" />
      </div>

      <div>
        <label for="reg_email" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Email Address
        </label>
        <input
          type="email"
          id="reg_email"
          name="reg_email"
          required
          class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
          placeholder="Enter your email" />
      </div>

      <div>
        <label for="reg_password" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Password
        </label>
        <div class="relative w-full">
          <input
            type="password"
            id="reg_password"
            name="reg_password"
            required
            minlength="6"
            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-12 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
            placeholder="At least 6 characters" />
          <button
            type="button"
            onclick="togglePassword('reg_password', 'reg_password_toggle')"
            id="reg_password_toggle"
            class="password-toggle-btn absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-green-700 focus:outline-none transition"
            aria-label="Toggle password visibility">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <div>
        <label for="reg_confirm_password" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Confirm Password
        </label>
        <div class="relative w-full">
          <input
            type="password"
            id="reg_confirm_password"
            name="reg_confirm_password"
            required
            minlength="6"
            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-12 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
            placeholder="Re-enter your password" />
          <button
            type="button"
            onclick="togglePassword('reg_confirm_password', 'reg_confirm_password_toggle')"
            id="reg_confirm_password_toggle"
            class="password-toggle-btn absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-green-700 focus:outline-none transition"
            aria-label="Toggle password visibility">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <button
        type="submit"
        name="register"
        class="w-full flex items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-green-800 focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
        <span>Create Account</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
        </svg>
      </button>
    </form>

    <!-- Login Form (Hidden by default) -->
    <form method="POST" action="" class="space-y-4 sm:space-y-5 hidden" id="loginForm">
      <div>
        <label for="username" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Username
        </label>
        <input
          type="text"
          id="username"
          name="username"
          required
          class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
          placeholder="Enter your username" />
      </div>

      <div>
        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
          Password
        </label>
        <div class="relative w-full">
          <input
            type="password"
            id="password"
            name="password"
            required
            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-12 text-sm text-gray-700 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-400/50"
            placeholder="Enter your password" />
          <button
            type="button"
            onclick="togglePassword('password', 'password_toggle')"
            id="password_toggle"
            class="password-toggle-btn absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-green-700 focus:outline-none transition"
            aria-label="Toggle password visibility">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <div class="flex items-center justify-end">
        <a href="forgot_password.php" class="text-xs font-medium text-green-700 hover:text-green-800 hover:underline">
          Forgot password?
        </a>
      </div>

      <button
        type="submit"
        name="login"
        class="w-full flex items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-green-800 focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
        <span>Sign In</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
        </svg>
      </button>
    </form>

  </div>
</div>

<script>
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const loginTab = document.getElementById('loginTab');
  const registerTab = document.getElementById('registerTab');
  const formTitle = document.getElementById('formTitle');

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

  // Show registration form by default
  function showRegisterForm() {
    registerForm.classList.remove('hidden');
    loginForm.classList.add('hidden');
    registerTab.classList.add('border-green-700', 'text-green-700');
    registerTab.classList.remove('border-transparent', 'text-gray-500');
    loginTab.classList.remove('border-green-700', 'text-green-700');
    loginTab.classList.add('border-transparent', 'text-gray-500');
    formTitle.textContent = "Create an account to get started";
  }

  // Show login form
  function showLoginForm() {
    loginForm.classList.remove('hidden');
    registerForm.classList.add('hidden');
    loginTab.classList.add('border-green-700', 'text-green-700');
    loginTab.classList.remove('border-transparent', 'text-gray-500');
    registerTab.classList.remove('border-green-700', 'text-green-700');
    registerTab.classList.add('border-transparent', 'text-gray-500');
    formTitle.textContent = "Sign in to access the dashboard";
  }

  // Tab click handlers
  registerTab.addEventListener('click', showRegisterForm);
  loginTab.addEventListener('click', showLoginForm);

  // If there's a success message after registration, show login form
  <?php if ($success): ?>
    showLoginForm();
  <?php endif; ?>
</script>
</div>

<?php include('includes/footer.php'); ?>