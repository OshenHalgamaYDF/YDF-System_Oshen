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
        .year-filter-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4 text-primary">Buying Price Summary</h1>
        <button class="btn btn-primary mb-3" onclick="window.location.href='BuyingPriceAnalysis.php'">
            <i class="fas fa-arrow-left"></i> Back to Analysis
        </button>

        <!-- Year Filter -->
        <div class="year-filter-container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label for="yearFilter" class="form-label fw-bold">Select Year:</label>
                    <select class="form-select" id="yearFilter" onchange="changeYear(this.value)">
                        <?php foreach ($availableYears as $year): 
                            $isSelected = ($year == $selectedYear) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $year; ?>" <?php echo $isSelected; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8 row align-items-center">
                    <div class="d-flex  justify-content-md-end mt-3 mt-md-0">
                        <span class="fw-bold me-3">Showing data for:</span>
                        <span class="badge bg-primary">
                            <i class="fas fa-calendar me-2"></i><?php echo $selectedYear; ?>
                        </span>
                        <span class="ms-3 text-muted">
                            <i class="fas fa-boxes me-1"></i>
                            <?php echo count($summaryProducts); ?> product(s)
                        </span>
                        <?php if (!empty($months)): ?>
                            <span class="ms-3 text-muted">
                                <i class="fas fa-chart-bar me-1"></i>
                                <?php echo count($months); ?> month(s)
                            </span>
                        <?php endif; ?>
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
                    <?php if (!empty($months)): ?>
                        <th colspan="<?php echo count($months); ?>" class="text-center align-middle">Monthly Average Prices (<?php echo $selectedYear; ?>)</th>
                    <?php else: ?>
                        <th class="text-center align-middle">Monthly Average</th>
                    <?php endif; ?>
                    <th rowspan="2" class="text-center align-middle">Yearly Average</th>
                </tr>
                <tr>
                    <?php 
                    if (!empty($months)) {
                        foreach ($months as $month) {
                            echo "<th class='text-center' title='{$month['month_code']}'>" . 
                                 substr($month['month_name'], 0, 3) . "</th>";
                        }
                    } else {
                        echo "<th class='text-center'>No Data</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($summaryProducts)) {
                    foreach ($summaryProducts as $product) {
                        $productCode = $product['product_code'];
                        $productName = $product['product_name'];
                        $scientificName = $product['scientific_name'];
                        
                        echo "<tr>
                                <td>{$productCode}</td>
                                <td>{$productName}</td>
                                <td>{$scientificName}</td>";
                        
                        $monthlyAverages = [];
                        $totalAverage = 0;
                        $monthCount = 0;
                        
                        // Get monthly averages for this product
                        if (!empty($months)) {
                            foreach ($months as $month) {
                                $monthCode = $month['month_code'];
                                
                                // FIXED: Remove STR_TO_DATE since date is already in proper format
                                $avgSql = "SELECT AVG(sold_price) as avg_price 
                                          FROM buyingpriceanlaysistable 
                                          WHERE product_code = '{$productCode}' 
                                          AND DATE_FORMAT(date, '%Y-%m') = '{$monthCode}'
                                          AND YEAR(date) = $selectedYear";
                                
                                $avgResult = $conn->query($avgSql);
                                $avgPrice = 0;
                                
                                if ($avgResult && $avgRow = $avgResult->fetch_assoc()) {
                                    $avgPrice = $avgRow['avg_price'] ? floatval($avgRow['avg_price']) : 0;
                                }
                                
                                if ($avgPrice > 0) {
                                    $totalAverage += $avgPrice;
                                    $monthCount++;
                                }
                                
                                echo "<td class='text-end'>" . ($avgPrice > 0 ? number_format($avgPrice, 2) : '-') . "</td>";
                            }
                        } else {
                            echo "<td class='text-center'>-</td>";
                        }
                        
                        // Calculate overall average
                        $overallAverage = $monthCount > 0 ? $totalAverage / $monthCount : 0;
                        echo "<td class='text-end fw-bold'>" . ($overallAverage > 0 ? number_format($overallAverage, 2) : '-') . "</td>";
                        
                        echo "</tr>";
                    }
                } else {
                    $colspan = !empty($months) ? count($months) + 4 : 5;
                    echo "<tr><td colspan='{$colspan}' class='text-center'>No products found for selected year</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function changeYear(year) {
            // Redirect to the same page with the selected year
            window.location.href = 'BuyingPriceSummary.php?year=' + year;
        }

        $(document).ready(function() {
            $('#buyingPriceTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true,
                "order": [[1, 'asc']], // Sort by product name
                "dom": '<"top"lf>rt<"bottom"ip><"clear">',
                "language": {
                    "search": "Search products:",
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