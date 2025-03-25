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

    public function createRequirement($eventId, $requirementName, $requirementDescription, $dueDate) {
        $query = "INSERT INTO requirement (event_id, requirement_name, requirement_desc, due_date) VALUES (:event_id, :requirement_name, :requirement_desc, :due_date)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':requirement_name', $requirementName);
        $stmt->bindParam(':requirement_desc', $requirementDescription);
        $stmt->bindParam(':due_date', $dueDate);
        return $stmt->execute();
    }

    public function getRequirementById($requirementId) {
        $query = "SELECT * FROM requirement WHERE requirement_id = :requirementId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':requirementId', $requirementId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRequirement($requirementId, $requirementName, $requirementDescription, $dueDate) {
        $query = "UPDATE requirement SET requirement_name = :requirement_name, requirement_desc = :requirement_desc, due_date = :due_date 
                  WHERE requirement_id = :requirement_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':requirement_name', $requirementName);
        $stmt->bindParam(':requirement_desc', $requirementDescription);
        $stmt->bindParam(':due_date', $dueDate);
        $stmt->bindParam(':requirement_id', $requirementId);
        return $stmt->execute();
    }

    public function getRequirementsByEventId($eventId) {
        $query = "SELECT * FROM requirement WHERE event_id = :event_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteRequirement($requirementId) {
        $query = "DELETE FROM requirement WHERE requirement_id = :requirement_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':requirement_id', $requirementId);
        return $stmt->execute();
    }
}
?>