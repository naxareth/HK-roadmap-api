<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Upload Document
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload') {
    $student_id = $_POST['student_id']; // Changed from user_id to student_id
    $file_path = $_FILES['document']['tmp_name'];
    $file_name = $_FILES['document']['name'];
    $target_path = "uploads/" . basename($file_name);

    if (move_uploaded_file($file_path, $target_path)) {
        $created_at = date('Y-m-d H:i:s'); // Get the current timestamp

        $sql = "INSERT INTO document (student_id, file_path, created_at) VALUES (:student_id, :file_path, :created_at)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':file_path', $target_path);
        $stmt->bindParam(':created_at', $created_at); // Bind the created_at parameter

        if ($stmt->execute()) {
            echo json_encode(["message" => "Document uploaded successfully."]);
        } else {
            echo json_encode(["message" => "Failed to save document information."]);
        }
    } else {
        echo json_encode(["message" => "Failed to upload document."]);
    }
}

// Get Documents for a Student
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['student_id'])) {
    $student_id = $_GET['student_id']; 

    $sql = "SELECT * FROM document WHERE student_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($documents);
}
?>