<?php

namespace Controllers;

use Models\Event;
use Controllers\AdminController;

require_once '../models/Event.php';
require_once 'AdminController.php';

class EventController {
    private $eventModel;
    private $adminController;

    public function __construct($db) {
        $this->eventModel = new Event($db);
        $this->adminController = new AdminController($db);
    }

    public function getEvents() {
        return $this->eventModel->getAllEvents();
    }

    public function createEvent() {
        if (!$this->adminController->validateToken()) {
            echo json_encode(["message" => "Unauthorized access."]);
            return;
        }

        $admin = $this->adminController->validateToken();
        if (!$admin) {
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