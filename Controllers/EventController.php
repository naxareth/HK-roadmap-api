<?php

namespace Controllers;

use Models\Event;
use Controllers\AdminController;

require_once '../models/Event.php';
require_once 'AdminController.php';

class EventController {
    private $eventModel;
    private $adminController;
    private $studentController;

    public function __construct($db) {
        $this->eventModel = new Event($db);
        $this->adminController = new AdminController($db);
        $this->studentController = new StudentController($db);
    }

    public function getEventById() {
        $headers = apache_request_headers(); 
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }
    
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token) && !$this->studentController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }
    
        if (!isset($_GET['event_id'])) {
            echo json_encode(["message" => "Event ID is required."]);
            return;
        }

        $eventId = $_GET['event_id'];
        
        $event = $this->eventModel->getEventById($eventId);
    
        if ($event) {
            echo json_encode($event);
        } else {
            echo json_encode(["message" => "Event not found."]);
        }
    }

    public function getEvent() {
        $headers = apache_request_headers(); 
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }
    
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token) && !$this->studentController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }

        $events = $this->eventModel->getAllEvent();
        echo json_encode($events);
    }
    
    public function editEvent() {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }
    
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }
    
        $putData = json_decode(file_get_contents("php://input"), true);
    
        if (!isset($putData['event_id'], $putData['event_name'], $putData['date'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }
    
        $eventId = $putData['event_id'];
        $eventName = $putData['event_name'];
        $date = $putData['date'];
    
        if ($this->eventModel->updateEvent($eventId, $eventName, $date)) {
            echo json_encode(["message" => "Event updated successfully."]);
        } else {
            echo json_encode(["message" => "Failed to update event."]);
        }
    }

    public function createEvent() {
        $headers = apache_request_headers(); 
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }


        if (!isset($_POST['event_name'], $_POST['date'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $eventName = $_POST['event_name'];
        $date = $_POST['date'];

        if ($this->eventModel->createEvent($eventName, $date)) {
            header('Content-Type: application/json');
            echo json_encode(["message" => "Event created successfully."]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(["message" => "Failed to create event."]);
        }
    }

    public function deleteEvent() {
        $headers = apache_request_headers(); 
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }

        if (!isset($_GET['event_id'])) {
            echo json_encode(["message" => "Event ID is required."]);
            return;
        }

        $eventId = $_GET['event_id'];

        if ($this->eventModel->deleteEvent($eventId)) {
            echo json_encode(["message" => "Event deleted successfully."]);
        } else {
            echo json_encode(["message" => "Failed to delete event or event not found."]);
        }
    }
}
?>
