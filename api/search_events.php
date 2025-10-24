<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');

include 'db.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$yearFrom = isset($_GET['year_from']) ? intval($_GET['year_from']) : null;
$yearTo = isset($_GET['year_to']) ? intval($_GET['year_to']) : null;
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'year';

// Start building SQL
$sql = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = "";

// Search by title or description
if (!empty($query)) {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$query%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Filter by tag
if (!empty($tag)) {
    $sql .= " AND tags LIKE ?";
    $params[] = "%$tag%";
    $types .= "s";
}

// Filter by year range
if ($yearFrom !== null) {
    $sql .= " AND year >= ?";
    $params[] = $yearFrom;
    $types .= "i";
}

if ($yearTo !== null) {
    $sql .= " AND year <= ?";
    $params[] = $yearTo;
    $types .= "i";
}

// Sort
switch($sortBy) {
    case 'likes':
        $sql .= " ORDER BY likes DESC";
        break;
    case 'created_at':
        $sql .= " ORDER BY id DESC"; // Use id if created_at doesn't exist
        break;
    case 'year':
    default:
        $sql .= " ORDER BY year DESC";
        break;
}

$sql .= " LIMIT 100";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

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
$conn->close();
?>