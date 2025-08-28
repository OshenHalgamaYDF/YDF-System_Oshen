<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet">
    <style>
        /* Your existing login styles here */
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow-sm" style="width: 400px;">
        <!-- Logo -->
        <div class="text-center mb-4">
            <img src="<?php echo BASE_URL; ?>/assets/img/ydf.png" alt="YDF Logo" style="width: 100px; height: auto;">
        </div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success text-center"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" id="loginForm">
            <div class="mb-3 input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-control ps-5" placeholder="Email" required>
            </div>
            <div class="mb-3 input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="password" class="form-control ps-5" placeholder="Password" required>
                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="loginButton">
                <span id="loginText">Login</span>
                <span id="loginSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
            <div class="mt-3 text-center">
                <a href="<?php echo BASE_URL; ?>/register" class="text-decoration-none">Don't have an account? Register</a>
            </div>
        </form>
    </div>

    <script>
        // Your existing JavaScript code here
    </script>
</body>
</html>