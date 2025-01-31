<?php
class Student {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($student_id, $email, $password) {
        $sql = "INSERT INTO student (student_id, email, password) VALUES (:student_id, :email, :password)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
        return $stmt->execute();
    }

    public function login($student_id, $password) {
        $sql = "SELECT * FROM student WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        return $student && password_verify($password, $student['password']) ? $student : false;
    }

    public function updateToken($student_id, $token) {
        $sql = "UPDATE student SET token = :token WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':student_id', $student_id);
        return $stmt->execute();
    }
}
?>