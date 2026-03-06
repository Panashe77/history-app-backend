<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_clean();
    exit;
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid JSON body"]);
        exit;
    }

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email and password required"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password_hash'])) {
            ob_clean();
            echo json_encode([
                "status" => "success",
                "user_id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email']
            ]);
        } else {
            ob_clean();
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Invalid password"]);
        }
    } else {
        ob_clean();
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
} else {
    ob_clean();
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
