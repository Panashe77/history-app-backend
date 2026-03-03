<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

    if (empty($title) || empty($description) || empty($date)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Title, description and date are required']);
        exit;
    }

    // Extract month and day from date
    $month = date('n', strtotime($date));
    $day = date('j', strtotime($date));

    // Handle image upload
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_ext, $allowed)) {
            $filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = 'api/uploads/' . $filename;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO events (title, description, year, date, image_url, tags, month, day, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id");
    $stmt->execute([$title, $description, $year, $date, $image_url, $tags, $month, $day, $user_id]);
    $row = $stmt->fetch();

    echo json_encode([
        'status' => 'success',
        'message' => 'Event added successfully',
        'event_id' => $row['id']
    ]);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
