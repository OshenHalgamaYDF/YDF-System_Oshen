<?php
    include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\YDFOutstandingStatement\YDFOutstandingStatementController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YDF Outstanding Statement</title>
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

        h2 {
            font-weight: 800;
            color: #0d6efd;
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2.7rem;
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
    <div class="container mt-4">
        <h2 class="text-center text-primary mb-4">YDF Outstanding Statement</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#YDFOutstandingModal" id="addNewBtn">
                <i class="fas fa-plus"></i> Enter Outstanding Values
        </button>

        <!--Table Display-->
        <div class="table-responsive mt-4">
            <table id="ydfOutstandingTable" class="table table-striped table-bordered nowrap" style="width:100%">
                <thead class="table-dark text-center">
                    <tr>
                        <th class="text-center">Date</th>
                        <th class="text-center">Activity</th>
                        <th class="text-center">Reference</th>
                        <th class="text-center">Due Date</th>
                        <th class="text-center">Invoices (USD)</th>
                        <th class="text-center">Payments (USD)</th>
                        <th class="text-center">Balance (USD)</th>
                        <th class="text-center">Action</th> <!-- New Action column -->
                    </tr>
                </thead>
                <tbody class="text-center align-middle">
                    <?php
                        $sql = "
                            SELECT `id`, `date`, activity, reference, `type`, value, timestamp
                            FROM ydf_outstanding
                            ORDER BY 
                                `date` ASC,
                                CASE 
                                    WHEN activity = 'Payment Received' THEN 1
                                    WHEN type = 'payment' THEN 2
                                    WHEN type = 'invoice' THEN 3
                                    ELSE 4
                                END
                        ";
                        $result = $conn->query($sql);

                        $balance = 0;

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $id = $row['id']; // For edit/delete actions
                                $date = htmlspecialchars($row['date']);
                                $activity = htmlspecialchars($row['activity']);
                                $reference = htmlspecialchars($row['reference']);
                                $type = htmlspecialchars($row['type']);
                                $value = floatval($row['value']);

                                // Due date
                                $dueDate = ($type === 'invoice') ? date('Y-m-d', strtotime($row['date'] . ' +7 days')) : '';

                                $invoice = $type === 'invoice' ? $value : 0;
                                $payment = $type === 'payment' ? $value : 0;

                                // Update balance
                                if ($type === 'invoice') {
                                    $balance += $value;
                                } elseif ($type === 'payment') {
                                    $balance -= $value;
                                }

                                // Payment style
                                $paymentStyle = ($type === 'payment' && $activity !== 'Payment Received') ? 'style="color:red;"' : '';
                    ?>
                                <tr>
                                    <td><?= $date ?></td>
                                    <td><?= $activity ?></td>
                                    <td><?= $reference ?></td>
                                    <td><?= htmlspecialchars($dueDate) ?></td>
                                    <td><?= $invoice ? number_format($invoice, 2) : '' ?></td>
                                    <td <?= $paymentStyle ?>><?= $payment ? number_format($payment, 2) : '' ?></td>
                                    <td><strong><?= number_format($balance, 2) ?></strong></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-warning edit-btn" data-id="<?= $id ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $id ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                    <?php
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center text-muted'>No data found</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for YDF Outstanding Statement Input -->
    <div class="modal fade" id="YDFOutstandingModal" tabindex="-1" aria-labelledby="YDFOutstandingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="post" action="" id="ydfOutstandingForm" class="needs-validation" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title" id="YDFOutstandingModalLabel">YDF Outstanding Statement Input</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="ydfDate" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="ydfDate" name="ydfDate" required>
                                        <div class="invalid-feedback">Please enter the Date.</div>
                                    </div>
                                    <div class="col-md-9">
                                        <label for="ydfActivity" class="form-label">Activity</label>
                                        <input type="text" class="form-control" id="ydfActivity" name="ydfActivity" required>
                                        <div class="invalid-feedback">Please enter the Activity.</div>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="ydfReference" class="form-label">Reference</label>
                                        <input type="text" class="form-control" id="ydfReference" name="ydfReference">
                                        <div class="invalid-feedback">Please enter the Reference.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ydftype" class="form-label">Select Outstanding Type</label>
                                        <select class="form-select" id="ydftype" name="ydftype" required>
                                            <option value="" disabled selected>Select type</option>
                                            <option value="invoice">Invoices</option>
                                            <option value="payment">Payments</option>
                                        </select>
                                        <div class="invalid-feedback">Please select an Outstanding Type.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ydfValue" class="form-label">Value</label>
                                        <input type="number" class="form-control" id="ydfValue" step="0.001" name="ydfValue" required>
                                        <div class="invalid-feedback">Please enter the Value.</div>
                                    </div>
                                </div>
                        </div>
                        <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" form="ydfOutstandingForm" name="ydfoutstandingsubmit" class="btn btn-primary">Save Entry</button>
                                <button type="submit" name="ydfoutstandingupdate" class="btn btn-primary" style="display:none;">Update Entry</button>
                        </div>
                    </form>
                </div>
            </div>
    </div>
 
<script>
    $(document).ready(function(){
        // Initialize DataTable
        var table = $('#ydfOutstandingTable').DataTable({
            responsive:true,
            fixedHeader:true,
            columnDefs:[{orderable:false, targets:-1}],
            "displayStart": 0, // we'll override below
            "initComplete": function(settings, json) {
                var api = this.api();
                var lastPageIndex = Math.ceil(api.rows().count() / api.page.len()) - 1;
                api.page(lastPageIndex).draw('page');
            }
        });

        // Edit Button
        $('#ydfOutstandingTable').on('click','.edit-btn', function(){
            var id = $(this).data('id');
            $.get(window.location.href, {fetchId:id}, function(data){
                data = JSON.parse(data);
                $('#ydfDate').val(data.date);
                $('#ydfActivity').val(data.activity);
                $('#ydfReference').val(data.reference);
                $('#ydftype').val(data.type);
                $('#ydfValue').val(data.value);
                $('#ydfOutstandingForm').append('<input type="hidden" id="ydfId" name="ydfId" value="'+id+'">');
                $('button[name="ydfoutstandingsubmit"]').hide();
                $('button[name="ydfoutstandingupdate"]').show();
                $('#YDFOutstandingModal .modal-title').text('Edit Outstanding Entry');
                $('#YDFOutstandingModal').modal('show');
            });
        });

        // Delete Button
        $('#ydfOutstandingTable').on('click','.delete-btn', function(){
            var id = $(this).data('id');
            if(confirm('Are you sure you want to delete this entry?')) {
                $.post(window.location.href,{deleteId:id},function(response){
                    var res = JSON.parse(response);
                    if(res.status === 'success') location.reload();
                    else alert('Failed to delete entry.');
                });
            }
        });

        // Reset modal on close
        $('#YDFOutstandingModal').on('hidden.bs.modal', function(){
            $('#ydfOutstandingForm')[0].reset();
            $('#ydfId').remove();
            $('button[name="ydfoutstandingsubmit"]').show();
            $('button[name="ydfoutstandingupdate"]').hide();
            $('#YDFOutstandingModal .modal-title').text('Enter Outstanding Values');
            $('#ydfOutstandingForm').removeClass('was-validated');
        });

        // Bootstrap form validation
        $('#ydfOutstandingForm').on('submit', function(event){
            if(!this.checkValidity()){ event.preventDefault(); event.stopPropagation(); }
            $(this).addClass('was-validated');
        });
    });
</script>
</body>
</html>