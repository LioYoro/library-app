<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$userId = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("User  not found.");
?>

<?php include '../views/header.php'; ?>

<div class="max-w-4xl mx-auto px-6 py-10 bg-white border border-gray-300 rounded-lg shadow-md mt-8">
  <h2 class="text-center text-2xl font-bold mb-8">Edit Your Profile</h2>

  <form id="profileForm" method="post" enctype="multipart/form-data">
    <!-- Profile Picture -->
    <div class="col-span-2 flex items-center gap-6 mb-8">
      <!-- Image Preview -->
      <div class="w-28 h-28 rounded-full overflow-hidden border border-gray-300 flex items-center justify-center bg-gray-100">
        <?php
        $hasProfilePicture = !empty($user['profile_picture']);
        $profileImagePath = $hasProfilePicture ? '/library-app/' . htmlspecialchars($user['profile_picture']) : '/library-app/assets/default_pp.png';
        ?>
        <img
          id="profilePreview"
          src="<?= $profileImagePath ?>"
          alt="Profile Picture"
          class="object-cover w-full h-full">
      </div>

      <!-- Upload Input -->
      <div>
        <label for="profile_picture" class="block text-sm font-medium mb-1">Upload New Picture</label>
        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="block text-sm mb-1">
        <p class="text-xs text-gray-500 mb-2">JPEG or PNG up to 2MB.</p>

        <!-- Hidden input to signal removal -->
        <input type="hidden" name="remove_picture" id="remove_picture" value="0">

        <!-- Remove button (moved below) -->
        <button type="button"
          id="removeImageBtn"
          class="bg-white border border-red-500 text-red-500 text-xs px-3 py-1 rounded shadow hover:bg-red-500 hover:text-white transition">
          Remove Image
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Left Column: Primary Info -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold mb-4">📌 Primary Information</h3>

        <div>
          <label class="block font-medium mb-1">First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="w-full border px-3 py-2 rounded">
        </div>

        <div>
          <label class="block font-medium mb-1">Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="w-full border px-3 py-2 rounded">
        </div>

        <div>
          <label class="block font-medium mb-1">Contact Number</label>
          <input type="text" name="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" class="w-full border px-3 py-2 rounded">
        </div>

        <div>
          <label class="block font-medium mb-1">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full border px-3 py-2 rounded">
        </div>

        <div>
          <label class="block font-medium mb-1">New Password (Leave blank if no change)</label>
          <input type="password" name="password" class="w-full border px-3 py-2 rounded">
        </div>

        <div>
          <label class="block font-medium mb-1">Gender</label>
          <select name="gender" class="w-full border px-3 py-2 rounded">
            <option value="Male" <?= $user['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $user['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
          </select>
        </div>

        <div>
          <label class="block font-medium mb-1">Age</label>
          <input type="number" name="age" value="<?= htmlspecialchars($user['age']) ?>" class="w-full border px-3 py-2 rounded">
        </div>

        <div>
          <label for="religion" class="block font-medium mb-1">Relihiyon</label>
          <select name="religion" id="religion" class="w-full border px-3 py-2 rounded" required>
            <option value="">Pumili ng Relihiyon</option>
            <?php
            $religions = [
              "Catholic" => "Katoliko",
              "Christian" => "Kristiyano",
              "Iglesia ni Cristo (INC)" => "Iglesia ni Cristo (INC)",
              "Islam" => "Islam",
              "Protestant" => "Protestant",
              "Others" => "Iba pa"
            ];
            foreach ($religions as $value => $label) {
              $selected = ($user['religion'] === $value) ? 'selected' : '';
              echo "<option value=\"$value\" $selected>$label</option>";
            }
            ?>
          </select>
        </div>
      </div>

      <!-- Right Column: Secondary + Education Info -->
      <div class="space-y-6">
        <div class="space-y-4">
          <h3 class="text-lg font-semibold mb-4">🏠 Secondary Information</h3>

          <div>
            <label class="block font-medium mb-1">Are you a Mandaluyong resident?</label>
            <select name="is_mandaluyong_resident" class="w-full border px-3 py-2 rounded">
              <option value="">-- Select --</option>
              <option value="yes" <?= $user['is_mandaluyong_resident'] === 'yes' ? 'selected' : '' ?>>Yes</option>
              <option value="no" <?= $user['is_mandaluyong_resident'] === 'no' ? 'selected' : '' ?>>No</option>
            </select>
          </div>

          <div>
            <label class="block font-medium mb-1" for="barangay">Barangay</label>
            <select name="barangay" id="barangay" class="w-full border px-3 py-2 rounded">
              <option value="">--Pumili--</option>
              <?php
              $barangays = [
                "Addition Hills", "Bagong Silang", "Barangka Drive", "Barangka Ibaba",
                "Barangka Ilaya", "Barangka Itaas", "Buayang Bato", "Burol",
                "Daang Bakal", "Hagdang Bato Itaas", "Hagdang Bato Libis", "Harapin ang Bukas",
                "Highway Hills", "Hulo", "Mabini-J. Rizal", "Malamig",
                "Mauway", "Namayan", "New Zaniga", "Old Zaniga",
                "Pag-asa", "Plainview", "Pleasant Hills", "Poblacion",
                "San Jose", "Vergara", "Wack-Wack-Greenhills East"
              ];
              foreach ($barangays as $b) {
                $selected = ($user['barangay'] === $b) ? 'selected' : '';
                echo "<option value=\"$b\" $selected>$b</option>";
              }
              ?>
            </select>
          </div>

          <div>
            <label class="block font-medium mb-1">City Outside Mandaluyong</label>
            <input type="text" name="city_outside_mandaluyong" value="<?= htmlspecialchars($user['city_outside_mandaluyong']) ?>" class="w-full border px-3 py-2 rounded">
          </div>
        </div>

        <!-- College and SHS Dropdowns -->
        <div class="space-y-4 pt-4 border-t border-gray-300">
          <h3 class="text-lg font-semibold mb-4">🎓 Education Information</h3>

          <div>
            <label class="block font-medium mb-1">Education Level</label>
            <select name="education_level" class="w-full border px-3 py-2 rounded" onchange="toggleEducationDropdowns(this.value)">
              <option value="">--Pumili--</option>
              <option value="JHS" <?= $user['education_level'] === 'JHS' ? 'selected' : '' ?>>Junior High School</option>
              <option value="SHS" <?= $user['education_level'] === 'SHS' ? 'selected' : '' ?>>Senior High School</option>
              <option value="College" <?= $user['education_level'] === 'College' ? 'selected' : '' ?>>Kolehiyo</option>
            </select>
          </div>

          <!-- College -->
          <div id="collegeDropdown" class="<?= $user['education_level'] === 'College' ? '' : 'hidden' ?>">
              <label class="block font-medium mb-1" for="major">Kurso sa Kolehiyo</label>
              <input list="collegeCourseList" name="major" id="major" value="<?= htmlspecialchars($user['major']) ?>" class="w-full border px-3 py-2 rounded">
              <datalist id="collegeCourseList">
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
          </div>

          <!-- SHS -->
          <div id="shsDropdown" class="<?= $user['education_level'] === 'SHS' ? '' : 'hidden' ?>">
              <label class="block font-medium mb-1" for="strand">Strand sa SHS</label>
              <select name="strand" id="strand" class="w-full border px-3 py-2 rounded">
                  <option value="">--Pumili ng Strand--</option>
                  <option value="ABM" <?= $user['strand'] === 'ABM' ? 'selected' : '' ?>>ABM (Accountancy, Business and Management)</option>
                  <option value="STEM" <?= $user['strand'] === 'STEM' ? 'selected' : '' ?>>STEM (Science, Technology, Engineering, and Mathematics)</option>
                  <option value="HUMSS" <?= $user['strand'] === 'HUMSS' ? 'selected' : '' ?>>HUMSS (Humanities and Social Sciences)</option>
                  <option value="GAS" <?= $user['strand'] === 'GAS' ? 'selected' : '' ?>>GAS (General Academic Strand)</option>
                  <option value="TVL" <?= $user['strand'] === 'TVL' ? 'selected' : '' ?>>TVL (Technical-Vocational-Livelihood)</option>
                  <option value="Arts and Design" <?= $user['strand'] === 'Arts and Design' ? 'selected' : '' ?>>Arts and Design</option>
                  <option value="Sports Track" <?= $user['strand'] === 'Sports Track' ? 'selected' : '' ?>>Sports Track</option>
              </select>
          </div>

          <div>
            <label class="block font-medium mb-1">School Name</label>
            <input type="text" name="school_name" value="<?= htmlspecialchars($user['school_name']) ?>" class="w-full border px-3 py-2 rounded">
          </div>
        </div>
      </div>
    </div>

    <!-- Form alert (above Confirm Changes button) -->
    <div id="formAlert" class="text-center text-red-600 font-medium mb-4 hidden"></div>

    <!-- Submit button -->
    <div class="mt-10 text-center">
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
        Confirm Changes
      </button>
    </div>
  </form>
</div>

<script>
function toggleEducationDropdowns(level) {
  document.getElementById('collegeDropdown').classList.add('hidden');
  document.getElementById('shsDropdown').classList.add('hidden');
  if (level === 'College') {
    document.getElementById('collegeDropdown').classList.remove('hidden');
  } else if (level === 'SHS') {
    document.getElementById('shsDropdown').classList.remove('hidden');
  }
}
toggleEducationDropdowns("<?= $user['education_level'] ?>");
</script>

<script>
const educationLevelSelect = document.querySelector("select[name='education_level']");
educationLevelSelect.addEventListener("change", function() {
  toggleEducationDropdowns(this.value);
});
</script>

<script>
const profileForm = document.querySelector("#profileForm");
const formAlert = document.querySelector("#formAlert");

// Capture initial form data (excluding files)
const initialData = {};
document.querySelectorAll("#profileForm input, #profileForm select").forEach(input => {
  if (input.type === "file") return;
  initialData[input.name] = input.value;
});

// Compare current form with initial snapshot
function formHasChanges() {
  let changed = false;
  document.querySelectorAll("#profileForm input, #profileForm select").forEach(input => {
    if (input.type === "file") {
      if (input.files.length > 0) changed = true;
    } else {
      if (initialData[input.name] !== input.value) changed = true;
    }
  });
  return changed;
}

// Show inline alert above the button
function showFormMessage(message, color = "text-red-600") {
  formAlert.textContent = message;
  formAlert.classList.remove("hidden", "text-red-600", "text-green-600");
  formAlert.classList.add(color);

  setTimeout(() => {
    formAlert.classList.add("hidden");
  }, 5000);
}

// On Confirm Changes
profileForm.addEventListener("submit", async function (e) {
  e.preventDefault();

  if (!formHasChanges()) {
    showFormMessage("⚠️ No changes detected.");
    return;
  }

  const formData = new FormData(profileForm);
  
  try {
    const response = await fetch("save_changes.php", {
      method: "POST",
      body: formData
    });
    
    const result = await response.text();
    
    if (result.includes("successfully")) {
      showFormMessage("✅ Profile updated successfully!", "text-green-600");
      setTimeout(() => {
        window.location.reload();
      }, 1500);
    } else {
      showFormMessage("❌ " + result);
    }
  } catch (error) {
    showFormMessage("❌ An error occurred: " + error.message);
  }
});
</script>

<script>
document.querySelector('#profile_picture').addEventListener('change', function (e) {
  const file = e.target.files[0];
  const previewImg = document.getElementById('profilePreview');

  if (file) {
    previewImg.src = URL.createObjectURL(file);
  } else {
    previewImg.src = '/library-app/assets/default_pp.png'; // Default image path
  }
});
</script>

<script>
const profilePictureInput = document.getElementById('profile_picture');
const profilePreview = document.getElementById('profilePreview');
const removeBtn = document.getElementById('removeImageBtn');
const removeInput = document.getElementById('remove_picture');

const defaultImage = '/library-app/uploads/default_pp.jpg';

profilePictureInput.addEventListener('change', function (e) {
  const file = e.target.files[0];
  if (file) {
    profilePreview.src = URL.createObjectURL(file);
    removeInput.value = '0'; // Cancel any removal if uploading new image
  } else {
    profilePreview.src = defaultImage;
  }
});

removeBtn.addEventListener('click', function () {
  profilePreview.src = defaultImage;
  profilePictureInput.value = ''; // Clear uploaded file
  removeInput.value = '1'; // Set remove flag
});
</script>

<?php include '../views/footer.php'; ?>
