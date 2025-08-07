<?php
session_start(); // IMPORTANT: this must be at the top
require '../includes/db.php';  // Go up one level to find includes folder
require_once '../includes/PHPMailer/src/PHPMailer.php';
require_once '../includes/PHPMailer/src/SMTP.php';
require_once '../includes/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Store submitted registration data in session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['registration'] = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'contact_number' => $_POST['contact_number'] ?? '',
        'password' => $_POST['password'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'age' => $_POST['age'] ?? '',
        'religion' => $_POST['religion'] ?? '',
        'is_mandaluyong_resident' => $_POST['is_mandaluyong_resident'] ?? '',
        'barangay' => $_POST['barangay'] ?? '',
        'city_outside_mandaluyong' => $_POST['city_outside_mandaluyong'] ?? '',
        'education_level' => $_POST['education_level'] ?? '',
        'major' => $_POST['major'] ?? '',
        'strand' => $_POST['strand'] ?? '',
        'school_name' => $_POST['school_name'] ?? ''
    ];
}

$email = $_SESSION['registration']['email'] ?? null;
if (!$email) {
    echo "No email provided.";
    exit();
}

// Check if email already exists
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        echo "Email already registered. Please use a different email.";
        exit();
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
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
$mail->Password = 'brgm uejx knoj upsi'; // Make sure this is your actual app password
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('hextech.abcy@gmail.com', 'Library Registration');
$mail->addAddress($email);
$mail->Subject = 'Your OTP Code';
$mail->Body = "Your OTP code is: $otp\n\nValid for 5 minutes only.";

if (!$mail->send()) {
    echo 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'success';
}
?>
