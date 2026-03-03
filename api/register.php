<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// Check if user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->rowCount() > 0) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "Username or email already exists"]);
    exit;
}

// Hash password and create user
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?) RETURNING id");
$stmt->execute([$username, $email, $password_hash]);
$row = $stmt->fetch();
$user_id = $row['id'];

echo json_encode([
    "status" => "success",
    "user_id" => $user_id,
    "username" => $username,
    "email" => $email
]);
?>
