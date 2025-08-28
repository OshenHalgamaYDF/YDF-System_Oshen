<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #FE8E00, #2575fc, #6BC400); /*#6a11cb, #2575fc, #FE8E00, #6BC400*/
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
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
<div class="card p-4 shadow-sm" style="width: 400px;">
    <!-- Logo -->
    <div class="text-center mb-4">
        <img src="<?php echo BASE_URL; ?>/assets/img/ydf.png" alt="YDF Logo" style="width: 100px; height: auto;">
    </div>
    <!-- <h3 class="text-center mb-4">Register</h3> -->

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Registration Form -->
    <form method="POST" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="mb-3 input-group">
            <i class="fas fa-user input-icon"></i>
            <input type="text" name="name" class="form-control ps-5" placeholder="Full Name" required>
        </div>
        <div class="mb-3 input-group">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control ps-5" placeholder="Email" required>
        </div>
        <div class="mb-3 input-group">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" id="password" class="form-control ps-5" placeholder="Password" required>
        </div>
        <div class="mb-3 input-group">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control ps-5" placeholder="Confirm Password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100" id="registerButton">
            <span id="registerText">Register</span>
            <span id="registerSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
        <div class="mt-3 text-center">
            <a href="<?php echo BASE_URL; ?>/login" class="text-decoration-none">Already have an account? Login</a>
        </div>
    </form>
</div>

<script>
    // Add loading spinner on form submission
    document.getElementById('registerForm').addEventListener('submit', function () {
        document.getElementById('registerText').classList.add('d-none');
        document.getElementById('registerSpinner').classList.remove('d-none');
        document.getElementById('registerButton').disabled = true;
    });
</script>
</body>
</html>