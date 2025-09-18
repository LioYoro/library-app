<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
  #sidebar {
    transition: all 0.3s ease;
    transform: translateX(0);
    z-index: 1000;
  }

  #sidebar.collapsed {
    transform: translateX(-99%);
  }

  #sidebar.collapsed .sidebar-text {
    opacity: 0;
    width: 0;
    overflow: hidden;
  }

  #sidebar.collapsed .section-title {
    opacity: 0;
    height: 0;
    padding: 0;
    margin: 0;
    overflow: hidden;
  }

  #sidebar.collapsed .sidebar-icon {
    margin-right: 0.5rem;
  }
</style>

<div id="sidebar" 
     class="w-60 bg-[#244a9b] text-white flex flex-col h-screen fixed left-0 top-0"
     onmouseover="expandSidebar()" 
     onmouseout="collapseSidebar()">

  <div class="flex items-center px-4 py-4 space-x-3">
   <img src="/library-app/admin/assets/image/test_logo_admin.jpg" class="w-8 h-8 rounded-full object-cover" alt="Kaban ng Hiyas Logo">
  <span class="font-bold text-sm sidebar-text">KABAN NG HIYAS</span>
  </div>

  <nav class="flex flex-col flex-1 px-2">
    <a href="/library-app/admin/dashboard.php" class="flex items-center justify-between px-4 py-2 rounded hover:bg-blue-700 <?= $currentPage === 'dashboard.php' ? 'bg-blue-700' : '' ?>">
      <span class="sidebar-text">DASHBOARD</span>
      <img src="/library-app/admin/assets/icons/dashboards.png" alt="Dashboard Icon" class="w-11 h-11">
    </a>

    <div class="section-title text-xs uppercase tracking-widest text-white px-4 pt-4 pb-1">TOOLS</div>

    <a href="/library-app/admin/books.php" class="flex items-center justify-between px-4 py-2 rounded hover:bg-blue-700 <?= $currentPage === 'books.php' ? 'bg-blue-700' : '' ?>">
      <span class="sidebar-text">BOOKS</span>
      <img src="/library-app/admin/assets/icons/book.png" alt="Dashboard Icon" class="w-11 h-11">
    </a>

    <a href="/library-app/admin/reservations.php" class="flex items-center justify-between px-4 py-2 rounded hover:bg-blue-700 <?= $currentPage === 'reservations.php' ? 'bg-blue-700' : '' ?>">
      <span class="sidebar-text">RESERVATIONS</span>
      <img src="/library-app/admin/assets/icons/reservation.png" alt="Dashboard Icon" class="w-11 h-11">
    </a>

    <a href="/library-app/admin/users.php" class="flex items-center justify-between px-4 py-2 rounded hover:bg-blue-700 <?= $currentPage === 'users.php' ? 'bg-blue-700' : '' ?>">
      <span class="sidebar-text">USERS</span>
      <img src="/library-app/admin/assets/icons/user.png" alt="Dashboard Icon" class="w-11 h-11">
    </a>

    <!-- ✅ New ANNOUNCEMENT Section -->
    <a href="/library-app/admin/announcements.php" class="flex items-center justify-between px-4 py-2 rounded hover:bg-blue-700 <?= $currentPage === 'announcements.php' ? 'bg-blue-700' : '' ?>">
      <span class="sidebar-text">ANNOUNCEMENTS</span>
    <img src="/library-app/admin/assets/icons/announcement.png" alt="Dashboard Icon" class="w-11 h-11">
    </a>
    <!-- ✅ End New Section -->

    <a href="/library-app/admin/events.php" class="flex items-center justify-between px-4 py-2 rounded hover:bg-blue-700 <?= $currentPage === 'events.php' ? 'bg-blue-700' : '' ?>">
      <span class="sidebar-text">EVENTS</span>
      <img src="/library-app/admin/assets/icons/event.png" alt="Dashboard Icon" class="w-11 h-11">
    </a>

    <a href="/library-app/admin/roles.php" class="flex items-center justify-between px-4 py-2 rounded hover:bg-blue-700 <?= $currentPage === 'roles.php' ? 'bg-blue-700' : '' ?>">
      <span class="sidebar-text">ROLES</span>
      <img src="/library-app/admin/assets/icons/roles.png" alt="Dashboard Icon" class="w-11 h-11">
    </a>
  </nav>

  <!-- ✅ Button at the bottom -->
  <div class="p-4 mt-auto">
    <form action="../index.php" method="get">
      <button type="submit" 
        style="width: 100%; padding: 10px 20px; background-color: #1d4ed8; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Go to User Panel
      </button>
    </form>
  </div>
</div>


<script>
  let isCollapsed = false;
  let hoverTimeout;
  const collapseDelay = 300;

  function expandSidebar() {
    clearTimeout(hoverTimeout);
    if (isCollapsed) {
      document.getElementById('sidebar').classList.remove('collapsed');
      document.getElementById('main-content').classList.remove('collapsed');
      isCollapsed = false;
    }
  }

  function collapseSidebar() {
    hoverTimeout = setTimeout(() => {
      if (!isCollapsed) {
        document.getElementById('sidebar').classList.add('collapsed');
        document.getElementById('main-content').classList.add('collapsed');
        isCollapsed = true;
      }
    }, collapseDelay);
  }

  window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('sidebar').classList.add('collapsed');
    document.getElementById('main-content').classList.add('collapsed');
    isCollapsed = true;
  });
</script>
