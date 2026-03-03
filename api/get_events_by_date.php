<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');
include 'db.php';

$date = $_GET['date'] ?? '';
if (empty($date)) {
    echo json_encode([]);
    exit;
}

$month = date('n', strtotime($date));
$day = date('j', strtotime($date));

// PostgreSQL uses EXTRACT instead of MONTH()/DAY()
$stmt = $pdo->prepare("SELECT * FROM events WHERE EXTRACT(MONTH FROM date) = ? AND EXTRACT(DAY FROM date) = ? ORDER BY year ASC");
$stmt->execute([$month, $day]);

$events = [];
while ($row = $stmt->fetch()) {
    $row['id'] = (int)$row['id'];
    $row['year'] = (int)$row['year'];
    $row['likes'] = (int)$row['likes'];
    $row['dislikes'] = (int)$row['dislikes'];
    $events[] = $row;
}

echo json_encode($events);
?>
