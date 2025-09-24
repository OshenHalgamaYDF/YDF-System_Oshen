<?php
// Database connection and data processing should be at the TOP
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle DELETE operation first
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM supplierledger WHERE ID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            header("Location: SupplierLedgerInput.php");
            exit();
        } else {
            echo "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle INSERT/UPDATE operations
if (isset($_POST['SubmitSupplierLedger']) || isset($_POST['UpdateSupplierLedger'])) {
    // Validate and sanitize inputs
    $id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    $datele = isset($_POST['date']) ? mysqli_real_escape_string($conn, trim($_POST['date'])) : '';
    $supplierle = isset($_POST['supplier_name']) ? mysqli_real_escape_string($conn, trim($_POST['supplier_name'])) : '';
    $refle = isset($_POST['reference_number']) ? mysqli_real_escape_string($conn, trim($_POST['reference_number'])) : '';
    $describle = isset($_POST['description']) ? mysqli_real_escape_string($conn, trim($_POST['description'])) : '';
    $creditle = isset($_POST['credit']) ? floatval($_POST['credit']) : 0;
    $debitle = isset($_POST['debit']) ? floatval($_POST['debit']) : 0;
    $remark = isset($_POST['remark']) ? mysqli_real_escape_string($conn, trim($_POST['remark'])) : '';
    $creditleDol = isset($_POST['creditDol']) ? floatval($_POST['creditDol']) : 0;
    $debitleDol = isset($_POST['debitDol']) ? floatval($_POST['debitDol']) : 0;
    $typeSelect = isset($_POST['TypeSelect']) ? mysqli_real_escape_string($conn, trim($_POST['TypeSelect'])) : '';

    // Validate required fields
    if (empty($datele) || empty($supplierle) || empty($refle) || empty($describle) || empty($typeSelect)) {
        die("Error: All fields are required.");
    }

    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $datele)) {
        die("Error: Invalid date format.");
    }

    // Reset amounts based on selected type
    if ($typeSelect === 'LKR') {
        $creditleDol = 0;
        $debitleDol = 0;
    } elseif ($typeSelect === 'Dollar') {
        $creditle = 0;
        $debitle = 0;
    }

    if ($id > 0 && isset($_POST['UpdateSupplierLedger'])) {
        // UPDATE query
        $stmt = $conn->prepare("UPDATE supplierledger SET date=?, supplier_name=?, ref_no=?, description=?, `credit(LKR)`=?, `debit(LKR)`=?, `credit($)`=?, `debit($)`=?, remark=? WHERE ID=?");
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("ssssddddsi", $datele, $supplierle, $refle, $describle, $creditle, $debitle, $creditleDol, $debitleDol, $remark, $id);
        
        if ($stmt->execute()) {
            header("Location: SupplierLedgerInput.php");
            exit();
        } else {
            echo "Error updating record: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['SubmitSupplierLedger'])) {
        // INSERT query
        $stmt = $conn->prepare("INSERT INTO supplierledger (date, supplier_name, ref_no, description, `credit(LKR)`, `debit(LKR)`, `credit($)`, `debit($)`, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("ssssdddds", $datele, $supplierle, $refle, $describle, $creditle, $debitle, $creditleDol, $debitleDol, $remark);
        
        if ($stmt->execute()) {
            header("Location: SupplierLedgerInput.php");
            exit();
        } else {
            echo "Error adding record: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get distinct supplier names from supplierledger table
$suppliername = [];
$supplierresult = $conn->query("SELECT DISTINCT supplier_name 
                                FROM supplierledger 
                                ORDER BY supplier_name ASC");

if ($supplierresult) {
    while ($row = $supplierresult->fetch_assoc()) {
        $suppliername[] = $row['supplier_name'];
    }
} else {
    die("Error fetching suppliers: " . $conn->error);
}

// Get Supplier name form supplier table
$supplierDD = [];
$supplierDDcollection = $conn->query("SELECT name FROM suppliers ORDER BY name ASC");

if ($supplierDDcollection){
    while ($row = $supplierDDcollection->fetch_assoc()){
        $supplierDD[] = $row['name'];
    }
} else {
    die("Error fetching suppliers: " . $conn->error);
}

// Get all records
$allRecords = [];
$result = $conn->query("SELECT * FROM supplierledger ORDER BY date ASC, supplier_name ASC");
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $allRecords[] = $row;
        }
    }
} else {
    die("Error fetching records: " . $conn->error);
}

// Group records by supplier for the tabs
$recordsBySupplier = [];
foreach ($allRecords as $record) {
    $supplier = $record['supplier_name'];
    if (!isset($recordsBySupplier[$supplier])) {
        $recordsBySupplier[$supplier] = [];
    }
    $recordsBySupplier[$supplier][] = $record;
}

// Now include the controller (if needed)
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery FIRST -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        table.dataTable tfoot td {
            white-space: nowrap;
        }

        table.dataTable {
            width: 100% !important;
            table-layout: auto;
        }
        .hidden {
            display: none !important;
        }
        .currency-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .table th {
            white-space: nowrap;
        }
        .hidden-column {
            display: none;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
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

        <!-- Modal-->
        <div class="modal fade" id="supplierLedgerModal" tabindex="-1" aria-labelledby="supplierLedgerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="supplierLedgerModalLabel">Add a Ledger Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" class="row g-3" id="supplierLedgerForm" novalidate autocomplete="off">
                            <div class="col-md-12">
                                <label>Date:</label>
                                <input type="date" name="date" id="date" class="form-control" required>
                                <div class="invalid-feedback">Please enter a date.</div>
                            </div>

                            <div class="col-md-12">
                                <label>Supplier Name:</label>
                                <select name="supplier_name" id="supplier_name" class="form-select" required>
                                    <option value="">-- Select a Supplier --</option>
                                    <?php foreach ($supplierDD as $supplier): ?>
                                        <option value="<?php echo htmlspecialchars($supplier); ?>">
                                            <?php echo htmlspecialchars($supplier); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a supplier name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label>Reference Number:</label>
                                <input type="text" name="reference_number" id="reference_number" class="form-control" required>
                                <div class="invalid-feedback">Reference number is required.</div>
                            </div>

                            <div class="col-md-6">
                                <label>Description:</label>
                                <input type="text" name="description" id="description" class="form-control" required>
                                <div class="invalid-feedback">Please enter the description.</div>
                            </div>
                            
                            <div class="col-md-12">
                                <label for="TypeSelect">Choose the record type:</label>
                                <select id="TypeSelect" name="TypeSelect" class="form-select" required>
                                    <option value="">-- Select a Type--</option>
                                    <option value="LKR">Enter in LKR only</option>
                                    <option value="Dollar">Enter in $ only</option>
                                    <option value="both">Enter Both Types</option>
                                </select>
                                <div class="invalid-feedback">Please Select a Type.</div> 
                            </div>

                            <div id="CreditLKR" class="col-md-6 hidden">
                                <label>Credit (LKR):</label>
                                <input type="number" step="0.01" name="credit" id="credit" class="form-control" value="0">
                                <div class="invalid-feedback">Please enter the credit amount (LKR).</div>
                            </div>

                            <div id="DebitLKR" class="col-md-6 hidden">
                                <label>Debit (LKR):</label>
                                <input type="number" step="0.01" name="debit" id="debit" class="form-control" value="0">
                                <div class="invalid-feedback">Please enter the debit amount (LKR).</div>
                            </div>

                            <div id="CreditDol" class="col-md-6 hidden">
                                <label>Credit ($):</label>
                                <input type="number" step="0.01" name="creditDol" id="creditDol" class="form-control" value="0">
                                <div class="invalid-feedback">Please enter the credit amount ($).</div>
                            </div>

                            <div id="DebitDol" class="col-md-6 hidden">
                                <label>Debit ($):</label>
                                <input type="number" step="0.01" name="debitDol" id="debitDol" class="form-control" value="0">
                                <div class="invalid-feedback">Please enter the debit amount ($).</div>
                            </div>

                            <div class="col-md-12">
                                <label>Remark:</label>
                                <input type="text" name="remark" id="remark" class="form-control">
                                <div class="invalid-feedback">Please enter remark.</div>
                            </div>
                            
                            <input type="hidden" name="record_id" id="record_id" value="">
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="SubmitSupplierLedger" class="btn btn-primary px-4" id="submitBtn">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navbar-->
        <div class="container mt-4">
            <!-- Tables Navbar-->
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
            <!-- Table content-->
            <div class="tab-content mt-2">
                <?php if (count($suppliername) > 0): ?>
                    <?php foreach ($suppliername as $index => $name): ?>
                        <?php
                        $supplierRecords = isset($recordsBySupplier[$name]) ? $recordsBySupplier[$name] : [];
                        $hasLKR = false;
                        $hasUSD = false;
                        
                        // Check what currencies exist for this supplier
                        foreach ($supplierRecords as $row) {
                            if ($row['credit(LKR)'] > 0 || $row['debit(LKR)'] > 0) {
                                $hasLKR = true;
                            }
                            if (isset($row['credit($)']) && ($row['credit($)'] > 0 || (isset($row['debit($)']) && $row['debit($)'] > 0))) {
                                $hasUSD = true;
                            }
                            // If we found both, no need to continue checking
                            if ($hasLKR && $hasUSD) break;
                        }
                        
                        // Determine column structure
                        $lkrColumns = 3; // Credit LKR, Debit LKR, Balance LKR
                        $usdColumns = 3; // Credit USD, Debit USD, Balance USD
                        $baseColumns = 4; // Date, Supplier, Ref No, Description
                        $otherColumns = 2; // Remark, Actions
                        
                        $totalColumns = $baseColumns + $otherColumns;
                        if ($hasLKR) $totalColumns += $lkrColumns;
                        if ($hasUSD) $totalColumns += $usdColumns;
                        ?>
                        <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                             id="content-<?php echo md5($name); ?>" 
                             role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped supplier-ledger-table" id="table-<?php echo md5($name); ?>" data-has-lkr="<?php echo $hasLKR ? 'true' : 'false'; ?>" data-has-usd="<?php echo $hasUSD ? 'true' : 'false'; ?>">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Supplier Name</th>
                                            <th>Reference Number</th>
                                            <th>Description</th>
                                            
                                            <?php if ($hasLKR): ?>
                                                <th>Credit (LKR)</th>
                                                <th>Debit (LKR)</th>
                                                <th>Balance (LKR)</th>
                                            <?php endif; ?>
                                            
                                            <?php if ($hasUSD): ?>
                                                <th>Credit ($)</th>
                                                <th>Debit ($)</th>
                                                <th>Balance ($)</th>
                                            <?php endif; ?>
                                            
                                            <th>Remark</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $balanceLKR = 0;
                                        $balanceUSD = 0;
                                        
                                        if (count($supplierRecords) > 0) {
                                            foreach ($supplierRecords as $row) {
                                                $balanceLKR += ($row['credit(LKR)'] - $row['debit(LKR)']);
                                                $creditUSD = isset($row['credit($)']) ? $row['credit($)'] : 0;
                                                $debitUSD = isset($row['debit($)']) ? $row['debit($)'] : 0;
                                                $balanceUSD += ($creditUSD - $debitUSD);
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['ref_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                                    
                                                    <?php if ($hasLKR): ?>
                                                        <td class="text-success"><?php echo number_format($row['credit(LKR)'], 2); ?></td>
                                                        <td class="text-danger"><?php echo number_format($row['debit(LKR)'], 2); ?></td>
                                                        <td class="fw-bold <?php echo $balanceLKR >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                            <?php echo number_format($balanceLKR, 2); ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hasUSD): ?>
                                                        <td class="text-success"><?php echo number_format($creditUSD, 2); ?></td>
                                                        <td class="text-danger"><?php echo number_format($debitUSD, 2); ?></td>
                                                        <td class="fw-bold <?php echo $balanceUSD >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                            <?php echo number_format($balanceUSD, 2); ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    
                                                    <td><?php echo htmlspecialchars($row['remark'] ?? ''); ?></td>
                                                    <td>
                                                        <button class='btn btn-sm btn-warning editBtn' 
                                                                data-id='<?php echo $row['ID']; ?>' 
                                                                data-date='<?php echo $row['date']; ?>' 
                                                                data-supplier_name='<?php echo htmlspecialchars($row['supplier_name']); ?>' 
                                                                data-ref_no='<?php echo htmlspecialchars($row['ref_no']); ?>' 
                                                                data-description='<?php echo htmlspecialchars($row['description']); ?>' 
                                                                data-credit='<?php echo $row['credit(LKR)']; ?>' 
                                                                data-debit='<?php echo $row['debit(LKR)']; ?>'
                                                                data-creditdol='<?php echo $creditUSD; ?>'
                                                                data-debitdol='<?php echo $debitUSD; ?>'
                                                                data-remark='<?php echo htmlspecialchars($row['remark'] ?? ''); ?>'
                                                                data-typesel='<?php 
                                                                    $hasLKRRecord = $row['credit(LKR)'] > 0 || $row['debit(LKR)'] > 0;
                                                                    $hasUSDRecord = $creditUSD > 0 || $debitUSD > 0;
                                                                    if ($hasLKRRecord && $hasUSDRecord) echo 'both';
                                                                    elseif ($hasUSDRecord) echo 'Dollar';
                                                                    else echo 'LKR';
                                                                ?>'>
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href='SupplierLedgerInput.php?delete_id=<?php echo $row['ID']; ?>' 
                                                           class='btn btn-sm btn-danger deleteBtn' 
                                                           onclick='return confirmDelete()'>
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                            } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-info">
                                            <td colspan="6" class="fw-bold text-end">
                                                Amount to be settled:
                                            </td>
                                            
                                            <?php if ($hasLKR): ?>
                                                <td class="fw-bold text-center <?php echo $balanceLKR >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    LKR <?php echo number_format($balanceLKR, 2); ?>
                                                </td>
                                            <?php endif; ?>
                                            
                                            <?php if ($hasUSD): ?>
                                                <td></td>
                                                <td></td>
                                                <td class="fw-bold text-center <?php echo $balanceUSD >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    $ <?php echo number_format($balanceUSD, 2); ?>
                                                </td>
                                            <?php endif; ?>
                                            
                                            <td colspan="2"></td>
                                        </tr>
                                        
                                        <?php if ($hasLKR && $hasUSD): ?>
                                        <tr class="table-warning">
                                            <td colspan="<?php echo $totalColumns; ?>" class="fw-bold text-center">
                                                Total Payable: 
                                                LKR <?php echo number_format($balanceLKR, 2); ?> | 
                                                $ <?php echo number_format($balanceUSD, 2); ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tfoot>
                                        <?php } else {
                                            ?>
                                            <tr>
                                                <td colspan='<?php echo $totalColumns; ?>' class='text-center'>No records found for <?php echo htmlspecialchars($name); ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
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

    // Delete confirmation function
    function confirmDelete() {
        return confirm("Are you sure you want to delete this record?");
    }

    //Input Type select
    const dropdown = document.getElementById('TypeSelect');
    const creditlkrField = document.getElementById('CreditLKR');
    const debitlkrField = document.getElementById('DebitLKR');
    const creditdolField = document.getElementById('CreditDol');
    const debitdolField = document.getElementById('DebitDol');

    dropdown.addEventListener('change', function() {
        // Hide all first
        creditlkrField.classList.add('hidden');
        debitlkrField.classList.add('hidden');
        creditdolField.classList.add('hidden');
        debitdolField.classList.add('hidden');

        // Remove required attributes first
        document.getElementById('credit').removeAttribute('required');
        document.getElementById('debit').removeAttribute('required');
        document.getElementById('creditDol').removeAttribute('required');
        document.getElementById('debitDol').removeAttribute('required');

        // Show based on selected value and set required attributes
        if (this.value === 'LKR') {
            creditlkrField.classList.remove('hidden');
            debitlkrField.classList.remove('hidden');
            document.getElementById('credit').setAttribute('required', 'required');
            document.getElementById('debit').setAttribute('required', 'required');
        } else if (this.value === 'Dollar') {
            creditdolField.classList.remove('hidden');
            debitdolField.classList.remove('hidden');
            document.getElementById('creditDol').setAttribute('required', 'required');
            document.getElementById('debitDol').setAttribute('required', 'required');
        } else if (this.value === 'both') {
            creditlkrField.classList.remove('hidden');
            debitlkrField.classList.remove('hidden');
            creditdolField.classList.remove('hidden');
            debitdolField.classList.remove('hidden');
            document.getElementById('credit').setAttribute('required', 'required');
            document.getElementById('debit').setAttribute('required', 'required');
            document.getElementById('creditDol').setAttribute('required', 'required');
            document.getElementById('debitDol').setAttribute('required', 'required');
        }
    });

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Initialize DataTables for each supplier table with dynamic column handling
        $('.supplier-ledger-table').each(function() {
            const table = $(this);
            const hasLKR = table.data('has-lkr') === 'true';
            const hasUSD = table.data('has-usd') === 'true';
            
            // Determine which columns are sortable (exclude remark and actions)
            let nonSortableColumns = [];
            let baseColumns = 4; // Date, Supplier, Ref No, Description
            
            if (hasLKR) baseColumns += 3;
            if (hasUSD) baseColumns += 3;
            
            // Remark and Actions are the last 2 columns
            nonSortableColumns.push(baseColumns); // Remark column
            nonSortableColumns.push(baseColumns + 1); // Actions column

            table.DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true,
                "info": true,
                "paging": true,
                "order": [[0, 'asc']], // Sort by date ascending
                "columnDefs": [
                    { "orderable": false, "targets": nonSortableColumns }
                ],
                "autoWidth": false,
                "responsive": true
            });
        });

        // Edit button functionality - use event delegation for dynamically loaded content
        $(document).on('click', '.editBtn', function() {
            // Populate form with existing data
            $('#date').val($(this).data('date'));
            $('#supplier_name').val($(this).data('supplier_name'));
            $('#reference_number').val($(this).data('ref_no'));
            $('#description').val($(this).data('description'));
            $('#credit').val(parseFloat($(this).data('credit')) || 0);
            $('#debit').val(parseFloat($(this).data('debit')) || 0);
            $('#creditDol').val(parseFloat($(this).data('creditdol')) || 0);
            $('#debitDol').val(parseFloat($(this).data('debitdol')) || 0);
            $('#remark').val($(this).data('remark'));
            $('#record_id').val($(this).data('id'));
            $('#TypeSelect').val($(this).data('typesel'));

            // Trigger change event to show/hide appropriate fields
            $('#TypeSelect').trigger('change');

            // Change modal title and button text
            $('#supplierLedgerModalLabel').text('Edit Ledger Record');
            $('#submitBtn').text('Update').attr('name', 'UpdateSupplierLedger');

            // Show modal smoothly
            var modal = new bootstrap.Modal(document.getElementById('supplierLedgerModal'));
            modal.show();
        });

        // Reset modal when hidden
        $('#supplierLedgerModal').on('hidden.bs.modal', function() {
            $('#supplierLedgerForm')[0].reset();
            $('#supplierLedgerForm').removeClass('was-validated');
            $('#supplierLedgerModalLabel').text('Add a Ledger Record');
            $('#submitBtn').text('Add').attr('name', 'SubmitSupplierLedger');
            $('#record_id').val('');
            $('#TypeSelect').val('').trigger('change');
        });

        // Adjust DataTable when tab changes
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            let targetTable = $($(e.target).attr('data-bs-target')).find('table');
            if ($.fn.DataTable.isDataTable(targetTable)) {
                targetTable.DataTable().columns.adjust().responsive.recalc();
            }
        });

        // Handle delete links
        $(document).on('click', '.deleteBtn', function(e) {
            if (!confirm("Are you sure you want to delete this record?")) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>