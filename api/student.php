<?php
require_once '../config/database.php';

// Function to generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Student Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert the new student record
    $sql = "INSERT INTO student (student_id, email, password) VALUES (:student_id, :email, :password)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);

    if ($stmt->execute()) {
        // Generate a token
        $token = generateToken();

        // Update the student record with the token
        $sql = "UPDATE student SET token = :token WHERE student_id = :student_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();

        echo json_encode(["message" => "Student registered successfully.", "token" => $token]);
    } else {
        echo json_encode(["message" => "Student registration failed."]);
    }
}

// Student Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $student_id = $_POST['student_id'];
    $password = $_POST['password'];

    // Select the student record
    $sql = "SELECT * FROM student WHERE student_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student && password_verify($password, $student['password'])) {
        // Generate a token
        $token = generateToken();

        // Update the student record with the new token
        $sql = "UPDATE student SET token = :token WHERE student_id = :student_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();

        echo json_encode(["message" => "Login successful.", "token" => $token]);
    } else {
        echo json_encode(["message" => "Invalid student ID or password."]);
    }
}
?>