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
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
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
</head>
<body class="bg-white font-sans">

<!-- Header -->
<header class="bg-gray-500 text-white flex justify-between items-center px-6 py-3 flex-wrap gap-2">
  <div class="font-serif font-bold text-lg select-none">
    ARK
  </div>

  <nav class="flex space-x-6 text-sm font-normal">
    <a class="hover:underline" href="/library-app/index.php">Home</a>
    <a class="hover:underline" href="category.php">Category</a>
    <a class="hover:underline" href="events.php">Events</a>
    <a class="hover:underline" href="about.php">About</a>
    <a class="hover:underline" href="contact.php">Contact</a>
  </nav>

  <?php if (isset($_SESSION['first_name'])): ?>
    <div class="flex items-center gap-4 text-sm">
      <span>Hello, <strong><?= htmlspecialchars($_SESSION['first_name']) ?>!</strong></span>
      <form action="login/logout.php" method="post">
        <button type="submit" class="border border-gray-300 rounded px-4 py-1 hover:bg-red-600 hover:text-white transition">
          Logout
        </button>
      </form>
    </div>
  <?php else: ?>
    <button
      class="border border-gray-300 rounded px-4 py-1 text-sm hover:bg-gray-400 hover:text-white transition"
      type="button"
      onclick="window.location.href='login/login.php'">
      Login
    </button>
  <?php endif; ?>
</header>
