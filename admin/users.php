<?php
include('includes/header.php');
include('includes/sidebar.php');
?>

<div class="flex h-screen overflow-hidden">
  <div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
    <header class="h-16 w-full bg-blue-600 text-white flex items-center justify-between px-6 shadow">
      <h1 class="text-xl font-bold">User Management</h1>
      <div class="flex items-center space-x-3">
        <i class="fas fa-user-cog text-2xl"></i>
      </div>
    </header>

    <main class="p-6 overflow-auto">
      <h2 class="text-2xl font-semibold mb-4">User Administration</h2>

      <div class="space-y-4 max-w-lg">
        <a href="user_tools/users_report.php" class="block bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded shadow text-center font-semibold">
          View User Reports & Edit Users
        </a>

        <!-- Additional links can go here, like Add User, Import Users, etc. -->
      </div>
    </main>
  </div>
</div>

<?php include('includes/footer.php'); ?>
