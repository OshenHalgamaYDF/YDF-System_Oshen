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
            <!-- Add this to your nav tabs list with the other tabs -->
            <li class="nav-item" role="presentation">
                <button class="nav-link" 
                        id="tab-daily-trends" 
                        data-bs-toggle="tab" 
                        data-bs-target="#content-daily-trends"
                        type="button" 
                        role="tab">
                    Daily Trends
                </button>
            </li>
        </ul>
        <!--Graphnic Tab Content-->
        <!-- Add this to your tab content section -->
        <div class="tab-pane fade" id="content-daily-trends" role="tabpanel">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Daily Price Fluctuations</h5>
                </div>
                <div class="card-body">
                    <!-- Month Filter -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="dailyTrendsMonthFilter" class="form-label">Select Month:</label>
                            <select class="form-select" id="dailyTrendsMonthFilter">
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
                        <div class="col-md-6">
                            <label for="productFilter" class="form-label">Filter by Product (Optional):</label>
                            <select class="form-select" id="productFilter">
                                <option value="all">All Products</option>
                                <?php
                                // Get distinct products
                                $productSql = "SELECT DISTINCT product_code, product_name 
                                            FROM buyingpriceanlaysistable 
                                            ORDER BY product_code";
                                $productResult = $conn->query($productSql);
                                if ($productResult && $productResult->num_rows > 0) {
                                    while ($product = $productResult->fetch_assoc()) {
                                        echo '<option value="' . $product['product_code'] . '">' . 
                                            htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']) . 
                                            '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Chart Container -->
                    <div class="chart-container">
                        <canvas id="dailyTrendsChart" height="350"></canvas>
                    </div>
                    
                    <!-- Summary Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Highest Daily Avg</h6>
                                    <p class="card-text h5" id="highestDailyAvg">0.00</p>
                                    <small id="highestDate">-</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Lowest Daily Avg</h6>
                                    <p class="card-text h5" id="lowestDailyAvg">0.00</p>
                                    <small id="lowestDate">-</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Monthly Average</h6>
                                    <p class="card-text h5" id="monthlyAverage">0.00</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Price Fluctuation</h6>
                                    <p class="card-text h5" id="priceFluctuation">0.00%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

        <!-- Average Calculation Tab -->
        <div class="tab-average mt-2">
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

// <!-- Replace the existing script section with this fixed version -->
// Function to load daily trends data
function loadDailyTrendsData(month, productCode = 'all') {
    // Show loading state
    document.getElementById('highestDailyAvg').textContent = '...';
    document.getElementById('lowestDailyAvg').textContent = '...';
    document.getElementById('monthlyAverage').textContent = '...';
    document.getElementById('priceFluctuation').textContent = '...';
    document.getElementById('highestDate').textContent = '-';
    document.getElementById('lowestDate').textContent = '-';
    
    // Fetch data via AJAX
    fetch('get_daily_trends_data.php?month=' + month + '&product=' + productCode)
        .then(response => response.json())
        .then(data => {
            // Update summary statistics
            if (data.days && data.days.length > 0) {
                const highest = data.summary.highest;
                const lowest = data.summary.lowest;
                
                document.getElementById('highestDailyAvg').textContent = highest.avg_price.toFixed(2);
                document.getElementById('highestDate').textContent = formatDate(highest.date);
                
                document.getElementById('lowestDailyAvg').textContent = lowest.avg_price.toFixed(2);
                document.getElementById('lowestDate').textContent = formatDate(lowest.date);
                
                document.getElementById('monthlyAverage').textContent = data.summary.monthly_avg.toFixed(2);
                
                // Calculate price fluctuation percentage
                if (lowest.avg_price > 0) {
                    const fluctuation = ((highest.avg_price - lowest.avg_price) / lowest.avg_price * 100).toFixed(2);
                    document.getElementById('priceFluctuation').textContent = fluctuation + '%';
                } else {
                    document.getElementById('priceFluctuation').textContent = 'N/A';
                }
            } else {
                document.getElementById('highestDailyAvg').textContent = '0.00';
                document.getElementById('lowestDailyAvg').textContent = '0.00';
                document.getElementById('monthlyAverage').textContent = '0.00';
                document.getElementById('priceFluctuation').textContent = '0.00%';
                document.getElementById('highestDate').textContent = 'No data';
                document.getElementById('lowestDate').textContent = 'No data';
            }
            
            // Create the chart
            createDailyTrendsChart(data);
        })
        .catch(error => {
            console.error('Error loading daily trends data:', error);
            alert('Error loading daily trends data. Please try again.');
        });
}

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// Function to create the daily trends chart
function createDailyTrendsChart(data) {
    const ctx = document.getElementById('dailyTrendsChart').getContext('2d');
    
    // Destroy previous chart if it exists
    if (window.dailyTrendsChartInstance) {
        window.dailyTrendsChartInstance.destroy();
    }
    
    // Check if we have valid data
    if (!data.days || data.days.length === 0) {
        // Display a message instead of a chart
        ctx.font = '16px Arial';
        ctx.fillStyle = '#666';
        ctx.textAlign = 'center';
        ctx.fillText('No data available for the selected criteria', 
                     ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }
    
    // Prepare data - only include days with actual data
    const validDays = data.days.filter(day => day.avg_price !== null);
    
    if (validDays.length === 0) {
        // Display a message if no valid data points
        ctx.font = '16px Arial';
        ctx.fillStyle = '#666';
        ctx.textAlign = 'center';
        ctx.fillText('No price data available for the selected month', 
                     ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }
    
    const dates = validDays.map(day => {
        const date = new Date(day.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    const avgPrices = validDays.map(day => day.avg_price);
    
    // Create the chart
    window.dailyTrendsChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Daily Average Price',
                data: avgPrices,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true,
                tension: 0.1,
                pointBackgroundColor: 'rgb(54, 162, 235)',
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Daily Average Price Fluctuation - ' + data.month_name + 
                          (data.product_name ? ' (' + data.product_name + ')' : '')
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Average Price: LKR ' + context.parsed.y.toFixed(2);
                        },
                        title: function(context) {
                            // Show full date in tooltip
                            const index = context[0].dataIndex;
                            const fullDate = new Date(validDays[index].date);
                            return fullDate.toLocaleDateString('en-US', { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric' 
                            });
                        }
                    }
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Average Price (LKR)'
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
            }
        }
    });
}

// Event listeners
document.getElementById('tab-daily-trends').addEventListener('shown.bs.tab', function() {
    const month = document.getElementById('dailyTrendsMonthFilter').value;
    const product = document.getElementById('productFilter').value;
    loadDailyTrendsData(month, product);
});

document.getElementById('dailyTrendsMonthFilter').addEventListener('change', function() {
    const month = this.value;
    const product = document.getElementById('productFilter').value;
    loadDailyTrendsData(month, product);
});

document.getElementById('productFilter').addEventListener('change', function() {
    const month = document.getElementById('dailyTrendsMonthFilter').value;
    const product = this.value;
    loadDailyTrendsData(month, product);
});

// Initial load if daily trends tab is active
if (document.getElementById('tab-daily-trends').classList.contains('active')) {
    const month = document.getElementById('dailyTrendsMonthFilter').value;
    const product = document.getElementById('productFilter').value;
    loadDailyTrendsData(month, product);
}
</script>
</body>
</html>