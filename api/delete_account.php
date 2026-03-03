<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $email = isset($data['email']) ? $data['email'] : '';
    $password = isset($data['password']) ? $data['password'] : '';

    if ($user_id <= 0 && empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID or email required']);
        exit;
    }

    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $user_id = $user['id'];

        if (!password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit;
        }
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM event_likes WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("UPDATE events SET user_id = NULL WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
