<?php
session_start();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Clear all session data
session_destroy();

// Redirect to home page
header('Location: /library-app/index.php');
exit();
?>
