<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/db.php';

// Header utility functions
function getUserProfilePicture($conn, $userId) {
    if (!$userId) return null;
    
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['profile_picture'])) {
        return '/library-app/' . ltrim($result['profile_picture'], '/');
    }
    return null;
}

function isUserLoggedIn() {
    return isset($_SESSION['first_name']) && isset($_SESSION['user_id']);
}

// Get user data
$isLoggedIn = isUserLoggedIn();
$profilePic = $isLoggedIn ? getUserProfilePicture($conn, $_SESSION['user_id']) : null;
$firstName = $_SESSION['first_name'] ?? '';
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
    <a href="/library-app/index.php" style="color: white; text-decoration: none;">Home</a>
    <a href="/library-app/events.php" style="color: white; text-decoration: none;">Events</a>
    <a href="/library-app/about.php" style="color: white; text-decoration: none;">About</a>
    <a href="/library-app/contact.php" style="color: white; text-decoration: none;">Contact</a>
    
    <?php if ($isLoggedIn): ?>
      <!-- User Profile Section -->
      <div class="user-section" style="display: flex; align-items: center; gap: 15px; font-size: 14px;">
        <!-- Profile Button -->
        <a href="/library-app/profile/edit.php" class="profile-btn" style="
          display: flex;
          align-items: center;
          gap: 8px;
          border: 1px solid white;
          padding: 6px 12px;
          border-radius: 6px;
          color: white;
          text-decoration: none;
          transition: all 0.3s ease;
        ">
          <?php if ($profilePic): ?>
            <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" class="profile-img" style="
              width: 28px;
              height: 28px;
              border-radius: 50%;
              object-fit: cover;
              border: 1px solid white;
            ">
          <?php else: ?>
            <div class="profile-placeholder" style="
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
            "><?= strtoupper(substr($firstName, 0, 1)) ?></div>
          <?php endif; ?>
          <span>Profile</span>
        </a>
        
        <!-- Welcome Message -->
        <span class="welcome-msg" style="color: white;">
          Hello, <strong><?= htmlspecialchars($firstName) ?>!</strong>
        </span>
        
        <!-- Logout Button -->
        <form action="/library-app/login/logout.php" method="post" style="margin: 0;">
          <button type="submit" class="logout-btn" style="
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
          ">
            Logout
          </button>
        </form>
      </div>
    <?php else: ?>
      <!-- Login Button -->
      <button class="btnLogin-popup" style="
        background: transparent;
        border: 2px solid white;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
      ">
        Login
      </button>
    <?php endif; ?>
  </nav>
</header>

<style>
/* Header Styles */
.profile-btn:hover {
  background-color: white !important;
  color: black !important;
}

.logout-btn:hover {
  background-color: #dc2626 !important;
  border-color: #dc2626 !important;
}

.btnLogin-popup:hover {
  background-color: white !important;
  color: black !important;
}

/* Responsive Design */
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
  
  .user-section {
    gap: 10px !important;
    flex-wrap: wrap;
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
  
  .welcome-msg {
    display: none; /* Hide welcome message on very small screens */
  }
  
  .profile-btn span {
    display: none; /* Hide "Profile" text, keep only the image */
  }
}
</style>
