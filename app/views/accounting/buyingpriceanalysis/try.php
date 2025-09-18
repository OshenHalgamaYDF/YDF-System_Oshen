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
            <i class="fas fa-plus"></i> Buying Price
        </button>
        <button class="btn btn-danger px-4" onclick="window.location.href='BuyingPriceSummary.php'">
            <i class="fas fa-chart-bar"></i> View Summary   
        </button>
    </div>

    <!-- Add/Edit Modal -->
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
                        <input type="hidden" name="record_id" id="record_id">
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
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display: none;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <button type="submit" name="SubmitOffel" class="btn btn-primary px-4">
                                <i class="fas fa-save"></i> Save
                            </button>
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
                            role="tab">
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
                    role="tabpanel">

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
                                    <th rowspan="2" class="text-center align-middle">Remark</th>
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
                                        GROUP_CONCAT(DISTINCT CONCAT(buyer_name, ': ', COALESCE(remark, '')) SEPARATOR ', ') AS remarks
                                        FROM buyingpriceanlaysistable
                                        WHERE date = '$dateResult'
                                        GROUP BY product_code, product_name, scientific_name, specification, size_range, target_price";

                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($product = $result->fetch_assoc()) {
                                        // Get all records for this product to show individual buyer prices
                                        $sqlRecords = "SELECT * FROM buyingpriceanlaysistable 
                                                     WHERE date = '$dateResult' 
                                                     AND product_code = '".$product['product_code']."'";
                                        $recordsResult = $conn->query($sqlRecords);
                                        $records = [];
                                        while ($record = $recordsResult->fetch_assoc()) {
                                            $records[] = $record;
                                        }
                                        ?>
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
                                        foreach ($buyers as $buyer) {
                                            $found = false;
                                            foreach ($records as $record) {
                                                if ($record['buyer_name'] === $buyer) {
                                                    $found = true;
                                                    $totalPrice += $record['sold_price'];
                                                    $buyerCount++;?>
                                                    <td>
                                                        <?= number_format($record['sold_price'],2) ?>
                                                        <input type="hidden" class="record-data" 
                                                               data-id="<?= $record['id'] ?>"
                                                               data-date="<?= $record['date'] ?>"
                                                               data-product-code="<?= $record['product_code'] ?>"
                                                               data-product-name="<?= $record['product_name'] ?>"
                                                               data-scientific-name="<?= $record['scientific_name'] ?>"
                                                               data-specification="<?= $record['specification'] ?>"
                                                               data-size-range="<?= $record['size_range'] ?>"
                                                               data-target-price="<?= $record['target_price'] ?>"
                                                               data-buyer-name="<?= $record['buyer_name'] ?>"
                                                               data-sold-price="<?= $record['sold_price'] ?>"
                                                               data-remark="<?= $record['remark'] ?>">
                                                    </td>
                                                    <?php
                                                    break;
                                                }
                                            }
                                            if (!$found) {?>
                                                <td>-</td>
                                            <?php }
                                        }

                                        // Average price
                                        if ($buyerCount > 0) {
                                            $average = $totalPrice / $buyerCount;?>
                                            <td><?= number_format($average,2) ?></td>
                                        <?php } else {?>
                                            <td>0.00</td>
                                        <?php } ?>

                                        <td><?= htmlspecialchars($product['remarks']) ?></td>
                                        <td>
                                            <button class='btn btn-sm btn-warning edit-btn' data-bs-toggle="modal" data-bs-target="#offelModal">
                                                <i class="fas fa-edit"></i>
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
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr class='text-center'>";
                                echo "<td>".htmlspecialchars($row['product_code'])."</td>";
                                echo "<td>".htmlspecialchars($row['product_name'])."</td>";
                                echo "<td>".htmlspecialchars($row['size_range'])."</td>";
                                echo "<td>".number_format($row['target_price'],2)."</td>";
                                echo "<td>".number_format($row['average_price'],2)."</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No records found</td></tr>";
                        }
                        ?>
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
})();

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables for the first active tab
    $('.tab-pane.active table').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true,
        "lengthChange": true
    });

    // Initialize DataTables for other tabs when they are shown
    $('#productTabs button').on('shown.bs.tab', function (e) {
        var target = $(e.target).data('bs-target');
        var tableId = $(target + ' table').attr('id');
        
        if (!$.fn.DataTable.isDataTable('#' + tableId)) {
            $('#' + tableId).DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true
            });
        }
    });

    // Edit button functionality
    $('.edit-btn').click(function() {
        const recordData = $(this).closest('tr').find('.record-data:first').data();
        if (recordData) {
            $('#record_id').val(recordData.id);
            $('#date').val(recordData.date);
            $('#product_code').val(recordData.productCode);
            $('#product_name').val(recordData.productName);
            $('#scientific_name').val(recordData.scientificName);
            $('#specification').val(recordData.specification);
            $('#size_range').val(recordData.sizeRange);
            $('#target_price').val(recordData.targetPrice);
            $('#buyer_name').val(recordData.buyerName);
            $('#sold_price').val(recordData.soldPrice);
            $('#remark').val(recordData.remark);
            
            $('#offelModalLabel').text('Edit Buying Price Record');
            $('#deleteBtn').show();
        }
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
            $.post('delete_record.php', { id: recordId }, function(response) {
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

    // Reset modal when closed
    $('#offelModal').on('hidden.bs.modal', function () {
        $('#buyingPriceForm')[0].reset();
        $('#record_id').val('');
        $('#offelModalLabel').text('Form to Add Buying Price Record');
        $('#deleteBtn').hide();
    });
});
</script>
</body>
</html>