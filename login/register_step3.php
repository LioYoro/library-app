<!-- register_step3.php -->
<?php
session_start();

// Save step 2 values to session
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $_SESSION['registration']['education_level'] = $_POST['education_level'];
  $_SESSION['registration']['major'] = $_POST['major'] ?? '';
  $_SESSION['registration']['strand'] = $_POST['strand'] ?? '';
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
          <option value="Bagong Silang">Bagong Silang</option>
          <option value="Barangka Drive">Barangka Drive</option>
          <option value="Barangka Ibaba">Barangka Ibaba</option>
          <option value="Barangka Ilaya">Barangka Ilaya</option>
          <option value="Barangka Itaas">Barangka Itaas</option>
          <option value="Buayang Bato">Buayang Bato</option>
          <option value="Burol">Burol</option>
          <option value="Daang Bakal">Daang Bakal</option>
          <option value="Hagdang Bato Itaas">Hagdang Bato Itaas</option>
          <option value="Hagdang Bato Libis">Hagdang Bato Libis</option>
          <option value="Harapin ang Bukas">Harapin ang Bukas</option>
          <option value="Highway Hills">Highway Hills</option>
          <option value="Hulo">Hulo</option>
          <option value="Mabini-J. Rizal">Mabini-J. Rizal</option>
          <option value="Malamig">Malamig</option>
          <option value="Mauway">Mauway</option>
          <option value="Namayan">Namayan</option>
          <option value="New Zaniga">New Zaniga</option>
          <option value="Old Zaniga">Old Zaniga</option>
          <option value="Pag-asa">Pag-asa</option>
          <option value="Plainview">Plainview</option>
          <option value="Pleasant Hills">Pleasant Hills</option>
          <option value="Poblacion">Poblacion</option>
          <option value="San Jose">San Jose</option>
          <option value="Vergara">Vergara</option>
          <option value="Wack-Wack-Greenhills East">Wack-Wack-Greenhills East</option>
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
