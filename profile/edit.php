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

if (!$user) die("User not found.");
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

      <div class="space-y-4 pt-4 border-t border-gray-300">
        <h3 class="text-lg font-semibold mb-4">🎓 Education Information</h3>

        <div>
          <label class="block font-medium mb-1">Education Level</label>
          <input type="text" name="education_level" value="<?= htmlspecialchars($user['education_level']) ?>" class="w-full border px-3 py-2 rounded">
        </div>

        <div>
          <label class="block font-medium mb-1">Course</label>
          <input type="text" name="course" value="<?= htmlspecialchars($user['course']) ?>" class="w-full border px-3 py-2 rounded">
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

<!-- OTP Modal -->
<div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white p-6 rounded-lg shadow-md w-80">
    <h2 class="text-lg font-semibold mb-2">Enter OTP</h2>
    <input type="text" id="otpInput" class="w-full border px-3 py-2 rounded mb-3" placeholder="6-digit OTP">
    <div id="otpError" class="text-red-500 text-sm mb-2 hidden"></div>
    <div class="flex justify-end gap-2">
      <button id="cancelBtn" class="text-sm px-4 py-1 border border-gray-300 rounded">Cancel</button>
      <button id="confirmOtpBtn" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">Confirm</button>
    </div>
  </div>
</div>

</div>

<script>
const profileForm = document.querySelector("#profileForm");
const otpModal = document.querySelector("#otpModal");
const otpInput = document.querySelector("#otpInput");
const otpError = document.querySelector("#otpError");
const formAlert = document.querySelector("#formAlert");

const mandaluyongSelect = document.querySelector('select[name="is_mandaluyong_resident"]');
const barangaySelect = document.querySelector('select[name="barangay"]');

// Toggle Barangay enable/disable
function toggleBarangayDropdown() {
  if (mandaluyongSelect.value === 'yes') {
    barangaySelect.disabled = false;
    barangaySelect.classList.remove("bg-gray-100");
  } else {
    barangaySelect.disabled = true;
    barangaySelect.value = "";
    barangaySelect.classList.add("bg-gray-100");
  }
}
toggleBarangayDropdown();
mandaluyongSelect.addEventListener("change", toggleBarangayDropdown);

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

  const sendOtp = await fetch("send_otp.php");
  const otpResponse = await sendOtp.text();

  if (!otpResponse.includes("OTP sent")) {
    showFormMessage("❌ Failed to send OTP.");
    return;
  }

  formAlert.classList.add("hidden");
  otpModal.classList.remove("hidden");
});

// Cancel OTP modal
document.querySelector("#cancelBtn").onclick = () => {
  otpModal.classList.add("hidden");
  otpInput.value = '';
  otpError.classList.add("hidden");
};

// Confirm OTP
document.querySelector("#confirmOtpBtn").onclick = async () => {
  const otp = otpInput.value.trim();
  if (!otp) {
    otpError.textContent = "Please enter the OTP.";
    otpError.classList.remove("hidden");
    return;
  }

  const formData = new FormData(profileForm);
  formData.append("otp", otp);

  const res = await fetch("save_changes.php", {
    method: "POST",
    body: formData
  });

  const text = await res.text();
  if (text.includes("successfully")) {
    alert("✅ " + text);
    window.location.reload();
  } else {
    otpError.textContent = text;
    otpError.classList.remove("hidden");
  }
};
</script>

<script>
document.querySelector('#profile_picture').addEventListener('change', function (e) {
  const file = e.target.files[0];
  const previewImg = document.getElementById('profilePreview');
  const noImageText = document.getElementById('noImageText');

  if (file) {
    previewImg.src = URL.createObjectURL(file);
    previewImg.classList.remove("hidden");
    noImageText.classList.add("hidden");
  } else {
    previewImg.src = "";
    previewImg.classList.add("hidden");
    noImageText.classList.remove("hidden");
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
