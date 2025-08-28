<?php include APP_PATH . '/views/partials/navbar.php'; ?>

<div class="content" id="content">
    <div class="container mt-4">
        <h2>Manage Suppliers</h2>

        <!-- Add Supplier Button -->
        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            Add Supplier
        </button>

        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Suppliers Table -->
        <table class="table table-bordered" id="suppliersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Supplier Name</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td>
                            <!-- Edit Button with Data Attributes -->
                            <button class="btn btn-warning btn-sm edit-btn"
                                data-id="<?= $row['id'] ?>"
                                data-supplier-name="<?= htmlspecialchars($row['name']) ?>"
                                data-supplier-phone="<?= htmlspecialchars($row['phone']) ?>">
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

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="mb-3">
                            <label>Supplier Name</label>
                            <input type="text" name="supplier_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Supplier Phone</label>
                            <input type="text" name="supplier_phone" class="form-control">
                        </div>
                        <button type="submit" name="create_supplier" class="btn btn-primary">Add</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" id="editSupplierId">
                        <div class="mb-3">
                            <label>Supplier Name</label>
                            <input type="text" name="supplier_name" id="editSupplierName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" name="supplier_phone" id="editSupplierPhone" class="form-control">
                        </div>
                        <button type="submit" name="update_supplier" class="btn btn-primary">Update</button>
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
                    Are you sure you want to delete this supplier?
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
                const supplierId = this.getAttribute('data-id');
                const supplierName = this.getAttribute('data-supplier-name');
                const supplierPhone = this.getAttribute('data-supplier-phone') || '';

                // Set values in the edit modal
                document.getElementById('editSupplierId').value = supplierId;
                document.getElementById('editSupplierName').value = supplierName;
                document.getElementById('editSupplierPhone').value = supplierPhone;

                // Show the edit modal
                new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
            });
        });

        // Delete Confirmation
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const supplierId = this.getAttribute('data-id');
                document.getElementById('confirmDelete').href = '<?php echo BASE_URL; ?>/suppliers/manage?delete_id=' + supplierId;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });

        // Auto-hide messages after 3 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
        }, 3000);

        $(document).ready(function() {
            $('#suppliersTable').DataTable({
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