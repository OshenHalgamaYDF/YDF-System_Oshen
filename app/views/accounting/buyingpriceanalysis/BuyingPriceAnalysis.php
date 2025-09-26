<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\buyingpriceanalysis\BuyingPriceAnalysisController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Buying Price Analysis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!--chart.js-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .nav-underline .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }
        .table th {
            white-space: nowrap;
        }
        .dataTables_wrapper .dataTables_filter {
            float: right;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
        }
        .dataTables_wrapper .dataTables_paginate {
            float: right;
        }
        .month-filter-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .month-filter-label {
            margin-right: 10px;
            font-weight: bold;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
        .graph-controls {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1 class="text-center mb-4 text-primary fw-bold">Buying Price Analysis</h1>

    
    <div class="mb-2">
        <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#offelModal" id="addOffelBtn">
            + Buying Price
        </button>
        <button class="btn btn-danger px-4" onclick="window.location.href='BuyingPriceSummary.php'">
            View Summary   
        </button>
    </div>
    
    <!-- Month Filter -->
    <div class="month-filter-container">
        <span class="month-filter-label">Filter by Month:</span>
        <select class="form-select w-auto" id="monthFilter">
            <?php foreach ($availableMonths as $month): 
                $monthName = date('F Y', strtotime($month . '-01'));
                $isSelected = ($month == $selectedMonth) ? 'selected' : '';
            ?>
                <option value="<?php echo $month; ?>" <?php echo $isSelected; ?>>
                    <?php echo $monthName; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Add New Record Modal -->
    <div class="modal fade" id="offelModal" tabindex="-1" aria-labelledby="offelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="offelModalLabel">Form to Add Buying Price Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-3" id="buyingPriceForm" novalidate autocomplete="off">
                        <div class="col-md-12">
                            <label>Date:</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                            <div class="invalid-feedback">Please enter a date.</div>
                        </div>

                        <div class="col-md-12">
                            <label>Product Name:</label>
                            <select name="product_name" id="product_name" class="form-select" required onchange="fillProductDetails()">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($product['product_name']); ?>"
                                        data-product-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                                        data-scientific-name="<?php echo htmlspecialchars($product['scientific_name']); ?>"
                                        data-size-range="<?php echo htmlspecialchars($product['size_range']); ?>"
                                        data-specification="<?php echo htmlspecialchars($product['specification']); ?>"
                                        data-target-price="<?php echo htmlspecialchars($product['target_buying_price']); ?>"
                                    >
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a product.</div>
                        </div>

                        <input type="hidden" name="scientific_name" id="scientific_name" />

                        <div class="col-md-4">
                            <label>Product Code:</label>
                            <input type="text" name="product_code" id="product_code" class="form-control" required readonly>
                            <div class="invalid-feedback">Product code is required.</div>
                        </div>

                        <div class="col-md-4">
                            <label>Size Range:</label>
                            <input type="text" name="size_range" id="size_range" class="form-control" required>
                            <div class="invalid-feedback">Please enter the size range.</div>
                        </div>

                        <div class="col-md-4">
                            <label>Specification:</label>
                            <input type="text" name="specification" id="specification" class="form-control" required>
                            <div class="invalid-feedback">Please enter the specification.</div>
                        </div>

                        <div class="col-md-4">
                            <label>Target Price:</label>
                            <input type="number" step="0.01" name="target_price" id="target_price" class="form-control" required>
                            <div class="invalid-feedback">Please enter the target price.</div>
                        </div>

                        <div class="col-md-4">
                            <label>Buyer Name:</label>
                            <select name="buyer_name" id="buyer_name" class="form-select" required>
                                <option value="">Select Buyer</option>
                                <option value="Madushan">Madushan</option>
                                <option value="Charith">Charith</option>
                                <option value="Miranda">Miranda</option>
                                <option value="Mahesh">Mahesh</option>
                                <option value="Safras">Safras</option>
                                <option value="Rijas">Rijas</option>
                                <option value="Layoma">Layoma</option>
                                <option value="Sameera">Sameera</option>
                                <option value="Sujan">Sujan</option>
                            </select>
                            <div class="invalid-feedback">Please select a buyer.</div>
                        </div>

                        <div class="col-md-4">
                            <label>Buying Price:</label>
                            <input type="number" step="0.01" name="sold_price" id="sold_price" class="form-control" required>
                            <div class="invalid-feedback">Please enter the buying price.</div>
                        </div>
                        <div class="col-md-12">
                            <label>Remark (Optional):</label>
                            <input type="text" name="remark" id="remark" class="form-control">
                        </div>

                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="SubmitOffel" class="btn btn-primary px-4">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
       <!-- Bootstrap Tabs -->
        <ul class="nav nav-underline" id="productTabs" role="tablist">
            <?php if (count($dateselected) > 0): ?>
                <?php foreach ($dateselected as $index => $dateResult): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $index===0 ? 'active' : ''; ?>" 
                                id="tab-<?php echo md5($dateResult); ?>" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-<?php echo md5($dateResult); ?>" 
                                type="button" 
                                role="tab"
                                data-date="<?php echo htmlspecialchars($dateResult); ?>">
                            <?php echo htmlspecialchars($dateResult); ?>
                        </button>   
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" disabled>No data for selected month</button>
                </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                    <button class="nav-link text-danger" 
                            id="tab-average" 
                            data-bs-toggle="tab" 
                            data-bs-target="#content-average"
                            type="button" 
                            role="tab">
                        Average
                    </button>
            </li>
            <li class="nav-item" role="presentation"> 
                <button class="nav-link text-warning"
                        id="tab-statistics" 
                        data-bs-toggle="tab" 
                        data-bs-target="#content-statistics"
                        type="button" 
                        role="tab">
                    Statistics Graph
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-2">
            <?php if (count($dateselected) > 0): ?>
                <?php foreach ($dateselected as $index => $dateResult): ?>
                    <div class="tab-pane fade <?php echo $index===0 ? 'show active' : ''; ?>" 
                    id="content-<?php echo md5($dateResult); ?>" 
                    role="tabpanel"
                    data-date="<?php echo htmlspecialchars($dateResult); ?>">
                    
                    <div class="table-responsive">
                            <table class="table table-striped table-hover text-center align-middle table-bordered" 
                            id="table-<?php echo md5($dateResult); ?>">
                            <thead class="table-primary table-dark">
                                <tr>
                                    <th rowspan="2" class="text-center align-middle">Product Code</th>
                                        <th rowspan="2" class="text-center align-middle">Product Name</th>
                                        <th rowspan="2" class="text-center align-middle">Scientific Name</th>
                                        <th rowspan="2" class="text-center align-middle">Specification</th>
                                        <th rowspan="2" class="text-center align-middle">Size Range</th>
                                        <th rowspan="2" class="text-center align-middle">Target Price</th>
                                        <th colspan="<?php 
                                            $buyers = [];
                                            $sql = "SELECT DISTINCT buyer_name FROM buyingpriceanlaysistable WHERE date = '$dateResult'";
                                            $res = $conn->query($sql);
                                            if ($res && $res->num_rows > 0) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $buyers[] = $row['buyer_name'];
                                                }
                                            }
                                            echo count($buyers);
                                        ?>" class="text-center align-middle">Buyer Price</th>
                                        <th rowspan="2" class="text-center align-middle">Average Price</th>
                                        <th rowspan="2" class="text-center align-middle">Action</th>
                                    </tr>
                                    <tr>
                                        <?php foreach ($buyers as $buyer): ?>
                                            <th class="text-center align-middle"><?= htmlspecialchars($buyer) ?></th>
                                            <?php endforeach; ?>
                                    </tr>
                                    </thead>
                                <tbody>
                                    <?php
                                    // Get distinct products for that date
                                    $sql = "SELECT product_code, product_name, scientific_name, specification, size_range, target_price, 
                                            GROUP_CONCAT(DISTINCT CONCAT(buyer_name, ': ', remark) SEPARATOR ', ') AS remarks
                                            FROM buyingpriceanlaysistable
                                            WHERE date = '$dateResult'
                                            GROUP BY product_code, product_name, scientific_name, specification, size_range, target_price";

                                    $result = $conn->query($sql);

                                    if ($result && $result->num_rows > 0) {
                                        while ($product = $result->fetch_assoc()) {?>
                                            <tr class='text-center'>
                                            <td><?= htmlspecialchars($product['product_code']) ?></td>
                                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                                            <td><?= htmlspecialchars($product['scientific_name']) ?></td>
                                            <td><?= htmlspecialchars($product['specification']) ?></td>
                                            <td><?= htmlspecialchars($product['size_range']) ?></td>
                                            <td><?= number_format($product['target_price'],2) ?></td>
                                            
                                            <?php
                                            // Buyer prices
                                            $totalPrice = 0;
                                            $buyerCount = 0;
                                            $buyerPrices = [];
                                            foreach ($buyers as $buyer) {
                                                $sql2 = "SELECT id, sold_price FROM buyingpriceanlaysistable 
                                                        WHERE date = '$dateResult' 
                                                        AND product_code = '".$product['product_code']."' 
                                                        AND buyer_name = '".$conn->real_escape_string($buyer)."' 
                                                        LIMIT 1";
                                                $res2 = $conn->query($sql2);
                                                if ($res2 && $row2 = $res2->fetch_assoc()) {?>
                                                    <td class="record-data" 
                                                    data-id="<?= $row2['id'] ?>"
                                                        data-date="<?= $dateResult ?>"
                                                        data-product-code="<?= $product['product_code'] ?>"
                                                        data-product-name="<?= $product['product_name'] ?>"
                                                        data-scientific-name="<?= $product['scientific_name'] ?>"
                                                        data-specification="<?= $product['specification'] ?>"
                                                        data-size-range="<?= $product['size_range'] ?>"
                                                        data-target-price="<?= $product['target_price'] ?>"
                                                        data-buyer-name="<?= $buyer ?>"
                                                        data-sold-price="<?= $row2['sold_price'] ?>"
                                                        data-remark="<?= $product['remarks'] ?>">
                                                        <?= number_format($row2['sold_price'],2) ?>
                                                    </td>
                                                    <?php
                                                    $totalPrice += $row2['sold_price'];
                                                    $buyerCount++;
                                                    $buyerPrices[$buyer] = $row2['id'];
                                                } else {?>
                                                    <td>-</td>  <!-- no price for this buyer -->
                                                <?php
                                                }
                                            }

                                            // Average price
                                            if ($buyerCount > 0) {
                                                $average = $totalPrice / $buyerCount;?>
                                                <td><?= number_format($average,2) ?></td>
                                            <?php } else {?>
                                                <td>0.00</td>
                                            <?php } ?>

                                            <td>
                                                <button class='btn btn-sm btn-warning edit-btn' 
                                                data-bs-toggle="modal" 
                                                        data-bs-target="#editModal"
                                                        data-date="<?= $dateResult ?>"
                                                        data-product-code="<?= $product['product_code'] ?>"
                                                        data-buyers='<?= json_encode($buyerPrices) ?>'>
                                                    <i class="fas fa-info"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php }
                                    } else {?>
                                        <tr><td colspan='<?php echo (7 + count($buyers)); ?>' class='text-center'>No records for <?= $dateResult ?></td></tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="tab-pane fade show active" id="no-data" role="tabpanel">
                    <div class="alert alert-info text-center">
                        No data available for the selected month.
                    </div>
                </div>
            <?php endif; ?>
            </div>

        <!-- Average Calculation Tab -->
        <div class="tab-content mt-2">
            <div class="tab-pane fade" id="content-average" role="tabpanel">
                <div class="table-responsive">
                    <h4><b>Average Calculation for <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></b></h4>
                    <table class="table table-striped table-hover text-center align-middle table-bordered" id="averageTable">
                        <thead class="table-primary table-dark">
                            <tr>
                                <th rowspan="2" class="text-center align-middle">Product Code</th>
                                <th rowspan="2" class="text-center align-middle">Product Name</th>
                                <th rowspan="2" class="text-center align-middle">Size Range</th>
                                <th rowspan="2" class="text-center align-middle">Target Price</th>
                                <th colspan="<?php 
                                    // Get all buyers for the selected month
                                    $buyers = [];
                                    $buyerSql = "SELECT DISTINCT buyer_name 
                                                FROM buyingpriceanlaysistable 
                                                WHERE DATE_FORMAT(date, '%Y-%m') = '$selectedMonth'
                                                ORDER BY buyer_name";
                                    $buyerRes = $conn->query($buyerSql);
                                    if ($buyerRes && $buyerRes->num_rows > 0) {
                                        while ($buyerRow = $buyerRes->fetch_assoc()) {
                                            $buyers[] = $buyerRow['buyer_name'];
                                        }
                                    }
                                    echo count($buyers);
                                ?>" class="text-center align-middle">Buyer Average</th>
                                <th rowspan="2" class="text-center align-middle">Overall Average</th>
                            </tr>
                            <tr>
                                <?php foreach ($buyers as $buyer): ?>
                                    <th class="text-center align-middle"><?= htmlspecialchars($buyer) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get distinct products for the selected month
                            $productSql = "SELECT DISTINCT product_code, product_name, size_range, target_price
                                        FROM buyingpriceanlaysistable 
                                        WHERE DATE_FORMAT(date, '%Y-%m') = '$selectedMonth'
                                        ORDER BY product_code";
                            $productResult = $conn->query($productSql);

                            if ($productResult && $productResult->num_rows > 0) {
                                while ($product = $productResult->fetch_assoc()) {
                                    $productCode = $product['product_code'];
                                    $productName = $product['product_name'];
                                    $sizeRange = $product['size_range'];
                                    $targetPrice = $product['target_price'];
                                    
                                    // Initialize variables for calculating overall average
                                    $buyerAverages = [];
                                    $buyerCount = 0;
                                    $buyerSum = 0;
                                    ?>
                                    <tr class='text-center'>
                                        <td><?= htmlspecialchars($productCode) ?></td>
                                        <td><?= htmlspecialchars($productName) ?></td>
                                        <td><?= htmlspecialchars($sizeRange) ?></td>
                                        <td><?= number_format($targetPrice, 2) ?></td>
                                        
                                        <?php
                                        // Display average for each buyer and collect for overall average
                                        foreach ($buyers as $buyer) {
                                            $buyerAvgSql = "SELECT AVG(sold_price) AS buyer_avg 
                                                        FROM buyingpriceanlaysistable 
                                                        WHERE product_code = '$productCode' 
                                                        AND buyer_name = '" . $conn->real_escape_string($buyer) . "'
                                                        AND DATE_FORMAT(date, '%Y-%m') = '$selectedMonth'";
                                            $buyerAvgRes = $conn->query($buyerAvgSql);
                                            
                                            if ($buyerAvgRes && $buyerAvgRow = $buyerAvgRes->fetch_assoc()) {
                                                $buyerAvg = $buyerAvgRow['buyer_avg'];
                                                if ($buyerAvg !== null) {
                                                    $buyerAverages[$buyer] = $buyerAvg;
                                                    $buyerSum += $buyerAvg;
                                                    $buyerCount++;
                                                    echo "<td>" . number_format($buyerAvg, 2) . "</td>";
                                                } else {
                                                    $buyerAverages[$buyer] = null;
                                                    echo "<td>-</td>";
                                                }
                                            } else {
                                                $buyerAverages[$buyer] = null;
                                                echo "<td>-</td>";
                                            }
                                        }
                                        
                                        // Calculate overall average as the average of buyer averages
                                        $overallAvg = ($buyerCount > 0) ? $buyerSum / $buyerCount : 0;
                                        ?>
                                        
                                        <td><?= number_format($overallAvg, 2) ?></td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="<?php echo 5 + count($buyers); ?>" class='text-center'>
                                        No records found for selected month
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-content mt-2">
            <!-- Statistics Graph Tab -->
            <div class="tab-pane fade" id="content-statistics" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-chart-line me-2"></i>Buying Price Statistics</h4>
                    </div>
                    <div class="card-body">
                        <!-- Graph Controls -->
                        <div class="graph-controls">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="graphProductSelect" class="form-label">Product:</label>
                                    <select id="graphProductSelect" class="form-select">
                                        <option value="">-- All Products --</option>
                                        <?php
                                        $productRes = $conn->query("SELECT DISTINCT product_name, product_code FROM buyingpriceanlaysistable ORDER BY product_name ASC");
                                        if ($productRes && $productRes->num_rows > 0) {
                                            while ($row = $productRes->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['product_code']) . "'>" . htmlspecialchars($row['product_name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="graphBuyerSelect" class="form-label">Buyer:</label>
                                    <select id="graphBuyerSelect" class="form-select">
                                        <option value="">-- All Buyers --</option>
                                        <?php
                                        $buyerRes = $conn->query("SELECT DISTINCT buyer_name FROM buyingpriceanlaysistable ORDER BY buyer_name ASC");
                                        if ($buyerRes && $buyerRes->num_rows > 0) {
                                            while ($row = $buyerRes->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['buyer_name']) . "'>" . htmlspecialchars($row['buyer_name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="graphTypeSelect" class="form-label">Chart Type:</label>
                                    <select id="graphTypeSelect" class="form-select">
                                        <option value="line">Line Chart</option>
                                        <option value="bar">Bar Chart</option>
                                        <option value="radar">Radar Chart</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="dateFrom" class="form-label">Date From:</label>
                                    <input type="date" id="dateFrom" class="form-control" value="<?php echo date('Y-m-01', strtotime($selectedMonth . '-01')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="dateTo" class="form-label">Date To:</label>
                                    <input type="date" id="dateTo" class="form-control" value="<?php echo date('Y-m-t', strtotime($selectedMonth . '-01')); ?>">
                                </div>
                                <div class="col-12">
                                    <button type="button" id="generateGraph" class="btn btn-primary">
                                        <i class="fas fa-chart-bar me-1"></i>Generate Graph
                                    </button>
                                    <button type="button" id="resetGraph" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo me-1"></i>Reset
                                    </button>
                                    <button type="button" id="downloadGraph" class="btn btn-success">
                                        <i class="fas fa-download me-1"></i>Download Image
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading Spinner -->
                        <div class="loading-spinner" id="graphLoading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Generating graph...</p>
                        </div>

                        <!-- Chart Container -->
                        <div class="chart-container">
                            <canvas id="statisticsChart"></canvas>
                        </div>

                        <!-- Statistics Summary -->
                        <div class="row mt-4" id="statsSummary">
                            <div class="col-md-3">
                                <div class="card text-center bg-light stats-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary" id="avgPrice">0.00</h5>
                                        <p class="card-text"><i class="fas fa-dollar-sign me-1"></i>Average Price</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center bg-light stats-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-success" id="minPrice">0.00</h5>
                                        <p class="card-text"><i class="fas fa-arrow-down me-1"></i>Minimum Price</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center bg-light stats-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger" id="maxPrice">0.00</h5>
                                        <p class="card-text"><i class="fas fa-arrow-up me-1"></i>Maximum Price</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center bg-light stats-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-info" id="dataPoints">0</h5>
                                        <p class="card-text"><i class="fas fa-database me-1"></i>Data Points</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Selection Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editSelectionForm">
                            <div class="col-md-12">
                                <label>Select Buyer to Edit:</label>
                                <select name="buyer_name" id="editBuyerSelect" class="form-select" required>
                                    <option value="">Select Buyer To Edit</option>
                                    <!-- Options will be populated by JavaScript -->
                                </select>
                            </div>
                            <input type="hidden" id="editDate" name="date">
                            <input type="hidden" id="editProductCode" name="product_code">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="proceedToEditBtn">Edit</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Info Modal -->
        <div class="modal fade" id="editinfoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Buying Price Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" class="row g-3" id="editInfoForm" novalidate autocomplete="off">
                            <input type="hidden" name="record_id" id="record_id" />
                            <input type="hidden" name="UpdateRecord" value="1" />
                            <div class="col-md-12">
                                <label>Date:</label>
                                <input type="date" name="date" id="edit_date" class="form-control" required>
                                <div class="invalid-feedback">Please enter a date.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Product Name:</label>
                                <select name="product_name" id="edit_product_name" class="form-select" required onchange="fillEditProductDetails()">
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option
                                            value="<?php echo htmlspecialchars($product['product_name']); ?>"
                                            data-product-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                                            data-scientific-name="<?php echo htmlspecialchars($product['scientific_name']); ?>"
                                            data-size-range="<?php echo htmlspecialchars($product['size_range']); ?>"
                                            data-specification="<?php echo htmlspecialchars($product['specification']); ?>"
                                            data-target-price="<?php echo htmlspecialchars($product['target_buying_price']); ?>"
                                        >
                                            <?php echo htmlspecialchars($product['product_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a product.</div>
                            </div>

                            <input type="hidden" name="scientific_name" id="edit_scientific_name" />

                            <div class="col-md-4">
                                <label>Product Code:</label>
                                <input type="text" name="product_code" id="edit_product_code" class="form-control" required readonly>
                                <div class="invalid-feedback">Product code is required.</div>
                            </div>

                            <div class="col-md-4">
                                <label>Size Range:</label>
                                <input type="text" name="size_range" id="edit_size_range" class="form-control" required>
                                <div class="invalid-feedback">Please enter the size range.</div>
                            </div>

                            <div class="col-md-4">
                                <label>Specification:</label>
                                <input type="text" name="specification" id="edit_specification" class="form-control" required>
                                <div class="invalid-feedback">Please enter the specification.</div>
                            </div>
                            <div class="col-md-4">
                                <label>Target Price:</label>
                                <input type="number" step="0.01" name="target_price" id="edit_target_price" class="form-control" required>
                                <div class="invalid-feedback">Please enter the target price.</div>
                            </div>
                            <div class="col-md-4">
                                <label>Buyer Name:</label>
                                <select name="buyer_name" id="edit_buyer_name" class="form-select" required>
                                    <option value="">Select Buyer</option>
                                    <option value="Madushan">Madushan</option>
                                    <option value="Charith">Charith</option>
                                    <option value="Miranda">Miranda</option>
                                    <option value="Mahesh">Mahesh</option>
                                    <option value="Safras">Safras</option>
                                    <option value="Rijas">Rijas</option>
                                    <option value="Layoma">Layoma</option>
                                    <option value="Sameera">Sameera</option>
                                    <option value="Sujan">Sujan</option>
                                </select>
                                <div class="invalid-feedback">Please select a buyer.</div>
                            </div>
                            <div class="col-md-4">
                                <label>Buying Price:</label>
                                <input type="number" step="0.01" name="sold_price" id="edit_sold_price" class="form-control" required>
                                <div class="invalid-feedback">Please enter the buying price.</div>
                            </div>
                            <div class="col-md-12">
                                <label>Remark (Optional):</label>
                                <input type="text" name="remark" id="edit_remark" class="form-control">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                                <button type="button" class="btn btn-danger" id="deleteBtn">Delete</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillProductDetails() {
    const productSelect = document.getElementById('product_name');
    const selectedOption = productSelect.options[productSelect.selectedIndex];

    document.getElementById('product_code').value = selectedOption.dataset.productCode || '';
    document.getElementById('scientific_name').value = selectedOption.dataset.scientificName || '';
    document.getElementById('size_range').value = selectedOption.dataset.sizeRange || '';
    document.getElementById('specification').value = selectedOption.dataset.specification || '';
    document.getElementById('target_price').value = selectedOption.dataset.targetPrice || '';
}

function fillEditProductDetails() {
    const productSelect = document.getElementById('edit_product_name');
    const selectedOption = productSelect.options[productSelect.selectedIndex];

    document.getElementById('edit_product_code').value = selectedOption.dataset.productCode || '';
    document.getElementById('edit_scientific_name').value = selectedOption.dataset.scientificName || '';
    document.getElementById('edit_size_range').value = selectedOption.dataset.sizeRange || '';
    document.getElementById('edit_specification').value = selectedOption.dataset.specification || '';
    document.getElementById('edit_target_price').value = selectedOption.dataset.targetPrice || '';
}

// Month filter functionality
document.getElementById('monthFilter').addEventListener('change', function() {
    const selectedMonth = this.value;
    // Redirect to the same page with the month parameter
    window.location.href = 'BuyingPriceAnalysis.php?month=' + selectedMonth;
});

// Bootstrap 5 validation
(function () {
    'use strict';
    const form = document.getElementById('buyingPriceForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
    
    const editForm = document.getElementById('editInfoForm');
    editForm.addEventListener('submit', function(event) {
        if (!editForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        editForm.classList.add('was-validated');
    }, false);
})();

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables for the first active tab
    $('.tab-pane.active table').DataTable({
        "pageLength": 10,     // show 10 rows per page
        "ordering": true,     // enable column sorting
        "searching": true,    // enable search
        "lengthChange": true  // allow user to change page size
    });

    // Initialize DataTables for other tabs when they are shown
    $('#productTabs button').on('shown.bs.tab', function (e) {
        var target = $(e.target).data('bs-target');
        var tableId = $(target + ' table').attr('id');
        
        if (!$.fn.DataTable.isDataTable('#' + tableId)) {
            $('#' + tableId).DataTable({
                "pageLength": 10,     // show 10 rows per page
                "ordering": true,     // enable column sorting
                "searching": true,    // enable search
                "lengthChange": true  // allow user to change page size
            });
        }
    });
    
    // Initialize average table
    $('#averageTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true,
        "lengthChange": true
    });
});

// Edit button functionality
$('.edit-btn').click(function() {
    const date = $(this).data('date');
    const productCode = $(this).data('product-code');
    const buyers = $(this).data('buyers');
    
    // Set the hidden fields
    $('#editDate').val(date);
    $('#editProductCode').val(productCode);
    
    // Clear and populate buyer select
    $('#editBuyerSelect').empty().append('<option value="">Select Buyer To Edit</option>');
    
    for (const [buyerName, recordId] of Object.entries(buyers)) {
        $('#editBuyerSelect').append(`<option value="${buyerName}" data-record-id="${recordId}">${buyerName}</option>`);
    }
});

// Proceed to edit button
$('#proceedToEditBtn').click(function() {
    const selectedBuyer = $('#editBuyerSelect option:selected');
    if (selectedBuyer.val() === '') {
        alert('Please select a buyer to edit');
        return;
    }
    
    const recordId = selectedBuyer.data('record-id');
    
    // Find the table cell with the record data
    const recordCell = $(`.record-data[data-id="${recordId}"]`);
    
    if (recordCell.length) {
        // Populate the edit form with the record data
        $('#record_id').val(recordId);
        $('#edit_date').val(recordCell.data('date'));
        $('#edit_product_code').val(recordCell.data('product-code'));
        $('#edit_product_name').val(recordCell.data('product-name'));
        $('#edit_scientific_name').val(recordCell.data('scientific-name'));
        $('#edit_specification').val(recordCell.data('specification'));
        $('#edit_size_range').val(recordCell.data('size-range'));
        $('#edit_target_price').val(recordCell.data('target-price'));
        $('#edit_buyer_name').val(recordCell.data('buyer-name'));
        $('#edit_sold_price').val(recordCell.data('sold-price'));
        $('#edit_remark').val(recordCell.data('remark'));
        
        // Close the selection modal and open the edit modal
        $('#editModal').modal('hide');
        $('#editinfoModal').modal('show');
    } else {
        alert('Record data not found');
    }
});

// Delete button in edit modal - FIXED
$('#deleteBtn').click(function() {
    const recordId = $('#record_id').val();
    if (recordId) {
        if (confirm('Are you sure you want to delete this record?')) {
            $.post('BuyingPriceAnalysis.php', { id: recordId }, function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        alert('Record deleted successfully');
                        location.reload();
                    } else {
                        alert('Error deleting record: ' + (data.message || 'Unknown error'));
                    }
                } catch (e) {
                    alert('Error parsing server response');
                }
            }).fail(function(xhr, status, error) {
                alert('Error deleting record: ' + error);
            });
        }
    }
});

// Chart instance variable
let statisticsChart = null;

// Initialize graph when statistics tab is shown
document.getElementById('tab-statistics').addEventListener('shown.bs.tab', function() {
    initializeStatisticsGraph();
});

// Generate graph button
document.getElementById('generateGraph').addEventListener('click', function() {
    initializeStatisticsGraph();
});

// Reset graph button
document.getElementById('resetGraph').addEventListener('click', function() {
    document.getElementById('graphProductSelect').value = '';
    document.getElementById('graphBuyerSelect').value = '';
    document.getElementById('graphTypeSelect').value = 'line';
    document.getElementById('dateFrom').value = '<?php echo date('Y-m-01', strtotime($selectedMonth . '-01')); ?>';
    document.getElementById('dateTo').value = '<?php echo date('Y-m-t', strtotime($selectedMonth . '-01')); ?>';
    
    if (statisticsChart) {
        statisticsChart.destroy();
        statisticsChart = null;
    }
    
    // Reset statistics
    updateStatisticsSummary([], []);
});

// Download graph button
document.getElementById('downloadGraph').addEventListener('click', function() {
    if (statisticsChart) {
        const link = document.createElement('a');
        link.download = 'buying-price-chart.png';
        link.href = statisticsChart.toBase64Image();
        link.click();
    } else {
        alert('Please generate a graph first.');
    }
});

function initializeStatisticsGraph() {
    const productCode = document.getElementById('graphProductSelect').value;
    const buyerName = document.getElementById('graphBuyerSelect').value;
    const chartType = document.getElementById('graphTypeSelect').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    // Show loading spinner
    document.getElementById('graphLoading').style.display = 'block';
    
    // Fetch data from server
    fetch('get_graph_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_code=${productCode}&buyer_name=${buyerName}&date_from=${dateFrom}&date_to=${dateTo}`
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading spinner
        document.getElementById('graphLoading').style.display = 'none';
        
        if (data.success) {
            renderChart(data.labels, data.prices, chartType, data.product_name);
            updateStatisticsSummary(data.labels, data.prices);
        } else {
            alert('Error loading graph data: ' + data.message);
        }
    })
    .catch(error => {
        document.getElementById('graphLoading').style.display = 'none';
        console.error('Error:', error);
        alert('Error loading graph data');
    });
}

function renderChart(labels, prices, chartType, productName) {
    const ctx = document.getElementById('statisticsChart').getContext('2d');
    
    // Destroy existing chart
    if (statisticsChart) {
        statisticsChart.destroy();
    }
    
    // Chart configuration
    const chartConfig = {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: productName || 'Buying Prices',
                data: prices,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: chartType === 'line' ? 'rgba(75, 192, 192, 0.1)' : 'rgba(75, 192, 192, 0.7)',
                borderWidth: 2,
                fill: chartType === 'line',
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Buying Price Analysis',
                    font: { size: 16 }
                },
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return `Price: LKR ${context.parsed.y.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Price (LKR)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'LKR ' + value.toFixed(2);
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    };
    
    // Create new chart
    statisticsChart = new Chart(ctx, chartConfig);
}

function updateStatisticsSummary(labels, prices) {
    if (prices.length === 0) {
        document.getElementById('avgPrice').textContent = '0.00';
        document.getElementById('minPrice').textContent = '0.00';
        document.getElementById('maxPrice').textContent = '0.00';
        document.getElementById('dataPoints').textContent = '0';
        return;
    }
    
    const avg = prices.reduce((a, b) => a + b, 0) / prices.length;
    const min = Math.min(...prices);
    const max = Math.max(...prices);
    
    document.getElementById('avgPrice').textContent = avg.toFixed(2);
    document.getElementById('minPrice').textContent = min.toFixed(2);
    document.getElementById('maxPrice').textContent = max.toFixed(2);
    document.getElementById('dataPoints').textContent = prices.length;
}
</script>
</body>
</html>