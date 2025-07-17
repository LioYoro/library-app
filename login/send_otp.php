<?php
session_start(); // IMPORTANT: this must be at the top

require '../includes/db.php';
require_once '../includes/PHPMailer/src/PHPMailer.php';
require_once '../includes/PHPMailer/src/SMTP.php';
require_once '../includes/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Store submitted registration data in session (first time)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['registration']['is_mandaluyong_resident'] = $_POST['is_mandaluyong_resident'] ?? '';
    $_SESSION['registration']['barangay'] = $_POST['barangay'] ?? '';
    $_SESSION['registration']['city_outside_mandaluyong'] = $_POST['city_outside_mandaluyong'] ?? '';
}


$email = $_SESSION['registration']['email'] ?? null;

if (!$email) {
    die("No email provided.");
}

$otp = rand(100000, 999999);
$_SESSION['registration']['otp_code'] = $otp;
$_SESSION['registration']['otp_expires_at'] = date("Y-m-d H:i:s", strtotime("+5 minutes"));

// Send email
$mail = new PHPMailer;
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'hextech.abcy@gmail.com';
$mail->Password = 'PASTE HERE'; // Make sure this is your actual app password
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('hextech.abcy@gmail.com', 'Library Registration');
$mail->addAddress($email);
$mail->Subject = 'Your OTP Code';
$mail->Body    = "Your OTP code is: $otp\n\nValid for 5 minutes only.";

if (!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    // Redirect to OTP verification page
    header("Location: verify_otp.php");
    exit;
}
?>
