<title> Books</title>

<?php

include('includes/header.php'); 
include('includes/sidebar.php');



 ?>

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
  
  <!-- Header Bar -->
  <header class="h-16 bg-blue-500 text-white flex items-center justify-between px-6 shadow">
    <h1 class="text-xl font-bold"> Books</h1>
    <div class="flex items-center space-x-3">
      <span class="text-sm">ADMIN</span>
      <i class="fas fa-user-circle text-2xl"></i>
    </div>
  </header>
  <!-- manage books -->
      <div class="p-4">
      <h1 class="text-xl font-bold mb-4">TOOLS</h1>
      
      <a href="books_tools/manage_books.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
          MANAGE BOOKS
      </a>
      <br>
      <br>
      <a href="books_tools/book_summary.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
          BOOKS SUMMARY
      </a>
      <!-- You can add more buttons here for other tools -->
        </div>


  


   
<?php include('includes/footer.php'); ?>

