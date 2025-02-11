<?php
class BaseController {
    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function handleError($message, $status = 400) {
        $this->jsonResponse(["message" => $message], $status);
    }
}
?>
