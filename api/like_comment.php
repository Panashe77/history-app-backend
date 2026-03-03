<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if ($comment_id > 0 && $user_id > 0) {
        $check = $pdo->prepare("SELECT like_type FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $check->execute([$comment_id, $user_id]);
        $existing = $check->fetch();

        if ($existing) {
            if ($existing['like_type'] == 'like') {
                $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?")->execute([$comment_id, $user_id]);
                $pdo->prepare("UPDATE comments SET likes = likes - 1 WHERE id = ?")->execute([$comment_id]);
                echo json_encode(['success' => true, 'action' => 'removed_like']);
            } else {
                $pdo->prepare("UPDATE comment_likes SET like_type = 'like' WHERE comment_id = ? AND user_id = ?")->execute([$comment_id, $user_id]);
                $pdo->prepare("UPDATE comments SET likes = likes + 1, dislikes = dislikes - 1 WHERE id = ?")->execute([$comment_id]);
                echo json_encode(['success' => true, 'action' => 'changed_to_like']);
            }
        } else {
            $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id, like_type) VALUES (?, ?, 'like')")->execute([$comment_id, $user_id]);
            $pdo->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?")->execute([$comment_id]);
            echo json_encode(['success' => true, 'action' => 'added_like']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}
?>
