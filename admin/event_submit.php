<!-- event_submit.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Submit Event Proposal</title>
</head>
<body>
    <h2>Event Proposal Submission</h2>
    <form action="events_tools/process_event_submission.php" method="POST" enctype="multipart/form-data">
        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Event Title:</label><br>
        <input type="text" name="event_title" required><br><br>

        <label>Short Description:</label><br>
        <textarea name="description" required></textarea><br><br>

        <label>Contact (Email or Phone):</label><br>
        <input type="text" name="contact" required><br><br>

        <label>Upload File (PDF/PNG only):</label><br>
        <input type="file" name="event_file" accept=".pdf,.png" required><br><br>

        <button type="submit">Submit Proposal</button>
    </form>
</body>
</html>
