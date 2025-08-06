<?php 
$pageTitle = 'Events';
include('includes/header.php'); 
include('includes/sidebar.php'); 
?>

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
  <header class="h-16 bg-blue-500 text-white flex items-center justify-between px-6 shadow">
    <h1 class="text-xl font-bold"><?= $pageTitle ?></h1>
    <div class="flex items-center space-x-3">
      <span class="text-sm">ADMIN</span>
      <i class="fas fa-user-circle text-2xl"></i>
    </div>
  </header>

  <main class="flex-1 p-6 bg-white overflow-y-auto">
    <h2 class="text-lg font-semibold mb-4">Events Dashboard</h2>

    <a href="events_tools/event_proposals.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded inline-block">
      View Event Proposals
    </a>

    <!-- You can add more event sections or summaries here later -->
  </main>
</div>

<?php include('includes/footer.php'); ?>
