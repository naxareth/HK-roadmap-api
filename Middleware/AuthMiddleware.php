<?php
namespace Middleware;

class AuthMiddleware extends Middleware {
    public function handle($request, $next) {
        // Check if user is authenticated
        session_start();
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }
        
        // Continue to next middleware/controller
        return $next($request);
    }
}
