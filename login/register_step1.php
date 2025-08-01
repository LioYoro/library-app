<?php
session_start();
require '../includes/db.php'; // Assumes PDO connection as $conn

$emailExistsError = ""; // Default to no error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $emailExistsError = "Email already exists.";
    } else {
        $_SESSION['registration'] = $_POST;
        header("Location: register_step2.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tl">
<head>
    <meta charset="UTF-8">
    <title>Hakbang 1: Impormasyon</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
    <script>
        function validateForm() {
            const pass = document.getElementById("password").value;
            const confirm = document.getElementById("confirm_password").value;

            if (pass.length < 8 || !/[A-Z]/.test(pass) || !/[a-z]/.test(pass)) {
                alert("Password must be at least 8 characters with uppercase and lowercase letters.");
                return false;
            }

            if (pass !== confirm) {
                alert("Passwords do not match.");
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <form method="POST" action="" onsubmit="return validateForm()" class="form-card">
        <h2>Hakbang 1: Personal na Impormasyon</h2>

        <?php if (!empty($emailExistsError)): ?>
            <div class="error"><?= $emailExistsError ?></div>
        <?php endif; ?>

        <input type="text" name="first_name" placeholder="Pangalan" required>
        <input type="text" name="last_name" placeholder="Apelyido" required>
        <label for="contact_number">Contact Number:</label>
        <input type="text" name="contact_number" id="contact_number" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" id="password" name="password" placeholder="Password" required>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Kumpirmahin ang Password" required>

        <label>Kasarian:</label><br>
        <label><input type="radio" name="gender" value="Male" required> Lalaki</label>
        <label><input type="radio" name="gender" value="Female" required> Babae</label><br>

        <input type="number" name="age" placeholder="Edad" min="1" max="99" required>
        
        <select name="religion" required>
            <option value="">Pumili ng Relihiyon</option>
            <option value="Catholic">Katoliko</option>
            <option value="Christian">Kristiyano</option>
            <option value="Islam">Iglesia ni Cristo (INC)</option>
            <option value="Islam">Islam</option>
            <option value="Islam">Protestant</option>
            <option value="Others">Wala sa nabanggit</option>
        </select>

        <button type="submit">Susunod</button>
    </form>
</body>
</html>
