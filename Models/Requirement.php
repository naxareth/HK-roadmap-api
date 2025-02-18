<?php
namespace Models;

use PDO;
use PDOException;


class Requirement {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function add($student_id, $event_name, $event_date, $due_date, $shared, $image = null) {
        try {
            $sql = "INSERT INTO requirements (student_id, event_name, event_date, due_date, shared, image) VALUES (:student_id, :event_name, :event_date, :due_date, :shared, :image)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':event_name', $event_name);
            $stmt->bindParam(':event_date', $event_date);
            $stmt->bindParam(':due_date', $due_date);
            $stmt->bindParam(':shared', $shared, PDO::PARAM_INT);
            $stmt->bindParam(':image', $image);

    
            if ($stmt->execute()) {
                return true;
            } else {
                error_log("SQL Error: " . implode(", ", $stmt->errorInfo())); // Log SQL errors
                return false;
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage()); // Log PDO exceptions
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

    public function getAllRequirements() {
        try {
            $sql = "SELECT * FROM requirements";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }
}
?>
