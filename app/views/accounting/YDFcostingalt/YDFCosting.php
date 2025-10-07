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
    <!-- DataTables FixedColumns -->
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
    <style>
        /* Body and page title */
        body {
            background-color: #f8f9fa;
        }

        h1 {
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 30px;
        }

        /* Table container with horizontal scroll */
        .table-responsive {
            overflow-x: auto;
            position: relative;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            background: #fff;
            padding: 1rem;
        }

        /* Main table cells */
        #ydfCostingTable th,
        #ydfCostingTable td {
            white-space: nowrap;
            vertical-align: middle !important;
            text-align: center;
            font-size: 0.97rem;
        }

        /* Main table header */
        #ydfCostingTable thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #212529 !important; /* black */
            color: #fff;
            font-size: 1rem;
            border-bottom: 2px solid #0d6efd;
        }

        /* Sticky columns */
        #ydfCostingTable th.sticky-col,
        #ydfCostingTable td.sticky-col {
            position: sticky;
            left: 0;
            z-index: 4;
            background: #fff; /* body cells */
            min-width: 150px;
            max-width: 200px;
            box-shadow: 2px 0 5px -2px #ccc;
        }

        #ydfCostingTable thead th.sticky-col {
            background: #212529 !important; /* header black */
            color: #fff !important;
            z-index: 6;
        }

        #ydfCostingTable th.sticky-col-2,
        #ydfCostingTable td.sticky-col-2 {
            position: sticky;
            left: 150px;
            z-index: 4;
            background: #fff;
            min-width: 150px;
            max-width: 200px;
            box-shadow: 2px 0 5px -2px #ccc;
        }

        #ydfCostingTable thead th.sticky-col-2 {
            background: #212529 !important;
            color: #fff !important;
            z-index: 6;
        }

        #ydfCostingTable th.sticky-col-3,
        #ydfCostingTable td.sticky-col-3 {
            position: sticky;
            left: 300px;
            z-index: 4;
            background: #fff;
            min-width: 350px;
            max-width: 400px;
            box-shadow: 2px 0 5px -2px #ccc;
        }

        #ydfCostingTable thead th.sticky-col-3 {
            background: #212529 !important;
            color: #fff !important;
            z-index: 6;
        }

        /* Zebra striping */
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #f6f8fa;
        }

        /* Responsive font size */
        @media (max-width: 1200px) {
            #ydfCostingTable th, #ydfCostingTable td {
                font-size: 0.92rem;
            }
        }

        /* Modal styling */
        .modal-content {
            border-radius: 0.7rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
        }
        .modal-header {
            background: #0d6efd;
            color: #fff;
            border-top-left-radius: 0.7rem;
            border-top-right-radius: 0.7rem;
        }
        .modal-title {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .modal-footer {
            background: #f8f9fa;
            border-bottom-left-radius: 0.7rem;
            border-bottom-right-radius: 0.7rem;
        }

        /* Button spacing */
        .mb-2 > .btn, .mb-2 > button {
            margin-right: 0.5rem;
        }

        /* Sticky DataTables controls */
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            position: sticky;
            top: 0;
            z-index: 12;
            background: #fff;
            padding: 0.5rem 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Align controls */
        .dataTables_wrapper .dataTables_filter { float: right; }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_info { float: left; }
        .dataTables_wrapper .dataTables_paginate { float: right; margin-top: 0.5rem; }

        /* FixedColumns cloned headers for first 3 sticky columns */
        .dataTables_wrapper .DTFC_LeftHeadWrapper th,
        .dataTables_wrapper .DTFC_LeftBodyWrapper td {
            background-color: #212529 !important; /* header black */
            color: #fff !important;
            border-bottom: 2px solid #0d6efd !important;
        }

        /* Optional: Right fixed columns (if any) */
        .dataTables_wrapper .DTFC_RightHeadWrapper th,
        .dataTables_wrapper .DTFC_RightBodyWrapper td {
            background-color: #212529 !important;
            color: #fff !important;
        }

        /* Optional scrollbar styling */
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.2);
            border-radius: 4px;
        }

        /* Force header color for both main and cloned (fixed) headers */
        #ydfCostingTable thead th,
        .dataTables_wrapper .DTFC_LeftHeadWrapper th,
        .dataTables_wrapper .DTFC_RightHeadWrapper th {
            background: #212529 !important;
            color: #fff !important;
            border-bottom: 2px solid #0d6efd !important;
        }

        /* Force black background and white text for all DataTables headers, including FixedColumns clones */
        #ydfCostingTable thead th,
        .dataTables_scrollHeadInner th,
        .dataTables_scrollHead th,
        .dataTables_wrapper .DTFC_LeftHeadWrapper th,
        .dataTables_wrapper .DTFC_RightHeadWrapper th,
        .dataTables_wrapper .DTFC_LeftHeadWrapper table th,
        .dataTables_wrapper .DTFC_RightHeadWrapper table th {
            background: #212529 !important;
            color: #fff !important;
            border-bottom: 2px solid #0d6efd !important;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center text-primary mb-4"><b>YDF Costing</b></h1>
        <div class="mb-2">
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#exchangeRateModal">
                <i class="fas fa-dollar-sign"></i> Change Exchange Rate
            </button>

            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#costingModal" id="addCostingBtn"><i class="fa fa-plus"></i> Costing Record Input</button>
        </div>
        <!-- CostingEnter Modal-->
        <div class="modal fade" id="costingModal" tabindex="-1" aria-labelledby="costingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Costing Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="costingForm" method="POST" class="needs-validation" novalidate>
                        <div class="modal-body">
                            <div class="row g-3">
                                
                                <div class="col-md-12">
                                    <label for="productSelect" class="form-label">Product: </label>
                                    <select id="productSelect" name="product_id" class="form-select" required>
                                        <option value="" disabled selected>Select a product</option>
                                        <?php
                                        // Fetch products from the database
                                        $productSql = "SELECT id, product_name, product_code, scientific_name FROM products ORDER BY product_name ASC";
                                        $productResult = $conn->query($productSql);
                                        if ($productResult && $productResult->num_rows > 0) {
                                            while ($productRow = $productResult->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($productRow['id']) . '" 
                                                    data-product-code="' . htmlspecialchars($productRow['product_code']) . '" 
                                                    data-scientific-name="' . htmlspecialchars($productRow['scientific_name']) . '">'
                                                    . htmlspecialchars($productRow['product_name']) . '</option>';
                                            }
                                        }
                                        ?>                                 </select>    
                                    <div class="invalid-feedback">Please select a product.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="productCode" class="form-label">Product Code: </label>
                                    <input type="text" id="productCode" name="product_code" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="scientificName" class="form-label">Scientific Name: </label>
                                    <input type="text" id="scientificName" name="scientific_name" class="form-control" readonly>
                                </div>
                                <div class="col-md-12">
                                    <label for="specification" class="form-label">Specification: </label>
                                    <input type="text" id="specification" name="specification" class="form-control" required>
                                    <div class="invalid-feedback">Please enter a specification.</div>   
                                </div>
                                <div class="col-md-4">
                                    <label for="buyingPrice" class="form-label">Buying Price: </label>
                                    <input type="number" id="buyingPrice" name="buyingprice" class="form-control" min="0" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid buying price.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="volume" class="form-label">Volume (g): </label>
                                    <input type="number" id="volume" name="volume" class="form-control" min="0" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid volume.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="expectedYield" class="form-label">Expected Yield (%): </label>
                                    <input type="number" id="expectedYield" name="expectedyield" class="form-control" min="0" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid expected yield.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="buyingLogistic" class="form-label">Buying Cost & Buying Logistic (LKR): </label>
                                    <input type="number" id="buyingLogistic" name="buying_logistic" class="form-control" min="0" value="50" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid buying & logistic cost.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="processingCharge" class="form-label">Processing Charge (USD): </label>
                                    <input type="number" id="processingCharge" name="processingcharge" class="form-control" min="0" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid processing charge.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="packageCost" class="form-label">Packaging Cost (USD): </label>
                                    <input type="number" id="packageCost" name="packagecost" class="form-control" value="0" min="0" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid packaging cost.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="freightCost" class="form-label">Freight Cost (USD): </label>
                                    <input type="number" id="freightCost" name="freightcost" class="form-control" min="0" value="6" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid freight cost.</div>  
                                </div>
                                <div class="col-md-6">
                                    <label for="estimateGrossToNet" class="form-label">Estimate Gross to Net (%): </label>
                                    <input type="number" id="estimateGrossToNet" name="estimategrosstonet" class="form-control" min="0" value="25" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid estimate gross to net ratio.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="margin" class="form-label">Margin (%): </label>
                                    <input type="number" id="margin" name="margin" class="form-control" min="0" value="1" step="any" required>
                                    <div class="invalid-feedback">Please enter a valid margin.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" id="costingSubmitBtn" name="addCosting" class="btn btn-primary px-4">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
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

        <!-- 500g Rounded Price & MCO+ Modal -->
        <div class="modal fade" id="roundedPriceModal" tabindex="-1" aria-labelledby="roundedPriceModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="roundedPriceForm" method="post" class="modal-content needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="roundedPriceModalLabel">Enter 500g Rounded Price & MCO+ Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="costing_id" id="roundedPriceCostingId">
                    <div class="mb-3">
                    <label for="rounded_price_500g" class="form-label">500g Rounded Price (USD)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="rounded_price_500g" name="rounded_price_500g" required>
                    <div class="invalid-feedback">Please enter the 500g rounded price.</div>
                    </div>
                    <div class="mb-3">
                    <label for="mco_plus_price" class="form-label">MCO+ Price (USD)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="mco_plus_price" name="mco_plus_price" required>
                    <div class="invalid-feedback">Please enter the MCO+ price.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_rounded_mco" class="btn btn-primary">Save</button>
                </div>
                </form>
            </div>
        </div>

        <!--Table-->
        <div class="table-responsive rounded shadow-sm mt-4 mb-4">
            <table id="ydfCostingTable" class="table table-bordered table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="sticky-col text-center">Product Code</th>
                        <th class="sticky-col-2 text-center">Product Name</th>
                        <th class="sticky-col-3 text-center">Specification</th>
                        <th class="text-center">Buying Price</th>
                        <th class="text-center">Volume</th>
                        <th class="text-center">Expected Yield</th>
                        <th class="text-center">Processed Cost</th>
                        <th class="text-center">Buying and Logistics Cost</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Exchange Rate to USD</th>
                        <th class="text-center">USD Price</th>
                        <th class="text-center">Processing</th>
                        <th class="text-center">Packaging Cost</th>
                        <th class="text-center">Freight Cost</th>
                        <th class="text-center">Estimated Gross to Net Ratio</th>
                        <th class="text-center">Freight Cost for the Gross</th>
                        <th class="text-center">CNF</th>
                        <th class="text-center">Margin</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Price 500g</th>
                        <th class="text-center">Rounded Price</th>
                        <th class="text-center">Price + MCO</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($costingData as $row): ?>
                    <tr>
                        <td class="sticky-col"><?= htmlspecialchars($row['product_code']); ?></td>
                        <td class="sticky-col-2"><?= htmlspecialchars($row['product_name']); ?></td>
                        <td class="sticky-col-3"><?= htmlspecialchars($row['specification']); ?></td>
                        <td>LKR.<?= htmlspecialchars($row['buyingprice']); ?></td>
                        <td><?= htmlspecialchars($row['volume']); ?></td>
                        <td><?= htmlspecialchars($row['expectedyield'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                        <td>LKR.
                            <?php 
                                $processingCost = $row['buyingprice'] / ($row['expectedyield'] / 100);
                                echo htmlspecialchars(number_format($processingCost, 2));
                            ?>
                        </td>
                        <td>$<?= htmlspecialchars($row['buying_logistic']); ?></td>
                        <td>LKR.
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
                        <td>$<?= htmlspecialchars($row['freightcost']); ?></td>
                        <td><?= htmlspecialchars($row['estimategrosstonet']); ?>%</td>
                        <td>
                            <?php 
                                $freightCostGross = ($row['freightcost'] * ($row['estimategrosstonet']/100)) + $row['freightcost'];
                                echo htmlspecialchars(number_format($freightCostGross, 2));
                            ?>
                        </td>
                        <?php $cnf = ($usdPrice + $row['processingcharge'] + $row['packagecost'] + $freightCostGross); ?>
                        <td>$<?= htmlspecialchars(number_format($cnf, 2)); ?></td>
                        <td><?= htmlspecialchars($row['margin']); ?></td>
                        <td>$
                            <?php 
                                $margin = $row['margin']; // Convert percentage to decimal
                                $price = $cnf + $margin;
                                echo htmlspecialchars(number_format($price, 2));
                            ?>
                        </td>
                        <td>$<?= htmlspecialchars(number_format(($price / 2), 2)); ?></td>
                        <td>
                            <?php 
                                if ($row['500groundedprice']) {
                                    echo '$' . htmlspecialchars(number_format($row['500groundedprice'], 2));
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                        <td>
                            <?php 
                                if ($row['500grounded_MCO']) {
                                    echo '$' . htmlspecialchars(number_format($row['500grounded_MCO'], 2));
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning edit-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#costingModal"
                                    data-id="<?= $row['id'] ?>"
                                    data-product-id="<?= $row['product_id'] ?>"
                                    data-product-code="<?= htmlspecialchars($row['product_code']) ?>"
                                    data-scientific-name="<?= htmlspecialchars($row['scientific_name']) ?>"
                                    data-specification="<?= htmlspecialchars($row['specification']) ?>"
                                    data-buyingprice="<?= $row['buyingprice'] ?>"
                                    data-volume="<?= $row['volume'] ?>"
                                    data-expectedyield="<?= $row['expectedyield'] ?>"
                                    data-buying-logistic="<?= $row['buying_logistic'] ?>"
                                    data-processingcharge="<?= $row['processingcharge'] ?>"
                                    data-packagecost="<?= $row['packagecost'] ?>"
                                    data-freightcost="<?= $row['freightcost'] ?>"
                                    data-estimategrosstonet="<?= $row['estimategrosstonet'] ?>"
                                    data-margin="<?= $row['margin'] ?>"
                                >
                                    <i class="fas fa-edit"></i>
                                </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                <input type="hidden" name="costing_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_costing" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> 
                                </button>
                                <button type="button"
                                    class="btn btn-info btn-sm rounded-price-btn"
                                    data-costing-id="<?= $row['id'] ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#roundedPriceModal"
                                    title="Enter 500g Rounded Price and MCO+ Price">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </form>
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
        "scrollX": true, // enable horizontal scroll
        "fixedColumns": {
            leftColumns: 3 // matches your sticky-col setup
        },
        "order": [[0, 'asc']],
        "language": {
            "emptyTable": "No costing records available",
            "search": "_INPUT_",
            "searchPlaceholder": "Search records...",
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

document.getElementById('productSelect').addEventListener('change', function() {
var selected = this.options[this.selectedIndex];
document.getElementById('productCode').value = selected.getAttribute('data-product-code') || '';
document.getElementById('scientificName').value = selected.getAttribute('data-scientific-name') || '';
});

// When edit button is clicked, fill modal fields
$('.edit-btn').on('click', function() {
    $('#costingForm')[0].reset();

    // Set form to edit mode
    $('#costingSubmitBtn').attr('name', 'edit_costing').text('Update');
    if (!$('#costingForm input[name="costing_id"]').length) {
        $('#costingForm').prepend('<input type="hidden" name="costing_id" />');
    }
    $('#costingForm input[name="costing_id"]').val($(this).data('id'));

    // Fill fields as before...
    $('#productSelect').val($(this).data('product-id')).trigger('change');
    $('#productCode').val($(this).data('product-code'));
    $('#scientificName').val($(this).data('scientific-name'));
    $('#specification').val($(this).data('specification'));
    $('#buyingPrice').val($(this).data('buyingprice'));
    $('#volume').val($(this).data('volume'));
    $('#expectedYield').val($(this).data('expectedyield'));
    $('#buyingLogistic').val($(this).data('buying-logistic'));
    $('#processingCharge').val($(this).data('processingcharge'));
    $('#packageCost').val($(this).data('packagecost'));
    $('#freightCost').val($(this).data('freightcost'));
    $('#estimateGrossToNet').val($(this).data('estimategrosstonet'));
    $('#margin').val($(this).data('margin'));
});

// When add button is clicked, reset to add mode
$('#addCostingBtn').on('click', function() {
    $('#costingForm')[0].reset();
    $('#costingSubmitBtn').attr('name', 'addCosting').text('Save');
    $('#costingForm input[name="costing_id"]').remove();
});

// Show modal and set costing_id when 500g/MCO+ button is clicked
$('.rounded-price-btn').on('click', function() {
    $('#roundedPriceForm')[0].reset();
    $('#roundedPriceCostingId').val($(this).data('costing-id'));
});

// Bootstrap 5 validation for modal form
(function () {
    'use strict';
    var form = document.getElementById('roundedPriceForm');
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();

// Bootstrap 5 validation for modal form
(function () {
        'use strict';
        var form = document.getElementById('costingForm');
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
</script>
</body>
</html>