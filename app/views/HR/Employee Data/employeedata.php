<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$employees = [];
$sql = "SELECT id, employee_name FROM tbl_hr_employees ORDER BY id ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Data</title>
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
        /* Main table cells */
        #fgsCostingTable th,
        #fgsCostingTable td {
            vertical-align: middle !important;
            text-align: center;
            font-size: 0.97rem;
            white-space: nowrap;
            min-width: 100px;
        }
        /* Sticky header */
        #fgsCostingTable thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #212529 !important;
            color: #fff !important;
            font-size: 1rem;
            border-bottom: 2px solid #0d6efd !important;
            text-align: center;
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
<body>
    <div class="container mt-4">
        <h1 class="text-center mt-4">Employee Data</h1>
        <div class="mb-2">
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addemployeeModal" id="addemployeeBtn">
                + Add Employee
            </button>
        </div>

        <div class="modal fade" id="addemployeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="employeeModalLabel">Employee Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Employee details will be loaded here via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive mt-5">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Action</th>
                        <th>More</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['id']); ?></td>
                            <td><?php echo htmlspecialchars($employee['employee_name']); ?></td>
                            <td>
                                <a href="edit_employee.php?id=<?php echo urlencode($employee['id']); ?>" class="btn btn-sm btn-warning">
                                    <i class="fa fa-edit"></i> 
                                </a>
                                <a href="delete_employee.php?id=<?php echo urlencode($employee['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this employee?');">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                            <td>
                                <a href="view_employee.php?id=<?php echo urlencode($employee['id']); ?>" class="btn btn-sm btn-info">
                                    <i class="fa fa-info-circle"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>