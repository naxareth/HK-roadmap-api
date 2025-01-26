<?php
require_once '../config/database.php';

// Add Requirement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $user_id = $_POST['user_id'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $shared = isset($_POST['shared']) ? $_POST['shared'] : false; // Check if shared is set

    $sql = "INSERT INTO requirements (user_id, description, due_date, shared) VALUES (:user_id, :description, :due_date, :shared)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':due_date', $due_date);
    $stmt->bindParam(':shared', $shared, PDO::PARAM_BOOL); // Bind shared as boolean

    if ($stmt->execute()) {
        echo json_encode(["message" => "Requirement added successfully."]);
    } else {
        echo json_encode(["message" => "Failed to add requirement."]);
    }
}

// Get Requirements for a User or Shared Requirements
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if ($user_id) {
        // Get user-specific requirements
        $sql = "SELECT * FROM requirements WHERE user_id = :user_id OR shared = TRUE";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
    } else {
        // Get all requirements if no user_id is provided
        $sql = "SELECT * FROM requirements";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($requirements);
}
?>