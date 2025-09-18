<?php require __DIR__ . '/../views/header.php'; ?>

<link rel="stylesheet" href="events_user.css">

<div class="event-submit-container">
    <h2>📩 Submit Event Proposal</h2>
    <p class="note">*Please upload a PDF or PNG file containing detailed information about your proposed event.</p>

    <form action="process_event_submission.php" method="POST" enctype="multipart/form-data" class="event-submit-form">
        <label for="name">Your Name:</label>
        <input type="text" id="name" name="name" required placeholder="Enter your full name">

        <label for="event_title">Event Title (max 60 chars):</label>
        <input type="text" id="event_title" name="event_title" maxlength="60"
        pattern="[A-Za-z0-9\s.,:&'!\@#\-]+"
        title="Use letters, numbers, spaces, and these symbols: . , - & ' : ! @ #"
        required>
        <p style="font-size:12px;color:#555;">Please keep the title concise and clear.</p>

        <label for="description">Short Description (max 200 chars):</label>
        <textarea id="description" name="description" rows="4" maxlength="200" placeholder="Provide a brief description of the event"></textarea>
        <p id="charCount" style="font-size:12px;color:#555;">0/200</p>

        <label for="contact">Contact Info (Email or Phone):</label>
        <input type="text" id="contact" name="contact" required placeholder="Enter your contact details">

        <label for="event_date">Event Date:</label>
        <input type="date" 
            name="event_date" 
            id="event_date" 
            required
            min="<?= date('Y-m-d', strtotime('+5 days')) ?>" 
            max="<?= date('Y-m-d', strtotime('+1 month +5 days')) ?>">

        <label for="event_time">Event Time:</label>
        <select name="event_time" id="event_time" required>
            <?php
            $start = strtotime("09:00");
            $end = strtotime("19:30");
            for ($time = $start; $time <= $end; $time += 30 * 60) {
                $formatted = date("H:i", $time);   // stored value (24h)
                $label = date("g:i A", $time);     // display label (12h AM/PM)
                echo "<option value='$formatted'>$label</option>";
            }
            ?>
        </select>


        <label for="event_file">Upload File <span class="file-note">(PDF/PNG only)</span>:</label>
        <input type="file" id="event_file" name="event_file" accept=".pdf,.png" required>

        <button type="submit">Submit Proposal</button>
    </form>
</div>

<!-- Fixed popup modal structure and removed conflicting inline styles -->
<div id="popupMessage"></div>

<!-- Added overlay for better modal visibility -->
<div id="popupOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1999;"></div>

<script>
const desc = document.getElementById('description');
const charCount = document.getElementById('charCount');

desc.addEventListener('input', () => {
    charCount.textContent = `${desc.value.length}/200`;
});

const form = document.querySelector('.event-submit-form');
const popup = document.getElementById('popupMessage');
const overlay = document.getElementById('popupOverlay');

document.addEventListener("DOMContentLoaded", function() {
    const eventDateInput = document.getElementById("event_date");

    eventDateInput.addEventListener("input", function() {
        const selectedDate = new Date(this.value);
        // Sunday = 0
        if (selectedDate.getDay() === 0) {
            alert("Sundays are not available for booking. Please choose another date.");
            this.value = ""; // Clear invalid date
        }
    });
});

form.addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(form);

    popup.innerHTML = `<div style="text-align:center; padding:20px;">
                           <p>⏳ Submitting your proposal...</p>
                       </div>`;
    popup.style.display = 'block';
    popup.classList.add('show');
    overlay.style.display = 'block';

    fetch('process_event_submission.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(html => {
        console.log("DEBUG Response:", html);
        
        popup.innerHTML = html;
        popup.style.display = 'block';
        popup.classList.add('show');
        overlay.style.display = 'block';

        // Add listener to close button inside popup
        const closeBtn = document.getElementById('closeModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                popup.style.display = 'none';
                popup.classList.remove('show');
                overlay.style.display = 'none';
            });
        }

        overlay.addEventListener('click', () => {
            popup.style.display = 'none';
            popup.classList.remove('show');
            overlay.style.display = 'none';
        });

        // Reset the form
        form.reset();
        document.getElementById('charCount').textContent = "0/200";
    })
    .catch(err => {
        console.error("Fetch error:", err);
        popup.innerHTML = `<div style="color:red; text-align:center; padding:20px;">
                               <p>⚠️ An error occurred. Please try again.</p>
                               <button id="closeModal" style="padding:8px 20px; border:none; border-radius:6px; cursor:pointer; background:#dc2626; color:white; margin-top:10px;">Close</button>
                           </div>`;
        popup.style.display = 'block';
        popup.classList.add('show');
        overlay.style.display = 'block';
        
        const closeBtn = document.getElementById('closeModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                popup.style.display = 'none';
                popup.classList.remove('show');
                overlay.style.display = 'none';
            });
        }
    });
});
</script>

<?php require __DIR__ . '/../views/footer.php'; ?>
