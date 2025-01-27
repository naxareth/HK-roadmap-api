<?php
require_once '../config/database.php';

// Function to generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Admin Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert the new admin record
    $sql = "INSERT INTO admin (name, email, password) VALUES (:name, :email, :password)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);

    try {
        if ($stmt->execute()) {
            // Generate a token
            $token = generateToken();

            // Update the admin record with the token
            $sql = "UPDATE admin SET token = :token WHERE name = :name";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':name', $name); // Use name as the identifier
            $stmt->execute();

            echo json_encode(["message" => "Admin registered successfully.", "token" => $token]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "Admin registration failed: " . $e->getMessage()]);
    }
}

// Admin Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $name = $_POST['name'];
    $password = $_POST['password'];

    // Select the admin record
    $sql = "SELECT * FROM admin WHERE name = :name";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // Generate a token
        $token = generateToken();

        // Update the admin record with the new token
        $sql = "UPDATE admin SET token = :token WHERE name = :name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':name', $name); // Use name as the identifier
        $stmt->execute();

        echo json_encode(["message" => "Login successful.", "token" => $token]);
    } else {
        echo json_encode(["message" => "Invalid name or password."]);
    }
}
?>