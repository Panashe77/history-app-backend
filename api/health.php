<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'message' => 'History App API is running',
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    include 'db.php';
    $pdo->query("SELECT 1");
    $response['database'] = 'connected';
} catch (Exception $e) {
    $response['database'] = 'disconnected';
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
