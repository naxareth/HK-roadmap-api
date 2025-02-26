<?php

namespace Models;

use PDO;

class Event {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllEvents() {
        $query = "SELECT * FROM event";
        $result = $this->db->query($query);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEventById($eventId) {
        $query = "SELECT * FROM event WHERE event_id = :event_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateEvent($eventId, $eventName, $date) {
        $query = "UPDATE event SET event_name = :event_name, date = :date WHERE event_id = :event_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_name', $eventName);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':event_id', $eventId);
        return $stmt->execute();
    }

    public function createEvent($eventName, $date) {
    
        $this->db->beginTransaction();
    
        try {
            $query = "INSERT INTO event (event_name, date) VALUES (:event_name, :date)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':event_name', $eventName);
            $stmt->bindParam(':date', $date);
            $stmt->execute();

            $eventId = $this->db->lastInsertId();

            $requirementName = 'First Requirement'; 
            $requirementQuery = "INSERT INTO requirement (event_id, requirement_name, due_date) VALUES (:event_id, :requirement_name, :due_date)";
            $requirementStmt = $this->db->prepare($requirementQuery);
            $requirementStmt->bindParam(':event_id', $eventId);
            $requirementStmt->bindParam(':requirement_name', $requirementName);
            $requirementStmt->bindParam(':due_date', $date); 
            $requirementStmt->execute();

            $this->db->commit();
            return true; 
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
}

?> 