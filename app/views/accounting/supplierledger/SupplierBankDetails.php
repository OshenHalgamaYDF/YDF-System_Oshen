<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\supplierledger\SupplierBankDetailsController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Supplier Bank Details</title>
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
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4 text-primary">Supplier Bank Details</h1>
        <button class="btn btn-primary mb-3" onclick="window.location.href='SupplierLedgerInput.php'">
            <i class="fas fa-arrow-left"></i> Back to Add Record
        </button>

        <div class="mb-2">
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#supplierbankdetailsModal" id="addSupplierbankdetailsBtn">
                + Supplier info
            </button>
        </div>

        <div class="modal fade" id="supplierbankdetailsModal" tabindex="-1" aria-labelledby="supplierLedgerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Supplier Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" class="row g-3 needs-validation" novalidate>
                            <input type="hidden" name="supplier_id" id="supplier_id"> <!-- For Edit -->

                            <div class="col-md-12">
                                <label>Name:</label>
                                <input type="text" name="dename" id="dename" class="form-control" required>
                                <div class="invalid-feedback">Please enter a name.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Nick-name:</label>
                                <input type="text" name="deniname" id="deniname" class="form-control" required>
                                <div class="invalid-feedback">Please enter a Nick-Name.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Bank Details and Branch:</label>
                                <input type="text" name="debankbranch" id="debankbranch" class="form-control" required>
                                <div class="invalid-feedback">Please enter Bank Details and Branch.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Account Number:</label>
                                <input type="text" name="deaccno" id="deaccno" class="form-control" required>
                                <div class="invalid-feedback">Please enter Account Number.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Swift code:</label>
                                <input type="text" name="descode" id="descode" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label>Remark:</label>
                                <input type="text" name="deremark" id="deremark" class="form-control">
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="saveSupplier" class="btn btn-primary px-4" id="submitBtn">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!--Table-->
        <div class="table-responsive">
            <table id="supplierTable" class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Nick Name</th>
                        <th>Bank Name and Branch</th>
                        <th>Account No</th>
                        <th>Swift code</th>
                        <th>Remark</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supplierdetails as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['nickname']); ?></td>
                            <td><?php echo htmlspecialchars($row['bankname_branch']); ?></td>
                            <td><?php echo htmlspecialchars($row['acc_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['swift_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['remark']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning editBtn"
                                    data-id="<?= $row['id'] ?>"
                                    data-name="<?= htmlspecialchars($row['name']) ?>"
                                    data-nickname="<?= htmlspecialchars($row['nickname']) ?>"
                                    data-bank="<?= htmlspecialchars($row['bankname_branch']) ?>"
                                    data-acc="<?= htmlspecialchars($row['acc_no']) ?>"
                                    data-swift="<?= htmlspecialchars($row['swift_code']) ?>"
                                    data-remark="<?= htmlspecialchars($row['remark']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <a href="SupplierBankDetails.php?delete=<?= $row['id'] ?>" 
                                class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this record?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    
<script>
// Bootstrap Validation Script 
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

$(document).ready(function() {
    // Edit Button Click
    $('.editBtn').click(function() {
        $('#modalTitle').text('Edit Supplier Details');
        $('#submitBtn').text('Update');
        
        // Fill data
        $('#supplier_id').val($(this).data('id'));
        $('#dename').val($(this).data('name'));
        $('#deniname').val($(this).data('nickname'));
        $('#debankbranch').val($(this).data('bank'));
        $('#deaccno').val($(this).data('acc'));
        $('#descode').val($(this).data('swift'));
        $('#deremark').val($(this).data('remark'));

        // Open modal
        $('#supplierbankdetailsModal').modal('show');
    });

    // Reset modal for Add
    $('#addSupplierbankdetailsBtn').click(function() {
        $('#modalTitle').text('Add Supplier Details');
        $('#submitBtn').text('Add');
        $('#supplier_id').val('');
        $('form')[0].reset();
    });
});

$(document).ready(function() {
    $('#supplierTable').DataTable({
        // Optional settings
        paging: true,
        searching: true,
        ordering: true,
        responsive: true,
        columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting on "Action" column
        ]
    });
});
</script>
</body>
</html>