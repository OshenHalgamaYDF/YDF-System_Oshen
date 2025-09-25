<?php
include('c:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\freightdelay\FreightDelayStoringController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freight Delay</title>
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
        <h1 class="text-center mb-4 text-primary">Freight Delay Details</h1>

        <div class="mb-2">
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#freightdelaydetailsModal" id="freightdelaydetailsBtn">
                + Delay info
            </button>
        </div>

        <div class="modal fade" id="freightdelaydetailsModal" tabindex="-1" aria-labelledby="freightdelayModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Freight Delay Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" class="row g-3 needs-validation" novalidate>
                            <input type="hidden" name="delay_id" id="delay_id"> <!-- For Edit -->

                            <div class="col-md-12">
                                <label>Invoice No:</label>
                                <input type="text" name="frinvoice" id="frinvoice" class="form-control" required>
                                <div class="invalid-feedback">Please enter Invoice Number.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Shipment Date:</label>
                                <input type="date" name="shipdate" id="shipdate" class="form-control" required>
                                <div class="invalid-feedback">Please enter Shipment Date.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Delay Charges:</label>
                                <input type="number" step="0.01" name="delaych" id="delaych" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label>Reason:</label>
                                <input type="text" name="dreson" id="dreson" class="form-control" required>
                                <div class="invalid-feedback">Please a Reason.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Remark:</label>
                                <input type="text" name="dremark" id="dremark" class="form-control">
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="SubmitFreightDelay" class="btn btn-primary px-4" id="submitBtn">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!--Table-->
        <div class="table-responsive">
            <table id="fdelayTable" class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center align-middle">ID</th>
                        <th class="text-center align-middle">Invoice No</th>
                        <th class="text-center align-middle">Shipment Date</th>
                        <th class="text-center align-middle">Delay Charges</th>
                        <th class="text-center align-middle">Reason</th>
                        <th class="text-center align-middle">Remark</th>
                        <th class="text-center align-middle">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($delaydetails as $row): ?>
                        <tr>
                            <td class="text-center align-middle"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td class="text-center align-middle"><?php echo htmlspecialchars($row['invoice_no']); ?></td>
                            <td class="text-center align-middle"><?php echo htmlspecialchars($row['shipment_date']); ?></td>
                            <td class="text-center align-middle">$<?php echo htmlspecialchars($row['delay_charges']); ?></td>
                            <td class="text-center align-middle"><?php echo htmlspecialchars($row['reason']); ?></td>
                            <td class="text-center align-middle"><?php echo htmlspecialchars($row['remark']); ?></td>
                            <td class="text-center align-middle">
                                <button class="btn btn-sm btn-warning editBtn"
                                    data-id="<?= $row['id'] ?>"
                                    data-invoice="<?= htmlspecialchars($row['invoice_no']) ?>"
                                    data-shipdate="<?= htmlspecialchars($row['shipment_date']) ?>"
                                    data-delaycharge="<?= htmlspecialchars($row['delay_charges']) ?>"
                                    data-reson="<?= htmlspecialchars($row['reason']) ?>"
                                    data-remark="<?= htmlspecialchars($row['remark']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <a href="FreightDelayStoring.php?delete=<?= $row['id'] ?>" 
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
        $('#modalTitle').text('Edit Freight Delay Details');
        $('#submitBtn').text('Update');

        $('#delay_id').val($(this).data('id'));
        $('#frinvoice').val($(this).data('invoice'));
        $('#shipdate').val($(this).data('shipdate'));
        $('#delaych').val($(this).data('delaycharge'));
        $('#dreson').val($(this).data('reson'));   // FIX: match "data-reson"
        $('#dremark').val($(this).data('remark'));

        $('#freightdelaydetailsModal').modal('show');
    });

});

$(document).ready(function() {
    $('#fdelayTable').DataTable({
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