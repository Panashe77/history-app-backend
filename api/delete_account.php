<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $email = isset($data['email']) ? $data['email'] : '';
    $password = isset($data['password']) ? $data['password'] : '';

    if ($user_id <= 0 && empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID or email required']);
        exit;
    }

    // Verify user
    if (!empty($email)) {
        $verify_sql = "SELECT id, password_hash FROM users WHERE email = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("s", $email);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        
        if ($result->num_rows == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit;
        }
        
        $verify_stmt->close();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete user's comment likes
        $conn->query("DELETE FROM comment_likes WHERE user_id = $user_id");
        
        // Delete user's event likes
        $conn->query("DELETE FROM event_likes WHERE user_id = $user_id");
        
        // Delete user's bookmarks
        $conn->query("DELETE FROM bookmarks WHERE user_id = $user_id");
        
        // Delete user's comments (this will cascade delete replies due to foreign key)
        $conn->query("DELETE FROM comments WHERE user_id = $user_id");
        
        // Anonymize or delete user's events (keeping them but removing user association)
        $conn->query("UPDATE events SET user_id = NULL WHERE user_id = $user_id");
        
        // Delete user account
        $delete_user = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_user);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Account and all associated data deleted successfully'
        ]);
        
        $stmt->close();
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

$conn->close();
?>