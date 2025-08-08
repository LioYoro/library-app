<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_code'])) {
        echo "Unauthorized access. Please request a new reset code.";
        exit;
    }
    
    // Check if code has expired
    if (isset($_SESSION['reset_code_expires']) && time() > $_SESSION['reset_code_expires']) {
        // Clear expired session data
        unset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['reset_code_expires']);
        echo "Reset code has expired. Please request a new one.";
        exit;
    }
    
    $enteredCode = $_POST['code'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($enteredCode) || empty($newPass) || empty($confirm)) {
        echo "All fields are required.";
        exit;
    }
    
    if ($enteredCode == $_SESSION['reset_code']) {
        if ($newPass === $confirm) {
            if (strlen($newPass) < 8) {
                echo "Password must be at least 8 characters long.";
                exit;
            }
            
            try {
                require '../includes/db.php';
                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE email = :email");
                $stmt->execute([
                    ':password' => $hashed,
                    ':email' => $_SESSION['reset_email']
                ]);
                
                // Clear session data
                unset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['reset_code_expires']);
                
                echo "success";
            } catch (PDOException $e) {
                echo "Database error: " . $e->getMessage();
            }
        } else {
            echo "Passwords do not match.";
        }
    } else {
        echo "Invalid code.";
    }
} else {
    echo "Invalid request method.";
}
?>