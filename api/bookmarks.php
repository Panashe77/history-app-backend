<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if ($user_id > 0) {
        $stmt = $pdo->prepare("SELECT e.* FROM events e INNER JOIN bookmarks b ON e.id = b.event_id WHERE b.user_id = ? ORDER BY b.created_at DESC");
        $stmt->execute([$user_id]);
        $events = [];
        while ($row = $stmt->fetch()) {
            $row['id'] = (int)$row['id'];
            $row['year'] = (int)$row['year'];
            $row['likes'] = (int)$row['likes'];
            $row['dislikes'] = (int)$row['dislikes'];
            $events[] = $row;
        }
        echo json_encode($events);
    } else {
        echo json_encode([]);
    }

} elseif ($method == 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if ($event_id > 0 && $user_id > 0) {
        $check = $pdo->prepare("SELECT id FROM bookmarks WHERE event_id = ? AND user_id = ?");
        $check->execute([$event_id, $user_id]);

        if ($check->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Already bookmarked']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO bookmarks (event_id, user_id) VALUES (?, ?)");
            if ($stmt->execute([$event_id, $user_id])) {
                echo json_encode(['success' => true, 'message' => 'Bookmark added']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add bookmark']);
            }
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }

} elseif ($method == 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $event_id = isset($_DELETE['event_id']) ? intval($_DELETE['event_id']) : 0;
    $user_id = isset($_DELETE['user_id']) ? intval($_DELETE['user_id']) : 0;

    if ($event_id > 0 && $user_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE event_id = ? AND user_id = ?");
        if ($stmt->execute([$event_id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Bookmark removed']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to remove bookmark']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}
?>
