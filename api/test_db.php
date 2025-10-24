<?php
include 'db.php';

echo "<h2>Database Connection Test</h2>";

// Test connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
    exit;
}
echo "<p style='color: green;'>✅ Database connected successfully</p>";

// Check if events table exists
$table_check = $conn->query("SHOW TABLES LIKE 'events'");
if ($table_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ Events table exists</p>";
} else {
    echo "<p style='color: red;'>❌ Events table does not exist</p>";
    echo "<p>Create table with this SQL:</p>";
    echo "<pre>
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    year INT NOT NULL,
    date DATE NOT NULL,
    imageUrl VARCHAR(255),
    tags TEXT,
    likes INT DEFAULT 0,
    dislikes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
    </pre>";
    exit;
}

// Check table structure
echo "<h3>Table Structure:</h3>";
$structure = $conn->query("DESCRIBE events");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $structure->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Show all events
echo "<h3>All Events in Database:</h3>";
$all_events = $conn->query("SELECT * FROM events ORDER BY date DESC");
if ($all_events->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Title</th><th>Description</th><th>Year</th><th>Date</th><th>Tags</th><th>Created</th></tr>";
    while ($row = $all_events->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>" . substr($row['description'], 0, 50) . "...</td>";
        echo "<td>{$row['year']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['tags']}</td>";
        echo "<td>" . ($row['created_at'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No events found in database.</p>";
}

// Test date query
echo "<h3>Test Date Query (Today's Date):</h3>";
$today = date('Y-m-d');
$month = date('m');
$day = date('d');

echo "<p>Testing query for today ($today) - Month: $month, Day: $day</p>";

$test_query = $conn->prepare("SELECT * FROM events WHERE MONTH(date) = ? AND DAY(date) = ?");
$test_query->bind_param("ii", $month, $day);
$test_query->execute();
$result = $test_query->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found {$result->num_rows} events for today's date</p>";
    while ($row = $result->fetch_assoc()) {
        echo "<p>- {$row['title']} ({$row['year']}) on {$row['date']}</p>";
    }
} else {
    echo "<p style='color: orange;'>No events found for today's month/day combination</p>";
}
?>