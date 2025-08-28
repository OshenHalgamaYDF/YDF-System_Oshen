<?php include APP_PATH . '/views/partials/navbar.php'; ?>

<div class="content" id="content">
    <div class="container mt-4">
        <h2>Manage Customers</h2>

        <!-- Add Customer Button -->
        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            Add Customer
        </button>

        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Customers Table -->
        <table class="table table-bordered" id="customerTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td>
                            <!-- Edit Button with Data Attributes -->
                            <button class="btn btn-warning btn-sm edit-btn"
                                data-id="<?= $row['id'] ?>"
                                data-customer-name="<?= htmlspecialchars($row['name']) ?>">
                                Edit
                            </button>
                            <button class="btn btn-danger btn-sm delete-btn"
                                data-id="<?= $row['id'] ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="mb-3">
                            <label>Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                        <button type="submit" name="create_customer" class="btn btn-primary">Add</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" id="editCustomerId">
                        <div class="mb-3">
                            <label>Customer Name</label>
                            <input type="text" name="customer_name" id="editCustomerName" class="form-control" required>
                        </div>
                        <button type="submit" name="update_customer" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this customer?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="confirmDelete" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Button Click Handler
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Get data attributes
                const customerId = this.getAttribute('data-id');
                const customerName = this.getAttribute('data-customer-name');

                // Set values in the edit modal
                document.getElementById('editCustomerId').value = customerId;
                document.getElementById('editCustomerName').value = customerName;

                // Show the edit modal
                new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
            });
        });

        // Delete Confirmation
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-id');
                document.getElementById('confirmDelete').href = '<?php echo BASE_URL; ?>/customers/manage?delete_id=' + customerId;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });

        // Auto-hide messages after 3 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
        }, 3000);

        $(document).ready(function() {
            $('#customersTable').DataTable({
                dom: 'Bfrtip',
                buttons: [{
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    action: function(e, dt, node, config) {
                        // You can implement PDF export functionality here
                        alert('PDF export functionality would be implemented here');
                    }
                }],
                pageLength: 1000,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ]
            });
        });
    </script>

    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <!-- JSZip for Excel export -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
    <!-- PDFMake for PDF export -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <!-- Buttons HTML5 export -->
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <!-- Buttons Print -->
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <style>
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 2px;
        }
    </style>
</div>