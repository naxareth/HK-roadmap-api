<?php
namespace Controllers;

use Models\Profile;
use PDOException;
use PDO;

class ProfileController {
    private $profileModel;
    private $db;
    private $adminController;
    private $studentController;
    private $staffController;

    public function __construct($db) {
        $this->db = $db;
        $this->profileModel = new Profile($db);
        $this->adminController = new AdminController($db);
        $this->studentController = new StudentController($db);
        $this->staffController = new StaffController($db);
    }

    private function validateUserToken() {
        try {
            $headers = getallheaders();
            
            if (!isset($headers['Authorization'])) {
                http_response_code(401);
                echo json_encode(["message" => "Authorization header missing"]);
                return null;
            }
            
            $authHeader = $headers['Authorization'];
            
            if (strpos($authHeader, 'Bearer ') !== 0) {
                http_response_code(401);
                echo json_encode(["message" => "Invalid Authorization header format"]);
                return null;
            }
            
            $token = substr($authHeader, 7);
            
            // Direct database query for student tokens (same as ProfileRequirementsController)
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
                return ['type' => 'student', 'id' => $result['user_id']];
            }
            
            // Direct database query for admin tokens
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
                return ['type' => 'admin', 'id' => $result['user_id']];
            }
            
            // Direct database query for staff tokens
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
                return ['type' => 'staff', 'id' => $result['user_id']];
            }
            
            http_response_code(401);
            echo json_encode(["message" => "Invalid token"]);
            return null;
        } catch (\Exception $e) {
            error_log("Error in validateUserToken: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
            return null;
        }
    }

    public function getProfile() {
        try {
            $userData = $this->validateUserToken();
            if (!$userData) {
                return;
            }

            $profile = $this->profileModel->getProfile($userData['id'], $userData['type']);
            
            if ($profile) {
                echo json_encode($profile);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Profile not found"]);
            }
        } catch (PDOException $e) {
            error_log("Get profile error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function updateProfile() {
        try {
            $userData = $this->validateUserToken();
            if (!$userData) {
                return;
            }

            // Get JSON input
            $jsonInput = file_get_contents('php://input');
            $jsonData = json_decode($jsonInput, true);

            // Initialize data array
            $data = [];
            
            // Merge JSON data if available
            if ($jsonData) {
                $data = $jsonData;
            }
            
            // Merge POST data if available
            if (!empty($_POST)) {
                $data = array_merge($data, $_POST);
            }

            // Handle profile picture upload if present
            $profilePicture = null;
            if (isset($_FILES['profile_picture'])) {
                $profilePicture = $this->profileModel->uploadProfilePicture($_FILES['profile_picture']);
                if ($profilePicture === false) {
                    http_response_code(400);
                    echo json_encode(["message" => "Failed to upload profile picture"]);
                    return;
                }
                $data['profile_picture_url'] = $profilePicture;
            }

            // Validate department_others if department is "Others"
            if (isset($data['department']) && $data['department'] === 'Others') {
                if (empty($data['department_others'])) {
                    http_response_code(400);
                    echo json_encode(["message" => "Please specify other department"]);
                    return;
                }
            }

            // Get existing profile data
            $existingProfile = $this->profileModel->getProfile($userData['id'], $userData['type']);

            // Merge with existing data to preserve values not included in update
            $finalData = [
                'name' => $data['name'] ?? $existingProfile['name'] ?? null,
                'email' => $data['email'] ?? $existingProfile['email'] ?? null,
                'department' => $data['department'] ?? $existingProfile['department'] ?? null,
                'department_others' => $data['department_others'] ?? $existingProfile['department_others'] ?? null,
                'contact_number' => $data['contact_number'] ?? $existingProfile['contact_number'] ?? null,
                'profile_picture_url' => $data['profile_picture_url'] ?? $existingProfile['profile_picture_url'] ?? null
            ];

            // Add user type specific fields
            if ($userData['type'] === 'student') {
                $finalData += [
                    'student_number' => $data['student_number'] ?? $existingProfile['student_number'] ?? null,
                    'college_program' => $data['college_program'] ?? $existingProfile['college_program'] ?? null,
                    'year_level' => $data['year_level'] ?? $existingProfile['year_level'] ?? null,
                    'scholarship_type' => $data['scholarship_type'] ?? $existingProfile['scholarship_type'] ?? null
                ];
            } elseif ($userData['type'] === 'admin') {
                $finalData += [
                    'position' => $data['position'] ?? $existingProfile['position'] ?? null
                ];
            } elseif ($userData['type'] === 'staff') {
                $finalData += [
                    'student_number' => $data['student_number'] ?? $existingProfile['student_number'] ?? null,
                    'college_program' => $data['college_program'] ?? $existingProfile['college_program'] ?? null,
                    'year_level' => $data['year_level'] ?? $existingProfile['year_level'] ?? null,
                    'scholarship_type' => $data['scholarship_type'] ?? $existingProfile['scholarship_type'] ?? null,
                    'position' => $data['position'] ?? $existingProfile['position'] ?? null
                ];
            }

            if ($this->profileModel->createOrUpdateProfile($userData['id'], $userData['type'], $finalData)) {
                echo json_encode([
                    "message" => "Profile updated successfully",
                    "profile" => $finalData,
                    "id" => $userData['id'],
                    "type" => $userData['type']
                ]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Failed to update profile"]);
            }
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error: " . $e->getMessage()]);
        }
    }

    public function getDepartments() {
        echo json_encode(["departments" => Profile::getDepartments()]);
    }

    public function getPrograms() {
        echo json_encode(["programs" => Profile::getPrograms()]);
    }

    public function getAllProfiles() {
        try {
            $userData = $this->validateUserToken();
            if (!$userData) {
                return;
            }
    
            $type = $_GET['type'] ?? null;
    
            // Validate user permissions and type parameter
            switch ($userData['type']) {
                case 'admin':
                    // Admins can view all profile types
                    if (!$type || !in_array($type, ['student', 'admin', 'staff'])) {
                        http_response_code(400);
                        echo json_encode(["message" => "Invalid or missing type parameter"]);
                        return;
                    }
                    break;
    
                case 'staff':
                    // Staff can only view student profiles
                    if ($type !== 'student') {
                        http_response_code(403);
                        echo json_encode(["message" => "Staff can only view student profiles"]);
                        return;
                    }
                    break;
    
                default:
                    // Other user types are not authorized
                    http_response_code(403);
                    echo json_encode(["message" => "Unauthorized to view profiles"]);
                    return;
            }
    
            $profiles = $this->profileModel->getAllProfiles($type);
            
            if ($profiles) {
                echo json_encode($profiles);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "No profiles found"]);
            }
        } catch (PDOException $e) {
            error_log("Get all profiles error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }
}
?>
