<?php
// Test the Flask API directly
$testData = [
    'user_id' => 3,
    'education_level' => 'College',
    'major' => 'BS Dentistry',
    'strand' => ''
];

echo "<h2>Testing Flask API Directly</h2>";
echo "<h3>Sending this data:</h3>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

$flask_api_url = 'http://127.0.0.1:5001/recommend_by_field';
$ch = curl_init($flask_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($testData))
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "<h3>Flask API Response:</h3>";
echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>Raw Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data) {
        echo "<p><strong>Decoded Response:</strong></p>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        
        if (isset($data['recommendations'])) {
            echo "<p><strong>Number of recommendations:</strong> " . count($data['recommendations']) . "</p>";
        }
    }
}

curl_close($ch);

// Also test what categories should be matched
echo "<h3>Expected Behavior:</h3>";
echo "<p>For BS Dentistry (College level), Flask should look for books in categories: <strong>['Health']</strong></p>";
echo "<p>Your database has <strong>10 books</strong> in the 'Health' category.</p>";
echo "<p>Flask should return up to 5 of these books, sorted by 'Like' count.</p>";

// Let's also test a direct database query to see what Flask should return
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h3>What Flask Should Return (Top 5 Health books by Likes):</h3>";
$stmt = $pdo->prepare("SELECT TITLE, AUTHOR, General_Category, `Like` FROM books WHERE General_Category = 'Health' ORDER BY `Like` DESC LIMIT 5");
$stmt->execute();
$expectedBooks = $stmt->fetchAll();

if (empty($expectedBooks)) {
    echo "<p style='color: red;'>❌ No books found in Health category</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Title</th><th>Author</th><th>Category</th><th>Likes</th></tr>";
    foreach ($expectedBooks as $book) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($book['TITLE']) . "</td>";
        echo "<td>" . htmlspecialchars($book['AUTHOR']) . "</td>";
        echo "<td>" . htmlspecialchars($book['General_Category']) . "</td>";
        echo "<td>" . ($book['Like'] ?? 0) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test if Flask server is even running
echo "<h3>Flask Server Status:</h3>";
$ch = curl_init('http://127.0.0.1:5001/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 || $httpCode == 404) {
    echo "<p style='color: green;'>✅ Flask server is running on port 5001</p>";
} else {
    echo "<p style='color: red;'>❌ Flask server is not responding on port 5001</p>";
    echo "<p>Make sure your Flask app is running with: <code>python app.py</code></p>";
}
?>

<style>
table {
    margin: 10px 0;
    border-collapse: collapse;
}
th, td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}
th {
    background-color: #f2f2f2;
}
pre {
    background: #f5f5f5;
    padding: 10px;
    border: 1px solid #ddd;
    overflow-x: auto;
}
</style>
