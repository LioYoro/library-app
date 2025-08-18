<!-- login/login.php -->
<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if (!$user['email_verified']) {
            $_SESSION['unverified_email'] = $user['email'];
            header("Location: verify_otp.php");
            exit();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Maling email o password.";
    }
}
?>

<!DOCTYPE html>
<html lang="tl">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <form method="POST" class="form-card">
        <h2>Mag-login</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <p>Wala pang account? <a href="register_step1.php">Magrehistro</a></p>
    </form>
</body>
</html>

<p><a href="forgot_password.php">Forgot Password?</a></p>