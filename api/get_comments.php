<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');
include 'db.php';

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE event_id = ? ORDER BY created_at ASC");
    $stmt->execute([$event_id]);
    $comments = $stmt->fetchAll();
    echo json_encode($comments);
} else {
    echo json_encode([]);
}
?>
