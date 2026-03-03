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
            if ($existing['like_type'] == 'dislike') {
                // Remove dislike
                $pdo->prepare("DELETE FROM event_likes WHERE event_id = ? AND user_id = ?")->execute([$event_id, $user_id]);
                $pdo->prepare("UPDATE events SET dislikes = GREATEST(0, dislikes - 1) WHERE id = ?")->execute([$event_id]);
                echo json_encode(['success' => true, 'action' => 'removed_dislike']);
            } else {
                // Change like to dislike
                $pdo->prepare("UPDATE event_likes SET like_type = 'dislike' WHERE event_id = ? AND user_id = ?")->execute([$event_id, $user_id]);
                $pdo->prepare("UPDATE events SET dislikes = dislikes + 1, likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$event_id]);
                echo json_encode(['success' => true, 'action' => 'changed_to_dislike']);
            }
        } else {
            // New dislike
            $pdo->prepare("INSERT INTO event_likes (event_id, user_id, like_type) VALUES (?, ?, 'dislike')")->execute([$event_id, $user_id]);
            $pdo->prepare("UPDATE events SET dislikes = dislikes + 1 WHERE id = ?")->execute([$event_id]);
            echo json_encode(['success' => true, 'action' => 'added_dislike']);
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
