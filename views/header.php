<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/db.php';

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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $title ?? 'Library App' ?></title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- App CSS -->
  <link rel="stylesheet" href="css/map.css">
  <link rel="stylesheet" href="css/announcement.css">
  <link rel="stylesheet" href="css/booksection.css">
  <link rel="stylesheet" href="css/info.css">
  <link rel="stylesheet" href="css/comment.css">
  <link rel="stylesheet" href="css/logintest.css">
  <style>
    body {
        margin: 0;
        padding-top: 80px;
        font-family: sans-serif;
    }
    .search-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    #searchForm {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
        width: 100%;
    }
    .search-input-group {
        display: flex;
        align-items: center;
        border: 1px solid #000;
        border-radius: 6px;
        flex-grow: 1;
        min-width: 250px;
    }
    .search-input-group input {
        flex-grow: 1;
        padding: 8px;
        font-size: 14px;
        outline: none;
        border: none;
    }
    .search-input-group button {
        padding: 8px 12px;
        font-size: 16px;
        color: #000;
        background: none;
        border: none;
        cursor: pointer;
    }
    .search-btn {
        border: 1px solid #000;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 12px;
        background: white;
        cursor: pointer;
        text-decoration: none;
        color: #000;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .search-btn:hover {
        background-color: #f3f4f6;
    }
    @media (max-width: 768px) {
        #searchForm {
            flex-direction: column;
            align-items: stretch;
        }
        .search-input-group {
            min-width: 100%;
            margin-bottom: 8px;
        }
        .search-btn {
            width: 100%;
            justify-content: center;
        }
    }
  </style>
</head>
<body>
<!-- Header -->
<header style="position: fixed; top: 0; left: 0; width: 100%; height: 80px; background-color: #000; z-index: 1000; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; color: white;">
  <h2 class="logo" style="margin: 0;">ARK</h2>
  <nav class="navigation" style="display: flex; align-items: center; gap: 30px;">
    <a href="/library-app/index.php" style="color: white; text-decoration: none;">Home</a>
    <a href="/library-app/views/events.php" style="color: white; text-decoration: none;">Events</a>
    <a href="/library-app/views/about.php" style="color: white; text-decoration: none;">About</a>
    <a href="/library-app/views/contact.php" style="color: white; text-decoration: none;">Contact</a>
    <?php if (isset($_SESSION['first_name'])): ?>
      <div style="display: flex; align-items: center; gap: 15px; font-size: 14px;">
        <a href="/library-app/profile/edit.php" style="display: flex; align-items: center; gap: 8px; border: 1px solid white; padding: 6px 12px; border-radius: 6px; color: white; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='white'; this.style.color='black';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='white';">
          <?php if ($profilePic): ?>
            <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid white;">
          <?php else: ?>
            <div style="width: 28px; height: 28px; border-radius: 50%; background-color: #666; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;">
              <?= strtoupper(substr($_SESSION['first_name'], 0, 1)) ?>
            </div>
          <?php endif; ?>
          <span>Profile</span>
        </a>
        <span style="color: white;">Hello, <strong><?= htmlspecialchars($_SESSION['first_name']) ?>!</strong></span>
        <form action="/library-app/login/logout.php" method="post" style="margin: 0;">
          <button type="submit" style="background: transparent; border: 1px solid white; color: white; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#dc2626'; this.style.borderColor='#dc2626';" onmouseout="this.style.backgroundColor='transparent'; this.style.borderColor='white';">
            Logout
          </button>
        </form>
      </div>
    <?php else: ?>
      <button id="openLoginModal" class="btnLogin-popup" style="background: transparent; border: 2px solid white; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='white'; this.style.color='black';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='white';">
        Login
      </button>
    <?php endif; ?>
  </nav>
</header>

<main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const openBtn = document.getElementById("openLoginModal");
    
    if (openBtn) {
        console.log("Login button found");
        
        openBtn.addEventListener("click", function() {
            console.log("Login button clicked - creating modal");
            
            // Remove any existing modal first
            const existingModal = document.getElementById('loginModalOverlay');
            if (existingModal) {
                existingModal.remove();
            }
            
            const modalOverlay = document.createElement('div');
            modalOverlay.id = 'loginModalOverlay';
            modalOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.8);
                z-index: 2000;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
                box-sizing: border-box;
            `;

            const iframeContainer = document.createElement('div');
            iframeContainer.style.cssText = `
                position: relative;
                background: transparent;
                border-radius: 20px;
                max-width: 450px;
                width: 100%;
                max-height: 90vh;
                overflow: hidden;
            `;

            const iframe = document.createElement('iframe');
            iframe.src = '/library-app/login/logintest.php';
            iframe.style.cssText = `
                width: 100%;
                height: 500px;
                border: none;
                border-radius: 20px;
                background: transparent;
            `;

            iframe.addEventListener('load', () => {
                console.log('Iframe loaded successfully');
            });
            
            iframe.addEventListener('error', (e) => {
                console.log('Iframe failed to load:', e);
            });

            function closeModal() {
                if (document.body.contains(modalOverlay)) {
                    document.body.removeChild(modalOverlay);
                }
                document.body.style.overflow = 'auto';
            }
            
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            // Listen for messages from iframe
            window.addEventListener('message', function(e) {
                console.log('Received message:', e.data);
                if (e.data === 'loginSuccess') {
                    closeModal();
                    window.location.reload();
                } else if (e.data === 'closeModal') {
                    closeModal();
                }
            });

            iframeContainer.appendChild(iframe);
            modalOverlay.appendChild(iframeContainer);
            document.body.appendChild(modalOverlay);
            document.body.style.overflow = 'hidden';
            
            console.log("Modal created and added to DOM");
        });
    } else {
        console.log("Login button not found");
    }
});
</script>

<!-- Ionicons -->
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
