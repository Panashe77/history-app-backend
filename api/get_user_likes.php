<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');
include 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    $stmt = $pdo->prepare("SELECT event_id, like_type FROM event_likes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $likes = [];
    while ($row = $stmt->fetch()) {
        $likes[$row['event_id']] = $row['like_type'];
    }
    echo json_encode($likes);
} else {
    echo json_encode([]);
}
?>
