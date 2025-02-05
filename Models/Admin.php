<?php
class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($admin_id, $email, $password) {
        $sql = "INSERT INTO admin (admin_id, email, password) VALUES (:admin_id, :email, :password)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
        return $stmt->execute();
    }

    public function login($admin_id, $password) {
        $sql = "SELECT * FROM admin WHERE admin_id = :admin_id"; // Only fetch by admin_id
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['admin_id' => $admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Check if the admin exists and verify the password
        if ($admin && password_verify($password, $admin['password'])) {
            $token = bin2hex(random_bytes(32));
            $query = "INSERT INTO admin_tokens (token, admin_id) VALUES (:token, :admin_id)";
            $stmt = $this->conn->prepare($query); // Use $this->conn here
            $stmt->execute(['token' => $token, 'admin_id' => $admin['admin_id']]);
    
            return ['token' => $token, 'admin_id' => $admin['admin_id']];
        }

        return false;
    }

    public function validateToken($token) {
        // Query to validate token and get admin info
        $query = "SELECT admin_id FROM admin_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query); // Use $this->conn here
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function logout($token) {
        $query = "DELETE FROM admin_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        return $stmt->execute(['token' => $token]);
    }

    public function emailAddress($email) {
        $query = "SELECT * FROM admin WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($admin) {
            /* TO DO: OTP AND PASSWORD CHANGE */
        }
    }
}
?>