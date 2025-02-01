<?php
class Requirement {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function add($student_id, $event_name, $due_date, $shared) {
        try {
            $submission = date('Y-m-d H:i:s');
            $sql = "INSERT INTO requirements (student_id, event_name, due_date, shared, submission) VALUES (:student_id, :event_name, :due_date, :shared, :submission)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':event_name', $event_name);
            $stmt->bindParam(':due_date', $due_date);
            $stmt->bindParam(':shared', $shared, PDO::PARAM_INT);
            $stmt->bindParam(':submission', $submission);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public function getRequirements($student_id) {
        try {
            $sql = "SELECT * FROM requirements WHERE student_id = :student_id OR shared = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
}
?>