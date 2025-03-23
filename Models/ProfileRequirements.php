<?php

namespace Models;

use PDO;
use PDOException;
use Exception;

class ProfileRequirements {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getProfileWithRequirements($userId, $userType) {
        try {
            // First get the profile information
            $profileData = $this->getProfileInfo($userId, $userType);
            if (!$profileData) {
                return false;
            }

            // Only get requirements list if user is a student
            $requirements = $userType === 'student' 
                ? $this->getRequirementsList($userId)
                : [];  // Empty array for admin/staff

            return [
                'profile' => $profileData,
                'requirements' => $requirements
            ];
        } catch (PDOException $e) {
            error_log("Error in getProfileWithRequirements: " . $e->getMessage());
            return false;
        }
    }

    private function getProfileInfo($userId, $userType) {
        try {
            $query = "SELECT 
                        name,
                        email,
                        department,
                        student_number,
                        college_program,
                        year_level,
                        scholarship_type,
                        contact_number,
                        profile_picture_url
                    FROM user_profiles 
                    WHERE user_id = :user_id 
                    AND user_type = :user_type";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType
            ]);

            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (empty($profile['profile_picture_url'])) {
                $profile['profile_picture_url'] = '/assets/jpg/default-profile.png';
            }

            return $profile;
        } catch (PDOException $e) {
            error_log("Error fetching profile info: " . $e->getMessage());
            return false;
        }
    }

    private function getRequirementsList($userId) {
        try {
            $query = "
                SELECT 
                    e.event_name,
                    r.requirement_name,
                    s.submission_date,
                    s.approved_by,
                    s.status
                FROM event e
                JOIN requirement r ON e.event_id = r.event_id
                LEFT JOIN submission s ON (
                    r.requirement_id = s.requirement_id 
                    AND s.student_id = :user_id
                )
                ORDER BY 
                    e.event_name ASC,
                    r.requirement_name ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organize by events
            $organized = [];
            foreach ($results as $row) {
                $eventName = $row['event_name'];
                if (!isset($organized[$eventName])) {
                    $organized[$eventName] = [];
                }

                // Only show approved_by if status is 'approved'
                $status = strtoupper($row['status'] ?? '');
                $approvedBy = ($status === 'APPROVED' && !empty($row['approved_by'])) 
                    ? $row['approved_by'] 
                    : null;
                
                $organized[$eventName][] = [
                    'requirement' => $row['requirement_name'],
                    'submission_date' => $row['submission_date'],
                    'approved_by' => $approvedBy
                ];
            }

            return $organized;
        } catch (PDOException $e) {
            error_log("Error fetching requirements list: " . $e->getMessage());
            return false;
        }
    }
}
?>