<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\FGScosting\FGSCostingController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FGS Costing</title>
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
    body {
        background-color: #f8f9fa;
    }
    h1 {
        font-weight: bold;
        color: #0d6efd;
        margin-bottom: 30px;
    }
    .table-responsive {
        overflow-x: auto;
        position: relative;
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        background: #fff;
        padding: 1rem;
    }
    /* Main table cells */
    #fgsCostingTable th,
    #fgsCostingTable td {
        vertical-align: middle !important;
        text-align: center;
        font-size: 0.97rem;
        white-space: nowrap;
        min-width: 100px;
    }
    /* Sticky header */
    #fgsCostingTable thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #212529 !important;
        color: #fff !important;
        font-size: 1rem;
        border-bottom: 2px solid #0d6efd !important;
        text-align: center;
    }
    /* Zebra striping */
    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #f6f8fa;
    }
    /* Responsive font size */
    @media (max-width: 1200px) {
        #fgsCostingTable th, #fgsCostingTable td {
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
    .dataTables_wrapper .dataTables_filter { float: right; }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info { float: left; }
    .dataTables_wrapper .dataTables_paginate { float: right; margin-top: 0.5rem; }
    /* DataTables main and cloned headers: always black */
    #fgsCostingTable thead th,
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
    /* Optional scrollbar styling */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 4px;
    }
</style>
</head>
<body>
    <!-- FGS costing Header-->
    <div class="container-fluid mt-4">
        <h1 class="text-center mt-4">FGS Costing</h1>
        <div class="mb-2">
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#exchangeRateModal">
                <i class="fas fa-dollar-sign"></i> Change Exchange Rate
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCostingModal">
                <i class="fas fa-plus"></i> Costing Record
            </button>
        </div>
    </div>
    <!-- Type Filter -->
    <div class="container-fluid mb-4">
        <div class="mb-3">
            <label for="typeFilter" class="form-label fw-bold me-2">Filter by Type:</label>
            <select id="typeFilter" class="form-select d-inline-block w-auto">
                <option value="">Show All</option>
                <option value="Whole">Whole</option>
                <option value="Vacummed">Vacuumed Products</option>
                <option value="CleanedBulk">Costing for Cleaned Bulk</option>
            </select>
        </div>
    </div>
    <!-- Add Costing Modal -->
    <div class="modal fade" id="addCostingModal" tabindex="-1" aria-labelledby="addCostingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" id="fgsCostingForm" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCostingModalLabel">Add New FGS Costing</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="product_id" class="form-label">Product</label>
                                <select class="form-select" id="product_id" name="product_id" required>
                                    <option value="" disabled selected>Select Product</option>
                                    <?php
                                    $productSql = "SELECT id, product_name, category, product_code, scientific_name FROM products ORDER BY product_name ASC";
                                    $productResult = $conn->query($productSql);
                                    if ($productResult && $productResult->num_rows > 0) {
                                        while ($product = $productResult->fetch_assoc()) {
                                            echo '<option value="' . $product['id'] . '" 
                                                data-product-code="' . htmlspecialchars($product['product_code']) . '" 
                                                data-scientific-name="' . htmlspecialchars($product['scientific_name']) . '">'
                                                . htmlspecialchars($product['product_name']) . ' (' . htmlspecialchars($product['category']) . ')</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback">Please select a product.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="product_code" class="form-label">Product Code</label>
                                <input type="text" class="form-control" id="product_code" name="product_code" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="scientific_name" class="form-label">Scientific Name</label>
                                <input type="text" class="form-control" id="scientific_name" name="scientific_name" readonly>
                            </div>
                            <div class="col-md-12">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type" name="type" required>
                                <option value="" disabled selected>Select Type</option>
                                    <option value="Whole">Whole</option>
                                    <option value="Vacummed">Vacummed Products</option>
                                    <option value="CleanedBulk">Costing for Cleaned Bulk</option>
                                </select>
                                <div class="invalid-feedback">Please select a type.</div>
                            </div>
                            <!-- Add your other fields here as needed -->
                            <div class="col-md-4">
                                <label for="size" class="form-label">Size</label>
                                <input type="text" class="form-control" id="size" name="size" value="-">
                            </div>
                            <div class="col-md-4">
                                <label for="buying_price" class="form-label">Buying Price (LKR)</label>
                                <input type="number" class="form-control" id="buying_price" name="buying_price" step="0.01" required>
                                <div class="invalid-feedback">Please enter the buying price.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="volume" class="form-label">Volume (kg)</label>
                                <input type="number" class="form-control" id="volume" name="volume" step="0.01" required>
                                <div class="invalid-feedback">Please enter the volume.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="expected_yield" class="form-label">Expected Yield (%)</label>
                                <input type="number" class="form-control" id="expected_yield" name="expected_yield" step="0.01" required>
                                <div class="invalid-feedback">Please enter the expected yield.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="processing_charge" class="form-label">Processing Charge ($)</label>
                                <input type="number" class="form-control" id="processing_charge" name="processing_charge" step="0.01" value="0.6" required>
                                <div class="invalid-feedback">Please enter the processing charge.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="packaging_cost" class="form-label">Packaging Cost ($)</label>
                                <input type="number" class="form-control" id="packaging_cost" name="packaging_cost" step="0.01" value="0" required>
                                <div class="invalid-feedback">Please enter the packaging cost.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="freightcost" class="form-label">Freight Cost ($)</label>
                                <input type="number" class="form-control" id="freightcost" name="freightcost" step="0.01" value="3.8" required>
                                <div class="invalid-feedback">Please enter the freight cost.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="buyingandlogistic_cost" class="form-label">Buying Cost & Logistics</label>
                                <input type="number" class="form-control" id="buyingandlogistic_cost" name="buyingandlogistic_cost" step="0.01" value="20" required>
                                <div class="invalid-feedback">Please enter the buying cost and logistics.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="estimate_gross_to_net" class="form-label">Estimate Gross to Net (%)</label>
                                <input type="number" class="form-control" id="estimate_gross_to_net" name="estimate_gross_to_net" step="0.01" value="0" required>
                                <div class="invalid-feedback">Please enter the estimate gross to net.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_fgs_costing" class="btn btn-primary">Add Costing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCostingModal" tabindex="-1" aria-labelledby="deleteCostingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteCostingForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteCostingModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this costing record?
                        <input type="hidden" name="costing_id" id="delete_costing_id">
                        <input type="hidden" name="delete_fgs_costing" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
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
    <!-- Costing Table -->
    <div class="container-fluid mb-5">
        <div class="table-responsive">
            <table id="fgsCostingTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th class="text-center">Product Code</th>
                        <th class="text-center">Product Name</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Size</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Buying Price (LKR)</th>
                        <th class="text-center">Volume (kg)</th>
                        <th class="text-center">Expected Yield (%)</th>
                        <th class="text-center">Processed Cost</th>
                        <th class="text-center">Buying Cost and Logistic</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">USD Rate</th>
                        <th class="text-center">USD Price</th>
                        <th class="text-center">Processing Charge ($)</th>
                        <th class="text-center">Package Cost ($)</th>
                        <th class="text-center">Freight Cost ($)</th>
                        <th class="text-center">Estimate Gross to Net (%)</th>
                        <th class="text-center">Freight Cost for the Gross</th>
                        <th class="text-center">CNF(USD)</th>
                        <th class="text-center">£ Rate</th>
                        <th class="text-center">CNF(£)</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($costingDatafgs as $costing): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($costing['product_code']); ?></td>
                            <td><?php echo htmlspecialchars($costing['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($costing['type']); ?></td>
                            <td><?php echo htmlspecialchars($costing['size']); ?></td>
                            <td><?php echo htmlspecialchars($costing['category']); ?></td>
                            <td>LKR. <?php echo number_format($costing['buyingprice'], 2); ?></td>
                            <td><?php echo number_format($costing['volume'], 2); ?> kg</td>
                            <td><?php echo number_format($costing['expectedyield'], 2); ?>%</td>
                            <td>LKR.
                                <?php 
                                    $processedCost = ($costing['buyingprice'] / ($costing['expectedyield'] / 100));
                                    echo number_format($processedCost, 2); 
                                ?>
                            </td>
                            <td>LKR. <?php echo number_format($costing['buyingcostandlogic'], 2); ?></td>
                            <td>LKR. <?php echo number_format($processedCost + $costing['buyingcostandlogic'], 2); ?></td>
                            <td><?= htmlspecialchars($exchangeRateUsdtoLkr, 2); ?></td>
                            <td>$
                                <?php 
                                    $usdPrice = ($processedCost + $costing['buyingcostandlogic']) / $exchangeRateUsdtoLkr;
                                    echo number_format($usdPrice, 2); 
                                ?>
                            </td>
                            <td>$<?php echo number_format($costing['processingcharge'], 2); ?></td>
                            <td>$<?php echo number_format($costing['packagingcost'], 2); ?></td>
                            <td>$<?php echo number_format($costing['freightcost'], 2); ?></td>
                            <td><?php echo number_format($costing['estgrosstonet'], 2); ?>%</td>
                            <td>
                                <?php
                                    $freightForGross = ($costing['freightcost'] + $costing['freightcost'] * ($costing['estgrosstonet'] / 100));
                                    echo number_format($freightForGross, 2);
                                ?>
                            </td>
                            <td>
                                <?php
                                    $cnfUsd = $usdPrice + $costing['processingcharge'] + $costing['packagingcost'] + $freightForGross;
                                    echo number_format($cnfUsd, 2);
                                ?>
                            </td>
                            <td><?= htmlspecialchars($exchangeRateUsdtoGbp, 2); ?></td>
                            <td>£
                                <?php
                                    $cnfGbp = $cnfUsd / $exchangeRateUsdtoGbp;
                                    echo number_format($cnfGbp, 2);
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" data-id="<?php echo $costing['id']; ?>">
                                    <i class="fa fa-edit"></i> 
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $costing['id']; ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<script>
$(document).ready(function() {
    // Initialize DataTable with FixedColumns
    var table = $('#fgsCostingTable').DataTable({
        scrollX: true,
        scrollY: "60vh",
        scrollCollapse: true,
        paging: true,
        fixedHeader: true,
        autoWidth: false,
        fixedColumns: {
            left: 4, // Fix first 4 columns (Product Code to Specification)
            right: 1  // Fix last column (Actions)
        },
        columnDefs: [
            { targets: [2], visible: false } // Hide Type column (3rd column)
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records..."
        }
    });

    // --- Remember and auto-apply Type Filter ---
    $('#typeFilter').on('change', function() {
        var selectedType = $(this).val();
        localStorage.setItem('fgsTypeFilter', selectedType); // Save filter
        table.column(2).search(selectedType).draw(); // Apply filter to column 2 (Type)
    });

    // --- On page load, restore the previous filter ---
    var savedType = localStorage.getItem('fgsTypeFilter');
    if (savedType !== null && savedType !== "") {
        $('#typeFilter').val(savedType);
        table.column(2).search(savedType).draw(); // Apply saved filter
    }

    // --- TOOLTIP INIT ---
    $('[data-bs-toggle="tooltip"]').tooltip();

    // --- STICKY HEADER FIX (Bootstrap + DataTables) ---
    $('.dataTables_scrollHead').css({
        'position': 'sticky',
        'top': '0',
        'z-index': '10'
    });
    
    // --- Populate Product Code and Scientific Name on Product Change ---
    $('#product_id').on('change', function() {
        var selected = $(this).find('option:selected');
        $('#product_code').val(selected.data('product-code') || '');
        $('#scientific_name').val(selected.data('scientific-name') || '');
    });

    // Function to reset modal to add state
    function resetModalToAddState() {
        $('#fgsCostingForm')[0].reset();
        $('#fgsCostingForm').removeClass('was-validated');
        $('#fgsCostingForm input[name="costing_id"]').remove();
        $('#fgsCostingForm button[type="submit"]').attr('name', 'add_fgs_costing').text('Add Costing');
        $('#addCostingModalLabel').text('Add New FGS Costing');
        $('#product_code').val('');
        $('#scientific_name').val('');
    }

    // When add button is clicked, reset the modal
    $('button[data-bs-target="#addCostingModal"]').on('click', function() {
        resetModalToAddState();
    });

    // When modal is hidden, reset it to add state
    $('#addCostingModal').on('hidden.bs.modal', function() {
        resetModalToAddState();
    });

    // Form validation
    (function () {
        'use strict';
        var form = document.getElementById('fgsCostingForm');
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();

    // Edit button click
    $(document).on('click', '.edit-btn', function() {
        var costingId = $(this).data('id');

        $.ajax({
            url: 'FGSCosting.php',
            type: 'POST',
            data: { get_costing: 1, costing_id: costingId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    // Reset form first
                    $('#fgsCostingForm')[0].reset();
                    $('#fgsCostingForm input[name="costing_id"]').remove();
                    
                    // Add hidden field for costing ID
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'costing_id',
                        value: costingId
                    }).appendTo('#fgsCostingForm');

                    // Set product and trigger change to populate code and scientific name
                    $('#product_id').val(data.costing.product_id).trigger('change');

                    // Fill other fields
                    $('#type').val(data.costing.type);
                    $('#size').val(data.costing.size);
                    $('#specification').val(data.costing.specification);
                    $('#buying_price').val(data.costing.buyingprice);
                    $('#volume').val(data.costing.volume);
                    $('#expected_yield').val(data.costing.expectedyield);
                    $('#processing_charge').val(data.costing.processingcharge);
                    $('#packaging_cost').val(data.costing.packagingcost);
                    $('#freightcost').val(data.costing.freightcost);
                    $('#buyingandlogistic_cost').val(data.costing.buyingcostandlogic);
                    $('#estimate_gross_to_net').val(data.costing.estgrosstonet);

                    // Update button and modal title
                    $('#fgsCostingForm button[type="submit"]').attr('name', 'edit_fgs_costing').text('Update Costing');
                    $('#addCostingModalLabel').text('Edit FGS Costing');
                    $('#fgsCostingForm').removeClass('was-validated');

                    // Show modal
                    $('#addCostingModal').modal('show');
                } else {
                    alert('Could not fetch costing data.');
                }
            },
            error: function() {
                alert('Error fetching costing data.');
            }
        });
    });

    // Delete button click
    $(document).on('click', '.delete-btn', function() {
        var costingId = $(this).data('id');
        $('#delete_costing_id').val(costingId);
        $('#deleteCostingModal').modal('show');
    });
});
</script>
</body>
</html>