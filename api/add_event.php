<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); ob_clean(); exit; }
include 'db.php';

try {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $year        = intval($_POST['year']       ?? 0);
    $date        = trim($_POST['date']         ?? '');
    $tags        = trim($_POST['tags']         ?? '');
    $user_id     = intval($_POST['user_id']    ?? 0);

    if (empty($title) || empty($description) || empty($date)) {
        ob_clean(); http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Title, description and date are required']);
        exit;
    }

    // Parse month and day from date
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj) {
        ob_clean(); http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid date format, expected YYYY-MM-DD']);
        exit;
    }
    $month = $dateObj->format('n'); // no leading zero
    $day   = $dateObj->format('j'); // no leading zero

    $image_url = null;

    // Handle image upload - store in Supabase Storage via REST API
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $supabase_url    = getenv('SUPABASE_URL');
        $supabase_key    = getenv('SUPABASE_SERVICE_KEY');
        $bucket          = 'event-images';

        $file_tmp  = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_type = $_FILES['image']['type'];
        $unique_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);

        if (!empty($supabase_url) && !empty($supabase_key)) {
            // Upload to Supabase Storage
            $upload_url = "$supabase_url/storage/v1/object/$bucket/$unique_name";
            $file_data  = file_get_contents($file_tmp);

            $ch = curl_init($upload_url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $file_data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer $supabase_key",
                    "Content-Type: $file_type",
                    "x-upsert: true",
                ],
            ]);
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200 || $http_code === 201) {
                // Public URL format for Supabase Storage
                $image_url = "$supabase_url/storage/v1/object/public/$bucket/$unique_name";
            } else {
                error_log("Supabase upload failed ($http_code): $result");
                // Fall back to local storage
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $local_path = $upload_dir . $unique_name;
                if (move_uploaded_file($file_tmp, $local_path)) {
                    $image_url = 'api/uploads/' . $unique_name;
                }
            }
        } else {
            // No Supabase env vars - use local storage
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $local_path = $upload_dir . $unique_name;
            if (move_uploaded_file($file_tmp, $local_path)) {
                $image_url = 'api/uploads/' . $unique_name;
            }
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO events (title, description, year, date, image_url, tags, month, day, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $year, $date, $image_url, $tags, $month, $day, $user_id ?: null]);

    $event_id = $pdo->lastInsertId();
    if (empty($event_id)) {
        $stmt2 = $pdo->prepare("SELECT id FROM events WHERE title = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt2->execute([$title, $user_id ?: null]);
        $row = $stmt2->fetch();
        $event_id = $row['id'] ?? 0;
    }

    ob_clean();
    echo json_encode([
        'status'    => 'success',
        'event_id'  => (int)$event_id,
        'image_url' => $image_url,
    ]);

} catch (Exception $e) {
    error_log("add_event error: " . $e->getMessage());
    ob_clean(); http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'add_event failed: ' . $e->getMessage()]);
}
?>
