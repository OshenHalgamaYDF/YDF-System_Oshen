<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\Invoices\UK\InvoicesControllerYDFUK.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Generator-UK</title>
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
    <!-- Invoices Header-->
    <div class="container mt-4">
        <h1 class="text-center mt-4 text-primary fw-bold">Invoices Generator UK</h1>
        <div class="d-flexs align-items-left mt-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoicesModal">
                <i class="fas fa-plus"></i> Invoice Details
            </button>
            <button class="btn btn-warning md-2" data-bs-toggle="modal" data-bs-target="#shippingDiscountModal">
                <i class="fas fa-tags"></i> Shipping Discount
            </button>
            <button class="btn btn-secondary md-2" onclick="window.location.href='Invoices_discount_tableUK.php'">
                <i class="fas fa-table"></i> View Discount Table 
            </button>
        </div>
    </div>

    <!-- Date Select filter-->
    <div class="container mt-4">
        <form method="POST" action="">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="datefilter" class="col-form-label">Date:</label>
                </div>
                <div class="col-auto">
                    <input type="date" id="datefilter" name="Date" class="form-control" 
                    value="<?php echo htmlspecialchars($dateFilter ?? ''); ?>" required>
                </div>
                <div class="col-auto">
                    <a href="" class="btn btn-outline-secondary">Reset</a>
                    <button type="button" id="pdfBtn" class="btn btn-danger">Generate PDF UK</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Shipping Discount Modal -->
    <div class="modal fade" id="shippingDiscountModal" tabindex="-1" aria-labelledby="shippingDiscountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" id="shippingDiscountForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="shippingDiscountModalLabel">Add Shipping Discount</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Add this hidden input to identify the form -->
                        <input type="hidden" name="form_type" value="shipping_discount">
                        <div class="mb-3">
                            <label for="discountdate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="discountdate" name="discount_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <input type="text" class="form-control" id="reason" name="reason" required placeholder="Enter reason for discount">
                        </div>
                        <div class="mb-3">
                            <label for="discountAmount" class="form-label">Discount Amount (USD)</label>
                            <input type="number" step="0.01" class="form-control" id="discountAmount" name="discount_amount" required placeholder="Enter discount amount in USD">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Discount</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Invoices Modal -->
    <div class="modal fade" id="invoicesModal" tabindex="-1" aria-labelledby="invoicesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" id="fgsCostingForm" class="needs-validation" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="invoicesModalLabel">Add Invoice Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Add this hidden input to identify the form -->
                            <input type="hidden" name="form_type" value="invoice_details">
                            <div class="col-md-6">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                                <div class="invalid-feedback">Please select a date.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="companyreg" class="form-label">Company Reg</label>
                                <input type="text" class="form-control" id="companyreg" name="companyreg" required>
                                <div class="invalid-feedback">Please enter the VAT Reg.</div>  
                            </div>
                            <div class="col-md-6">
                                <label for="vatreg" class="form-label">VAT Reg</label>
                                <input type="text" class="form-control" id="vatreg" name="vatreg" required>
                                <div class="invalid-feedback">Please enter the VAT Reg.</div>  
                            </div>
                            <div class="col-md-6">
                                <label for="invoiceno" class="form-label">Invoice No</label>
                                <input type="text" class="form-control" id="invoiceno" name="invoiceno" required>
                                <div class="invalid-feedback">Please enter the Invoice Number.</div>
                            </div>
                            <div class="col-md-12">
                                <label for="customer" class="form-label">Customer Details</label>
                                <input type="text" class="form-control" id="customer" name="customer" required>
                                <div class="invalid-feedback">Please enter the Customer Details.</div>  
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Add</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal to add unit price -->
    <div class="modal fade" id="unitPriceModal" tabindex="-1" aria-labelledby="unitPriceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" id="unitPriceForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="unitPriceModalLabel">Add Unit Price</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Add form identifier -->
                        <input type="hidden" name="form_type" value="unit_price">
                        <input type="hidden" id="unitPriceFishType" name="fish_type">
                        <input type="hidden" id="unitPriceProductType" name="product_type">
                        <input type="hidden" id="unitPriceDate" name="production_date">
                        
                        <div class="mb-3">
                            <label for="unitPrice" class="form-label">Unit Price (£)</label>
                            <input type="number" step="0.01" class="form-control" id="unitPrice" name="unit_price" required placeholder="Enter unit price in USD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Details</label>
                            <div class="form-control bg-light">
                                <strong>Fish Type:</strong> <span id="displayFishType"></span><br>
                                <strong>Product Type:</strong> <span id="displayProductType"></span><br>
                                <strong>Date:</strong> <span id="displayDate"></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Unit Price</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="container mt-4">
        <table id="invoicesTable" class="table table-striped table-bordered table-hover nowrap " style="width:100%">
            <thead>
                <tr class="table-dark">
                    <th>Product Code</th>
                    <th>Description of Goods</th>
                    <th>Scientific Name</th>
                    <th>Sizes</th>
                    <th>Volume Per Kg</th>
                    <th>PRICE PER Kg(£)</th>
                    <th>TOTAL VALUE(£)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_price = 0;
                foreach ($invoices as $invoice) {
                    $unit_price = $invoice['unit_price'] ?? 0;
                    $total_weight = $invoice['total_weight'] ?? 0;
                    $value = $unit_price * $total_weight;
                    $total_price += $value;
                    
                    // Safely encode all attributes
                    $fish_type_attr = htmlspecialchars($invoice['fish_type'], ENT_QUOTES);
                    $product_type_attr = htmlspecialchars($invoice['product_type'] ?? '', ENT_QUOTES);
                    $date_attr = htmlspecialchars($invoice['production_date'] ?? $dateFilter, ENT_QUOTES);
                ?>
                    <tr class="text-center align-middle">
                        <td><?php echo htmlspecialchars($invoice['product_code']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['fish_type']) . ' ' . htmlspecialchars($invoice['product_type'] ?? ''); ?></td>
                        <td><i><?php echo htmlspecialchars($invoice['scientific_name']); ?></i></td>
                        <td><?php echo htmlspecialchars($invoice['grades']); ?></td>
                        <td><?php echo htmlspecialchars($total_weight); ?></td>
                        <td>£<?php echo number_format($unit_price, 2); ?></td>
                        <td>£<?php echo number_format($value, 2); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info add-btn" 
                                    data-fish-type="<?php echo $fish_type_attr; ?>"
                                    data-product-type="<?php echo $product_type_attr; ?>"
                                    data-date="<?php echo $date_attr; ?>"
                                    data-current-price="<?php echo $unit_price; ?>">
                                <i class="fa fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end fw-bold">Total Value (USD):</td>
                    <td colspan="2" class="fw-bold">$<?php echo number_format($total_price, 2); ?></td>
                </tr>
                <?php if ($shipping_discount_value > 0): ?>
                <tr>
                    <td colspan="6" class="text-end text-danger fw-bold">
                        Deduction (<?php echo $shipping_reason; ?>):
                    </td>
                    <td colspan="2" class="fw-bold text-danger">
                        -$<?php echo number_format($shipping_discount_value, 2); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end fw-bold text-success">Net Total (USD):</td>
                    <td colspan="2" class="fw-bold text-success">$<?php echo number_format($net_total, 2); ?></td>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>
    </div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#invoicesTable').DataTable({
        responsive: true,
        columnDefs: [
            { 
                orderable: false, 
                targets: [6],
                searchable: false
            }
        ]
    });

    // Unit Price Modal functionality
    const unitPriceModal = new bootstrap.Modal(document.getElementById('unitPriceModal'));
    
    $(document).on('click', '.add-btn', function() {
        const fishType = $(this).data('fish-type');
        const productType = $(this).data('product-type') || 'N/A';
        const date = $(this).data('date');
        const currentPrice = $(this).data('current-price') || 0;
        
        $('#unitPriceFishType').val(fishType);
        $('#unitPriceProductType').val(productType);
        $('#unitPriceDate').val(date);
        $('#unitPrice').val(currentPrice);
        
        $('#displayFishType').text(fishType);
        $('#displayProductType').text(productType);
        $('#displayDate').text(date);
        
        unitPriceModal.show();
    });
    
    $('#unitPriceModal').on('hidden.bs.modal', function() {
        $('#unitPriceForm')[0].reset();
    });
    
    // PDF Button functionality
    $('#pdfBtn').on('click', function() {
        const selectedDate = $('#datefilter').val();
        if (!selectedDate) {
            alert('Please select a date first!');
            return;
        }
        window.location.href = "InvoicespdfUK.php?date=" + encodeURIComponent(selectedDate);
    });

    // Reset button functionality
    $('a.btn-outline-secondary').on('click', function(e) {
        e.preventDefault();
        window.location.href = window.location.pathname;
    });

    // --- AUTO SUBMIT FORM WHEN DATE CHANGES ---
    $('#datefilter').on('change', function() {
        $(this).closest('form').submit();
    });
});

(function () {
    'use strict'

    // Fetch the form we want to apply validation to
    const form = document.getElementById('fgsCostingForm');

    form.addEventListener('submit', function (event) {
        // Check if form is valid
        if (!form.checkValidity()) {
            event.preventDefault(); // Stop form submission
            event.stopPropagation();
        }

        form.classList.add('was-validated'); // Bootstrap class to show feedback
    }, false);
})();
</script>
</body>
</html>