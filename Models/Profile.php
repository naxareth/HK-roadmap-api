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
            $existingProfile = $this->getProfile($userId, $userType);
            
            $query = "SELECT profile_id FROM user_profiles WHERE user_id = :user_id AND user_type = :user_type";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType
            ]);
            
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
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
    
            return $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType,
                ':name' => $name,
                ':email' => $email,
                ':department' => $data['department'] ?? ($existingProfile['department'] ?? null),
                ':department_others' => $departmentOthers,
                ':student_number' => $userType === 'student' ? ($data['student_number'] ?? ($existingProfile['student_number'] ?? null)) : null,
                ':college_program' => $userType === 'student' ? ($data['college_program'] ?? ($existingProfile['college_program'] ?? null)) : null,
                ':year_level' => $userType === 'student' ? ($data['year_level'] ?? ($existingProfile['year_level'] ?? null)) : null,
                ':scholarship_type' => $userType === 'student' ? ($data['scholarship_type'] ?? ($existingProfile['scholarship_type'] ?? null)) : null,
                ':position' => in_array($userType, ['admin', 'staff']) ? ($data['position'] ?? ($existingProfile['position'] ?? null)) : null,
                ':contact_number' => $data['contact_number'] ?? ($existingProfile['contact_number'] ?? null),
                ':profile_picture_url' => $data['profile_picture_url'] ?? ($existingProfile['profile_picture_url'] ?? null)
            ]);
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
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
            $targetDir = "../uploads/profile_pictures/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($file['name']);
            $targetPath = $targetDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return $fileName;
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
}
?>