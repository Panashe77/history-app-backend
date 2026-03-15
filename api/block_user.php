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
    $data       = json_decode(file_get_contents("php://input"), true) ?? [];
    $blocker_id = intval($data['blocker_id'] ?? 0);
    $blocked_id = intval($data['blocked_id'] ?? 0);
    $action     = trim($data['action']       ?? 'block'); // 'block' or 'unblock'

    if ($blocker_id === 0 || $blocked_id === 0) {
        ob_clean(); http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing blocker_id or blocked_id']);
        exit;
    }

    if ($blocker_id === $blocked_id) {
        ob_clean(); http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Cannot block yourself']);
        exit;
    }

    if ($action === 'unblock') {
        $pdo->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?")->execute([$blocker_id, $blocked_id]);
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'User unblocked']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?) ON CONFLICT (blocker_id, blocked_id) DO NOTHING");
        $stmt->execute([$blocker_id, $blocked_id]);
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'User blocked']);
    }
} catch (Exception $e) {
    error_log("block_user error: " . $e->getMessage());
    ob_clean(); http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'block_user failed: ' . $e->getMessage()]);
}
?>
