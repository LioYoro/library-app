<?php 
$pageTitle = 'Book Reservations Admin';
include('includes/header.php'); 
include('includes/sidebar.php');
?>

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
  <!-- Header Bar -->
  <header class="h-16 bg-blue-500 text-white flex items-center justify-between px-6 shadow">
    <h1 class="text-xl font-bold"><?= $pageTitle ?></h1>
    <div class="flex items-center space-x-3">
      <span class="text-sm">ADMIN</span>
      <i class="fas fa-user-circle text-2xl"></i>
    </div>
  </header>

  <main class="flex-1 p-6 bg-white overflow-y-auto">
    <h2 class="text-lg font-semibold mb-4">TOOLS</h2>

    <a href="../book_reservation/admin_side/reservation_tab.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow mb-2 inline-block">
        Manage Reservations
    </a>
    <br>

    <a href="../book_reservation/admin_side/book_status_tab.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow mb-2 inline-block">
        Manage Book Status
    </a>

    <a href="../book_reservation/admin_side/book_summary.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow mb-2 inline-block">
        Book Summary for Reservations
    </a>

    <a href="../book_reservation/check_expired_reservations.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow mb-2 inline-block">
        Reservation Expiry Check
    </a>
    <br>

    <!-- Placeholder for future admin tools -->
    <p class="mt-4 text-gray-600">Additional tools and reports will appear here as needed.</p>
  </main>
</div>

<?php include('includes/footer.php'); ?>
