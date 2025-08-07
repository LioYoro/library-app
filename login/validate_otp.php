<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'] ?? '';
    $expected_otp = $_SESSION['registration']['otp_code'] ?? '';
    $expires_at = strtotime($_SESSION['registration']['otp_expires_at'] ?? '0');
    
    if (time() > $expires_at) {
        echo "OTP has expired. Please restart the registration.";
        exit();
    }
    
    if ($entered_otp == $expected_otp) {
        $_SESSION['registration']['email_verified'] = true;
        echo 'success';
        exit();
    } else {
        echo "Invalid OTP. Please try again.";
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}
?>
