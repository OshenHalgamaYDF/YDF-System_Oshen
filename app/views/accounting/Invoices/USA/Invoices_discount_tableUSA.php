<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\Invoices\USA\InvoicesDiscountDetailsControllerUSA.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Discount Table USA</title>
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
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="text-center text-primary fw-bold mb-4">Shipping Discount Table USA</h2>
        <div class="mb-3">
            <button class="btn btn-secondary" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle bg-white">
                <thead class="table-dark text-center">
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Value (USD)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="text-center"><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td>$<?php echo number_format($row['value'], 2); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning editBtn" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-date="<?php echo htmlspecialchars($row['date']); ?>"
                                        data-reason="<?php echo htmlspecialchars($row['reason']); ?>"
                                        data-value="<?php echo htmlspecialchars($row['value']); ?>">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger deleteBtn" 
                                        data-id="<?php echo $row['id']; ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No discounts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== Edit Modal ===== -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit Shipping Discount</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="edit_id">
                        <div class="mb-3">
                            <label for="edit_date" class="form-label">Date</label>
                            <input type="date" id="edit_date" name="edit_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">Reason</label>
                            <input type="text" id="edit_reason" name="edit_reason" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_value" class="form-label">Discount Value (USD)</label>
                            <input type="number" step="0.01" id="edit_value" name="edit_value" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== Delete Confirmation Modal ===== -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p>Are you sure you want to delete this discount?</p>
                        <input type="hidden" id="delete_id" name="delete_id">
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    $(document).ready(function() {
        // ===== Edit Button =====
        $('.editBtn').on('click', function() {
            const id = $(this).data('id');
            const date = $(this).data('date');
            const reason = $(this).data('reason');
            const value = $(this).data('value');

            $('#edit_id').val(id);
            $('#edit_date').val(date);
            $('#edit_reason').val(reason);
            $('#edit_value').val(value);

            new bootstrap.Modal(document.getElementById('editModal')).show();
        });

        // ===== Delete Button =====
        $('.deleteBtn').on('click', function() {
            const id = $(this).data('id');
            $('#delete_id').val(id);
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    $(document).ready(function () {
        // Initialize DataTable
        $('table').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search discounts..."
            },
            columnDefs: [
                { orderable: false, targets: -1 } // Disable sorting on the Actions column
            ]
        });
    });
</script>
</body>
</html>
