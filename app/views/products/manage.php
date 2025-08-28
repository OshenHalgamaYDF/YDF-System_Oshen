<?php include APP_PATH . '/views/partials/navbar.php'; ?>

<div class="content" id="content">
    <div class="container mt-4">
        <h2>Manage Products</h2>

        <!-- Add Product Button -->
        <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
            Add Product
        </button>

        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Products Table -->
        <table class="table table-bordered" id="productsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Scientific Name</th>
                    <th>Category</th>
                    <th>Recovery %</th>
                    <th>CF Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['product_code']) ?></td>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td class="fst-italic"><?= htmlspecialchars($row['scientific_name']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><?= htmlspecialchars($row['recovery_percentage']) ?></td>
                        <td><?= htmlspecialchars($row['cf_name']) ?></td>
                        <td>
                            <!-- Edit Button with Data Attributes -->
                            <button class="btn btn-warning btn-sm edit-btn"
                                data-id="<?= $row['id'] ?>"
                                data-product-code="<?= htmlspecialchars($row['product_code']) ?>"
                                data-product-name="<?= htmlspecialchars($row['product_name']) ?>"
                                data-scientific-name="<?= htmlspecialchars($row['scientific_name']) ?>"
                                data-category="<?= htmlspecialchars($row['category']) ?>"
                                data-recovery-percentage="<?= $row['recovery_percentage'] ?>"
                                data-cf-name="<?= htmlspecialchars($row['cf_name']) ?>">
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="mb-3">
                            <label>Product Code</label>
                            <input type="text" name="product_code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Product Name</label>
                            <input type="text" name="product_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Scientific Name</label>
                            <input type="text" name="scientific_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Recovery Percentage</label>
                            <input type="number" step="0.01" name="recovery_percentage" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>CF Name</label>
                            <input type="text" name="cf_name" class="form-control">
                        </div>
                        <button type="submit" name="create_product" class="btn btn-primary">Add</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="id" id="editProductId">
                        <div class="mb-3">
                            <label>Product Code</label>
                            <input type="text" name="product_code" id="editProductCode" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Product Name</label>
                            <input type="text" name="product_name" id="editProductName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Scientific Name</label>
                            <input type="text" name="scientific_name" id="editScientificName" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Category</label>
                            <input type="text" name="category" id="editCategory" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Recovery Percentage</label>
                            <input type="number" step="0.01" name="recovery_percentage" id="editRecoveryPercentage" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>CF Name</label>
                            <input type="text" name="cf_name" id="editcfName" class="form-control">
                        </div>
                        <button type="submit" name="update_product" class="btn btn-primary">Update</button>
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
                    Are you sure you want to delete this product?
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
                const productId = this.getAttribute('data-id');
                const productCode = this.getAttribute('data-product-code');
                const productName = this.getAttribute('data-product-name');
                const scientificName = this.getAttribute('data-scientific-name') || '';
                const category = this.getAttribute('data-category');
                const recoveryPercentage = this.getAttribute('data-recovery-percentage');
                const cfName = this.getAttribute('data-cf-name');

                // Set values in the edit modal
                document.getElementById('editProductId').value = productId;
                document.getElementById('editProductCode').value = productCode;
                document.getElementById('editProductName').value = productName;
                document.getElementById('editScientificName').value = scientificName;
                document.getElementById('editCategory').value = category;
                document.getElementById('editRecoveryPercentage').value = recoveryPercentage;
                document.getElementById('editcfName').value = cfName;

                // Show the edit modal
                new bootstrap.Modal(document.getElementById('editProductModal')).show();
            });
        });

        // Delete Confirmation
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                document.getElementById('confirmDelete').href = '<?php echo BASE_URL; ?>/products/manage?delete_id=' + productId;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
        });

        // Auto-hide messages after 3 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
        }, 3000);

        $(document).ready(function() {
            $('#productsTable').DataTable({
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