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
    <div class="container mt-4">
        <h1 class="text-center text-primary mb-4"><b>YDF Costing</b></h1>
        <button type="button" class="btn btn-warning mb-3" data-bs-toggle="modal" data-bs-target="#exchangeRateModal">
            <i class="fas fa-dollar-sign"></i> Change Exchange Rate
        </button>
        <!-- Exchange Rate Modal -->
        <div class="modal fade" id="exchangeRateModal" tabindex="-1" aria-labelledby="exchangeRateModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Change Exchange Rate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="modal-body row g-3">
                            <div class="col-md-12">
                                <label>USD to LKR: </label>
                                <input type="number" name="newUsdToLkrRate" class="form-control" min="0" step="any" required
                                    value="<?php echo htmlspecialchars($current['UsdToLkr']); ?>">
                                <div class="invalid-feedback">Enter valid exchange rate.</div>
                            </div>
                            <div class="col-md-12">
                                <label>EUR to LKR: </label>
                                <input type="number" name="newEurToLkrRate" class="form-control" min="0" step="any" required
                                    value="<?php echo htmlspecialchars($current['EurToLkr']); ?>">
                                <div class="invalid-feedback">Enter valid exchange rate.</div>
                            </div>
                            <div class="col-md-12">
                                <label>GBP to LKR: </label>
                                <input type="number" name="newGbpToLkrRate" class="form-control" min="0" step="any" required
                                    value="<?php echo htmlspecialchars($current['GbpToLkr']); ?>">
                                <div class="invalid-feedback">Enter valid exchange rate.</div>
                            </div>
                            <div class="col-md-12">
                                <label>USD to GBP: </label>
                                <input type="number" name="newUsdToGbpRate" class="form-control" min="0" step="any" required
                                    value="<?php echo htmlspecialchars($current['UsdToGbp']); ?>">
                                <div class="invalid-feedback">Enter valid exchange rate.</div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="exchangeratereplace" class="btn btn-primary px-4">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--Table-->
        <div class="table-responsive rounded shadow-sm mt-4 mb-4">
            <table id="ydfCostingTable" class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-dark">
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
                        <th>Packaging Cost</th>
                        <th>Freight Cost</th>
                        <th>Estimated Gross to Net Ratio</th>
                        <th>Freight Cost for the Gross</th>
                        <th>CNF</th>
                        <th>Margin</th>
                        <th>Price</th>
                        <th>Price 500g</th>
                        <th>Rounded Price</th>
                        <th>Price + MCO</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($costingData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product_code']); ?></td>
                        <td><?= htmlspecialchars($row['product_name']); ?></td>
                        <td><?= htmlspecialchars($row['buyingprice']); ?></td>
                        <td><?= htmlspecialchars($row['volume']); ?></td>
                        <td><?= htmlspecialchars($row['expectedyield'], ENT_QUOTES, 'UTF-8'); ?>%</td>
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
                        <td>$
                            <?php 
                                $usdPrice = $exchangeRate > 0 ? $totalCost / $exchangeRate : 0;
                                echo htmlspecialchars(number_format($usdPrice, 2));
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['processingcharge']); ?></td>
                        <td><?= htmlspecialchars($row['packagecost']); ?></td>
                        <td>6</td>
                        <td>25%</td>
                        <td>
                            <?php 
                                $freightCostGross = 6 * 1.25;
                                echo htmlspecialchars(number_format($freightCostGross, 2));
                            ?>
                        </td>
                        <?php $cnf = ($usdPrice + $row['processingcharge'] + $row['packagecost'] + 7.5); ?>
                        <td>$<?= htmlspecialchars(number_format($cnf, 2)); ?></td>
                        <td>1</td>
                        <td>$
                            <?php 
                                $margin = 1; // 20%
                                $price = $cnf + 1;
                                echo htmlspecialchars(number_format($price, 2));
                            ?>
                        </td>
                        <td>$<?= htmlspecialchars(number_format(($price / 2), 2)); ?></td>
                        <td>$
                            <?php
                                $roundedPrice = ceil(($price / 2) * 20) / 20;
                                echo htmlspecialchars(number_format($roundedPrice, 2));
                            ?>
                        </td>
                        <td>$</td>
                        <td>
                            <button class="btn btn-sm btn-primary">Edit</button>
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#ydfCostingTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true,
                "order": [[0, 'asc']],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
        });
    </script>
</body>
</html>