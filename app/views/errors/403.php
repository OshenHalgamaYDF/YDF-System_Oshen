<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - YDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #FE8E00, #2575fc, #6BC400);
            color: #fff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #ffc107;
            margin-bottom: 0;
        }
        .error-message {
            color: #343a40;
            margin-bottom: 20px;
        }
        .btn-home {
            border-radius: 10px;
            padding: 10px 20px;
            background: #2575fc;
            border: none;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-home:hover {
            background: #1a5bbf;
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <h1 class="error-code">403</h1>
        <h2 class="error-message">Access Denied</h2>
        <p class="text-muted mb-4">You don't have permission to access this page.</p>
        <a href="<?php echo BASE_URL; ?>/" class="btn btn-home">
            <i class="fas fa-home me-2"></i>Go Back Home
        </a>
    </div>
</body>
</html>