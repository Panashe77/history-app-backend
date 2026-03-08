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
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $user_id    = intval($_POST['user_id']    ?? 0);
    if ($comment_id === 0 || $user_id === 0) {
        ob_clean(); http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing comment_id or user_id']);
        exit;
    }
    // Schema uses 'type' column in comment_likes (not 'like_type')
    $check = $pdo->prepare("SELECT type FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $check->execute([$comment_id, $user_id]);
    $existing = $check->fetch();
    if ($existing) {
        if ($existing['type'] === 'like') {
            $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?")->execute([$comment_id, $user_id]);
            $pdo->prepare("UPDATE comments SET likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$comment_id]);
            ob_clean(); echo json_encode(['success' => true, 'action' => 'removed_like']);
        } else {
            $pdo->prepare("UPDATE comment_likes SET type = 'like' WHERE comment_id = ? AND user_id = ?")->execute([$comment_id, $user_id]);
            $pdo->prepare("UPDATE comments SET likes = likes + 1, dislikes = GREATEST(0, dislikes - 1) WHERE id = ?")->execute([$comment_id]);
            ob_clean(); echo json_encode(['success' => true, 'action' => 'changed_to_like']);
        }
    } else {
        $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id, type) VALUES (?, ?, 'like')")->execute([$comment_id, $user_id]);
        $pdo->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?")->execute([$comment_id]);
        ob_clean(); echo json_encode(['success' => true, 'action' => 'added_like']);
    }
} catch (Exception $e) {
    error_log("like_comment error: " . $e->getMessage());
    ob_clean(); http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'like_comment failed: ' . $e->getMessage()]);
}
?>
