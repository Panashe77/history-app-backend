<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');
include 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $events_count = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(likes), 0) as total_likes FROM events WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_likes = $stmt->fetch()['total_likes'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookmarks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $bookmarks_count = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $comments_count = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_events = [];
    while ($row = $stmt->fetch()) {
        $row['id'] = (int)$row['id'];
        $row['year'] = (int)$row['year'];
        $row['likes'] = (int)$row['likes'];
        $row['dislikes'] = (int)$row['dislikes'];
        $recent_events[] = $row;
    }

    echo json_encode([
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
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
}
?>
