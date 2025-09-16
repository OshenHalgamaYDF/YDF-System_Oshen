<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\buyingpriceanalysis\BuyingPriceAnalysisController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buying Price Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Buying Price Summary</h1>
        <button class="btn btn-primary mb-3" onclick="window.location.href='BuyingPriceAnalysis.php'">
            &#x2B05; Back
        </button>
        <table id="buyingPriceTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product Name</th>
                    <th>Buyer Name</th>
                    <th>Buying Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buyingPrices as $price): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($price['date']); ?></td>
                        <td><?php echo htmlspecialchars($price['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($price['buyer_name']); ?></td>
                        <td><?php echo htmlspecialchars($price['buying_price']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        $(document).ready(function() {
            $('#buyingPriceTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true
            });
        });
    </script>
</body>
</html>
