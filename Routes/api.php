<?php
namespace Routes;

require_once '../config/database.php';

use Controllers\AdminController;
use Controllers\DocumentController;
use Controllers\EventController;
use Controllers\RequirementController;
use Controllers\StudentController;
use Controllers\SubmissionController;
use Controllers\CommentController;
use Controllers\NotificationController;
use Controllers\MailController;
use Controllers\ProfileController;
use Controllers\AnnouncementController;
use Controllers\StaffController;

require_once __DIR__ . '/../vendor/autoload.php';

class Api {
    private $adminController;
    private $documentController;
    private $requirementController;
    private $studentController;
    private $eventController;
    private $submissionController;
    private $commentController;
    private $notificationController;
    private $mailController;
    private $profileController;
    private $announcementController;
    private $staffController;
    private $middleware = [];

    public function __construct($db) {
        $this->adminController = new AdminController($db);
        $this->documentController = new DocumentController($db);
        $this->requirementController = new RequirementController($db);
        $this->studentController = new StudentController($db);
        $this->eventController = new EventController($db);
        $this->submissionController = new SubmissionController($db);
        $this->commentController = new CommentController($db);
        $this->notificationController = new NotificationController($db);
        $this->mailController = new MailController($db);
        $this->profileController = new ProfileController($db);
        $this->announcementController = new AnnouncementController($db);
        $this->staffController = new StaffController($db);
    }

    public function use($middleware) {
        $this->middleware[] = $middleware;
        return $this;
    }

    private function executeMiddleware($request) {
        $middlewareStack = $this->middleware;
        
        $next = function($request) {
            return $request;
        };
        
        while ($middleware = array_pop($middlewareStack)) {
            $next = function($request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }
        
        return $next($request);
    }

    public function route($path, $method) {
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $_POST = $input;
            }
        }

        $endpoint = implode('/', $path);
        
        switch ($endpoint) {
            // Admin Routes
            case 'admin/register':
                return $this->adminController->register();
            case 'admin/profile':
                return $this->adminController->getAdmin();
            case 'admin/login':
                return $this->adminController->login();
            case 'admin/logout':
                return $this->adminController->logout();
            case 'admin/request-otp':
                return $this->adminController->requestOtp();
            case 'admin/verify-otp':
                return $this->adminController->verifyOTP();
            case 'admin/change-password':
                return $this->adminController->changePassword();

            // Student Routes
            case 'student/emails':
                return $this->studentController->getStudentEmails();
            case 'student/register':
                return $this->studentController->register();
            case 'student/profile':
                return $this->studentController->getStudentProfile();
            case 'student/all-students':
                return $this->studentController->getStudent();
            case 'student/login':
                return $this->studentController->login();
            case 'student/logout':
                return $this->studentController->logout();
            case 'student/send-otp':
                return $this->studentController->sendOTP();
            case 'student/verify-otp':
                return $this->studentController->verifyOTP();
            case 'student/change-password':
                return $this->studentController->changePassword();
            
            //Staff Routes
            case 'staff/register':
                return $this->staffController->register();
            case 'staff/profile':
                return $this->staffController->getStaff();
            case 'staff/login':
                return $this->staffController->login();
            case 'staff/logout':
                return $this->staffController->logout();
            case 'staff/send-otp':
                return $this->staffController->requestOTP();
            case 'staff/verify-otp':
                return $this->staffController->verifyOTP();
            case 'staff/change-password':
                return $this->staffController->changePassword();

              
            // Profile Routes
            case 'profile/get':
                if ($method === 'GET') {
                    return $this->profileController->getProfile();
                }
                break;

            case 'profile/update':
                if ($method === 'POST') {
                    return $this->profileController->updateProfile();
                }
                break;

            case 'profile/departments':
                if ($method === 'GET') {
                    return $this->profileController->getDepartments();
                }
                break;

            case 'profile/programs':
                if ($method === 'GET') {
                    return $this->profileController->getPrograms();
                }
                break;

            case 'profile/all':
                if ($method === 'GET') {
                    return $this->profileController->getAllProfiles();
                }
                break;

            // Document Routes
            case 'documents/admin':
                if ($method === 'GET') {
                    return $this->documentController->getAllDocumentsByAdmin();
                }
                break;

            case 'documents/staff':
                if ($method === 'GET') {
                    return $this->documentController->getAllDocumentsByStaff();
                }

            case 'documents/student':
                if ($method === 'GET') {
                    return $this->documentController->getDocumentsByStudent();
                }
                break;

            case 'documents/upload':
                if ($method === 'POST') {
                    return $this->documentController->uploadDocument();
                }
                break;


            case 'documents/submit':
                if ($method === 'POST') {
                    return $this->documentController->submitDocument();
                }
                break;

            case 'documents/submit-multiple':
                if ($method === 'POST') {
                     return $this->documentController->submitMultiple();
                }
                break;
    

            case 'documents/unsubmit':
                if ($method === 'POST') {
                    return $this->documentController->unsubmitDocument();
                }
                break;

            case 'documents/unsubmit-multiple':
                if ($method === 'POST') {
                     return $this->documentController->unsubmitMultiple();
                 }
                break;

            case 'documents/delete':
                if ($method === 'DELETE') {
                    return $this->documentController->deleteDocument();
                }
                break;

            case (preg_match('/^documents\/status\/(\d+)$/', $endpoint, $matches) ? $endpoint : !$endpoint):
                if ($method === 'GET') {
                    return $this->documentController->getDocumentStatus($matches[1]);
                }
                break;

            // Comment Routes

            case 'comments/id':
                if ($method === 'GET') {
                    echo $this->commentController->getComment();
                    return;
                }
                break;

            case 'comments/add':
                if ($method === 'POST') {
                    echo $this->commentController->addComment();
                    return;
                }
                break;

            case 'comments/all':
                if ($method === 'GET') {
                    echo $this->commentController->getAllComments();
                    return;
                }
                break;
                
            case 'comments/get':
                if ($method === 'GET') {
                    $requirement_id = isset($_GET['requirement_id']) ? $_GET['requirement_id'] : null;
                    $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
                    
                    if (!$requirement_id || !$student_id) {
                        http_response_code(400);
                        echo json_encode(['message' => 'Missing requirement_id or student_id parameter']);
                        return;
                    }
                    
                    $this->commentController->getConversation();
                    return;
                }
                break;

            case 'comments/admin':
                if ($method === 'GET') {
                    $requirement_id = isset($_GET['requirement_id']) ? $_GET['requirement_id'] : null;
                        
                    if (!$requirement_id) {
                        http_response_code(400);
                        echo json_encode(['message' => 'Missing requirement_id parameter']);
                        return;
                    }
                        
                    $this->commentController->getCommentsForAdmin();
                    return;
                }
                break;

            case 'comments/update':
                if ($method === 'PUT') {
                    echo $this->commentController->updateComment();
                    return;
                }
                break;

            case 'comments/delete':
                if ($method === 'DELETE') {
                    echo $this->commentController->deleteComment();
                    return;
                }
                break;

            // Requirement Routes
            case 'requirements/get':
                if ($method === 'GET') {
                    return $this->requirementController->getRequirements();
                }
                break;

            case 'requirements/add':
                if ($method === 'POST') {
                    return $this->requirementController->createRequirement();
                } elseif ($method === 'GET') {
                    return $this->requirementController->getRequirementsByEventId();
                }
                break;

            case 'requirements/delete':
                if ($method === 'DELETE') {
                    return $this->requirementController->deleteRequirement();
                }
                break;
            
            case 'requirements/edit':
                if ($method === 'GET') {
                    return $this->requirementController->getRequirementById();
                }
                elseif ($method === 'PUT'){
                    return $this->requirementController->editRequirement();
                }
                break;

            // Event Routes
            case 'event/add':
                if ($method === 'POST') {
                    return $this->eventController->createEvent();
                }
                break;

            case 'event/get':
                if ($method === 'GET') {
                    return $this->eventController->getEvent();
                }
                break;

            case "event/edit":
                if ($method === 'PUT') {
                    return $this->eventController->editEvent();
                } elseif ($method === 'GET') {
                    return $this->eventController->getEventById();
                }
                break;

            case 'event/delete':
                if ($method === 'DELETE') {
                    return $this->eventController->deleteEvent();
                }
                break;

            // Submission Routes
            case 'submission/update':
                if ($method === 'PUT') {
                    return $this->submissionController->updateSubmissionStatus();
                } elseif ($method === 'GET') {
                    return $this->submissionController->getAllSubmissions();
                }
                break;

            case 'submission/detail':
                if ($method === 'GET') {
                    return $this->submissionController->getSubmissionsBySubId();
                }
                break;

            case 'notification/get':
                if ($method === 'GET') {
                    return $this->notificationController->getAdminNotifications();
                }
                break;

            case 'notification/mark-student':
                if ($method === 'PUT') {
                    return $this->notificationController->markAllStudentRead();
                }
                break;

            case 'notification/mark':
                if ($method === 'PUT') {
                    return $this->notificationController->markAllAdminRead();
                }
                break;

            case 'notification/count':
                if ($method === 'GET') {
                    return $this->notificationController->getAdminUnreadCount();
                }
                break;

            case 'notification/count-student':
                if ($method === 'GET') {
                    return $this->notificationController->getStudentUnreadCount();
                }
                break;
            
            case 'notification/student':
                if ($method === 'GET') {
                    return $this->notificationController->getStudentNotifications();
                }

            case (preg_match('/^notification\/edit$/', $endpoint) ? $endpoint : !$endpoint):
                if ($method === 'PUT') {
                    return $this->notificationController->toggleAdminNotification();
                }
                break;

            case (preg_match('/^notification\/edit-student$/', $endpoint) ? $endpoint : !$endpoint):
                if ($method === 'PUT') {
                    return $this->notificationController->toggleStudentNotification();
                }
                break;

            case 'notification/staff':
                if ($method === 'GET') {
                    return $this->notificationController->getStaffNotifications();
                }
                break;
        
            case (preg_match('/^notification\/edit-staff$/', $endpoint) ? $endpoint : !$endpoint):
                if ($method === 'PUT') {
                    return $this->notificationController->toggleStaffNotification();
                }
                break;
                
            case 'notification/staff/unread':
                if ($method === 'GET') {
                    return $this->notificationController->getStaffUnreadCount();
                }
                break;
                
            case 'notification/staff/mark-all':
                if ($method === 'PUT') {
                    return $this->notificationController->markAllStaffRead();
                }
                break;

            
            //mail send
            case 'mail/send':
                if ($method === 'POST') {
                    return $this->mailController->sendEmail();
                }

            case 'announcements/get':
                if ($method === 'GET') {
                    return $this->announcementController->getAllAnnouncements();
                }
                break;

            case 'announcements/add':
                if ($method === 'POST') {
                    return $this->announcementController->createAnnouncement();
                }
                break;
            
            case 'announcements/student':
                if ($method === 'GET') {
                    return $this->announcementController->getStudentNotifications();
                }
                break;

            case 'announcements/update':
                if ($method === 'PUT') {
                    return $this->announcementController->updateAnnouncement();
                }
                break;

            case 'announcements/delete':
                if ($method === 'DELETE') {
                    return $this->announcementController->deleteAnnouncement();
                }
                break;
                
            case (preg_match('/^uploads\/(.+)$/', $endpoint, $matches) ? $endpoint : !$endpoint):
                if ($method === 'GET') {
                    $filename = basename($matches[1]);
                    $filePath = __DIR__.'/../../Public/uploads/'.$filename;
                        
                    // Security checks
                    if (!file_exists($filePath)) {
                        http_response_code(404);
                        return json_encode(["message" => "File not found"]);
                    }
                        
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'gif'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                    if (!in_array($ext, $allowedTypes)) {
                        http_response_code(403);
                        return json_encode(["message" => "File type not allowed"]);
                    }
                
                    // Serve the file
                    header('Content-Type: '.mime_content_type($filePath));
                    header('Content-Length: ' . filesize($filePath));
                    readfile($filePath);
                    exit;
                }
                break;

            default:
                http_response_code(404);
                return json_encode([
                    "message" => "Endpoint not found",
                    "endpoint" => $endpoint,
                    "method" => $method,
                    "path" => $path
                ]);
        }
    }
}
?>