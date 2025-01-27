<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Function to validate the token
function validateToken($conn, $token) {
    $sql = "SELECT * FROM admin WHERE token = :token";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC); // Returns admin data if token is valid
}

// Add Requirement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    // Check if token is provided in POST request
    if (!isset($_POST['token'])) {
        echo json_encode(["message" => "Token not provided."]);
        exit;
    }

    $token = $_POST['token']; // Get the token from the request
    $admin = validateToken($conn, $token); // Validate the token

    if (!$admin) {
        echo json_encode(["message" => "Unauthorized access. Invalid token."]);
        exit; // Stop further execution if the token is invalid
    }

    $student_id = $_POST['student_id']; // Use student_id as the primary key
    $event_name = $_POST['event_name']; // Use event_name instead of description
    $due_date = $_POST['due_date'];
    $shared = isset($_POST['shared']) ? (int)$_POST['shared'] : 0; // Check if shared is set, default to 0
    $submission = date('Y-m-d H:i:s'); // Get the current timestamp for submission

    $sql = "INSERT INTO requirements (student_id, event_name, due_date, shared, submission) VALUES (:student_id, :event_name, :due_date, :shared, :submission)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':event_name', $event_name);
    $stmt->bindParam(':due_date', $due_date);
    $stmt->bindParam(':shared', $shared, PDO::PARAM_INT); // Bind shared as integer
    $stmt->bindParam(':submission', $submission); // Bind submission timestamp

    if ($stmt->execute()) {
        echo json_encode(["message" => "Requirement added successfully."]);
    } else {
        echo json_encode(["message" => "Failed to add requirement."]);
    }
}

// Get Requirements for a User or Shared Requirements
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['student_id'])) {
    // Check if token is provided in GET request
    if (!isset($_GET['token'])) {
        echo json_encode(["message" => "Token not provided."]);
        exit;
    }

    $token = $_GET['token']; // Get the token from the request
    $admin = validateToken($conn, $token); // Validate the token

    if (!$admin) {
        echo json_encode(["message" => "Unauthorized access. Invalid token."]);
        exit; // Stop further execution if the token is invalid
    }

    $student_id = $_GET['student_id']; // Get the student_id from the request

    // Get user-specific requirements
    $sql = "SELECT * FROM requirements WHERE student_id = :student_id OR shared = 1"; // Use 1 for TRUE
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    
    $stmt->execute();
    $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($requirements);
}
?>