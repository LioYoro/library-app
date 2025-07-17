<?php
session_start();
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_code'])) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredCode = $_POST['code'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($enteredCode == $_SESSION['reset_code']) {
        if ($newPass === $confirm) {
            require '../includes/db.php';
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE email = :email");
            $stmt->execute([
                ':password' => $hashed,
                ':email' => $_SESSION['reset_email']
            ]);

            session_destroy();
            echo "Password reset successful. <a href='../index.php'>Login</a>";
            exit;
        } else {
            echo "Passwords do not match.";
        }
    } else {
        echo "Invalid code.";
    }
}
?>

<form method="POST">
    <h2>Verify Code & Reset Password</h2>
    <input type="text" name="code" required placeholder="Enter the code sent to your email">
    <input type="password" name="new_password" required placeholder="New password">
    <input type="password" name="confirm_password" required placeholder="Confirm password">
    <button type="submit">Reset Password</button>
</form>
