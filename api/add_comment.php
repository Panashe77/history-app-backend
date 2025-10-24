<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $author = isset($_POST['author']) ? $_POST['author'] : 'Anonymous';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    
    if ($event_id > 0 && !empty($content)) {
        $sql = "INSERT INTO comments (event_id, author, content, user_id, parent_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issii", $event_id, $author, $content, $user_id, $parent_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
        
        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>