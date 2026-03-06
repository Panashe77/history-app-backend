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

    $username = trim($data['username'] ?? '');
    $email    = trim($data['email']    ?? '');
    $password = $data['password']      ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "All fields are required"]);
        exit;
    }

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        ob_clean();
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Username or email already exists"]);
        exit;
    }

    // Hash password and insert — column is 'password' not 'password_hash'
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashed]);

    // Fetch the new user's id (lastInsertId unreliable with pgsql driver)
    $stmt2 = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt2->execute([$email]);
    $row = $stmt2->fetch();
    $user_id = $row['id'] ?? 0;

    ob_clean();
    echo json_encode([
        "status"   => "success",
        "user_id"  => (int)$user_id,
        "username" => $username,
        "email"    => $email
    ]);

} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Registration failed: " . $e->getMessage()
    ]);
}
?>
