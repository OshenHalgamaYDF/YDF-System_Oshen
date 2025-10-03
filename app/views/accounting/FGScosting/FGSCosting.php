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
    /* Sticky columns */
    #fgsCostingTable th.sticky-col,
    #fgsCostingTable td.sticky-col {
        position: sticky;
        left: 0;
        z-index: 4;
        background: #fff;
        min-width: 150px;
        max-width: 180px;
        box-shadow: 2px 0 5px -2px #ccc;
    }
    #fgsCostingTable thead th.sticky-col {
        background: #212529 !important;
        color: #fff !important;
        z-index: 6;
    }
    #fgsCostingTable th.sticky-col-1,
    #fgsCostingTable td.sticky-col-1 {
        position: sticky;
        left: 150px;
        z-index: 4;
        background: #fff;
        min-width: 220px;
        max-width: 250px;
        box-shadow: 2px 0 5px -2px #ccc;
    }
    #fgsCostingTable thead th.sticky-col-1 {
        background: #212529 !important;
        color: #fff !important;
        z-index: 6;
    }
    /* Sticky column 2 */
    #fgsCostingTable th.sticky-col-2,
    #fgsCostingTable td.sticky-col-2 {
        position: sticky;
        left: 370px; /* sum of previous sticky column widths */
        z-index: 4;
        background: #fff;
        min-width: 130px;
        max-width: 150px;
        box-shadow: 2px 0 5px -2px #ccc;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #fgsCostingTable thead th.sticky-col-2 {
        background: #212529 !important;
        color: #fff !important;
        z-index: 6;
    }

    /* Sticky column 3 */
    #fgsCostingTable th.sticky-col-3,
    #fgsCostingTable td.sticky-col-3 {
        position: sticky;
        z-index: 3; /* below header */
        background: #fff;
        min-width: 220px;
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        box-shadow: 2px 0 5px -2px #ccc;
    }

    /* Header for sticky column 3 */
    #fgsCostingTable thead th.sticky-col-3 {
        background: #212529 !important;
        color: #fff !important;
        z-index: 6; /* above all */
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
    /* FixedColumns cloned headers for sticky columns */
    .dataTables_wrapper .DTFC_LeftHeadWrapper th,
    .dataTables_wrapper .DTFC_LeftBodyWrapper td,
    .dataTables_wrapper .DTFC_RightHeadWrapper th,
    .dataTables_wrapper .DTFC_RightBodyWrapper td {
        background-color: #212529 !important;
        color: #fff !important;
        border-bottom: 2px solid #0d6efd !important;
        text-align: center;
    }
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
    <div class="container-fluid mt-4">
        <h1 class="text-center mt-4">FGS Costing</h1>
        <div class="mb-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCostingModal">
                <i class="fa fa-plus"></i> Add Costing
            </button>
        </div>
    </div>
    <div class="container-fluid mb-4">
        <div class="mb-3">
            <label for="typeFilter" class="form-label fw-bold me-2">Filter by Type:</label>
            <select id="typeFilter" class="form-select d-inline-block w-auto">
                <option value="">Show All</option>
                <option value="Whole">Whole</option>
                <option value="Vacuumed">Vacuumed Products</option>
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
                                    $productSql = "SELECT id, product_name, product_code, scientific_name FROM products ORDER BY product_name ASC";
                                    $productResult = $conn->query($productSql);
                                    if ($productResult && $productResult->num_rows > 0) {
                                        while ($product = $productResult->fetch_assoc()) {
                                            echo '<option value="' . $product['id'] . '" 
                                                data-product-code="' . htmlspecialchars($product['product_code']) . '" 
                                                data-scientific-name="' . htmlspecialchars($product['scientific_name']) . '">'
                                                . htmlspecialchars($product['product_name']) . '</option>';
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
                                    <option value="Vacummed">Vacummed Packed</option>
                                </select>
                                <div class="invalid-feedback">Please select a type.</div>
                            </div>
                            <!-- Add your other fields here as needed -->
                            <div class="col-md-6">
                                <label for="size" class="form-label">Size</label>
                                <input type="text" class="form-control" id="size" name="size">
                            </div>
                            <div class="col-md-6">
                                <label for="specification" class="form-label">Specification</label>
                                <input type="text" class="form-control" id="specification" name="specification" required>
                                <div class="invalid-feedback">Please enter the specification.</div>
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
                                <input type="number" class="form-control" id="processing_charge" name="processing_charge" step="0.01" required>
                                <div class="invalid-feedback">Please enter the processing charge.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="packaging_cost" class="form-label">Packaging Cost ($)</label>
                                <input type="number" class="form-control" id="packaging_cost" name="packaging_cost" step="0.01" required>
                                <div class="invalid-feedback">Please enter the packaging cost.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="freightcost" class="form-label">Freight Cost ($)</label>
                                <input type="number" class="form-control" id="freightcost" name="freightcost" step="0.01" required>
                                <div class="invalid-feedback">Please enter the freight cost.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="buyingandlogistic_cost" class="form-label">Buying Cost & Logistics</label>
                                <input type="number" class="form-control" id="buyingandlogistic_cost" name="buyingandlogistic_cost" step="0.01" required>
                                <div class="invalid-feedback">Please enter the buying cost and logistics.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="estimate_gross_to_net" class="form-label">Estimate Gross to Net (%)</label>
                                <input type="number" class="form-control" id="estimate_gross_to_net" name="estimate_gross_to_net" step="0.01" required>
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


    <!-- Costing Table -->
    <div class="container-fluid mb-5">
        <div class="table-responsive">
            <table id="fgsCostingTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th class="sticky-col">Product Code</th>
                        <th class="sticky-col-1">Product Name</th>
                        <th class="sticky-col-2">Size</th>
                        <th class="sticky-col-3">Specification</th>
                        <th>Buying Price (LKR)</th>
                        <th>Volume (kg)</th>
                        <th>Expected Yield (%)</th>
                        <th>Processed Cost</th>
                        <th>Buying Cost and Logistic</th>
                        <th>Total</th>
                        <th>USD Rate</th>
                        <th>USD Price</th>
                        <th>Processing Charge ($)</th>
                        <th>Package Cost ($)</th>
                        <th>Freight Cost ($)</th>
                        <th>Estimate Gross to Net (%)</th>
                        <th>Freight Cost for the Gross</th>
                        <th>CNF(USD)</th>
                        <th>£ Rate</th>
                        <th>CNF(£)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($costingDatafgs as $costing): ?>
                        <tr>
                            <td class="sticky-col"><?php echo htmlspecialchars($costing['product_code']); ?></td>
                            <td class="sticky-col-1"><?php echo htmlspecialchars($costing['product_name']); ?></td>
                            <td class="sticky-col-2"><?php echo htmlspecialchars($costing['size']); ?></td>
                            <td class="sticky-col-3"><?php echo htmlspecialchars($costing['specification']); ?></td>
                            <td><?php echo number_format($costing['buyingprice'], 2); ?></td>
                            <td><?php echo number_format($costing['volume'], 2); ?></td>
                            <td><?php echo number_format($costing['expectedyield'], 2); ?>%</td>
                            <td>
                                <?php 
                                    $processedCost = ($costing['buyingprice'] / ($costing['expectedyield'] / 100));
                                    echo number_format($processedCost, 2); 
                                ?>
                            </td>
                            <td><?php echo number_format($costing['buyingcostandlogic'], 2); ?></td>
                            <td><?php echo number_format($processedCost + $costing['buyingcostandlogic'], 2); ?></td>
                            <td><?= htmlspecialchars($exchangeRateUsdtoLkr, 2); ?></td>
                            <td>
                                <?php 
                                    $usdPrice = ($processedCost + $costing['buyingcostandlogic']) / $exchangeRateUsdtoLkr;
                                    echo number_format($usdPrice, 2); 
                                ?>
                            </td>
                            <td><?php echo number_format($costing['processingcharge'], 2); ?></td>
                            <td><?php echo number_format($costing['packagingcost'], 2); ?></td>
                            <td><?php echo number_format($costing['freightcost'], 2); ?></td>
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
                            <td>
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
    var table = $('#fgsCostingTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true,
        "lengthChange": true,
        "scrollX": true,
        "fixedColumns": {
            leftColumns: 4
        },
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

    // Filter by Type dropdown
    $('#typeFilter').on('change', function() {
        var val = $(this).val();
        // "Type" is the 5th column (index 4) if you add it as a column, adjust index as needed
        table.column(3).search(val).draw(); // Adjust index if needed
    });
});

$('#product_id').on('change', function() {
    var selected = $(this).find('option:selected');
    $('#product_code').val(selected.data('product-code') || '');
    $('#scientific_name').val(selected.data('scientific-name') || '');
});

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

$(document).on('click', '.edit-btn', function() {
    var costingId = $(this).data('id');

    $.ajax({
        url: 'FGSCosting.php',
        type: 'POST',
        data: { get_costing: 1, costing_id: costingId },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                // Reset form and fill data
                $('#fgsCostingForm')[0].reset();
                $('#fgsCostingForm input[name="costing_id"]').remove();
                $('<input>').attr({
                    type: 'hidden',
                    name: 'costing_id',
                    value: costingId
                }).appendTo('#fgsCostingForm');

                $('#product_id').val(data.costing.product_id).trigger('change');

                // Fill readonly fields after change triggers
                setTimeout(function() {
                    $('#product_code').val(data.costing.product_code || '');
                    $('#scientific_name').val(data.costing.scientific_name || '');
                }, 100);

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

                $('#fgsCostingForm button[type="submit"]').attr('name', 'edit_fgs_costing').text('Update Costing');
                $('#addCostingModalLabel').text('Edit FGS Costing');
                $('#fgsCostingForm').removeClass('was-validated');

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

// Filter by Type
$('#typeFilter').on('change', function() {
    var selectedType = $(this).val();
    var table = $('#fgsCostingTable').DataTable();
    table.column(3).search(selectedType).draw(); // Assuming 'Type' is the fourth column
});

</script>
</body>
</html>
