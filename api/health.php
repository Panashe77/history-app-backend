<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$database = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 5432;

$response = [
    'status' => 'checking',
    'host' => $host,
    'user' => $user,
    'database' => $database,
    'port' => $port,
];

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$database;sslmode=require",
        $user,
        $password
    );
    $pdo->query("SELECT 1");
    $response['status'] = 'success';
    $response['database'] = 'connected';
} catch (PDOException $e) {
    $response['status'] = 'error';
    $response['database'] = 'disconnected';
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
