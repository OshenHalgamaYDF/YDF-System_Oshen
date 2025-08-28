<?php
require_once APP_PATH . '/core/Controller.php';

class AuthController extends Controller {
    public function login() {
        // If already logged in, redirect to home
        if (isLoggedIn()) {
            $this->redirect('');
        }
        
        $error = '';
        $conn = getDBConnection();
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            // Validate CSRF token
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $error = "Invalid form submission";
            } else {
                $sql = "SELECT * FROM users WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($user = $result->fetch_assoc()) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        
                        $this->redirect('');
                    } else {
                        $error = "Invalid credentials!";
                    }
                } else {
                    $error = "User not found!";
                }
            }
        }
        
        $this->view('auth/login', [
            'error' => $error,
            'csrf_token' => generateCsrfToken()
        ]);
    }
    
    public function register() {
        // If already logged in, redirect to home
        if (isLoggedIn()) {
            $this->redirect('');
        }
        
        $errors = [];
        $conn = getDBConnection();
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Validate CSRF token
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $errors[] = "Invalid form submission";
            } else {
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validate name
                if (empty($name)) {
                    $errors['name'] = "Name is required.";
                }
                
                // Validate email
                if (empty($email)) {
                    $errors['email'] = "Email is required.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = "Invalid email format.";
                } else {
                    // Check if email already exists
                    $sql = "SELECT id FROM users WHERE email = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $errors['email'] = "Email already exists.";
                    }
                }
                
                // Validate password
                if (empty($password)) {
                    $errors['password'] = "Password is required.";
                } elseif (strlen($password) < 8) {
                    $errors['password'] = "Password must be at least 8 characters long.";
                } elseif (!preg_match("/[A-Z]/", $password)) {
                    $errors['password'] = "Password must contain at least one uppercase letter.";
                } elseif (!preg_match("/[0-9]/", $password)) {
                    $errors['password'] = "Password must contain at least one number.";
                } elseif (!preg_match("/[!@#$%^&*]/", $password)) {
                    $errors['password'] = "Password must contain at least one special character.";
                }
                
                // Validate confirm password
                if ($password !== $confirm_password) {
                    $errors['confirm_password'] = "Passwords do not match.";
                }
                
                // If no errors, proceed with registration
                if (empty($errors)) {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $name, $email, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $this->redirect('login?message=Registration+Successful');
                    } else {
                        $errors['database'] = "Error: " . $conn->error;
                    }
                }
            }
        }
        
        $this->view('auth/register', [
            'errors' => $errors,
            'csrf_token' => generateCsrfToken()
        ]);
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        $this->redirect('login');
    }
    
    public function resetPassword() {
        requireAuth();
        
        $errors = [];
        $conn = getDBConnection();
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Validate CSRF token
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $errors[] = "Invalid form submission";
            } else {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validate current password
                if (empty($current_password)) {
                    $errors['current_password'] = "Current password is required.";
                }
                
                // Validate new password
                if (empty($new_password)) {
                    $errors['new_password'] = "New password is required.";
                } elseif (strlen($new_password) < 8) {
                    $errors['new_password'] = "Password must be at least 8 characters long.";
                } elseif (!preg_match("/[A-Z]/", $new_password)) {
                    $errors['new_password'] = "Password must contain at least one uppercase letter.";
                } elseif (!preg_match("/[0-9]/", $new_password)) {
                    $errors['new_password'] = "Password must contain at least one number.";
                } elseif (!preg_match("/[!@#$%^&*]/", $new_password)) {
                    $errors['new_password'] = "Password must contain at least one special character.";
                }
                
                // Validate confirm password
                if ($new_password !== $confirm_password) {
                    $errors['confirm_password'] = "Passwords do not match.";
                }
                
                // If no errors, proceed with password reset
                if (empty($errors)) {
                    $user_id = $_SESSION['user_id'];
                    $sql = "SELECT password FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($current_password, $user['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                        $sql = "UPDATE users SET password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $hashed_password, $user_id);
                        
                        if ($stmt->execute()) {
                            $success = "Password updated successfully!";
                            // Redirect to login page after 2 seconds
                            header("Refresh: 2; url=" . BASE_URL . "/login");
                        } else {
                            $errors['database'] = "Error updating password.";
                        }
                    } else {
                        $errors['current_password'] = "Current password is incorrect.";
                    }
                }
            }
        }
        
        $this->view('auth/reset_password', [
            'errors' => $errors,
            'success' => $success ?? '',
            'csrf_token' => generateCsrfToken()
        ]);
    }
    
    public function sessionPing() {
        header('Content-Type: application/json');
        
        if (!isLoggedIn()) {
            echo json_encode(['active' => false]);
            exit();
        }
        
        $_SESSION['last_activity'] = time();
        echo json_encode(['active' => true]);
        exit();
    }
}