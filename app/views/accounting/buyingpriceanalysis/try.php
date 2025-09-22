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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <style>
        .table th {
            white-space: nowrap;
        }
        .dataTables_wrapper .dataTables_filter {
            float: right;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4 text-primary">Buying Price Summary</h1>
        <button class="btn btn-primary mb-3" onclick="window.location.href='BuyingPriceAnalysis.php'">
            <i class="fas fa-arrow-left"></i> Back to Analysis
        </button>

        <!-- Month Filter -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Filter Data</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="yearFilter" class="form-label">Select Year:</label>
                        <select class="form-select" id="yearFilter">
                            <?php
                            // Get distinct years
                            $yearSql = "SELECT DISTINCT YEAR(STR_TO_DATE(date, '%Y-%m-%d')) as year 
                                       FROM buyingpriceanlaysistable 
                                       ORDER BY year DESC";
                            $yearResult = $conn->query($yearSql);
                            $currentYear = date('Y');
                            
                            echo '<option value="all">All Years</option>';
                            if ($yearResult && $yearResult->num_rows > 0) {
                                while ($yearRow = $yearResult->fetch_assoc()) {
                                    $selected = ($yearRow['year'] == $currentYear) ? 'selected' : '';
                                    echo "<option value='{$yearRow['year']}' $selected>{$yearRow['year']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="productFilter" class="form-label">Filter by Product:</label>
                        <select class="form-select" id="productFilter">
                            <option value="all">All Products</option>
                            <?php
                            // Get distinct products
                            $productSql = "SELECT DISTINCT product_code, product_name 
                                         FROM buyingpriceanlaysistable 
                                         ORDER BY product_name";
                            $productResult = $conn->query($productSql);
                            if ($productResult && $productResult->num_rows > 0) {
                                while ($productRow = $productResult->fetch_assoc()) {
                                    echo "<option value='{$productRow['product_code']}'>" . 
                                         htmlspecialchars($productRow['product_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <table id="buyingPriceTable" class="table table-striped table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th rowspan="2" class="text-center align-middle">Product Code</th>
                    <th rowspan="2" class="text-center align-middle">Product Name</th>
                    <th rowspan="2" class="text-center align-middle">Scientific Name</th>
                    <th rowspan="2" class="text-center align-middle">Size Range</th>
                    <th rowspan="2" class="text-center align-middle">Target Price</th>
                    <th colspan="12" class="text-center align-middle">Monthly Average Prices</th>
                    <th rowspan="2" class="text-center align-middle">Yearly Average</th>
                </tr>
                <tr>
                    <th class="text-center">Jan</th>
                    <th class="text-center">Feb</th>
                    <th class="text-center">Mar</th>
                    <th class="text-center">Apr</th>
                    <th class="text-center">May</th>
                    <th class="text-center">Jun</th>
                    <th class="text-center">Jul</th>
                    <th class="text-center">Aug</th>
                    <th class="text-center">Sep</th>
                    <th class="text-center">Oct</th>
                    <th class="text-center">Nov</th>
                    <th class="text-center">Dec</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Get distinct products with their details
                $productSql = "SELECT DISTINCT p.product_code, p.product_name, p.scientific_name, p.size_range, 
                              COALESCE(tbp.target_buying_price, 0) as target_price
                              FROM buyingpriceanlaysistable p
                              LEFT JOIN target_buying_price tbp ON p.product_code = tbp.product_code
                              ORDER BY p.product_name";
                
                $productResult = $conn->query($productSql);
                
                if ($productResult && $productResult->num_rows > 0) { 
                    while ($product = $productResult->fetch_assoc()) {
                        $productCode = $product['product_code'];
                        
                        echo "<tr>
                                <td>{$product['product_code']}</td>
                                <td>{$product['product_name']}</td>
                                <td>{$product['scientific_name']}</td>
                                <td>{$product['size_range']}</td>
                                <td class='text-end'>" . number_format($product['target_price'], 2) . "</td>";
                        
                        // Get monthly averages for this product
                        $monthlyAverages = [];
                        $yearlyTotal = 0;
                        $monthCount = 0;
                        
                        for ($month = 1; $month <= 12; $month++) {
                            $monthSql = "SELECT AVG(sold_price) as avg_price 
                                        FROM buyingpriceanlaysistable 
                                        WHERE product_code = '$productCode' 
                                        AND MONTH(STR_TO_DATE(date, '%Y-%m-%d')) = $month
                                        AND YEAR(STR_TO_DATE(date, '%Y-%m-%d')) = YEAR(CURDATE())";
                            
                            $monthResult = $conn->query($monthSql);
                            $avgPrice = 0;
                            
                            if ($monthResult && $monthRow = $monthResult->fetch_assoc()) {
                                $avgPrice = $monthRow['avg_price'] ? floatval($monthRow['avg_price']) : 0;
                            }
                            
                            $monthlyAverages[$month] = $avgPrice;
                            if ($avgPrice > 0) {
                                $yearlyTotal += $avgPrice;
                                $monthCount++;
                            }
                            
                            echo "<td class='text-end'>" . ($avgPrice > 0 ? number_format($avgPrice, 2) : '-') . "</td>";
                        }
                        
                        // Calculate yearly average
                        $yearlyAverage = $monthCount > 0 ? $yearlyTotal / $monthCount : 0;
                        echo "<td class='text-end fw-bold'>" . ($yearlyAverage > 0 ? number_format($yearlyAverage, 2) : '-') . "</td>";
                        
                        echo "</tr>";
                    }
                } else { 
                    echo "<tr><td colspan='17' class='text-center'>No records found</td></tr>"; 
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#buyingPriceTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true,
                "order": [[1, 'asc']], // Sort by product name
                "dom": '<"top"lf>rt<"bottom"ip><"clear">'
            });

            // Year filter functionality
            $('#yearFilter').on('change', function() {
                // This would typically reload the page with the selected year
                // For now, we'll just filter the existing data
                var year = $(this).val();
                if (year === 'all') {
                    table.search('').draw();
                } else {
                    table.search(year).draw();
                }
            });

            // Product filter functionality
            $('#productFilter').on('change', function() {
                var productCode = $(this).val();
                if (productCode === 'all') {
                    table.search('').draw();
                } else {
                    table.columns(0).search(productCode).draw();
                }
            });

            // Add custom search box for the table
            $('#buyingPriceTable_filter').prepend('<label>Search: <input type="search" class="form-control form-control-sm" placeholder="Search products..." /></label> ');
        });
    </script>
</body>
</html>