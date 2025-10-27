<?php
    include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\Shipment P&L\USAP_LController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USA Shipment P&L</title>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>   

    <style>
        body {
            background-color: #f8f9fa;
        }

        h1 {
            font-weight: 700;
            color: #0d6efd;
            text-align: center;
            margin-bottom: 2rem;
        }

        .btn-primary i {
            margin-right: 5px;
        }

        /* Table wrapper */
        .table-responsive {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            padding: 1rem;
        }

        /* Table appearance */
        #usaShippingTable th, 
        #usaShippingTable td {
            text-align: center;
            vertical-align: middle !important;
            white-space: nowrap;
            font-size: 0.95rem;
        }

        table.dataTable thead th {
            background-color: #212529 !important;
            color: #fff !important;
            border-bottom: 2px solid #0d6efd !important;
            top: 0;
            z-index: 10;
            font-size: 0.95rem;
        }


        /* Zebra striping */
        #usaShippingTable tbody tr:nth-of-type(odd) {
            background-color: #f6f8fa;
        }

        /* Responsive: allow wrapping on small screens */
        @media (max-width: 992px) {
            #usaShippingTable th, 
            #usaShippingTable td {
                white-space: normal;
                font-size: 0.9rem;
            }
        }

        /* DataTables Control Styling */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.25rem;
            padding: 0.3rem 0.5rem;
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 0.7rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .modal-header {
            background: #0d6efd;
            color: #fff;
        }

        .modal-footer {
            background: #f8f9fa;
        }

        /* Buttons in table */
        .action-buttons button {
            margin: 0 2px;
        }
        
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center text-primary mb-4"><b>USA Shipment P&L</b></h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#USAshipmentinputModal" id="addNewBtn">
                <i class="fas fa-plus"></i> Enter USA Shipment Data
        </button>

        <!-- Modal for USA Shipment Data Input -->
        <div class="modal fade" id="USAshipmentinputModal" tabindex="-1" aria-labelledby="USAshipmentinputModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="" id="usashippingForm" class="needs-validation" novalidate>
                        <input type="hidden" id="editId" name="editId" value="">
                        <div class="modal-header">
                            <h5 class="modal-title" id="USAshipmentinputModalLabel">Add New USA Shipment Data</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="usashipdate" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="usashipdate" name="usashipdate" required>
                                    <div class="invalid-feedback">Please enter the Date.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipincome" class="form-label">Shipment Income</label>
                                    <input type="number" class="form-control" id="usashipincome" name="usashipincome" step="0.001" required>
                                    <div class="invalid-feedback">Please enter the Shipment Income.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipkg" class="form-label">Shipment Weight (kg)</label>
                                    <input type="number" class="form-control" id="usashipkg" name="usashipkg" step="0.001" required>
                                    <div class="invalid-feedback">Please enter the Shipment Weight.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipjccommision" class="form-label">JC Commission </label>
                                    <input type="number" class="form-control" id="usashipjccommision" name="usashipjccommision" step="0.001">
                                    <div class="invalid-feedback">Please enter the JC Commission.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipceylonfresh" class="form-label">Ceylon Fresh (USD)</label>
                                    <input type="number" class="form-control" id="usashipceylonfresh" name="usashipceylonfresh" step="0.001" >
                                    <div class="invalid-feedback">Please enter the Ceylon Fresh.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashippowerfreight" class="form-label">Power Freight (USD)</label>
                                    <input type="number" class="form-control" id="usashippowerfreight" name="usashippowerfreight" step="0.001">
                                    <div class="invalid-feedback">Please enter the Power Freight.</div>
                                </div>
                                <div class="col-md-12">
                                    <label for="usashipexchangerate" class="form-label">Exchange Rate (LKR)</label>
                                    <input type="number" class="form-control text-center fw-bold" id="usashipexchangerate" name="usashipexchangerate" value='290' style="background-color: #e0f7fa;" required>
                                    <div class="invalid-feedback">Please enter the Exchange Rate.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipfishbill" class="form-label">Fish Bill Amount (LKR)</label>
                                    <input type="number" class="form-control" id="usashipfishbill" name="usashipfishbill" step="0.001">
                                    <div class="invalid-feedback">Please enter the Fish Bill.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipprocessingpay" class="form-label">Processing Payment (LKR)</label>
                                    <input type="number" class="form-control" id="usashipprocessingpay" name="usashipprocessingpay" step="0.001">
                                    <div class="invalid-feedback">Please enter the Processing Payment.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipfreightpayment" class="form-label">Freight Payment (LKR)</label>
                                    <input type="number" class="form-control" id="usashipfreightpayment" name="usashipfreightpayment" step="0.001">
                                    <div class="invalid-feedback">Please enter the Freight Payment.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipoh" class="form-label">OH value (LKR)</label>
                                    <input type="number" class="form-control" id="usashipoh" name="usashipoh" step="0.001">
                                    <div class="invalid-feedback">Please enter the OH.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipairportcost" class="form-label">Airport Transport (LKR)</label>
                                    <input type="number" class="form-control" id="usashipairportcost" name="usashipairportcost" step="0.001">
                                    <div class="invalid-feedback">Please enter the Airport Transport.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="usashipLabour" class="form-label">Labour Transport (LKR)</label>
                                    <input type="number" class="form-control" id="usashipLabour" name="usashipLabour" step="0.001">
                                    <div class="invalid-feedback">Please enter the Labour Transport.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="usaShipmentSubmit" class="btn btn-primary">Add Shipping Data</button>
                            <button type="submit" name="usaShipmentUpdate" class="btn btn-success" style="display:none;">Update Shipping Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div> 

        <!-- Table Output -->
        <div class="table-responsive mt-4">
            <table class="table table-bordered" id="usaShippingTable" style="width:100%">
                <thead>
                    <tr>
                        <th class="text-center">Date</th>
                        <th class="text-center">Income (USD)</th>
                        <th class="text-center">Weight (kg)</th>
                        <th class="text-center">JC's Commission (USD)</th>
                        <th class="text-center">Ceylon Fresh (USD)</th>
                        <th class="text-center">Power Freight (USD)</th>
                        <th class="text-center">Amount to Convert to LKR</th>
                        <th class="text-center">LKR value</th>
                        <th class="text-center">Fish Bill (LKR)</th>
                        <th class="text-center">Processing Cost (LKR)</th>
                        <th class="text-center">Freight Cost (LKR)</th>
                        <th class="text-center">OH (LKR)</th>
                        <th class="text-center">Airport Cost (LKR)</th>
                        <th class="text-center">Labour Cost (LKR)</th>
                        <th class="text-center">Profit (LKR)</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        // Fetch and display data from the database
                        $result = $conn->query("SELECT * FROM usa_shipping_pl ORDER BY date DESC");
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $JC_Commision = $row['kg'] * $row['jc'];
                                $MoneytoConvert = $row['income'] - ($row['ceylonfresh'] + $row['powerfreight'] + $JC_Commision);
                                $ConvertedMoney = $MoneytoConvert * $row['exchangerate'];
                                $Profit = $ConvertedMoney - ($row['fishbill'] + $row['processcost'] + $row['freightcost'] + $row['oh'] + $row['airportcost'] + $row['labourcost']);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['income']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kg']); ?></td>
                                    <td><?php echo htmlspecialchars($JC_Commision); ?></td>
                                    <td><?php echo htmlspecialchars($row['ceylonfresh']); ?></td>
                                    <td><?php echo htmlspecialchars($row['powerfreight']); ?></td>
                                    <td><?php echo htmlspecialchars($MoneytoConvert); ?></td>
                                    <td><?php echo htmlspecialchars($ConvertedMoney); ?></td>
                                    <td><?php echo htmlspecialchars($row['fishbill']); ?></td>
                                    <td><?php echo htmlspecialchars($row['processcost']); ?></td>
                                    <td><?php echo htmlspecialchars($row['freightcost']); ?></td>
                                    <td><?php echo htmlspecialchars($row['oh']); ?></td>
                                    <td><?php echo htmlspecialchars($row['airportcost']); ?></td>
                                    <td><?php echo htmlspecialchars($row['labourcost']); ?></td>
                                    <td><?php echo number_format($Profit, 3); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-warning btn-edit" onclick="editRecord('<?php echo htmlspecialchars($row['id'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-delete" onclick="deleteRecord('<?php echo htmlspecialchars($row['id'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php }
                        } else {
                            echo "<tr><td colspan='16' class='text-center'>No data available</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div> 
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this record?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
<script>
    $(document).ready(function() {
        var table = $('#usaShippingTable').DataTable({
        scrollX: true,
        scrollCollapse: true,
        responsive: false,
        autoWidth: false,
        columnDefs: [{ targets: -1, orderable: false }],
        pageLength: 10,
        lengthMenu: [5,10,25,50]
    });

    // Enable fixed header
    new $.fn.dataTable.FixedHeader(table);

    });

    // Form validation for modal form with id="usashippingForm"
    (function () {
        'use strict';
        var form = document.getElementById('usashippingForm');
        if (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        }

        // When modal closes, reset validation state
        var modalEl = document.getElementById('USAshipmentinputModal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                if (form) {
                    form.classList.remove('was-validated');
                    document.getElementById('editId').value = '';
                    document.querySelector('button[name="usaShipmentSubmit"]').style.display = 'block';
                    document.querySelector('button[name="usaShipmentUpdate"]').style.display = 'none';
                    document.getElementById('USAshipmentinputModalLabel').textContent = 'Add New USA Shipment Data';
                    form.reset();
                }
            });
        }
    })();
    
    // Improved Edit record function
    function editRecord(id) {
        console.log('Editing record ID:', id); // Debug log
        
        fetch('USAP_L.php?action=getRecord&id=' + id)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Populate form fields
                    document.getElementById('editId').value = data.record.id;
                    document.getElementById('usashipdate').value = data.record.date;
                    document.getElementById('usashipincome').value = data.record.income;
                    document.getElementById('usashipkg').value = data.record.kg;
                    document.getElementById('usashipceylonfresh').value = data.record.ceylonfresh;
                    document.getElementById('usashippowerfreight').value = data.record.powerfreight;
                    document.getElementById('usashipjccommision').value = data.record.jc;
                    document.getElementById('usashipexchangerate').value = data.record.exchangerate;
                    document.getElementById('usashipfishbill').value = data.record.fishbill;
                    document.getElementById('usashipprocessingpay').value = data.record.processcost;
                    document.getElementById('usashipfreightpayment').value = data.record.freightcost;
                    document.getElementById('usashipoh').value = data.record.oh;
                    document.getElementById('usashipairportcost').value = data.record.airportcost;
                    document.getElementById('usashipLabour').value = data.record.labourcost;
                    
                    // Update modal title and buttons
                    document.getElementById('USAshipmentinputModalLabel').textContent = 'Edit USA Shipment Data';
                    document.querySelector('button[name="usaShipmentSubmit"]').style.display = 'none';
                    document.querySelector('button[name="usaShipmentUpdate"]').style.display = 'block';
                    
                    // Show modal
                    var modal = new bootstrap.Modal(document.getElementById('USAshipmentinputModal'));
                    modal.show();
                } else {
                    alert('Error: ' + (data.message || 'Record not found'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching record data: ' + error.message);
            });
    }

    let deleteId = null; // Store the ID of the record to delete

    function deleteRecord(id) {
        deleteId = id; // store id globally
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
        deleteModal.show();
    }

    // Handle confirm delete button click
    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (!deleteId) return;

        fetch('USAP_L.php?action=deleteRecord&id=' + deleteId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Hide modal before reload
                    const deleteModalEl = document.getElementById('deleteConfirmationModal');
                    const modalInstance = bootstrap.Modal.getInstance(deleteModalEl);
                    modalInstance.hide();

                    // Refresh to update table
                    location.reload();
                } else {
                    alert('Error deleting record: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting record: ' + error.message);
            });
    });

</script>
</body>
</html>