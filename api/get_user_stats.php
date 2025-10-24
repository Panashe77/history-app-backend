<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');

include 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    // Get user info
    $user_sql = "SELECT username, email FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Count events created by user
    $events_sql = "SELECT COUNT(*) as count FROM events WHERE user_id = ?";
    $events_stmt = $conn->prepare($events_sql);
    $events_stmt->bind_param("i", $user_id);
    $events_stmt->execute();
    $events_result = $events_stmt->get_result();
    $events_count = $events_result->fetch_assoc()['count'] ?? 0;
    
    // Count total likes received on user's events
    $likes_sql = "SELECT COALESCE(SUM(likes), 0) as total_likes FROM events WHERE user_id = ?";
    $likes_stmt = $conn->prepare($likes_sql);
    $likes_stmt->bind_param("i", $user_id);
    $likes_stmt->execute();
    $likes_result = $likes_stmt->get_result();
    $total_likes = $likes_result->fetch_assoc()['total_likes'] ?? 0;
    
    // Count bookmarks (check if table exists)
    $bookmarks_count = 0;
    $table_check = $conn->query("SHOW TABLES LIKE 'bookmarks'");
    if ($table_check->num_rows > 0) {
        $bookmarks_sql = "SELECT COUNT(*) as count FROM bookmarks WHERE user_id = ?";
        $bookmarks_stmt = $conn->prepare($bookmarks_sql);
        $bookmarks_stmt->bind_param("i", $user_id);
        $bookmarks_stmt->execute();
        $bookmarks_result = $bookmarks_stmt->get_result();
        $bookmarks_count = $bookmarks_result->fetch_assoc()['count'] ?? 0;
        $bookmarks_stmt->close();
    }
    
    // Count comments
    $comments_count = 0;
    $comments_table_check = $conn->query("SHOW TABLES LIKE 'comments'");
    if ($comments_table_check->num_rows > 0) {
        $comments_sql = "SELECT COUNT(*) as count FROM comments WHERE user_id = ?";
        $comments_stmt = $conn->prepare($comments_sql);
        $comments_stmt->bind_param("i", $user_id);
        $comments_stmt->execute();
        $comments_result = $comments_stmt->get_result();
        $comments_count = $comments_result->fetch_assoc()['count'] ?? 0;
        $comments_stmt->close();
    }
    
    // Get recent events created by user
    $recent_sql = "SELECT * FROM events WHERE user_id = ? ORDER BY id DESC LIMIT 5";
    $recent_stmt = $conn->prepare($recent_sql);
    $recent_stmt->bind_param("i", $user_id);
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    
    $recent_events = [];
    while ($row = $recent_result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['year'] = (int)$row['year'];
        $row['likes'] = (int)$row['likes'];
        $row['dislikes'] = (int)$row['dislikes'];
        $recent_events[] = $row;
    }
    
    // Build response
    $response = [
        'success' => true,
        'user' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'avatar_url' => null,
            'bio' => null,
        ],
        'stats' => [
            'events_created' => (int)$events_count,
            'total_likes_received' => (int)$total_likes,
            'bookmarks_count' => (int)$bookmarks_count,
            'comments_count' => (int)$comments_count,
        ],
        'recent_events' => $recent_events,
    ];
    
    echo json_encode($response);
    
    $user_stmt->close();
    $events_stmt->close();
    $likes_stmt->close();
    $recent_stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
}

$conn->close();
?>