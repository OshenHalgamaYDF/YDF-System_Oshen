<?php 
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\offel\OffelController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offel Income</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="text-center text-primary mb-4">Offel Income Records</h1>
    
    <div class="card shadow p-4">
        <h4 class="mb-3">Received Amounts</h4>
        <div class="table-responsive">
            <?php if ($result && $result->num_rows > 0): ?>
            <table class="table table-striped table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Date']) ?></td>
                            <td><?= number_format($row['Amount'], 2) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="text-center text-muted py-4">No income records found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
