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
      <select name="education_level" id="education_level" required onchange="toggleInputs()">
        <option value="">--Pumili--</option>
        <option value="JHS">Junior High School</option>
        <option value="SHS">Senior High School</option>
        <option value="College">Kolehiyo</option>
      </select>

      <label for="major">Kurso sa Kolehiyo:</label>
      <input list="courseList" name="major" id="major" disabled>
      <datalist id="courseList">
        <option value="AB Political Science">
        <option value="AB Psychology">
        <option value="BA Broadcasting">
        <option value="BA History">
        <option value="BA Political Science">
        <option value="BS Accountancy">
        <option value="BS Architecture">
        <option value="BS Civil Engineering">
        <option value="BS Computer Engineering">
        <option value="BS Dentistry">
        <option value="BS ECE">
        <option value="BS Economics">
        <option value="BS Education">
        <option value="BS Education Major in Filipino">
        <option value="BS Education Major in Math">
        <option value="BS Education Major in Science">
        <option value="BS Education Major in Social Studies">
        <option value="BS Electrical Engineering">
        <option value="BS Elementary Education">
        <option value="BS Electronics Engineering">
        <option value="BS Entrepreneurship">
        <option value="BS Hospitality Management">
        <option value="BS Industrial Engineering">
        <option value="BS Information Technology">
        <option value="BS IT">
        <option value="BS Management Accounting">
        <option value="BS Mechanical Engineering">
        <option value="BS Nursing">
        <option value="BS Office Administration">
        <option value="BS Psychology">
        <option value="BSBA Financial Management">
        <option value="BSBA Human Resource Management">
        <option value="BSBA Marketing Management">
        <option value="BSE Filipino">
        <option value="BSE Math">
        <option value="BSE Science">
        <option value="BSE Social Studies">
        <option value="BSED Filipino">
        <option value="BSED ICT">
        <option value="BSED Science">
        <option value="BSES Social Studies">
        <option value="BTVTED Garments, Fashion and Design">
      </datalist>

      <label for="strand">Strand sa SHS:</label>
      <select name="strand" id="strand" disabled>
        <option value="">--Pumili ng Strand--</option>
        <option value="ABM">ABM (Accountancy, Business and Management)</option>
        <option value="STEM">STEM (Science, Technology, Engineering, and Mathematics)</option>
        <option value="HUMSS">HUMSS (Humanities and Social Sciences)</option>
        <option value="GAS">GAS (General Academic Strand)</option>
        <option value="TVL">TVL (Technical-Vocational-Livelihood)</option>
        <option value="Arts and Design">Arts and Design</option>
      </select>

      <label for="school_name">Pangalan ng Paaralan:</label>
      <input type="text" name="school_name" id="school_name" required>

      <button type="submit">Magpatuloy</button>
    </form>
  </div>

  <script>
    function toggleInputs() {
      const level = document.getElementById('education_level').value;
      const courseInput = document.getElementById('major');
      const strandInput = document.getElementById('strand');

      if (level === 'College') {
        courseInput.disabled = false;
        courseInput.required = true;
        strandInput.disabled = true;
        strandInput.required = false;
        strandInput.value = '';
      } else if (level === 'SHS') {
        courseInput.disabled = true;
        courseInput.required = false;
        courseInput.value = '';
        strandInput.disabled = false;
        strandInput.required = true;
      } else {
        courseInput.disabled = true;
        courseInput.required = false;
        courseInput.value = '';
        strandInput.disabled = true;
        strandInput.required = false;
        strandInput.value = '';
      }
    }

    document.addEventListener("DOMContentLoaded", toggleInputs);
  </script>
</body>
</html>
