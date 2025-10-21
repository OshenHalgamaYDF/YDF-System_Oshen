<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\cnfanalysis\CNFAnalysisController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CNF Analysis UK</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center text-primary fw-bold mb-4">Shipment CNF Analysis</h2>
    <div class="mb-2">
        <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#cnfModal" id="addcnfBtn">
            + CNF Data
        </button>
    </div>

    <div class="modal fade" id="cnfModal" tabindex="-1" aria-labelledby="offelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="offelModalLabel">Form to Add CNF Price Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-3" id="cnfForm" novalidate autocomplete="off">
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
                                        value="<?= htmlspecialchars($product['product_name'] ?? '') ?>"
                                        data-product-code="<?= htmlspecialchars($product['product_code'] ?? '') ?>"
                                        data-scientific-name="<?= htmlspecialchars($product['scientific_name'] ?? '') ?>"
                                        data-size-range="<?= htmlspecialchars($product['size_range'] ?? '') ?>"
                                        data-specification="<?= htmlspecialchars($product['specification'] ?? '') ?>"
                                    >
                                        <?= htmlspecialchars($product['product_name'] ?? 'Unknown Product') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a product.</div>
                        </div>

                        <input type="hidden" name="scientific_name" id="scientific_name" />

                        <div class="col-md-6">
                            <label>Product Code:</label>
                            <input type="text" name="product_code" id="product_code" class="form-control" required readonly>
                            <div class="invalid-feedback">Product code is required.</div>
                        </div>

                        <div class="col-md-6">
                            <label>Size Range:</label>
                            <input type="text" name="size_range" id="size_range" class="form-control" required>
                            <div class="invalid-feedback">Please enter the size range.</div>
                        </div>

                        <div class="col-md-6">
                            <label>Specification:</label>
                            <input type="text" name="specification" id="specification" class="form-control" required>
                            <div class="invalid-feedback">Please enter the specification.</div>
                        </div>

                        <div class="col-md-6">
                            <label>CNF Price:</label>
                            <input type="number" step="0.01" name="target_price" id="target_price" class="form-control" required>
                            <div class="invalid-feedback">Please enter the target price.</div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="Submitcnf" class="btn btn-primary px-4">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Table CNF Analysis-->
    <div class="container-fluid mb-5">
        <div class="table-responsive">
            <table id="fgsCostingTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th class="text-center">Product Code</th>
                        <th class="text-center">Product Name</th>
                        <th class="text-center">Scientific Name</th>
                        <th class="text-center">Size Range</th>
                        <th class="text-center">Specification</th>
                        <th class="text-center">CNF</th>
                        <th class="text-center">Average</th>
                    </tr>
                </thead>
            </table>
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
    }    
</script>
</html>