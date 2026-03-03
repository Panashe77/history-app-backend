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

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];

if (!empty($query)) {
    // PostgreSQL uses ILIKE for case-insensitive search
    $sql .= " AND (title ILIKE ? OR description ILIKE ?)";
    $searchTerm = "%$query%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($tag)) {
    $sql .= " AND tags ILIKE ?";
    $params[] = "%$tag%";
}

if ($yearFrom !== null) {
    $sql .= " AND year >= ?";
    $params[] = $yearFrom;
}

if ($yearTo !== null) {
    $sql .= " AND year <= ?";
    $params[] = $yearTo;
}

switch ($sortBy) {
    case 'likes':
        $sql .= " ORDER BY likes DESC";
        break;
    case 'created_at':
        $sql .= " ORDER BY id DESC";
        break;
    case 'year':
    default:
        $sql .= " ORDER BY year DESC";
        break;
}

$sql .= " LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$events = [];
while ($row = $stmt->fetch()) {
    $row['id'] = (int)$row['id'];
    $row['year'] = (int)$row['year'];
    $row['likes'] = (int)$row['likes'];
    $row['dislikes'] = (int)$row['dislikes'];
    $events[] = $row;
}

echo json_encode($events);
?>
