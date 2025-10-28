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
        <h1 class="text-center text-primary mb-4"><b>YDF Outstanding Statement</b></h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#YDFOutstandingModal" id="addNewBtn">
                <i class="fas fa-plus"></i> Enter Outstanding Values
        </button>

        <!-- Modal for YDF Outstanding Statement Input -->
        <div class="modal fade" id="YDFOutstandingModal" tabindex="-1" aria-labelledby="YDFOutstandingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="YDFOutstandingModalLabel">YDF Outstanding Statement Input</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="ydfOutstandingForm" method="post" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="ydfDate" class="form-label">Date</label>
                                    <input type="text" class="form-control" id="ydfDate" name="ydfDate" required>
                                    <div class="invalid-feedback">Please enter the Date.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ydfActivity" class="form-label">Activity</label>
                                    <input type="text" class="form-control" id="ydfActivity" name="ydfActivity" required>
                                    <div class="invalid-feedback">Please enter the Activity.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ydfReference" class="form-label">Reference</label>
                                    <input type="text" class="form-control" id="ydfReference" name="ydfReference" required>
                                    <div class="invalid-feedback">Please enter the Reference.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ydfCustomerName" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="ydfCustomerName" name="ydfCustomerName" required>
                                    <div class="invalid-feedback">Please enter the Customer Name.</div>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" id="ydftype" name="ydftype" required>
                                        <option value="" disabled selected>Select type</option>
                                        <option value="ydfinvoice">Invoices</option>
                                        <option value="ydfpayment">Payments</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="ydfValue" class="form-label">Value</label>
                                    <input type="text" class="form-control" id="ydfValue" name="ydfValue" required>
                                    <div class="invalid-feedback">Please enter the Value.</div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" form="ydfOutstandingForm" name="ydfoutstandingsubmit" class="btn btn-primary">Save Entry</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>