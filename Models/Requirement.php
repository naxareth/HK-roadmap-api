<?php

namespace Models;

use PDO;

class Requirement {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllRequirements() {
        $query = "SELECT * FROM requirement";
        $result = $this->db->query($query);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createRequirement($eventId, $requirementName, $dueDate) {
        $query = "INSERT INTO requirement (event_id, requirement_name, due_date) VALUES (:event_id, :requirement_name, :due_date)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':requirement_name', $requirementName);
        $stmt->bindParam(':due_date', $dueDate);
        return $stmt->execute();
    }

    public function getRequirementsByEventId($eventId) {
        $query = "SELECT * FROM requirement WHERE event_id = :event_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>