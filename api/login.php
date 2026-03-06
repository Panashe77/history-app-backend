<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_clean();
    exit;
}

include 'db.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid JSON body"]);
        exit;
    }

    $email    = trim($data['email']    ?? '');
    $password = $data['password']      ?? '';

    if (empty($email) || empty($password)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email and password required"]);
        exit;
    }

    // Column is 'password' not 'password_hash'
    $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        ob_clean();
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        ob_clean();
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid password"]);
        exit;
    }

    ob_clean();
    echo json_encode([
        "status"   => "success",
        "user_id"  => (int)$user['id'],
        "username" => $user['username'],
        "email"    => $user['email']
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Login failed: " . $e->getMessage()
    ]);
}
?>
