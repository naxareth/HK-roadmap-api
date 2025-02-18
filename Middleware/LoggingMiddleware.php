<?php
namespace Middleware;

class LoggingMiddleware extends Middleware {
    public function handle($request, $next) {
        // Log the incoming request
        error_log("Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
        
        // Continue to next middleware/controller
        $response = $next($request);
        
        // Log the response
        error_log("Response: " . http_response_code());
        
        return $response;
    }
}
