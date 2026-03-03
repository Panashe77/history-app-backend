<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $data_type = isset($data['data_type']) ? $data['data_type'] : 'all';

    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $deleted = [];

        switch ($data_type) {
            case 'events':
                $pdo->prepare("DELETE FROM events WHERE user_id = ?")->execute([$user_id]);
                $deleted[] = 'events';
                break;
            case 'comments':
                $pdo->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
                $deleted[] = 'comments';
                break;
            case 'likes':
                $pdo->prepare("DELETE FROM event_likes WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ?")->execute([$user_id]);
                $deleted[] = 'likes and reactions';
                break;
            case 'bookmarks':
                $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ?")->execute([$user_id]);
                $deleted[] = 'bookmarks';
                break;
            case 'all':
            default:
                $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM event_likes WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("UPDATE events SET user_id = NULL WHERE user_id = ?")->execute([$user_id]);
                $deleted[] = 'all user data';
                break;
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Data deleted successfully', 'deleted' => $deleted]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
