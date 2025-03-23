<?php
namespace Models;

use PDO;
use PDOException;
use Exception;

class Profile {
    private $conn;
    
    const DEPARTMENTS = [
        'CITE' => 'College of Information Technology and Engineering',
        'CAHS' => 'College of Allied Health Sciences',
        'CAS' => 'College of Arts and Sciences',
        'CELA' => 'College of Education and Liberal Arts',
        'CCJE' => 'College of Criminal Justice Education',
        'CMA' => 'College of Management and Accountancy',
        'CEA' => 'College of Engineering and Architecture',
        'Others' => 'Others'
    ];
    
    const PROGRAMS = [
        'BS Criminology',
        'Bachelor of Arts in Communication',
        'Bachelor of Arts in Political Science',
        'Bachelor of Elementary Education',
        'Bachelor of Secondary Education - Science',
        'Bachelor of Secondary Education - Social Studies',
        'Bachelor of Secondary Education - English',
        'BS Accountancy',
        'BS Hospitality Management',
        'BS Tourism Management',
        'BS Management Accounting',
        'BS Accounting Information System',
        'BS Business Administration - Financial Management',
        'BS Business Administration - Marketing Management',
        'BS Information Technology',
        'BS Nursing',
        'BS Pharmacy',
        'BS Psychology',
        'BS Medical Laboratory Science',
        'BS Architecture',
        'BS Civil Engineering',
        'BS Computer Engineering',
        'BS Electronics Engineering',
        'BS Electrical Engineering',
        'BS Mechanical Engineering'
    ];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createOrUpdateProfile($userId, $userType, $data) {
        try {
            // First, get existing profile data
            $existingProfile = $this->getProfile($userId, $userType);
            
            // Check if profile exists
            $query = "SELECT profile_id FROM user_profiles WHERE user_id = :user_id AND user_type = :user_type";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType
            ]);
            
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
                // Update existing profile
                $query = "UPDATE user_profiles SET 
                    name = :name,
                    email = :email,
                    department = :department,
                    department_others = :department_others,
                    student_number = :student_number,
                    college_program = :college_program,
                    year_level = :year_level,
                    scholarship_type = :scholarship_type,
                    position = :position,
                    contact_number = :contact_number,
                    profile_picture_url = :profile_picture_url
                    WHERE user_id = :user_id AND user_type = :user_type";
            } else {
                // Create new profile
                $query = "INSERT INTO user_profiles 
                    (user_id, user_type, name, email, department, department_others,
                    student_number, college_program, year_level, scholarship_type, 
                    position, contact_number, profile_picture_url)
                    VALUES 
                    (:user_id, :user_type, :name, :email, :department, :department_others,
                    :student_number, :college_program, :year_level, :scholarship_type,
                    :position, :contact_number, :profile_picture_url)";
            }
    
            $stmt = $this->conn->prepare($query);
            
            // Handle department_others field
            $departmentOthers = null;
            if (isset($data['department']) && $data['department'] === 'Others') {
                $departmentOthers = $data['department_others'] ?? null;
            }
    
            // Preserve existing name and email if not provided in update
            $name = isset($data['name']) ? $data['name'] : ($existingProfile['name'] ?? null);
            $email = isset($data['email']) ? $data['email'] : ($existingProfile['email'] ?? null);

            if (isset($data['name']) && $data['name'] !== $existingProfile['name']) {
                $this->updateAdminName($userId, $data['name']);
            }
    
            return $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType,
                ':name' => $name,
                ':email' => $email,
                ':department' => $data['department'] ?? ($existingProfile['department'] ?? null),
                ':department_others' => $departmentOthers,
                ':student_number' => in_array($userType, ['student', 'staff']) ? ($data['student_number'] ?? ($existingProfile['student_number'] ?? null)) : null,
                ':college_program' => in_array($userType, ['student', 'staff']) ? ($data['college_program'] ?? ($existingProfile['college_program'] ?? null)) : null,
                ':year_level' => in_array($userType, ['student', 'staff']) ? ($data['year_level'] ?? ($existingProfile['year_level'] ?? null)) : null,
                ':scholarship_type' => in_array($userType, ['student', 'staff']) ? ($data['scholarship_type'] ?? ($existingProfile['scholarship_type'] ?? null)) : null,
                ':position' => in_array($userType, ['admin', 'staff']) ? ($data['position'] ?? ($existingProfile['position'] ?? null)) : null,
                ':contact_number' => $data['contact_number'] ?? ($existingProfile['contact_number'] ?? null),
                ':profile_picture_url' => $data['profile_picture_url'] ?? ($existingProfile['profile_picture_url'] ?? null)
            ]);
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }

    private function updateAdminName($userId, $newName) {
        try {
            $query = "UPDATE admin SET name = :name WHERE admin_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':name' => $newName,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Admin name update error: " . $e->getMessage());
        }
    }


    public function getProfile($userId, $userType) {
        try {
            $query = "SELECT * FROM user_profiles WHERE user_id = :user_id AND user_type = :user_type";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get profile error: " . $e->getMessage());
            return false;
        }
    }

    public function uploadProfilePicture($file) {
        try {

            
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/profile_pictures/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = uniqid() . '.jpg';
            $targetPath = $targetDir . $fileName;
    
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return '/uploads/profile_pictures/' . $fileName;
            }
            return false;
        } catch (Exception $e) {
            error_log("Profile picture upload error: " . $e->getMessage());
            return false;
        }
    }
    public static function getDepartments() {
        return self::DEPARTMENTS;
    }

    public static function getPrograms() {
        return self::PROGRAMS;
    }

    public function getAllProfiles($type) {
        try {
            // Validate type parameter
            if (!in_array($type, ['student', 'admin', 'staff'])) {
                throw new Exception("Invalid profile type");
            }
    
            // Base query to get all profiles of a specific type
            $query = "SELECT 
                p.*,
                CASE 
                    WHEN p.department = 'Others' THEN p.department_others 
                    ELSE p.department 
                END as display_department
                FROM user_profiles p 
                WHERE p.user_type = :type";
    
            // Add type-specific sorting
            switch ($type) {
                case 'student':
                    $query .= " ORDER BY p.student_number ASC";
                    break;
                case 'admin':
                case 'staff':
                    $query .= " ORDER BY p.name ASC";
                    break;
            }
    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':type' => $type]);
            
            $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Process the results
            foreach ($profiles as &$profile) {
                // Clean up null values
                foreach ($profile as $key => $value) {
                    if ($value === null) {
                        $profile[$key] = '';
                    }
                }
    
                // Add default profile picture if none exists
                if (empty($profile['profile_picture_url'])) {
                    $profile['profile_picture_url'] = '/assets/jpg/default-profile.png';
                }
    
                // Format department display
                if ($profile['department'] === 'Others') {
                    $profile['department_display'] = $profile['department_others'];
                } else {
                    $profile['department_display'] = self::DEPARTMENTS[$profile['department']] ?? $profile['department'];
                }
    
                // Add type-specific formatting
                switch ($type) {
                    case 'student':
                        // Format student-specific fields
                        $profile['year_level_display'] = $profile['year_level'] ? $profile['year_level'] . ' Year' : '';
                        break;
                    case 'admin':
                    case 'staff':
                        // Format position display
                        $profile['position_display'] = $profile['position'] ?: 'Not specified';
                        $profile['year_level_display'] = $profile['year_level'] ? $profile['year_level'] . ' Year' : '';
                        break;
                }
            }
    
            return $profiles;
        } catch (PDOException $e) {
            error_log("Database error in getAllProfiles: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error in getAllProfiles: " . $e->getMessage());
            return false;
        }
    }
}
?>