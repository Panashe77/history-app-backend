<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');
include 'db.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo json_encode([
        'success' => true,
        'user_id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>
