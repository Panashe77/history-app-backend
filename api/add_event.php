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

    error_log("ADD_EVENT: title=$title date=$date user_id=$user_id");
    error_log("ADD_EVENT: FILES=" . json_encode(array_map(fn($f) => ['name'=>$f['name'],'size'=>$f['size'],'error'=>$f['error']], $_FILES)));

    if (empty($title) || empty($description) || empty($date)) {
        ob_clean(); http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Title, description and date are required']);
        exit;
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj) {
        ob_clean(); http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid date format, expected YYYY-MM-DD']);
        exit;
    }
    $month = $dateObj->format('n');
    $day   = $dateObj->format('j');

    $image_url = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $supabase_url = getenv('SUPABASE_URL');
        $supabase_key = getenv('SUPABASE_SERVICE_KEY');
        $bucket       = 'event-images';

        error_log("ADD_EVENT: SUPABASE_URL=" . ($supabase_url ? 'SET' : 'NOT SET'));
        error_log("ADD_EVENT: SUPABASE_SERVICE_KEY=" . ($supabase_key ? 'SET' : 'NOT SET'));

        $file_tmp    = $_FILES['image']['tmp_name'];
        $file_name   = $_FILES['image']['name'];
        $file_type   = $_FILES['image']['type'];
        $file_size   = $_FILES['image']['size'];
        $unique_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);

        error_log("ADD_EVENT: Image file=$file_name size=$file_size type=$file_type");

        if (!empty($supabase_url) && !empty($supabase_key)) {
            $upload_url = "$supabase_url/storage/v1/object/$bucket/$unique_name";
            error_log("ADD_EVENT: Uploading to Supabase: $upload_url");

            $file_data = file_get_contents($file_tmp);
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
            $result    = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err  = curl_error($ch);
            curl_close($ch);

            error_log("ADD_EVENT: Supabase response code=$http_code result=$result curl_err=$curl_err");

            if ($http_code === 200 || $http_code === 201) {
                $image_url = "$supabase_url/storage/v1/object/public/$bucket/$unique_name";
                error_log("ADD_EVENT: ✅ Supabase upload success, image_url=$image_url");
            } else {
                error_log("ADD_EVENT: ❌ Supabase upload FAILED ($http_code): $result — falling back to local");
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($file_tmp, $upload_dir . $unique_name)) {
                    $image_url = 'api/uploads/' . $unique_name;
                    error_log("ADD_EVENT: Local fallback image_url=$image_url");
                }
            }
        } else {
            error_log("ADD_EVENT: ❌ Supabase env vars missing — using local storage");
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (move_uploaded_file($file_tmp, $upload_dir . $unique_name)) {
                $image_url = 'api/uploads/' . $unique_name;
                error_log("ADD_EVENT: Local fallback image_url=$image_url");
            }
        }
    } else {
        $file_error = $_FILES['image']['error'] ?? 'no file';
        error_log("ADD_EVENT: No image uploaded or error. FILES error=$file_error");
    }

    error_log("ADD_EVENT: Inserting event with image_url=$image_url");

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

    error_log("ADD_EVENT: ✅ Event inserted id=$event_id image_url=$image_url");

    ob_clean();
    echo json_encode([
        'status'    => 'success',
        'event_id'  => (int)$event_id,
        'image_url' => $image_url,
        'debug'     => [
            'supabase_url_set'  => !empty(getenv('SUPABASE_URL')),
            'supabase_key_set'  => !empty(getenv('SUPABASE_SERVICE_KEY')),
            'image_uploaded'    => $image_url !== null,
            'storage_used'      => $image_url && str_contains($image_url, 'supabase') ? 'supabase' : 'local',
        ],
    ]);

} catch (Exception $e) {
    error_log("ADD_EVENT: Exception: " . $e->getMessage());
    ob_clean(); http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'add_event failed: ' . $e->getMessage()]);
}
?>
