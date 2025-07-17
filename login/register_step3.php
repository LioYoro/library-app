<!-- register_step3.php -->
<?php
session_start();

// Save step 2 values to session
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $_SESSION['registration']['education_level'] = $_POST['education_level'];
$_SESSION['registration']['is_shs_student'] = isset($_POST['is_shs_student']) ? 1 : 0;
$_SESSION['registration']['course'] = $_POST['course'];
$_SESSION['registration']['school_name'] = $_POST['school_name'];

}
?>
<!DOCTYPE html>
<html lang="tl">
<head>
  <meta charset="UTF-8">
  <title>Hakbang 3 - Tirahan</title>
  <link rel="stylesheet" href="../css/style.css">
  <script>
    function toggleFields() {
      const isResident = document.getElementById('is_resident').checked;
      document.getElementById('barangay_section').style.display = isResident ? 'block' : 'none';
      document.getElementById('city_section').style.display = isResident ? 'none' : 'block';
    }
  </script>
</head>
<body onload="toggleFields()">
  <div class="form-container">
    <h2>Hakbang 3: Impormasyon sa Tirahan</h2>
    <form action="send_otp.php" method="POST">
      <label>
        <input type="checkbox" id="is_resident" name="is_mandaluyong_resident" value="1" onchange="toggleFields()">
        Taga-Mandaluyong ka ba?
      </label>

      <div id="barangay_section" style="display:none;">
        <label for="barangay">Kung oo, anong barangay?</label>
        <select name="barangay" id="barangay">
          <option value="">--Pumili--</option>
          <option value="Addition Hills">Addition Hills</option>
          <option value="Barangka">Barangka</option>
          <option value="Buayang Bato">Buayang Bato</option>
          <option value="Hagdan Bato Itaas">Hagdan Bato Itaas</option>
          <option value="Hulo">Hulo</option>
          <!-- Add other barangays here -->
        </select>
      </div>

      <div id="city_section" style="display:none;">
        <label for="city">Kung hindi, anong lungsod?</label>
        <input type="text" name="city_outside_mandaluyong" id="city">
      </div>

      <button type="submit">Kumpirmahin ang Email</button>
    </form>
  </div>
</body>
</html>
