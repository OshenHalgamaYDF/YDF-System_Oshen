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
        body {
            background: linear-gradient(135deg, #FE8E00, #2575fc, #6BC400);
            color: #fff;
        }

        .card {
            border: none;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
        }

        .btn-primary {
            border-radius: 10px;
            padding: 10px;
            background: #2575fc;
            border: none;
        }

        .btn-primary:hover {
            background: #1a5bbf;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 15px;
            color: #6c757d;
        }

        .input-group {
            position: relative;
        }

        .alert {
            border-radius: 10px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow-sm" style="width: 400px;">
        <!-- Logo -->
        <div class="text-center mb-4">
            <img src="<?php echo BASE_URL; ?>/assets/img/ydf.png" alt="YDF Logo" style="width: 100px; height: auto;">
        </div>

        <!-- Error Message -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success text-center"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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
        // Add loading spinner on form submission
        document.getElementById('loginForm').addEventListener('submit', function () {
            document.getElementById('loginText').classList.add('d-none');
            document.getElementById('loginSpinner').classList.remove('d-none');
            document.getElementById('loginButton').disabled = true;
        });

        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        let isPasswordVisible = false;

        togglePassword.addEventListener('click', function () {
            isPasswordVisible = !isPasswordVisible; // Toggle state
            passwordInput.setAttribute('type', isPasswordVisible ? 'text' : 'password');
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>