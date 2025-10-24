<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header('Content-Type: application/json');

include 'db.php';

$date = date('Y-m-d', strtotime($_POST['date']));

$title = $_POST['title'];
$description = $_POST['description'];
$year = (int)$_POST['year'];
$tags = $_POST['tags'] ?? '';
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
$image_url = null;

if (isset($_FILES['image'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . time() . "_" . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $image_url = $targetFile;
    }
}

$sql = "INSERT INTO events (title, description, year, date, image_url, tags, likes, dislikes, user_id)
        VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssisssi", $title, $description, $year, $date, $image_url, $tags, $user_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>