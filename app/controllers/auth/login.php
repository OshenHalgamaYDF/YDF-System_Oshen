<?php
// app/controllers/auth/login.php

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/");
    exit();
}

// Include database connection
$conn = getDBConnection();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $sql = "SELECT id, name, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_logged_in'] = true;
                $_SESSION['name'] = $user['name'];
                
                // Redirect to home page
                header("Location: " . BASE_URL . "/");
                exit();
            } else {
                $error = "Invalid credentials!";
            }
        } else {
            $error = "User not found!";
        }
        
        $stmt->close();
    }
}

// Display the login form
require APP_PATH . '/views/auth/login.php';