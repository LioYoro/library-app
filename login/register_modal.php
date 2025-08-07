<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
  <link rel="stylesheet" href="css/login.css" />
</head>
<body>

<div id="registerModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="goBackToLogin()">&times;</span>
    <form id="registerForm">
      <div class="form-step step-1">
        <h2>Step 1: Primary Information</h2>
        <input type="text" name="first_name" placeholder="First Name" required />
        <input type="text" name="last_name" placeholder="Last Name" required />
        <input type="email" name="email" placeholder="Email" required />
        <input type="text" name="contact_number" placeholder="Contact Number" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
        <select name="gender" required>
          <option value="">Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
        <input type="number" name="age" placeholder="Age" required />
        <input type="text" name="religion" placeholder="Religion" required />
        <button type="button" onclick="nextStep(1)">Next</button>
      </div>

      <div class="form-step step-2 hidden">
        <h2>Step 2: Secondary Information</h2>
        <label>Are you a Mandaluyong Resident?</label>
        <select name="is_mandaluyong_resident" onchange="toggleBarangay(this.value)" required>
          <option value="">Select</option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
        <div id="barangay-field" class="conditional hidden">
          <input type="text" name="barangay" placeholder="Barangay" />
        </div>
        <div id="city-outside-field" class="conditional hidden">
          <input type="text" name="city_outside_mandaluyong" placeholder="City Outside Mandaluyong" />
        </div>
        <button type="button" onclick="nextStep(2)">Next</button>
        <button type="button" onclick="prevStep(1)">Back</button>
      </div>

      <div class="form-step step-3 hidden">
        <h2>Step 3: Education</h2>
        <label>Education Level:</label>
        <select name="education_level" onchange="toggleEducationFields(this.value)" required>
          <option value="">Select</option>
          <option value="SHS">Senior High School</option>
          <option value="College">College</option>
          <option value="Graduate">Graduate</option>
        </select>

        <div id="shs-strand" class="conditional hidden">
          <label>Strand:</label>
          <select name="strand">
            <option value="">Select Strand</option>
            <option value="STEM">STEM</option>
            <option value="ABM">ABM</option>
            <option value="HUMSS">HUMSS</option>
            <option value="GAS">GAS</option>
            <option value="TVL">TVL</option>
            <option value="Sports">Sports</option>
            <option value="Arts and Design">Arts and Design</option>
          </select>
        </div>

        <div id="college-course" class="conditional hidden">
          <label>Course:</label>
          <select name="major">
            <option value="">Select Course</option>
            <option value="BS Computer Science">BS Computer Science</option>
            <option value="BS Information Technology">BS Information Technology</option>
            <option value="BS Business Administration">BS Business Administration</option>
            <option value="BS Accountancy">BS Accountancy</option>
            <option value="BS Education">BS Education</option>
            <option value="BA Communication">BA Communication</option>
          </select>
        </div>

        <input type="text" name="school_name" placeholder="School Name" required />

        <button type="submit">Submit</button>
        <button type="button" onclick="prevStep(2)">Back</button>
      </div>
    </form>
  </div>
</div>

<!-- Scripts -->
<script src="js/register.js"></script>
<script>
function goBackToLogin() {
  window.location.href = 'logintest.php';
}
</script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>