<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require __DIR__ . '/../includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>ARK</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
    .scrollbar-thin::-webkit-scrollbar {
      width: 6px;
    }
    .scrollbar-thin::-webkit-scrollbar-track {
      background: transparent;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb {
      background-color: #a1a1aa;
      border-radius: 10px;
    }
  </style>
  <link rel="stylesheet" href="style.css" />
</head>
<body class="bg-white font-sans">

<!-- Header -->
<header class="bg-gray-500 text-white flex justify-between items-center px-6 py-3 flex-wrap gap-2">
  <div class="font-serif font-bold text-lg select-none">
    ARK
  </div>

  <nav class="flex space-x-6 text-sm font-normal">
    <a class="hover:underline" href="/library-app/index.php">Home</a>
    <a class="hover:underline" href="events.php">Events</a>
    <a class="hover:underline" href="about.php">About</a>
    <a class="hover:underline" href="contact.php">Contact</a>
  </nav>

  <?php if (isset($_SESSION['first_name'])): ?>
    <?php
// Get user's profile picture from database
$userId = $_SESSION['user_id'] ?? null;
$profilePic = null;

if ($userId) {
  $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($result && !empty($result['profile_picture'])) {
    $profilePic = '/library-app/' . ltrim($result['profile_picture'], '/');
  }
}
?>

<div class="flex items-center gap-4 text-sm">
  <a href="/library-app/profile/edit.php" class="flex items-center gap-2 border border-gray-300 px-3 py-1 rounded hover:bg-blue-600 hover:text-white transition">
    <?php if ($profilePic): ?>
      <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" class="w-7 h-7 rounded-full object-cover border border-white">
    <?php else: ?>
      <div class="w-7 h-7 rounded-full bg-gray-300 flex items-center justify-center text-white text-xs font-bold">?</div>
    <?php endif; ?>
    <span>Profile</span>
  </a>

  <span>Hello, <strong><?= htmlspecialchars($_SESSION['first_name']) ?>!</strong></span>

  <form action="/library-app/login/logout.php" method="post">
    <button type="submit" class="border border-gray-300 rounded px-4 py-1 hover:bg-red-600 hover:text-white transition">
      Logout
    </button>
  </form>
</div>

  <?php else: ?>
  <button id="openLoginModal" class="border border-gray-300 rounded px-4 py-1 text-sm hover:bg-white hover:text-gray-800 transition">
    Login
  </button>
<?php endif; ?>

</header>

<?php if (!isset($_SESSION['first_name'])): ?>
<div class="wrapper hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex justify-center items-center" id="loginWrapper">
  <div class="relative bg-white p-6 rounded-lg w-full max-w-md shadow-lg">
    <span class="absolute top-4 right-4 text-xl cursor-pointer text-gray-500" id="closeModal">
      <ion-icon name="close"></ion-icon>
    </span>

    <!-- Login Form -->
    <div class="form-box login">
      <h2 class="text-xl font-bold mb-4 text-center">Login</h2>
      <form action="#" method="post">
        <div class="mb-4 relative">
          <span class="absolute right-3 top-3 text-gray-400"><ion-icon name="mail"></ion-icon></span>
          <input type="email" name="email" required placeholder="Email"
                 class="w-full border rounded px-3 py-2 pr-10" />
        </div>

        <div class="mb-4 relative">
          <span class="absolute right-3 top-3 text-gray-400"><ion-icon name="lock-closed"></ion-icon></span>
          <input type="password" name="password" required placeholder="Password"
                 class="w-full border rounded px-3 py-2 pr-10" />
        </div>

        <div class="flex justify-between items-center text-sm mb-4">
          <label><input type="checkbox" /> Remember Me</label>
          <a href="#" class="text-blue-600 hover:underline">Forgot Password?</a>
        </div>

        <button type="submit" class="btn w-full bg-gray-700 text-white py-2 rounded hover:bg-gray-900 transition">Login</button>

        <p class="mt-4 text-center text-sm">Don't have an account?
          <a href="#" class="text-blue-600 hover:underline register-link">Register</a>
        </p>
      </form>
    </div>

    <!-- Register Form -->
    <div class="form-box register hidden">
      <h2 class="text-xl font-bold mb-4 text-center">Register</h2>
      <form action="#" method="post">
        <div class="mb-4 relative">
          <span class="absolute right-3 top-3 text-gray-400"><ion-icon name="person"></ion-icon></span>
          <input type="text" name="username" required placeholder="Username"
                 class="w-full border rounded px-3 py-2 pr-10" />
        </div>

        <div class="mb-4 relative">
          <span class="absolute right-3 top-3 text-gray-400"><ion-icon name="mail"></ion-icon></span>
          <input type="email" name="email" required placeholder="Email"
                 class="w-full border rounded px-3 py-2 pr-10" />
        </div>

        <div class="mb-4 relative">
          <span class="absolute right-3 top-3 text-gray-400"><ion-icon name="lock-closed"></ion-icon></span>
          <input type="password" name="password" required placeholder="Password"
                 class="w-full border rounded px-3 py-2 pr-10" />
        </div>

        <div class="text-sm mb-4">
          <label><input type="checkbox" /> Agree to Terms & Conditions</label>
        </div>

        <button type="submit" class="btn w-full bg-gray-700 text-white py-2 rounded hover:bg-gray-900 transition">Register</button>

        <p class="mt-4 text-center text-sm">Already have an account?
          <a href="#" class="text-blue-600 hover:underline login-link">Login</a>
        </p>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("loginWrapper");
    const openBtn = document.getElementById("openLoginModal");
    const closeBtn = document.getElementById("closeModal");
    const registerLink = document.querySelector(".register-link");
    const loginLink = document.querySelector(".login-link");
    const loginForm = document.querySelector(".form-box.login");
    const registerForm = document.querySelector(".form-box.register");

    openBtn?.addEventListener("click", () => {
      modal.classList.remove("hidden");
      loginForm.classList.remove("hidden");
      registerForm.classList.add("hidden");
    });

    closeBtn?.addEventListener("click", () => {
      modal.classList.add("hidden");
    });

    registerLink?.addEventListener("click", (e) => {
      e.preventDefault();
      loginForm.classList.add("hidden");
      registerForm.classList.remove("hidden");
    });

    loginLink?.addEventListener("click", (e) => {
      e.preventDefault();
      registerForm.classList.add("hidden");
      loginForm.classList.remove("hidden");
    });
  });
</script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>