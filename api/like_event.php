<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); ob_clean(); exit; }
include 'db.php';
try {
    $event_id = intval($_POST['event_id'] ?? 0);
    $user_id  = intval($_POST['user_id']  ?? 0);
    if ($event_id === 0 || $user_id === 0) {
        ob_clean(); http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing event_id or user_id']);
        exit;
    }
    // Schema uses 'like_type' column in event_likes
    $check = $pdo->prepare("SELECT like_type FROM event_likes WHERE event_id = ? AND user_id = ?");
    $check->execute([$event_id, $user_id]);
    $existing = $check->fetch();
    if ($existing) {
        if ($existing['like_type'] === 'like') {
            $pdo->prepare("DELETE FROM event_likes WHERE event_id = ? AND user_id = ?")->execute([$event_id, $user_id]);
            $pdo->prepare("UPDATE events SET likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$event_id]);
            ob_clean(); echo json_encode(['success' => true, 'action' => 'removed_like']);
        } else {
            $pdo->prepare("UPDATE event_likes SET like_type = 'like' WHERE event_id = ? AND user_id = ?")->execute([$event_id, $user_id]);
            $pdo->prepare("UPDATE events SET likes = likes + 1, dislikes = GREATEST(0, dislikes - 1) WHERE id = ?")->execute([$event_id]);
            ob_clean(); echo json_encode(['success' => true, 'action' => 'changed_to_like']);
        }
    } else {
        $pdo->prepare("INSERT INTO event_likes (event_id, user_id, like_type) VALUES (?, ?, 'like')")->execute([$event_id, $user_id]);
        $pdo->prepare("UPDATE events SET likes = likes + 1 WHERE id = ?")->execute([$event_id]);
        ob_clean(); echo json_encode(['success' => true, 'action' => 'added_like']);
    }
} catch (Exception $e) {
    error_log("like_event error: " . $e->getMessage());
    ob_clean(); http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'like_event failed: ' . $e->getMessage()]);
}
?>
