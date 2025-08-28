<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function validateForm() {
            const newPassword = document.getElementById("new_password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const errors = [];

            // Password validation
            if (newPassword.length < 8) {
                errors.push("Password must be at least 8 characters long.");
            }
            if (!/[A-Z]/.test(newPassword)) {
                errors.push("Password must contain at least one uppercase letter.");
            }
            if (!/[0-9]/.test(newPassword)) {
                errors.push("Password must contain at least one number.");
            }
            if (!/[!@#$%^&*]/.test(newPassword)) {
                errors.push("Password must contain at least one special character.");
            }

            // Confirm password validation
            if (newPassword !== confirmPassword) {
                errors.push("Passwords do not match.");
            }

            // Display errors
            if (errors.length > 0) {
                alert(errors.join("\n"));
                return false;
            }
            return true;
        }
    </script>
</head>

<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow-sm" style="width: 400px;">
        <h3 class="text-center">Reset Password</h3>
        
        <!-- Success Message -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" onsubmit="return validateForm()">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="mb-3">
                <input type="password" name="current_password" class="form-control" placeholder="Current Password" required>
            </div>
            <div class="mb-3">
                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="New Password" required>
            </div>
            <div class="mb-3">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
        </form>
    </div>
</body>

</html>