<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get exchange rates (USD→GBP and USD→LKR)
$exchangeRateUsdtoGbp = 0;
$exchangeRateUsdtoLkr = 0;

$rateResult = $conn->query("SELECT UsdToGbp, UsdToLkr FROM exchangeratefgs ORDER BY id DESC LIMIT 1");
if ($rateResult && $rateRow = $rateResult->fetch_assoc()) {
    $exchangeRateUsdtoGbp = $rateRow['UsdToGbp'];
    $exchangeRateUsdtoLkr = $rateRow['UsdToLkr'];
}

// Get unique years from the database
$yearQuery = "SELECT DISTINCT YEAR(date) as year FROM fgscosting ORDER BY year DESC";
$yearResult = $conn->query($yearQuery);
$years = [];
while($row = $yearResult->fetch_assoc()) {
    $years[] = $row['year'];
}

// Get unique months for the current year
$currentYear = date('Y');
$monthQuery = "SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month, 
                      DATE_FORMAT(date, '%M %Y') as month_name 
               FROM fgscosting 
               WHERE YEAR(date) = '$currentYear' 
               ORDER BY month DESC";
$monthResult = $conn->query($monthQuery);
$months = [];
while($row = $monthResult->fetch_assoc()) {
    $months[] = $row;
}

// Get all data for the table
$dataQuery = "SELECT date, type, size, specification, buyingprice, volume,  
                     buyingcostandlogic, processingcharge, packagingcost, freightcost, estgrosstonet, expectedyield,
                     p.scientific_name, p.product_code, p.category, p.product_name
              FROM fgscosting 
              JOIN products p ON product_id=p.id
              ORDER BY date DESC, product_code ASC";
$dataResult = $conn->query($dataQuery);
$allData = [];
while($row = $dataResult->fetch_assoc()) {
    $allData[] = $row;
}

// Organize data by month and date for the table structure
$monthlyData = [];
$monthlyAverages = [];
$yearlyProductData = []; // For summary tab

foreach($allData as $row) {
    $month = date('Y-m', strtotime($row['date']));
    $date = date('Y-m-d', strtotime($row['date']));
    $year = date('Y', strtotime($row['date']));
    
    // --- Correct CNF Formula (in GBP) ---
    $yield = $row['expectedyield'] > 0 ? $row['expectedyield'] : 100;
    $processedCost = ($row['buyingprice'] / ($yield / 100)); // LKR
    $buyingTotal = $processedCost + $row['buyingcostandlogic']; // LKR
    $usdBase = $exchangeRateUsdtoLkr > 0 ? $buyingTotal / $exchangeRateUsdtoLkr : 0; // USD

    $freightForGross = $row['freightcost'] * (1 + ($row['estgrosstonet'] / 100)); // USD
    $cnfUsd = $usdBase + $row['processingcharge'] + $row['packagingcost'] + $freightForGross; // USD
    $cnfGbp = $exchangeRateUsdtoGbp > 0 ? $cnfUsd / $exchangeRateUsdtoGbp : 0; // GBP
    
    $row['cnf'] = $cnfGbp;

    // Store for monthly tables
    if (!isset($monthlyData[$month])) {
        $monthlyData[$month] = [];
        $monthlyAverages[$month] = [];
    }
    if (!isset($monthlyData[$month][$date])) {
        $monthlyData[$month][$date] = [];
    }
    $monthlyData[$month][$date][] = $row;
    
    $productKey = $row['product_code'] . '|' . $date;
    if (!isset($monthlyAverages[$month][$productKey])) {
        $monthlyAverages[$month][$productKey] = [
            'total_cnf' => 0,
            'count' => 0,
            'product_data' => $row,
            'product_code' => $row['product_code'],
            'date' => $date
        ];
    }
    $monthlyAverages[$month][$productKey]['total_cnf'] += $cnfGbp;
    $monthlyAverages[$month][$productKey]['count']++;
    
    // Store for yearly summary
   $productSummaryKey = $row['product_code'] . '|' . $row['product_name'] . '|' . $row['size'] . '|' . $row['specification'];
    if (!isset($yearlyProductData[$year][$productSummaryKey])) {
        $yearlyProductData[$year][$productSummaryKey] = [
            'total_cnf' => 0,
            'count' => 0,
            'monthly_data' => [],
            'product_data' => $row
        ];
    }
    $yearlyProductData[$year][$productSummaryKey]['total_cnf'] += $cnfGbp;
    $yearlyProductData[$year][$productSummaryKey]['count']++;
    
    // Store monthly data for each product
    if (!isset($yearlyProductData[$year][$productSummaryKey]['monthly_data'][$month])) {
        $yearlyProductData[$year][$productSummaryKey]['monthly_data'][$month] = [
            'total_cnf' => 0,
            'count' => 0
        ];
    }
    $yearlyProductData[$year][$productSummaryKey]['monthly_data'][$month]['total_cnf'] += $cnfGbp;
    $yearlyProductData[$year][$productSummaryKey]['monthly_data'][$month]['count']++;
}

// Calculate averages per product per month
$productAverages = [];
foreach($monthlyAverages as $month => $dateProducts) {
    foreach($dateProducts as $productKey => $data) {
        $productCode = $data['product_code'];
        if (!isset($productAverages[$month][$productCode])) {
            $productAverages[$month][$productCode] = [
                'total_cnf' => 0,
                'count' => 0,
                'product_data' => $data['product_data']
            ];
        }
        $productAverages[$month][$productCode]['total_cnf'] += $data['total_cnf'];
        $productAverages[$month][$productCode]['count'] += $data['count'];
    }
}

// Calculate final averages for monthly tables
foreach($productAverages as $month => $products) {
    foreach($products as $productCode => $data) {
        $productAverages[$month][$productCode]['average_cnf'] = 
            $data['total_cnf'] / $data['count'];
    }
}

// Calculate yearly averages for summary tab
$yearlyAverages = [];
foreach($yearlyProductData as $year => $products) {
    foreach($products as $productKey => $data) {
        $yearlyAverages[$year][$productKey] = [
            'yearly_average' => $data['total_cnf'] / $data['count'],
            'monthly_averages' => []
        ];
        
        // Calculate monthly averages for each product
        foreach($data['monthly_data'] as $month => $monthData) {
            $yearlyAverages[$year][$productKey]['monthly_averages'][$month] = 
                $monthData['total_cnf'] / $monthData['count'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CNF Analysis UK</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- jQuery (must come before DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <style>
        .nav-tabs .nav-link.active {
            font-weight: bold;
            background-color: #e9ecef;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .cnf-header {
            background-color: #d1ecf1 !important;
            font-weight: bold;
        }
        .date-column {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .average-column {
            background-color: #fff3cd !important;
            font-weight: bold;
        }
        .yearly-average-column {
            background-color: #d4edda !important;
            font-weight: bold;
        }
        .product-row:hover {
            background-color: #f8f9fa;
        }
        .month-table {
            margin-bottom: 30px;
        }
        .product-details-column {
            min-width: 120px;
        }
        .cnf-cell {
            min-width: 80px;
        }
        .dataTables_wrapper {
            position: relative;
        }
        .summary-table-container {
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-5">
    <h2 class="text-center text-primary fw-bold mb-4">Shipment CNF Analysis</h2>
    <h4 class="text-left text-primary">Please Select year to view CNF analysis</h4>

    <!-- Year Filter and Monthly Tabs -->
    <div class="container-fluid mb-4">
        <div class="mb-3">
            <label for="yearFilter" class="form-label fw-bold me-2">Filter by Year:</label>
            <select id="yearFilter" class="form-select d-inline-block w-auto">
                <option value="all">All Years</option>
                <?php foreach($years as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year == $currentYear ? 'selected' : ''; ?>>
                    <?php echo $year; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="clearYearFilter" class="btn btn-outline-secondary ms-2">Clear</button>
        </div>
        
        <!-- Monthly Tabs - Show latest month first -->
        <ul class="nav nav-tabs" id="monthTabs">
            <?php 
            $firstMonth = true;
            foreach($months as $month): 
            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $firstMonth ? 'active' : ''; ?>" data-month="<?php echo $month['month']; ?>">
                    <?php echo $month['month_name']; ?>
                </a>
            </li>
            <?php 
            $firstMonth = false;
            endforeach; 
            ?>
            <li class="nav-item">
                <a class="nav-link text-danger" data-month="summary">Summary</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-warning" data-month="graph">Graph</a>
            </li>
        </ul>
    </div>
    
    <!-- CNF Analysis Tables -->
    <div class="container-fluid mb-5" id="cnfTablesContainer">
        <?php 
        $firstTable = true;
        foreach($monthlyData as $month => $dates): 
            $monthName = date('F Y', strtotime($month . '-01'));
            $monthDates = array_keys($dates);
            sort($monthDates);
        ?>
        <div class="month-table <?php echo $firstTable ? '' : 'd-none'; ?>" data-month="<?php echo $month; ?>">
            <h4 class="text-primary mb-3"><?php echo $monthName; ?> - CNF Analysis</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped month-cnf-table" style="width:100%">
                    <thead>
                        <tr>
                            <!-- Product Detail Columns -->
                            <th class="text-center date-column product-details-column">Product Code</th>
                            <th class="text-center date-column product-details-column">Product Name</th>
                            <th class="text-center date-column product-details-column">Scientific Name</th>
                            <th class="text-center date-column product-details-column">Size</th>
                            <th class="text-center date-column product-details-column">Specification</th>
                            
                            <!-- Daily CNF Columns -->
                            <?php foreach($monthDates as $date): 
                                $dayName = date('D', strtotime($date));
                                $dayNumber = date('j', strtotime($date));
                            ?>
                            <th class="text-center cnf-header cnf-cell" title="<?php echo $date; ?>">
                                <?php echo $dayName ?><br><?php echo $dayNumber; ?>
                            </th>
                            <?php endforeach; ?>
                            
                            <!-- Average Column -->
                            <th class="text-center average-column cnf-cell">Monthly Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get all unique products for this month
                        $products = [];
                        foreach($dates as $dateData) {
                            foreach($dateData as $productData) {
                                $productKey = $productData['product_code'] . '|' . $productData['product_name'];
                                if (!isset($products[$productKey])) {
                                    $products[$productKey] = $productData;
                                }
                            }
                        }
                        
                        foreach($products as $productKey => $product): 
                            list($productCode, $productName) = explode('|', $productKey);
                            $averageCnf = isset($productAverages[$month][$productCode]) ? 
                                number_format($productAverages[$month][$productCode]['average_cnf'], 2) : '-';
                        ?>
                        <tr class="product-row">
                            <!-- Product Detail Cells -->
                            <td class="text-center">
                                <strong class="text-primary"><?php echo $productCode; ?></strong>
                            </td>
                            <td class="text-center">
                                <?php echo $productName; ?>
                            </td>
                            <td class="text-center">
                                <span class="small"><?php echo $product['scientific_name']; ?></span>
                            </td>
                            <td class="text-center">
                                <?php echo $product['size']; ?>
                            </td>
                            <td class="text-center">
                                <?php echo $product['specification']; ?>
                            </td>
                            
                            <!-- Daily CNF Cells -->
                            <?php foreach($monthDates as $date): ?>
                            <td class="text-center cnf-cell">
                                <?php
                                $cnfValue = '';
                                if (isset($dates[$date])) {
                                    // Find all matching products for this date and code
                                    $matchingProducts = array_filter($dates[$date], function($p) use ($productCode) {
                                        return $p['product_code'] == $productCode;
                                    });
                                    
                                    if (!empty($matchingProducts)) {
                                        // If multiple entries for same product on same date, take average
                                        $total = 0;
                                        $count = 0;
                                        foreach($matchingProducts as $matchedProduct) {
                                            $total += $matchedProduct['cnf'];
                                            $count++;
                                        }
                                        $cnfValue = number_format($total / $count, 2);
                                    }
                                }
                                echo $cnfValue ?: '-';
                                ?>
                            </td>
                            <?php endforeach; ?>
                            
                            <!-- Average Cell -->
                            <td class="text-center average-column cnf-cell fw-bold">
                                <?php echo $averageCnf; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Monthly Summary Row -->
                        <tfoot class="table-info">
                            <td class="text-center fw-bold" colspan="5">Monthly Summary</td>
                            <?php 
                            // Calculate daily averages
                            $dailyAverages = [];
                            foreach($monthDates as $date) {
                                $dailyTotal = 0;
                                $dailyCount = 0;
                                if (isset($dates[$date])) {
                                    foreach($dates[$date] as $productData) {
                                        $dailyTotal += $productData['cnf'];
                                        $dailyCount++;
                                    }
                                }
                                $dailyAverages[$date] = $dailyCount > 0 ? number_format($dailyTotal / $dailyCount, 2) : '-';
                            }
                            
                            foreach($monthDates as $date): 
                            ?>
                            <td class="text-center cnf-cell fw-bold">
                                <?php echo $dailyAverages[$date]; ?>
                            </td>
                            <?php endforeach; ?>
                            <td class="text-center average-column cnf-cell fw-bold">
                                <?php
                                $monthlyTotal = 0;
                                $monthlyCount = 0;
                                if (isset($productAverages[$month])) {
                                    foreach($productAverages[$month] as $productData) {
                                        $monthlyTotal += $productData['average_cnf'];
                                        $monthlyCount++;
                                    }
                                }
                                echo $monthlyCount > 0 ? number_format($monthlyTotal / $monthlyCount, 2) : '-';
                                ?>
                            </td>
                        </tfoot>
                    </tbody>
                </table>
            </div>
        </div>
        <?php 
        $firstTable = false;
        endforeach; 
        ?>
        
        <!-- Summary Table -->
        <div class="month-table d-none" data-month="summary">
            <h4 class="text-primary mb-3">Yearly CNF Summary</h4>
            <div class="summary-table-container">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped summary-cnf-table" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center date-column product-details-column">Product Code</th>
                                <th class="text-center date-column product-details-column">Product Name</th>
                                <th class="text-center date-column product-details-column">Size</th>
                                <th class="text-center date-column product-details-column">Specification</th>
                                <!-- Monthly + Yearly columns follow -->

                                <!-- Monthly Average Columns -->
                                <?php 
                                $allMonths = array_keys($monthlyData);
                                sort($allMonths);
                                foreach($allMonths as $month): 
                                    $monthName = date('M Y', strtotime($month . '-01'));
                                ?>
                                <th class="text-center cnf-header cnf-cell" title="<?php echo $month; ?>">
                                    <?php echo $monthName; ?>
                                </th>
                                <?php endforeach; ?>
                                
                                <!-- Yearly Average Column -->
                                <th class="text-center yearly-average-column cnf-cell">Yearly Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get all unique products across all data
                            $allProducts = [];
                            foreach($allData as $row) {
                                $productKey = $row['product_code'] . '|' . $row['product_name'] . '|' . $row['size'] . '|' . $row['specification'];
                                if (!isset($allProducts[$productKey])) {
                                    $allProducts[$productKey] = $row;
                                }
                            }
                            
                            foreach($allProducts as $productKey => $product): 
                                list($productCode, $productName, $size, $specification) = explode('|', $productKey, 4);
                                $year = $currentYear; // Default to current year
                                
                                $yearlyAverage = isset($yearlyAverages[$year][$productKey]) ? 
                                    number_format($yearlyAverages[$year][$productKey]['yearly_average'], 2) : '-';
                            ?>
                            <tr class="product-row">
                                <!-- Product Detail Cells -->
                                <td class="text-center">
                                    <strong class="text-primary"><?php echo $productCode; ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php echo $productName; ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php echo $size; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $specification; ?>
                                </td>
                                
                                <!-- Monthly Average Cells -->
                                <?php foreach($allMonths as $month): ?>
                                <td class="text-center cnf-cell">
                                    <?php
                                    $monthlyAvg = '-';
                                    if (isset($yearlyAverages[$year][$productKey]['monthly_averages'][$month])) {
                                        $monthlyAvg = number_format($yearlyAverages[$year][$productKey]['monthly_averages'][$month], 2);
                                    } elseif (isset($productAverages[$month][$productCode])) {
                                        $monthlyAvg = number_format($productAverages[$month][$productCode]['average_cnf'], 2);
                                    }
                                    echo $monthlyAvg;
                                    ?>
                                </td>
                                <?php endforeach; ?>
                                
                                <!-- Yearly Average Cell -->
                                <td class="text-center yearly-average-column cnf-cell fw-bold">
                                    <?php echo $yearlyAverage; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Yearly Summary Row -->
                            <tfoot class="table-info">
                                <td class="text-center fw-bold" colspan="4">Yearly Summary</td>
                                <?php 
                                // Calculate monthly averages across all products
                                foreach($allMonths as $month): 
                                    $monthlyTotal = 0;
                                    $monthlyCount = 0;
                                    
                                    if (isset($productAverages[$month])) {
                                        foreach($productAverages[$month] as $productData) {
                                            $monthlyTotal += $productData['average_cnf'];
                                            $monthlyCount++;
                                        }
                                    }
                                    
                                    $monthlyAverage = $monthlyCount > 0 ? number_format($monthlyTotal / $monthlyCount, 2) : '-';
                                ?>
                                <td class="text-center cnf-cell fw-bold">
                                    <?php echo $monthlyAverage; ?>
                                </td>
                                <?php endforeach; ?>
                                <td class="text-center yearly-average-column cnf-cell fw-bold">
                                    <?php
                                    $yearlyTotal = 0;
                                    $yearlyCount = 0;
                                    if (isset($yearlyAverages[$year])) {
                                        foreach($yearlyAverages[$year] as $productData) {
                                            $yearlyTotal += $productData['yearly_average'];
                                            $yearlyCount++;
                                        }
                                    }
                                    echo $yearlyCount > 0 ? number_format($yearlyTotal / $yearlyCount, 2) : '-';
                                    ?>
                                </td>
                            </tfoot>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="month-table d-none" data-month="graph">
            <h1>Graph still Working</h1>
        
        </div>
    </div>
    
</div>
<script>
    $(document).ready(function() {
        // Simple tab functionality without DataTables complexity
        function showMonth(month) {
            // Hide all tables
            $('.month-table').addClass('d-none');
            
            // Show selected month table
            $(`.month-table[data-month="${month}"]`).removeClass('d-none');
            
            // Update active tab
            $('#monthTabs a').removeClass('active');
            $(`#monthTabs a[data-month="${month}"]`).addClass('active');
        }

        // Month tab click event
        $('#monthTabs').on('click', 'a', function(e) {
            e.preventDefault();
            const selectedMonth = $(this).data('month');
            showMonth(selectedMonth);
        });

        // Year filter functionality
        $('#yearFilter').on('change', function() {
            const selectedYear = $(this).val();

            if (selectedYear === 'all') {
                // Show all months and tabs
                $('#monthTabs li').show();
                $('.month-table').removeClass('d-none');
            } else {
                // Filter months by year
                $('#monthTabs li').each(function() {
                    const tabMonth = $(this).find('a').data('month');
                    if (tabMonth === 'summary') {
                        $(this).show(); // Always show summary tab
                    } else {
                        const tabYear = tabMonth.split('-')[0];
                        if (tabYear === selectedYear) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    }
                });
                
                // Show tables for selected year only
                $('.month-table').addClass('d-none');
                $('.month-table').each(function() {
                    const tableMonth = $(this).data('month');
                    if (tableMonth === 'summary') {
                        $(this).removeClass('d-none'); // Always show summary
                    } else {
                        const tableYear = tableMonth.split('-')[0];
                        if (tableYear === selectedYear) {
                            $(this).removeClass('d-none');
                        }
                    }
                });
            }

            // Activate first visible tab
            const firstVisibleTab = $('#monthTabs a:visible').first();
            if (firstVisibleTab.length) {
                const visibleMonth = firstVisibleTab.data('month');
                showMonth(visibleMonth);
            }
        });

        // Clear year filter
        $('#clearYearFilter').on('click', function() {
            $('#yearFilter').val('all').trigger('change');
        });

        // Initialize with first month
        const firstMonth = $('#monthTabs a.active').data('month');
        showMonth(firstMonth);

        // Initialize simple DataTables without complex features
        $('.month-cnf-table').DataTable({
            paging: false,
            searching: true,
            ordering: true,
            info: false,
            autoWidth: false,
            scrollX: true,
            dom: '<"row"<"col-sm-12"f>>rtip'
        });
        
        // Initialize summary table DataTable
        $('.summary-cnf-table').DataTable({
            paging: false,
            searching: true,
            ordering: true,
            info: false,
            autoWidth: false,
            scrollX: true,
            dom: '<"row"<"col-sm-12"f>>rtip'
        });

        // Fix DataTable width issue when switching tabs
        $('#monthTabs').on('click', 'a', function (e) {
            e.preventDefault();
            const selectedMonth = $(this).data('month');
            showMonth(selectedMonth);

            // Recalculate DataTables column widths when showing the table
            $.fn.dataTable
                .tables({ visible: true, api: true })
                .columns.adjust();
        });
    });
</script>
</body>
</html>