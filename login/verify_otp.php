<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['step3'] = $_POST;

    // Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + (5 * 60); // 5 minutes

    // Send OTP to user via email
    include 'send_otp.php';
}
?>

<!DOCTYPE html>
<html lang="tl">
<head>
    <meta charset="UTF-8">
    <title>I-verify ang Email</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Ilagay ang OTP na ipinadala sa iyong email</h2>
        <form action="validate_otp.php" method="POST">
            <label for="otp">OTP:</label>
            <input type="text" name="otp" maxlength="6" required>
            <button type="submit">I-verify</button>
        </form>
    </div>
</body>
</html>
