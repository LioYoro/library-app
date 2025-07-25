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
    <button
      class="border border-gray-300 rounded px-4 py-1 text-sm hover:bg-gray-400 hover:text-white transition"
      type="button"
      onclick="window.location.href='login/login.php'">
      Login
    </button>
  <?php endif; ?>
</header>
