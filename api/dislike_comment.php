<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if ($comment_id > 0 && $user_id > 0) {
        $check_sql = "SELECT like_type FROM comment_likes WHERE comment_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $comment_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            if ($row['like_type'] == 'dislike') {
                $delete_sql = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("ii", $comment_id, $user_id);
                $delete_stmt->execute();
                
                $update_sql = "UPDATE comments SET dislikes = dislikes - 1 WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $comment_id);
                $update_stmt->execute();
                
                echo json_encode(['success' => true, 'action' => 'removed_dislike']);
            } else {
                $update_like_sql = "UPDATE comment_likes SET like_type = 'dislike' WHERE comment_id = ? AND user_id = ?";
                $update_like_stmt = $conn->prepare($update_like_sql);
                $update_like_stmt->bind_param("ii", $comment_id, $user_id);
                $update_like_stmt->execute();
                
                $update_sql = "UPDATE comments SET dislikes = dislikes + 1, likes = likes - 1 WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $comment_id);
                $update_stmt->execute();
                
                echo json_encode(['success' => true, 'action' => 'changed_to_dislike']);
            }
        } else {
            $insert_sql = "INSERT INTO comment_likes (comment_id, user_id, like_type) VALUES (?, ?, 'dislike')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $comment_id, $user_id);
            $insert_stmt->execute();
            
            $update_sql = "UPDATE comments SET dislikes = dislikes + 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $comment_id);
            $update_stmt->execute();
            
            echo json_encode(['success' => true, 'action' => 'added_dislike']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}

$conn->close();
?>