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
    $response['database'] = 'connected';
    $conn->close();
} catch (Exception $e) {
    $response['database'] = 'disconnected';
}

echo json_encode($response);
?>