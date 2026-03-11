<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); ob_clean(); exit; }
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id  = intval($data['user_id']  ?? 0);
    $email    = trim($data['email']      ?? '');
    $password = trim($data['password']   ?? '');

    if ($user_id <= 0 && empty($email)) {
        ob_clean(); http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID or email required']);
        exit;
    }

    if (!empty($email)) {
        // FIX 1: use 'password' column not 'password_hash'
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            ob_clean(); http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        $user_id = $user['id'];
        if (!password_verify($password, $user['password'])) {
            ob_clean(); http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit;
        }
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM event_likes WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ?")->execute([$user_id]);
        // FIX 2: also delete from reports and blocked_users
        $pdo->prepare("DELETE FROM reports WHERE reporter_id = ? OR reported_user_id = ?")->execute([$user_id, $user_id]);
        $pdo->prepare("DELETE FROM blocked_users WHERE blocker_id = ? OR blocked_id = ?")->execute([$user_id, $user_id]);
        $pdo->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("UPDATE events SET user_id = NULL WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        $pdo->commit();
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("delete_account error: " . $e->getMessage());
        ob_clean(); http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
    }
} else {
    ob_clean(); http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
