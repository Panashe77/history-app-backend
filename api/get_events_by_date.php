<?php
// ✅ CORS headers for Flutter Web
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');

include 'db.php';

$date = $_GET['date']; // Expected format: YYYY-MM-DD

// Extract month and day from the input date
$month = date('n', strtotime($date)); // n = 1-12 without leading zeros
$day = date('j', strtotime($date));   // j = 1-31 without leading zeros

// ✅ Query for historical events that happened on the same month and day (ignoring year)
$sql = "SELECT * FROM events WHERE MONTH(date) = ? AND DAY(date) = ? ORDER BY year ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $month, $day);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    // ✅ Ensure consistent data types for Flutter
    $row['id'] = (int)$row['id'];
    $row['year'] = (int)$row['year'];
    $row['likes'] = (int)$row['likes'];
    $row['dislikes'] = (int)$row['dislikes'];
    $events[] = $row;
}

echo json_encode($events);
?>