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

    $conn->begin_transaction();

    try {
        $deleted = [];

        switch ($data_type) {
            case 'events':
                $conn->query("DELETE FROM events WHERE user_id = $user_id");
                $deleted[] = 'events';
                break;

            case 'comments':
                $conn->query("DELETE FROM comments WHERE user_id = $user_id");
                $deleted[] = 'comments';
                break;

            case 'likes':
                $conn->query("DELETE FROM event_likes WHERE user_id = $user_id");
                $conn->query("DELETE FROM comment_likes WHERE user_id = $user_id");
                $deleted[] = 'likes and reactions';
                break;

            case 'bookmarks':
                $conn->query("DELETE FROM bookmarks WHERE user_id = $user_id");
                $deleted[] = 'bookmarks';
                break;

            case 'all':
            default:
                $conn->query("DELETE FROM comment_likes WHERE user_id = $user_id");
                $conn->query("DELETE FROM event_likes WHERE user_id = $user_id");
                $conn->query("DELETE FROM bookmarks WHERE user_id = $user_id");
                $conn->query("DELETE FROM comments WHERE user_id = $user_id");
                $conn->query("UPDATE events SET user_id = NULL WHERE user_id = $user_id");
                $deleted[] = 'all user data';
                break;
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Data deleted successfully',
            'deleted' => $deleted
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>