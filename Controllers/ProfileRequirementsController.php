<?php

namespace Controllers;

use Models\ProfileRequirements;
use Exception;
use PDO;
use PDOException;

class ProfileRequirementsController {
    private $model;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new ProfileRequirements($db);
    }

    /**
     * Get profile and requirements information
     * Endpoint: GET /api/profile/requirements
     */
    public function getProfileRequirements() {
        try {
            // Get user data from session/token
            $userData = $this->getCurrentUser();
            if (!$userData) {
                $this->sendResponse(401, [
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]);
                return;
            }

            // Get profile and requirements data
            $data = $this->model->getProfileWithRequirements(
                $userData['user_id'],
                $userData['user_type']
            );

            if ($data === false) {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Failed to fetch profile and requirements data'
                ]);
                return;
            }

            if (empty($data['profile'])) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Profile not found'
                ]);
                return;
            }

            $this->sendResponse(200, [
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            error_log("Error in getProfileRequirements: " . $e->getMessage());
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'An unexpected error occurred'
            ]);
        }
    }

    /**
     * Get profile and requirements for a specific user (admin only)
     * Endpoint: GET /api/profile/requirements/{userId}
     */
    public function getSpecificUserRequirements($userId) {
        try {
            // Check if current user is admin
            $currentUser = $this->getCurrentUser();
            if (!$currentUser || $currentUser['user_type'] !== 'admin') {
                $this->sendResponse(403, [
                    'success' => false,
                    'message' => 'Access forbidden. Admin privileges required.'
                ]);
                return;
            }

            // Get user type for the requested user
            $userType = $this->getUserType($userId);
            if (!$userType) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'User not found'
                ]);
                return;
            }

            // Get profile and requirements data
            $data = $this->model->getProfileWithRequirements($userId, $userType);

            if ($data === false) {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Failed to fetch profile and requirements data'
                ]);
                return;
            }

            $this->sendResponse(200, [
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            error_log("Error in getSpecificUserRequirements: " . $e->getMessage());
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'An unexpected error occurred'
            ]);
        }
    }

    /**
     * Helper method to send JSON response
     */
    private function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Helper method to get current user from session/token
     * Implement according to your authentication system
     */
    private function getCurrentUser() {
        try {
            // Get Authorization header
            $headers = getallheaders();
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
            // Check if Bearer token exists
            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                error_log("No bearer token found");
                return null;
            }
    
            $token = $matches[1];
            
            // Check student tokens
            $query = "
                SELECT 
                    'student' as user_type,
                    st.student_id as user_id 
                FROM student_tokens st 
                WHERE st.token = :token";
                
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result) {
                error_log("Found student token: " . print_r($result, true));
                return $result;
            }
    
            // Check admin tokens (assuming similar structure)
            $query = "
                SELECT 
                    'admin' as user_type,
                    at.admin_id as user_id 
                FROM admin_tokens at 
                WHERE at.token = :token";
                
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result) {
                error_log("Found admin token: " . print_r($result, true));
                return $result;
            }
    
            // Check staff tokens (assuming similar structure)
            $query = "
                SELECT 
                    'staff' as user_type,
                    st.staff_id as user_id 
                FROM staff_tokens st 
                WHERE st.token = :token";
                
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result) {
                error_log("Found staff token: " . print_r($result, true));
                return $result;
            }
    
            error_log("No valid token found for token: " . $token);
            return null;
    
        } catch (Exception $e) {
            error_log("Error in getCurrentUser: " . $e->getMessage());
            return null;
        }
    }
    private function getUserType($userId) {
        try {
            $query = "SELECT user_type FROM user_profiles WHERE user_id = ?";
            $stmt = $this->db->prepare($query);  // Use controller's db connection
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['user_type'] : null;
        } catch (PDOException $e) {
            error_log("Error getting user type: " . $e->getMessage());
            return null;
        }
    }
}
?>