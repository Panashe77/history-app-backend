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
    $with_details = $_GET['details'] ?? 'false';

    if ($user_id === 0) { ob_clean(); echo json_encode([]); exit; }

    if ($with_details === 'true') {
        // Return full user details for profile blocked list
        $stmt = $pdo->prepare("
            SELECT bu.blocked_id as id, u.username, bu.created_at
            FROM blocked_users bu
            JOIN users u ON u.id = bu.blocked_id
            WHERE bu.blocker_id = ?
            ORDER BY bu.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $blocked = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Return just IDs (for filtering comments)
        $stmt = $pdo->prepare("SELECT blocked_id FROM blocked_users WHERE blocker_id = ?");
        $stmt->execute([$user_id]);
        $blocked = array_column($stmt->fetchAll(), 'blocked_id');
    }

    ob_clean();
    echo json_encode($blocked);
} catch (Exception $e) {
    error_log("get_blocked_users error: " . $e->getMessage());
    ob_clean(); http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
