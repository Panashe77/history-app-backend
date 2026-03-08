<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); ob_clean(); exit; }
include 'db.php';
try {
    $user_id = intval($_GET['user_id'] ?? 0);
    if ($user_id === 0) {
        ob_clean(); echo json_encode([]); exit;
    }
    // event_likes uses 'like_type', comment_likes uses 'type'
    $stmt = $pdo->prepare("SELECT event_id, like_type FROM event_likes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $likes = [];
    while ($row = $stmt->fetch()) {
        $likes[$row['event_id']] = $row['like_type'];
    }
    ob_clean(); echo json_encode($likes);
} catch (Exception $e) {
    error_log("get_user_likes error: " . $e->getMessage());
    ob_clean(); http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'get_user_likes failed: ' . $e->getMessage()]);
}
?>
