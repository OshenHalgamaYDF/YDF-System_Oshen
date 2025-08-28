<?php
require_once APP_PATH . '/core/Controller.php';

class AccessController extends Controller {
    public function users() {
        // Check if user is logged in and is admin
        requireAdmin();
        
        // Special requirement: Only allow access to user with email "sithumini@ydf.lk"
        if ($_SESSION['email'] !== 'sithumini@ydf.lk') {
            http_response_code(403);
            $this->view('errors/403', [
                'pageTitle' => 'Access Denied'
            ]);
            exit();
        }
        
        $conn = getDBConnection();
        $message = '';
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $message = "Invalid form submission";
            } else {
                if (isset($_POST['create_user'])) {
                    // Create new user
                    $name = trim($_POST['name']);
                    $email = trim($_POST['email']);
                    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                    $role = $_POST['role'];
                    
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $name, $email, $password, $role);
                    
                    if ($stmt->execute()) {
                        $message = "User created successfully!";
                    } else {
                        $message = "Error creating user: " . $conn->error;
                    }
                } elseif (isset($_POST['update_user'])) {
                    // Update user role
                    $id = $_POST['id'];
                    $role = $_POST['role'];
                    
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->bind_param("si", $role, $id);
                    
                    if ($stmt->execute()) {
                        $message = "User updated successfully!";
                    } else {
                        $message = "Error updating user: " . $conn->error;
                    }
                } elseif (isset($_POST['delete_user'])) {
                    // Delete user
                    $id = $_POST['id'];
                    
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $message = "User deleted successfully!";
                    } else {
                        $message = "Error deleting user: " . $conn->error;
                    }
                }
            }
        }
        
        // Search functionality
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $query = "SELECT id, name, email, role FROM users";
        
        if (!empty($search)) {
            $query .= " WHERE name LIKE ? OR email LIKE ?";
            $searchTerm = "%$search%";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
        } else {
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        
        $this->view('access/users', [
            'users' => $users,
            'search' => $search,
            'message' => $message,
            'pageTitle' => 'User Management',
            'csrf_token' => generateCsrfToken()
        ]);
    }
}