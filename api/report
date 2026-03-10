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
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $reporter_id      = intval($data['reporter_id']      ?? 0);
    $reported_user_id = intval($data['reported_user_id'] ?? 0);
    $content_type     = trim($data['content_type']       ?? ''); // 'comment' or 'event'
    $content_id       = intval($data['content_id']       ?? 0);
    $reason           = trim($data['reason']             ?? '');
    $details          = trim($data['details']            ?? '');

    if ($reporter_id === 0 || empty($content_type) || $content_id === 0 || empty($reason)) {
        ob_clean(); http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }

    // Check if already reported
    $check = $pdo->prepare("SELECT id FROM reports WHERE reporter_id = ? AND content_type = ? AND content_id = ?");
    $check->execute([$reporter_id, $content_type, $content_id]);
    if ($check->fetch()) {
        ob_clean();
        echo json_encode(['status' => 'already_reported', 'message' => 'You have already reported this content']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO reports (reporter_id, reported_user_id, content_type, content_id, reason, details, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$reporter_id, $reported_user_id ?: null, $content_type, $content_id, $reason, $details]);

    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully']);
} catch (Exception $e) {
    error_log("report error: " . $e->getMessage());
    ob_clean(); http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'report failed: ' . $e->getMessage()]);
}
?>
