<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Get user's bookmarks
if ($method == 'GET') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($user_id > 0) {
        $sql = "SELECT e.* FROM events e 
                INNER JOIN bookmarks b ON e.id = b.event_id 
                WHERE b.user_id = ? 
                ORDER BY b.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $row['year'] = (int)$row['year'];
            $row['likes'] = (int)$row['likes'];
            $row['dislikes'] = (int)$row['dislikes'];
            $events[] = $row;
        }
        
        echo json_encode($events);
        $stmt->close();
    } else {
        echo json_encode([]);
    }
}

// POST - Add bookmark
elseif ($method == 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if ($event_id > 0 && $user_id > 0) {
        // Check if already bookmarked
        $check_sql = "SELECT id FROM bookmarks WHERE event_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $event_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Already bookmarked']);
        } else {
            $sql = "INSERT INTO bookmarks (event_id, user_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $event_id, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Bookmark added']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add bookmark']);
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}

// DELETE - Remove bookmark
elseif ($method == 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $event_id = isset($_DELETE['event_id']) ? intval($_DELETE['event_id']) : 0;
    $user_id = isset($_DELETE['user_id']) ? intval($_DELETE['user_id']) : 0;
    
    if ($event_id > 0 && $user_id > 0) {
        $sql = "DELETE FROM bookmarks WHERE event_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $event_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Bookmark removed']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to remove bookmark']);
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}

$conn->close();
?>