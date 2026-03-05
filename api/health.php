<?php
ob_start();
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
ob_clean();

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
}

echo json_encode($response);
?>
