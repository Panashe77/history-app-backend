<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if ($event_id > 0 && $user_id > 0) {
        $check = $pdo->prepare("SELECT like_type FROM event_likes WHERE event_id = ? AND user_id = ?");
        $check->execute([$event_id, $user_id]);
        $existing = $check->fetch();

        if ($existing) {
            if ($existing['like_type'] == 'like') {
                // Remove like
                $pdo->prepare("DELETE FROM event_likes WHERE event_id = ? AND user_id = ?")->execute([$event_id, $user_id]);
                $pdo->prepare("UPDATE events SET likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$event_id]);
                echo json_encode(['success' => true, 'action' => 'removed_like']);
            } else {
                // Change dislike to like
                $pdo->prepare("UPDATE event_likes SET like_type = 'like' WHERE event_id = ? AND user_id = ?")->execute([$event_id, $user_id]);
                $pdo->prepare("UPDATE events SET likes = likes + 1, dislikes = GREATEST(0, dislikes - 1) WHERE id = ?")->execute([$event_id]);
                echo json_encode(['success' => true, 'action' => 'changed_to_like']);
            }
        } else {
            // New like
            $pdo->prepare("INSERT INTO event_likes (event_id, user_id, like_type) VALUES (?, ?, 'like')")->execute([$event_id, $user_id]);
            $pdo->prepare("UPDATE events SET likes = likes + 1 WHERE id = ?")->execute([$event_id]);
            echo json_encode(['success' => true, 'action' => 'added_like']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
