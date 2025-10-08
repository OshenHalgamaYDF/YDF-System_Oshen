<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\Invoices\InvoicesControllerYDF.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Generator</title>
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
</head>
<body>
    <!-- Invoices Header-->
    <div class="container mt-4">
        <h1 class="text-center mt-4 text-primary fw-bold">Invoices Generator</h1>
        <div class="d-flex justify-content-between align-items-center mt-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoicesModal">
                <i class="fas fa-plus"></i> Invoice Details
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
                    <input type="date" id="datefilter" name="Date" class="form-control" required>
                </div>
                <div class="col-auto">
                    <button type="submit" id="filterBtn" class="btn btn-secondary">Filter</button>
                    <a href="" class="btn btn-outline-secondary">Reset</a>
                    <button type="button" id="pdfBtn" class="btn btn-danger">Generate PDF</button>
                </div>
            </div>
        </form>
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
                            <div class="col-md-4">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                            <div class="col-md-4">
                                <label for="invoiceno" class="form-label">Invoice No</label>
                                <input type="text" class="form-control" id="invoiceno" name="invoiceno" required>
                            </div>
                            <div class="col-md-4">
                                <label for="AWBno" class="form-label">AWB No</label>
                                <input type="text" class="form-control" id="AWBno" name="AWBno" required>
                            </div>
                            <div class="col-md-4">
                                <label for="flightdetails" class="form-label">Flight Details</label>
                                <input type="text" class="form-control" id="flightdetails" name="flightdetails" required>
                            </div>
                            <div class="col-md-4">
                                <label for="destination" class="form-label">Destination</label>
                                <input type="text" class="form-control" id="destination" name="destination" required>
                            </div>
                            <div class="col-md-4">
                                <label for="FDAregno" class="form-label">FDA Reg No</label>
                                <input type="text" class="form-control" id="FDAregno" name="FDAregno" required>
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

    <!-- Invoices Table -->
    <div class="container mt-4">
        <table id="invoicesTable" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>No of boxes</th>
                    <th>Weight</th>
                    <th>Description of Goods </th>
                    <th>Scientific Name</th>
                    <th>UNIT PRICE(USD)</th>
                    <th>VALUE(USD)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch data from the database and populate the table
                foreach ($invoices as $invoice) {?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['box_no']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['net_weight']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['fish_type']) . ' ' . htmlspecialchars($invoice['product_type']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['fish_type']); ?></td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

<script>
document.getElementById('resetBtn')?.addEventListener('click', function() {
    window.location.href = window.location.pathname;
});
</script>

</body>
</html>