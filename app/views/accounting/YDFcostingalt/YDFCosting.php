<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\YDFcostingalt\YDFCostingController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YDF Costing</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1>YDF Costing</h1>
        <table id="ydfCostingTable" class="table table-bordered">
            <thead class="table-dark table-striped table-hover">
                <tr>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Buying Price</th>
                    <th>Volume</th>
                    <th>Expected Yield</th>
                    <th>Processing Cost</th>
                    <th>Buying and Logistics Cost</th>
                    <th>Total</th>
                    <th>Exchange Rate</th>
                    <th>USD Price</th>
                    <th>Processing</th>
                    <th>Freight Cost</th>
                    <th>Estimated Gross to Net Ratio</th>
                    <th>Freight Cost for the Gross</th>
                    <th>CNF</th>
                    <th>Margin</th>
                    <th>Price</th>
                    <th>Price 500g</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($costingData as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['product_code']); ?></td>
                    <td><?= htmlspecialchars($row['product_name']); ?></td>
                    <td><?= htmlspecialchars($row['buyingprice']); ?></td>
                    <td><?= htmlspecialchars($row['volume']); ?></td>
                    <td><?= htmlspecialchars($row['expectedyield']); ?></td>
                    <td>
                        <?php 
                            $processingCost = $row['buyingprice'] / ($row['expectedyield'] / 100);
                            echo htmlspecialchars(number_format($processingCost, 2));
                        ?>
                    </td>
                    <td>50</td>
                    <td>
                        <?php 
                            $totalCost = $processingCost + 50;
                            echo htmlspecialchars(number_format($totalCost, 2));
                        ?>
                    </td>
                    <td><?= htmlspecialchars($exchangeRate); ?></td>
                    <td>
                        <?php 
                            $usdPrice = $totalCost / $exchangeRate;
                            echo htmlspecialchars(number_format($usdPrice, 2));
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['processingcharge']); ?></td>
                    <td>6</td>
                    <td>25%</td>
                    <td>6 * 1.25 = 7.5</td>
                    <td><?php echo (($exchangeRate) + ($row['processingcharge']) + 7.5); ?></td>
                    <td>1</td>
                    <td>(CNF) * (1 + Margin/100)</td>
                    <td>(Price) / (Volume in g) * 500</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>