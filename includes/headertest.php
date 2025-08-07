<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/db.php';

// Get user's profile picture from database if logged in
$profilePic = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['profile_picture'])) {
        $profilePic = '/library-app/' . ltrim($result['profile_picture'], '/');
    }
}
?>

<header style="
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 80px;
  background-color: #000000ff;
  z-index: 1000;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 40px;
  color: white;
">
  <h2 class="logo" style="margin: 0;">ARK</h2>
  
  <nav class="navigation" style="display: flex; align-items: center; gap: 30px;">
    <a href="#" style="color: white; text-decoration: none;">Home</a>
    <a href="#" style="color: white; text-decoration: none;">About</a>
    <a href="#" style="color: white; text-decoration: none;">Services</a>
    <a href="#" style="color: white; text-decoration: none;">Contact</a>
    
    <?php if (isset($_SESSION['first_name'])): ?>
      <!-- Logged in user section -->
      <div style="display: flex; align-items: center; gap: 15px; font-size: 14px;">
        <!-- Profile Button with Picture -->
        <a href="/library-app/profile/edit.php" style="
          display: flex;
          align-items: center;
          gap: 8px;
          border: 1px solid white;
          padding: 6px 12px;
          border-radius: 6px;
          color: white;
          text-decoration: none;
          transition: all 0.3s ease;
        " onmouseover="this.style.backgroundColor='white'; this.style.color='black';" 
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='white';">
          <?php if ($profilePic): ?>
            <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" style="
              width: 28px;
              height: 28px;
              border-radius: 50%;
              object-fit: cover;
              border: 1px solid white;
            ">
          <?php else: ?>
            <div style="
              width: 28px;
              height: 28px;
              border-radius: 50%;
              background-color: #666;
              display: flex;
              align-items: center;
              justify-content: center;
              color: white;
              font-size: 12px;
              font-weight: bold;
            ">?</div>
          <?php endif; ?>
          <span>Profile</span>
        </a>
        
        <!-- Welcome message -->
        <span style="color: white;">
          Hello, <strong><?= htmlspecialchars($_SESSION['first_name']) ?>!</strong>
        </span>
        
        <!-- Logout button -->
        <form action="/library-app/login/logout.php" method="post" style="margin: 0;">
          <button type="submit" style="
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
          " onmouseover="this.style.backgroundColor='#dc2626'; this.style.borderColor='#dc2626';" 
             onmouseout="this.style.backgroundColor='transparent'; this.style.borderColor='white';">
            Logout
          </button>
        </form>
      </div>
    <?php else: ?>
      <!-- Login button for non-logged in users -->
      <button class="btnLogin-popup" style="
        background: transparent;
        border: 2px solid white;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
      " onmouseover="this.style.backgroundColor='white'; this.style.color='black';" 
         onmouseout="this.style.backgroundColor='transparent'; this.style.color='white';">
        Login
      </button>
    <?php endif; ?>
  </nav>
</header>

<style>
/* Add some responsive design for smaller screens */
@media (max-width: 768px) {
  header {
    padding: 0 20px !important;
    flex-wrap: wrap;
    height: auto !important;
    min-height: 80px;
  }
  
  .navigation {
    gap: 15px !important;
    flex-wrap: wrap;
  }
  
  .navigation a {
    font-size: 14px;
  }
  
  .navigation > div {
    flex-wrap: wrap;
    gap: 10px !important;
  }
}

@media (max-width: 480px) {
  header {
    padding: 10px !important;
  }
  
  .navigation {
    gap: 10px !important;
  }
  
  .navigation a {
    font-size: 12px;
  }
  
  .navigation span {
    display: none; /* Hide welcome message on very small screens */
  }
}
</style>