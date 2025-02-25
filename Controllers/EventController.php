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

    public function getEvents() {
        $headers = apache_request_headers(); 
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->studentController->validateToken($token) && !$this->adminController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }


        $events = $this->eventModel->getAllEvents();
        echo json_encode($events);
    }

    public function createEvent() {
        $headers = apache_request_headers(); 
        error_log("createEvent method called"); // Test log statement
        error_log("Headers: " . json_encode($headers)); // Debugging: Log headers
        error_log("Body: " . json_encode($_POST)); // Debugging: Log body

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
            echo json_encode(["message" => "Event created successfully."]);
        } else {
            echo json_encode(["message" => "Failed to create event."]);
        }
    }
}
?>
