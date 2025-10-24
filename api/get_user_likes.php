<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');

include 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    $sql = "SELECT event_id, like_type FROM event_likes WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $likes = [];
    
    while ($row = $result->fetch_assoc()) {
        $likes[$row['event_id']] = $row['like_type'];
    }
    
    echo json_encode($likes);
    $stmt->close();
} else {
    echo json_encode([]);
}

$conn->close();
?>