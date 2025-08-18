<?php 
$pageTitle = 'Dashboard';
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
    <div style="margin-top: 10px;">
        <form action="../index.php" method="get">
            <button type="submit" style="padding: 10px 20px; background-color: #1d4ed8; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Go to User Panel
            </button>
        </form>
        <!-- Add debug link -->
        <!--
        <a href="?debug=1" style="margin-left: 10px; padding: 10px 20px; background-color: #dc2626; color: white; text-decoration: none; border-radius: 5px;">
            Show Debug Info
        </a>
        -->
    </div>
  </main>
</div>

<?php include('includes/footer.php'); ?>
