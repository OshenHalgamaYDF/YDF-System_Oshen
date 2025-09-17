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
                                        value="<?php echo htmlspecialchars($product['Product_Name']); ?>"
                                        data-product-code="<?php echo htmlspecialchars($product['Product_Code']); ?>"
                                        data-scientific-name="<?php echo htmlspecialchars($product['Scientific_Name']); ?>"
                                        data-size-range="<?php echo htmlspecialchars($product['Size_Range']); ?>"
                                        data-specification="<?php echo htmlspecialchars($product['Specification']); ?>"
                                        data-target-price="<?php echo htmlspecialchars($product['Target_buying_price']); ?>"
                                    >
                                        <?php echo htmlspecialchars($product['Product_Name']); ?>
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
    <div class="container mt-4">
        <form method="get" class="row mb-3">
            <div class="col-md-4">
                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

       <!-- Bootstrap Tabs -->
        <ul class="nav nav-tabs" id="productTabs" role="tablist">
            <?php foreach ($productsForDate as $index => $product): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $index===0 ? 'active' : ''; ?>" 
                            id="tab-<?php echo md5($product); ?>" 
                            data-bs-toggle="tab" 
                            data-bs-target="#content-<?php echo md5($product); ?>" 
                            type="button" 
                            role="tab">
                        <?php echo htmlspecialchars($product); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content mt-3">
            <?php foreach ($productsForDate as $index => $product): ?>
                <div class="tab-pane fade <?php echo $index===0 ? 'show active' : ''; ?>" 
                    id="content-<?php echo md5($product); ?>" 
                    role="tabpanel">

                    <div class="table-responsive">
                        <table class="table table-striped table-hover text-center align-middle table-bordered" id="table-<?php echo md5($product); ?>">
                            <thead class="table-primary table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Scientific Name</th>
                                    <th>Size Range</th>
                                    <th>Specification</th>
                                    <th>Target Price</th>
                                    <th>Buyer Name</th>
                                    <th>Buying Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM buyingpriceanlaysistable WHERE date = '$selectedDate' AND product_name = '$product' ORDER BY buyer_name ASC";
                                $result = $conn->query($sql);
                                $totalPrice = 0;
                                $recordCount = 0;
                                
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $totalPrice += $row['sold_price'];
                                        $recordCount++;
                                        echo "<tr>
                                            <td>".htmlspecialchars($row['date'])."</td>
                                            <td>".htmlspecialchars($row['product_code'])."</td>
                                            <td>".htmlspecialchars($row['product_name'])."</td>
                                            <td>".htmlspecialchars($row['scientific_name'])."</td>
                                            <td>".htmlspecialchars($row['size_range'])."</td>
                                            <td>".htmlspecialchars($row['specification'])."</td>
                                            <td>".number_format($row['target_price'],2)."</td>
                                            <td>".htmlspecialchars($row['buyer_name'])."</td>
                                            <td>".number_format($row['sold_price'],2)."</td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='text-center'>No records for $product</td></tr>";
                                }
                                ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="8" class="text-end">Average Buying Price:</th>
                                    <th>
                                        <?php 
                                        if ($recordCount > 0) {
                                            $average = $totalPrice / $recordCount;
                                            echo number_format($average, 2);
                                        } else {
                                            echo "0.00";
                                        }
                                        ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
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
});
</script>
</body>
</html>