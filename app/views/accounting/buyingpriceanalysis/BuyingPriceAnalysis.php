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
                            <input type="text" name="product_code" id="product_code" class="form-control" required>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this record? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-fluid">
       <!-- Bootstrap Tabs -->
        <ul class="nav nav-underline" id="productTabs" role="tablist">
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
        </ul>

        <div class="tab-content mt-2">
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
                                            &nbsp;
                                            <button class='btn btn-sm btn-danger delete-btn' data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                <i class="fas fa-trash"></i>
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


        <div class="tab-average mt-2">
            <div class="tab-pane fade" 
                id="content-average" 
                role="tabpanel">
            <div class="table-responsive">
                <h4><b>Average Calculation</b></h4>
                <table class="table table-striped table-hover text-center align-middle table-bordered" id="averageTable">
                    <thead class="table-primary table-dark">
                        <tr>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Size Range</th>
                            <th>Target Price</th>
                            <th>Average Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT product_code, product_name, size_range, target_price, AVG(sold_price) AS average_price 
                                FROM buyingpriceanlaysistable 
                                GROUP BY product_code, product_name, size_range, target_price";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {?>
                                <tr class='text-center'>
                                    <td><?= htmlspecialchars($row['product_code']) ?></td>
                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                    <td><?= htmlspecialchars($row['size_range']) ?></td>
                                    <td><?= number_format($row['target_price'],2) ?></td>
                                    <td><?= number_format($row['average_price'],2) ?></td>
                                </tr>
                            <?php }
                        } else {?>
                            <tr><td colspan='5' class='text-center'>No records found</td></tr>
                        <?php }?>
                    </tbody>
                </table>
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

// Delete button functionality
$('.delete-btn').click(function() {
    const recordId = $(this).closest('tr').find('.record-data:first').data('id');
    $('#confirmDelete').data('record-id', recordId);
});

// Confirm delete functionality
$('#confirmDelete').click(function() {
    const recordId = $(this).data('record-id');
    if (recordId) {
        $.post('BuyingPriceAnalysis.php', { id: recordId }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error deleting record: ' + response.message);
            }
        }).fail(function() {
            alert('Error deleting record');
        });
    }
    $('#deleteModal').modal('hide');
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

// Delete button in edit modal
$('#deleteBtn').click(function() {
    const recordId = $('#record_id').val();
    if (recordId) {
        if (confirm('Are you sure you want to delete this record?')) {
            $.post('BuyingPriceAnalysis.php', { id: recordId }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error deleting record: ' + response.message);
                }
            }).fail(function() {
                alert('Error deleting record');
            });
        }
    }
});
</script>
</body>
</html>