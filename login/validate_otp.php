<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'] ?? '';
    $expected_otp = $_SESSION['registration']['otp_code'] ?? '';
    $expires_at = strtotime($_SESSION['registration']['otp_expires_at'] ?? '0');

    if (time() > $expires_at) {
        die("OTP has expired. Please restart the registration.");
    }

    if ($entered_otp == $expected_otp) {
        $_SESSION['registration']['email_verified'] = true;
        header("Location: save_user.php");
        exit();
    } else {
        die("Invalid OTP. Please go back and try again.");
    }
} else {
    die("Invalid request.");
}
?>
