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

$data = json_decode(file_get_contents("php://input"), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_clean();
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON body"]);
    exit;
}

$username = $data['username'] ?? '';
$email    = $data['email']    ?? '';
$password = $data['password'] ?? '';

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

// Hash password and create user
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?) RETURNING id");
$stmt->execute([$username, $email, $password_hash]);
$row = $stmt->fetch();
$user_id = $row['id'];

ob_clean();
echo json_encode([
    "status"   => "success",
    "user_id"  => $user_id,
    "username" => $username,
    "email"    => $email
]);
?>
