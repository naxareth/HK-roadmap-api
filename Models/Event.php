<?php

namespace Models;

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

    public function createEvent($eventName, $date) {
        $query = "INSERT INTO event (event_name, date) VALUES (:event_name, :date)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_name', $eventName);
        $stmt->bindParam(':date', $date);
        return $stmt->execute();
    }
}

?> 