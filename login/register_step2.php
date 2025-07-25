<!-- register_step2.php -->
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['registration']['first_name'] = $_POST['first_name'];
    $_SESSION['registration']['last_name'] = $_POST['last_name'];
    $_SESSION['registration']['contact_number'] = $_POST['contact_number'];
    $_SESSION['registration']['email'] = $_POST['email'];
    $_SESSION['registration']['password'] = $_POST['password'];
    $_SESSION['registration']['gender'] = $_POST['gender'];
    $_SESSION['registration']['age'] = $_POST['age'];
    $_SESSION['registration']['religion'] = $_POST['religion'];
}
?>

<!DOCTYPE html>
<html lang="tl">
<head>
  <meta charset="UTF-8">
  <title>Hakbang 2 - Edukasyon</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div class="form-container">
    <h2>Hakbang 2: Impormasyon ng Edukasyon</h2>
    <form action="register_step3.php" method="POST">
      <label for="education_level">Antas ng Edukasyon:</label>
      <select name="education_level" id="education_level" required onchange="toggleCourseInput()">
        <option value="">--Pumili--</option>
        <option value="JHS">Junior High School</option>
        <option value="SHS">Senior High School</option>
        <option value="College">Kolehiyo</option>
      </select>

      <label for="course">Kurso sa Kolehiyo:</label>
      <input type="text" name="course" id="course" disabled>

      <label for="school_name">Pangalan ng Paaralan:</label>
      <input type="text" name="school_name" id="school_name" required>

      <button type="submit">Magpatuloy</button>
    </form>
  </div>

  <script>
    function toggleCourseInput() {
      const educationLevel = document.getElementById('education_level').value;
      const courseInput = document.getElementById('course');

      if (educationLevel === 'College') {
        courseInput.disabled = false;
        courseInput.required = true;
      } else {
        courseInput.disabled = true;
        courseInput.required = false;
        courseInput.value = '';
      }
    }

    document.addEventListener("DOMContentLoaded", toggleCourseInput);
  </script>
</body>
</html>
