<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\supplierledger\SupplierLedgerController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Ledger</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- jQuery FIRST -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Then Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Then DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4 text-primary fw-bold">Supplier Ledger Analysis</h1>

        <div class="mb-2">
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#supplierLedgerModal" id="addSupplierLedgerBtn">
                + Ledger Record
            </button>
            <button class="btn btn-danger px-4" onclick="window.location.href='SupplierLedgerSummary.php'">
                View Summary   
            </button>
        </div>

        <!-- Modal (same as before) -->
        <div class="modal fade" id="supplierLedgerModal" tabindex="-1" aria-labelledby="supplierLedgerModalLabel" aria-hidden="true">
            <!-- Modal content remains the same -->
        </div>

        <div class="container mt-4">
            <ul class="nav nav-underline" id="supplierTabs" role="tablist">
                <?php if (count($suppliername) > 0): ?>
                    <?php foreach ($suppliername as $index => $name): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                    id="tab-<?php echo md5($name); ?>" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#content-<?php echo md5($name); ?>" 
                                    type="button" 
                                    role="tab">
                                <?php echo htmlspecialchars($name); ?>
                            </button>   
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" disabled>No suppliers available</button>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content mt-2">
                <?php if (count($suppliername) > 0): ?>
                    <?php foreach ($suppliername as $index => $name): ?>
                        <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                             id="content-<?php echo md5($name); ?>" 
                             role="tabpanel">
                            <div class="table-responsive">
                                <!-- Use class instead of ID for multiple tables -->
                                <table class="table table-bordered table-striped supplier-ledger-table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Supplier Name</th>
                                            <th>Reference Number</th>
                                            <th>Description</th>
                                            <th>Credit</th>
                                            <th>Debit</th>
                                            <th>Balance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $supplierRecords = isset($recordsBySupplier[$name]) ? $recordsBySupplier[$name] : [];
                                        $balance = 0;
                                        
                                        if (count($supplierRecords) > 0) {
                                            foreach ($supplierRecords as $row) {
                                                $balance += $row['credit'] - $row['debit'];
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['ref_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                    <td class="text-success"><?php echo number_format($row['credit'], 2); ?></td>
                                                    <td class="text-danger"><?php echo number_format($row['debit'], 2); ?></td>
                                                    <td class="fw-bold <?php echo $balance >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo number_format($balance, 2); ?>
                                                    </td>
                                                    <td>
                                                        <button class='btn btn-sm btn-warning editBtn' 
                                                                data-id='<?php echo $row['ID']; ?>' 
                                                                data-date='<?php echo $row['date']; ?>' 
                                                                data-supplier_name='<?php echo htmlspecialchars($row['supplier_name']); ?>' 
                                                                data-ref_no='<?php echo htmlspecialchars($row['ref_no']); ?>' 
                                                                data-description='<?php echo htmlspecialchars($row['description']); ?>' 
                                                                data-credit='<?php echo $row['credit']; ?>' 
                                                                data-debit='<?php echo $row['debit']; ?>'>
                                                            Edit
                                                        </button>
                                                        <a href='?delete_id=<?php echo $row['ID']; ?>' 
                                                           class='btn btn-sm btn-danger' 
                                                           onclick='return confirm("Are you sure you want to delete this record?")'>
                                                            Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                            } ?>
                                            <tr>
                                                <td colspan='8' class="fw-bold text-center">Amount to be settled LKR <?php echo number_format($balance);?></td>
                                            </tr>
                                        <?php } else {
                                            ?>
                                            <tr>
                                                <td colspan='8' class='text-center'>No records found for <?php echo htmlspecialchars($name); ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="tab-pane fade show active">
                        <div class="alert alert-info">No supplier data available. Add your first record using the button above.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        (function() {
            'use strict';
            var form = document.getElementById('supplierLedgerForm');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();

        // Initialize DataTables when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTables for each supplier table
            $('.supplier-ledger-table').each(function() {
                $(this).DataTable({
                    "pageLength": 10,
                    "ordering": true,
                    "searching": true,
                    "lengthChange": true,
                    "info": true,
                    "paging": true
                });
            });

            // Edit button functionality
            const editButtons = document.querySelectorAll('.editBtn');
            const modal = new bootstrap.Modal(document.getElementById('supplierLedgerModal'));
            const form = document.getElementById('supplierLedgerForm');
            const submitBtn = document.getElementById('submitBtn');
            const modalTitle = document.getElementById('supplierLedgerModalLabel');
            const recordId = document.getElementById('record_id');

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Populate form with existing data
                    document.getElementById('date').value = this.dataset.date;
                    document.getElementById('supplier_name').value = this.dataset.supplier_name;
                    document.getElementById('reference_number').value = this.dataset.ref_no;
                    document.getElementById('description').value = this.dataset.description;
                    document.getElementById('credit').value = this.dataset.credit;
                    document.getElementById('debit').value = this.dataset.debit;
                    recordId.value = this.dataset.id;

                    // Change modal title and button text
                    modalTitle.textContent = 'Edit Ledger Record';
                    submitBtn.textContent = 'Update';
                    submitBtn.name = 'UpdateSupplierLedger';

                    // Show modal
                    modal.show();
                });
            });

            // Reset modal when hidden
            document.getElementById('supplierLedgerModal').addEventListener('hidden.bs.modal', function() {
                form.reset();
                form.classList.remove('was-validated');
                modalTitle.textContent = 'Add a Ledger Record';
                submitBtn.textContent = 'Add';
                submitBtn.name = 'SubmitSupplierLedger';
                recordId.value = '';
            });

            // Re-initialize DataTables when tab changes (if needed)
            $('#supplierTabs button').on('shown.bs.tab', function() {
                $('.supplier-ledger-table').DataTable().columns.adjust().responsive.recalc();
            });
        });
    </script>
</body>
</html>