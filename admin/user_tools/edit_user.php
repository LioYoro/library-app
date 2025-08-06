<?php
include('../includes/header.php');
include('../includes/sidebar.php');
include('../db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users_report.php');
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: users_report.php');
    exit;
}
?>

<div class="flex h-screen overflow-hidden">
  <div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
    <header class="h-16 w-full bg-blue-500 text-white flex items-center justify-between px-6 shadow">
      <h1 class="text-xl font-bold">✏️ Edit User: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
      <div class="flex items-center space-x-3">
        <a href="/users.php" class="bg-gray-200 text-gray-700 px-3 py-1 rounded hover:bg-gray-300">🏠 Back to Users</a>
        <a href="users_report.php" class="bg-gray-200 text-gray-700 px-3 py-1 rounded hover:bg-gray-300">← Back to Report</a>
      </div>

    </header>

    <main class="p-6 overflow-auto max-w-3xl">
      <form action="process_update_user.php" method="POST" class="space-y-4">
        <input type="hidden" name="id" value="<?= $user['id'] ?>" />
        
        <div>
          <label class="block font-semibold mb-1">First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="border rounded w-full px-3 py-2" required />
        </div>

        <div>
          <label class="block font-semibold mb-1">Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="border rounded w-full px-3 py-2" required />
        </div>

        <div>
          <label class="block font-semibold mb-1">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="border rounded w-full px-3 py-2" required />
        </div>

        <div>
          <label class="block font-semibold mb-1">Gender</label>
          <select name="gender" class="border rounded w-full px-3 py-2" required>
            <?php
            $genders = ['Male', 'Female', 'Other'];
            foreach ($genders as $g) {
                $selected = ($user['gender'] === $g) ? 'selected' : '';
                echo "<option value=\"$g\" $selected>$g</option>";
            }
            ?>
          </select>
        </div>

        <div>
          <label class="block font-semibold mb-1">Age</label>
          <input type="number" name="age" value="<?= intval($user['age']) ?>" class="border rounded w-full px-3 py-2" min="1" max="150" />
        </div>

        <div>
          <label class="block font-semibold mb-1">Religion</label>
          <input type="text" name="religion" value="<?= htmlspecialchars($user['religion']) ?>" class="border rounded w-full px-3 py-2" />
        </div>

        <div>
          <label class="block font-semibold mb-1">Education Level</label>
          <input type="text" name="education_level" value="<?= htmlspecialchars($user['education_level']) ?>" class="border rounded w-full px-3 py-2" />
        </div>

        <div>
          <label class="block font-semibold mb-1">School Name</label>
          <input type="text" name="school_name" value="<?= htmlspecialchars($user['school_name']) ?>" class="border rounded w-full px-3 py-2" />
        </div>

        <div>
          <label class="block font-semibold mb-1">Is Mandaluyong Resident?</label>
          <select name="is_mandaluyong_resident" class="border rounded w-full px-3 py-2">
            <?php
              $options = ['Yes', 'No'];
              foreach ($options as $opt) {
                $selected = ($user['is_mandaluyong_resident'] === $opt) ? 'selected' : '';
                echo "<option value=\"$opt\" $selected>$opt</option>";
              }
            ?>
          </select>
        </div>

        <div>
          <label class="block font-semibold mb-1">Barangay</label>
          <input type="text" name="barangay" value="<?= htmlspecialchars($user['barangay']) ?>" class="border rounded w-full px-3 py-2" />
        </div>

        <div>
          <label class="block font-semibold mb-1">City Outside Mandaluyong</label>
          <input type="text" name="city_outside_mandaluyong" value="<?= htmlspecialchars($user['city_outside_mandaluyong']) ?>" class="border rounded w-full px-3 py-2" />
        </div>

        <div>
          <label class="block font-semibold mb-1">Major</label>
          <input type="text" name="major" value="<?= htmlspecialchars($user['major']) ?>" class="border rounded w-full px-3 py-2" />
        </div>

        <div>
          <label class="block font-semibold mb-1">Strand</label>
          <input type="text" name="strand" value="<?= htmlspecialchars($user['strand']) ?>" class="border rounded w-full px-3 py-2" />
        </div>

        <div>
          <label class="block font-semibold mb-1">Contact Number</label>
          <input type="text" name="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" class="border rounded w-full px-3 py-2" />
        </div>

        <div>
          <label class="block font-semibold mb-1">Profile Verified</label>
          <select name="profile_verified" class="border rounded w-full px-3 py-2">
            <option value="0" <?= $user['profile_verified'] == 0 ? 'selected' : '' ?>>No</option>
            <option value="1" <?= $user['profile_verified'] == 1 ? 'selected' : '' ?>>Yes</option>
          </select>
        </div>

        <div class="flex space-x-3 mt-6">
          <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700">Save Changes</button>
          <a href="users_report.php" class="bg-gray-300 px-5 py-2 rounded hover:bg-gray-400">Cancel</a>
        </div>
      </form>
    </main>
  </div>
</div>

<?php include('../includes/footer.php'); ?>
