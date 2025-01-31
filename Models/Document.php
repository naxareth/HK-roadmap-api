<?php
class Document {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function upload($student_id, $file_path) {
        $created_at = date('Y-m-d H:i:s');
        $sql = "INSERT INTO document (student_id, file_path, created_at) VALUES (:student_id, :file_path, :created_at)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':file_path', $file_path);
        $stmt->bindParam(':created_at', $created_at);
        return $stmt->execute();
    }

    public function getDocuments($student_id) {
        $sql = "SELECT * FROM document WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>