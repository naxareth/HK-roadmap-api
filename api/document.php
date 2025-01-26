<?php
require_once '../config/database.php';

// Upload Document
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload') {
    $user_id = $_POST['user_id'];
    $file_path = $_FILES['document']['tmp_name'];
    $file_name = $_FILES['document']['name'];
    $target_path = "uploads/" . basename($file_name);

    if (move_uploaded_file($file_path, $target_path)) {
        $sql = "INSERT INTO documents (user_id, file_path) VALUES (:user_id, :file_path)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':file_path', $target_path);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Document uploaded successfully."]);
        } else {
            echo json_encode(["message" => "Failed to save document information."]);
        }
    } else {
        echo json_encode(["message" => "Failed to upload document."]);
    }
}

// Get Documents for a User
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $sql = "SELECT * FROM documents WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($documents);
}
?>