<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Debug settings - enable error display and throw exceptions for mysqli
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$debug_mode = true; // set to false in production

// Upload directory (global) - ensure available for delete/update flows
$upload_dir = __DIR__ . '/uploads/documents/';
$web_upload_dir = 'uploads/documents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle UPDATE (edit) submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $conn->begin_transaction();
    try {
        $emp_id = $_POST['edit_emp_id'] ?? null;
        if (empty($emp_id)) {
            throw new Exception('No employee id provided for update.');
        }

        // Update main employee table
        $stmt = $conn->prepare("UPDATE tbl_hr_employees_data SET
            emp_id = ?, emp_name = ?, emp_NIC = ?, emp_residential_address = ?, emp_civil_status = ?,
            emp_gender = ?, emp_dob = ?, emp_contact_details = ?, emp_epf_no = ?, emp_department = ?,
            emp_designation = ?, emp_joined_date = ?, emp_status = ?
            WHERE emp_id = ?");
        if (!$stmt) throw new Exception('Prepare failed (update employee): ' . $conn->error);
        $stmt->bind_param(
            "ssssssssssssss",
            $_POST['emp_id'],
            $_POST['emp_name'],
            $_POST['emp_nic'],
            $_POST['emp_address'],
            $_POST['emp_civil_status'],
            $_POST['emp_gender'],
            $_POST['emp_dob'],
            $_POST['emp_contact_number'],
            $_POST['emp_epf_number'],
            $_POST['emp_department'],
            $_POST['emp_designation'],
            $_POST['emp_start_date'],
            $_POST['emp_status'],
            $emp_id
        );
        if (!$stmt->execute()) throw new Exception('Execute failed (update employee): ' . $stmt->error);
        $stmt->close();

        // Upsert emergency data using REPLACE (simple approach)
        $stmt = $conn->prepare("REPLACE INTO tbl_hr_employees_emergency_data
            (emp_id, emergency_name, emergency_relationship, emergency_address, emergency_contact)
            VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception('Prepare failed (emergency upsert): ' . $conn->error);
        $stmt->bind_param(
            "sssss",
            $_POST['emp_id'],
            $_POST['emp_emergency_contact_name'],
            $_POST['emp_emergency_relationship'],
            $_POST['emp_emergency_contact_address'],
            $_POST['emp_emergency_contact_number']
        );
        $stmt->execute();
        $stmt->close();

        // Upsert bank data
        $stmt = $conn->prepare("REPLACE INTO tbl_hr_employees_bank_data
            (emp_id, emp_acc_name, emp_acc_no, emp_bank_name, emp_branch, emp_details_change)
            VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception('Prepare failed (bank upsert): ' . $conn->error);
        $emp_acc_change_date = $_POST['emp_acc_change_date'] ?: NULL;
        $stmt->bind_param(
            "ssssss",
            $_POST['emp_id'],
            $_POST['emp_acc_name'],
            $_POST['emp_acc_number'],
            $_POST['emp_bank_name'],
            $_POST['emp_bank_branch'],
            $emp_acc_change_date
        );
        $stmt->execute();
        $stmt->close();

        // Handle document uploads for the updated employee (similar to create flow)
        $doc_types_result = $conn->query("SELECT id, document_types FROM tbl_hr_employees_data_document_type");
        $doc_types = [];
        while ($row = $doc_types_result->fetch_assoc()) {
            $doc_types[$row['id']] = $row['document_types'];
        }

        $document_fields = [
            'CV' => ['CV', false],
            'payment_agreement' => ['Payment Agreement', false],
            'job_description' => ['Job Description', false],
            'contract_employment' => ['Contract of Employment', false],
            'confirmation_employment' => ['Confirmation of Employment', false],
            'id_copy' => ['ID copy', false],
            'employee_certificates' => ['Employee Certificates', true],
            'police_report' => ['Police Report', false],
            'certificate_residence' => ['Certificate of residence', false],
            'birth_certificate' => ['Birth Certificate', false],
            'epf' => ['EPF Certification of Membership', false],
            'devices_responsibility' => ['Device Responsibility', true],
            'paysheet' => ['Pay Sheets', true]
        ];

        $stmt_document = $conn->prepare("INSERT INTO tbl_hr_employees_data_documents
            (employee_data_id, document_type_id, original_name, stored_name, file_path, timestamp)
            VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$stmt_document) throw new Exception('Prepare failed (document insert): ' . $conn->error);

        foreach ($document_fields as $field_name => [$doc_type_name, $is_multiple]) {
            if (!isset($_FILES[$field_name])) continue;

            // Get document type ID
            $doc_type_id = null;
            foreach ($doc_types as $id => $name) {
                if (strcasecmp(trim($name), trim($doc_type_name)) === 0) {
                    $doc_type_id = $id;
                    break;
                }
            }
            if ($doc_type_id === null) continue;

            $files = $is_multiple ? $_FILES[$field_name] : [
                'name' => [$_FILES[$field_name]['name']],
                'tmp_name' => [$_FILES[$field_name]['tmp_name']],
                'error' => [$_FILES[$field_name]['error']],
                'size' => [$_FILES[$field_name]['size']]
            ];

            foreach ($files['name'] as $index => $original_name) {
                if ($files['error'][$index] !== UPLOAD_ERR_OK) continue;

                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                $stored_name = uniqid() . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $stored_name;
                $db_file_path = $web_upload_dir . $stored_name;

                if (move_uploaded_file($files['tmp_name'][$index], $target_path)) {
                    $stmt_document->bind_param(
                        "iisss",
                        $_POST['emp_id'],
                        $doc_type_id,
                        $original_name,
                        $stored_name,
                        $db_file_path
                    );
                    $stmt_document->execute();
                }
            }
        }
        $stmt_document->close();

        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error updating employee: " . $e->getMessage();
        $success = false;
    }
}

// Handle DELETE (remove employee + files)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee']) && !empty($_POST['delete_emp_id'])) {

    $delete_emp_id = (int)$_POST['delete_emp_id']; // cast for safety
    $conn->begin_transaction();

    try {
        // 1. Delete files associated with this employee
        $stmt = $conn->prepare("
            SELECT stored_name 
            FROM tbl_hr_employees_data_documents 
            WHERE employee_data_id = ?
        ");
        $stmt->bind_param('i', $delete_emp_id);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $file_path = $upload_dir . $row['stored_name'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $stmt->close();

        // 2. Delete document records
        $stmt = $conn->prepare("
            DELETE FROM tbl_hr_employees_data_documents 
            WHERE employee_data_id = ?
        ");
        $stmt->bind_param('i', $delete_emp_id);
        $stmt->execute();
        $stmt->close();

        // 3. Delete emergency data
        $stmt = $conn->prepare("
            DELETE FROM tbl_hr_employees_emergency_data 
            WHERE emp_id = ?
        ");
        $stmt->bind_param('i', $delete_emp_id);
        $stmt->execute();
        $stmt->close();

        // 4. Delete bank data (IMPORTANT for FK)
        $stmt = $conn->prepare("
            DELETE FROM tbl_hr_employees_bank_data 
            WHERE emp_id = ?
        ");
        $stmt->bind_param('i', $delete_emp_id);
        $stmt->execute();
        $stmt->close();

        // 5. Delete main employee record ✅ FIXED COLUMN NAME
        $stmt = $conn->prepare("
            DELETE FROM tbl_hr_employees_data 
            WHERE id = ?
        ");
        $stmt->bind_param('i', $delete_emp_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error deleting employee: " . $e->getMessage();
        $success = false;
    }
}


// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    // Debug: log POST/FILES keys to error log if debug
    if (!empty($debug_mode)) {
        if (empty($_POST)) {
            error_log("Employee Data: POST received but \\$_POST is empty. CONTENT_LENGTH=" . ($_SERVER['CONTENT_LENGTH'] ?? 'N/A'));
        } else {
            error_log("Employee Data: POST keys: " . implode(', ', array_keys($_POST)));
        }
        error_log("Employee Data: FILES keys: " . implode(', ', array_keys($_FILES)));
    }

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. First, insert into main employee table (assuming it exists)
        // You may need to adjust this based on your actual main employee table structure
        $stmt_employee = $conn->prepare("
            INSERT INTO tbl_hr_employees_data (emp_id, emp_name, emp_NIC, emp_residential_address, emp_civil_status, 
                                         emp_gender, emp_dob, emp_contact_details, emp_epf_no, 
                                         emp_department, emp_designation, emp_joined_date, emp_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt_employee) {
            throw new Exception("Prepare failed (employee): " . $conn->error);
        }
        
        $stmt_employee->bind_param(
            "sssssssssssss",
            $_POST['emp_id'],
            $_POST['emp_name'],
            $_POST['emp_nic'],
            $_POST['emp_address'],
            $_POST['emp_civil_status'],
            $_POST['emp_gender'],
            $_POST['emp_dob'],
            $_POST['emp_contact_number'],
            $_POST['emp_epf_number'],
            $_POST['emp_department'],
            $_POST['emp_designation'],
            $_POST['emp_start_date'],
            $_POST['emp_status']
        );
        
        if (!$stmt_employee->execute()) {
            throw new Exception("Execute failed (employee): " . $stmt_employee->error);
        }
        $last_employee_id = $stmt_employee->insert_id;
        // If your table does not use an AUTO_INCREMENT primary key, fall back to posted emp_id
        if (empty($last_employee_id)) {
            $last_employee_id = $_POST['emp_id'] ?? null;
        }
        if (empty($last_employee_id)) {
            throw new Exception("Unable to determine employee id (insert_id empty and emp_id not provided).");
        }
        $stmt_employee->close();
        
        // 2. Insert emergency contact data
        $stmt_emergency = $conn->prepare("
            INSERT INTO tbl_hr_employees_emergency_data 
            (emp_id, emergency_name, emergency_relationship, emergency_address, emergency_contact)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt_emergency->bind_param(
            "sssss",
            $last_employee_id,
            $_POST['emp_emergency_contact_name'],
            $_POST['emp_emergency_relationship'],
            $_POST['emp_emergency_contact_address'],
            $_POST['emp_emergency_contact_number']
        );
        
        $stmt_emergency->execute();
        $stmt_emergency->close();
        
        // 3. Insert bank data
        $stmt_bank = $conn->prepare("
            INSERT INTO tbl_hr_employees_bank_data 
            (emp_id, emp_acc_name, emp_acc_no, emp_bank_name, emp_branch, emp_details_change)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $emp_acc_change_date = $_POST['emp_acc_change_date'] ?: NULL;
        $stmt_bank->bind_param(
            "ssssss",
            $last_employee_id,
            $_POST['emp_acc_name'],
            $_POST['emp_acc_number'],
            $_POST['emp_bank_name'],
            $_POST['emp_bank_branch'],
            $emp_acc_change_date
        );
        
        $stmt_bank->execute();
        $stmt_bank->close();
        
        // 4. Handle document uploads
        // First, get document type IDs from tbl_hr_employees_data_document_type
        $doc_types_result = $conn->query("SELECT id, document_types FROM tbl_hr_employees_data_document_type");
        $doc_types = [];
        while ($row = $doc_types_result->fetch_assoc()) {
            $doc_types[$row['id']] = $row['document_types'];
        }
        
        // Define document field mappings (form field name => database document type name)
        $document_fields = [
            'CV' => ['CV', false],
            'payment_agreement' => ['Payment Agreement', false],
            'job_description' => ['Job Description', false],
            'contract_employment' => ['Contract of Employment', false],
            'confirmation_employment' => ['Confirmation of Employment', false],
            'id_copy' => ['ID copy', false],
            'employee_certificates' => ['Employee Certificates', true], // ✅ MULTIPLE
            'police_report' => ['Police Report', false],
            'certificate_residence' => ['Certificate of residence', false],
            'birth_certificate' => ['Birth Certificate', false],
            'epf' => ['EPF Certification of Membership', false],
            'devices_responsibility' => ['Device Responsibility', true], // ✅ MULTIPLE
            'paysheet' => ['Pay Sheets', true] // ✅ MULTIPLE
        ];

        
        // Upload directory (use absolute path to avoid confusion about current working directory)
        $upload_dir = __DIR__ . '/uploads/documents/';
        $web_upload_dir = 'uploads/documents/';
        // Web-accessible relative path to store in DB
        $web_upload_dir = 'uploads/documents/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Prepare statement for document insertion
        $stmt_document = $conn->prepare("
            INSERT INTO tbl_hr_employees_data_documents 
            (employee_data_id, document_type_id, original_name, stored_name, file_path, timestamp)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt_document) {
            throw new Exception("Prepare failed (document): " . $conn->error);
        }
        
        // Process each document field
        foreach ($document_fields as $field_name => [$doc_type_name, $is_multiple]) {

            if (!isset($_FILES[$field_name])) {
                continue;
            }

            // Get document type ID
            $doc_type_id = null;
            foreach ($doc_types as $id => $name) {
                if (strcasecmp(trim($name), trim($doc_type_name)) === 0) {
                    $doc_type_id = $id;
                    break;
                }
            }
            if ($doc_type_id === null) {
                continue;
            }

            // Normalize file arrays
            $files = $is_multiple ? $_FILES[$field_name] : [
                'name' => [$_FILES[$field_name]['name']],
                'tmp_name' => [$_FILES[$field_name]['tmp_name']],
                'error' => [$_FILES[$field_name]['error']],
                'size' => [$_FILES[$field_name]['size']]
            ];

            foreach ($files['name'] as $index => $original_name) {

                if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                $stored_name = uniqid() . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $stored_name;
                $db_file_path = $web_upload_dir . $stored_name;

                if (move_uploaded_file($files['tmp_name'][$index], $target_path)) {

                    $stmt_document->bind_param(
                        "iisss",
                        $last_employee_id,
                        $doc_type_id,
                        $original_name,
                        $stored_name,
                        $db_file_path
                    );

                    $stmt_document->execute();
                }
            }
        }
        $stmt_document->close();
        
        // Commit transaction
        $conn->commit();
        
        $message = "Employee added successfully!";
        $success = true;
        
        // Refresh the page or redirect
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $success = false;
    }
}

// Fetch employees for display
$employees = [];
try {
    // Adjust this query based on your actual table structure
    // Fetch employee main data and left-join emergency and bank data to ease JS population for edit
    $result = $conn->query("SELECT e.emp_id, e.emp_name, e.emp_status, e.emp_NIC as emp_nic, e.emp_residential_address as emp_address,
        e.emp_civil_status, e.emp_gender, e.emp_dob, e.emp_contact_details as emp_contact_number, e.emp_epf_no as emp_epf_number,
        e.emp_department, e.emp_designation, e.emp_joined_date as emp_start_date,
        em.emergency_name, em.emergency_relationship, em.emergency_address, em.emergency_contact,
        b.emp_acc_name, b.emp_acc_no, b.emp_bank_name, b.emp_branch, b.emp_details_change
        FROM tbl_hr_employees_data e
        LEFT JOIN tbl_hr_employees_emergency_data em ON e.emp_id = em.emp_id
        LEFT JOIN tbl_hr_employees_bank_data b ON e.emp_id = b.emp_id
        ORDER BY e.emp_id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
        $result->free();
    }
} catch (Exception $e) {
    $message .= " Error fetching employees: " . $e->getMessage();
}
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
        <?php if (!empty($message) && isset($success) && $debug_mode): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> mt-2">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <h1 class="text-center mt-4">Employee Data</h1>
        <div class="mb-2">
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addemployeeModal" id="addemployeeBtn">
                + Add Employee
            </button>
        </div>

        <div class="modal fade" id="addemployeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="employeeModalLabel">Add Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="addEmployeeForm" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="edit_emp_id" id="edit_emp_id" value="">

                    <!-- Employee Basic Info -->
                    <h4 class="mb-3"><b><u>Employee Basic Information</u></b></h4>
                    <div class="row g-3">
                        <div class="col-md-2">
                        <label class="form-label">Employee ID</label>
                        <input type="text" id="emp_id" name="emp_id" class="form-control" required>
                        <div class="invalid-feedback">Please enter Employee ID.</div>
                        </div>

                        <div class="col-md-5">
                        <label class="form-label">Employee Name</label>
                        <input type="text" id="emp_name" name="emp_name" class="form-control" required>
                        <div class="invalid-feedback">Please enter Employee Name.</div>
                        </div>

                        <div class="col-md-5">
                        <label class="form-label">NIC</label>
                        <input type="text" id="emp_nic" name="emp_nic" class="form-control" required>
                        <div class="invalid-feedback">Please enter a valid NIC (10-12 characters).</div>
                        </div>

                        <div class="col-md-9">
                        <label class="form-label">Address</label>
                        <input type="text" name="emp_address" class="form-control" required>
                        <div class="invalid-feedback">Please enter Address.</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">Civil Status</label>
                        <select id="emp_civil_status" name="emp_civil_status" class="form-select" required>
                            <option value="">Select Civil Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                        <div class="invalid-feedback">Please enter civil status.</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">Gender</label>
                        <select id="emp_gender" name="emp_gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="invalid-feedback">Please select Gender.</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" id="emp_dob" name="emp_dob" class="form-control" required>
                        <div class="invalid-feedback">Please select a valid Date of Birth.</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" id="emp_contact_number" name="emp_contact_number" class="form-control" required>
                        <div class="invalid-feedback">Please enter a valid contact number (7-15 digits).</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">EPF membership number</label>
                        <input type="text" id="emp_epf_number" name="emp_epf_number" class="form-control" required>
                        <div class="invalid-feedback">Please enter a valid EPF membership number.</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <input type="text" id="emp_department" name="emp_department" class="form-control" required>
                        <div class="invalid-feedback">Please enter Department.</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">Designation</label>
                        <input type="text" name="emp_designation" class="form-control" required>
                        <div class="invalid-feedback">Please enter Designation.</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="emp_start_date" class="form-control" required>
                        <div class="invalid-feedback">Please select Start Date.</div>
                        </div>

                        <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="emp_status" name="emp_status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="Active">Active</option>
                            <option value="Resign">Resign</option>
                        </select>
                        <div class="invalid-feedback">Please select Status.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h4 class="mb-3"><b><u>Employee emergency data</u></b></h4>
                    <!-- Emergency Contact Section -->
                    <div class="row g-3">
                        <div class="col-md-4">
                        <label class="form-label">Emergency Contact Name</label>
                        <input type="text" name="emp_emergency_contact_name" class="form-control" required>
                        <div class="invalid-feedback">Please enter emergency contact name.</div>
                        </div>

                        <div class = "col-md-4">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="emp_emergency_relationship" class="form-control" required>
                        <div class="invalid-feedback">Please enter relationship.</div>
                        </div>

                        <div class="col-md-4">
                        <label class="form-label">Emergency Contact Number</label>
                        <input type="text" id="emp_emergency_contact_number" name="emp_emergency_contact_number" class="form-control" required>
                        <div class="invalid-feedback">Please enter a valid emergency contact number.</div>
                        </div>

                        <div class="col-md-12">
                        <label class="form-label">Emergency Contact Address</label>
                        <input type="text" name="emp_emergency_contact_address" class="form-control" required>
                        <div class="invalid-feedback">Please enter emergency contact address.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h4 class="mb-3"><b><u>Employee bank details</u></b></h4>
                    <!-- Bank Details Section -->
                    <div class="row g-3">
                        <div class="col-md-6">
                        <label class="form-label">Account holder Name</label>
                        <input type="text" id="emp_acc_name" name="emp_acc_name" class="form-control" required>
                        <div class="invalid-feedback">Please enter account holder name.</div>
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" id="emp_acc_number" name="emp_acc_number" class="form-control" required>
                        <div class="invalid-feedback">Please enter a valid account number.</div>
                        </div>  

                        <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <input type="text" id="emp_bank_branch" name="emp_bank_branch" class="form-control" required>
                        <div class="invalid-feedback">Please enter bank branch.</div>
                        </div>

                        <div class="col-md-4">
                        <label class="form-label">Bank Name</label>
                        <input type="text" id="emp_bank_name" name="emp_bank_name" class="form-control" required>
                        <div class="invalid-feedback">Please enter bank name.</div>
                        </div>

                        <div class="col-md-4">
                        <label class="form-label">Account change date</label>
                        <input type="date" name="emp_acc_change_date" class="form-control">     
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Documents Section -->
                    <h4 class="mb-3"><b><u>Employee Documents</u></b></h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                        <label class="form-label">CV</label>
                        <input type="file" name="CV" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Payment Agreement</label>
                        <input type="file" name="payment_agreement" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Job Description</label>
                        <input type="file" name="job_description" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Contract of Employment</label>
                        <input type="file" name="contract_employment" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Confirmation of Employment</label>
                        <input type="file" name="confirmation_employment" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">ID Copy</label>
                        <input type="file" name="id_copy" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Certificates</label>
                        <input type="file" name="employee_certificates[]" class="form-control" multiple>
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Police Report</label>
                        <input type="file" name="police_report" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Certificate of Residence</label>
                        <input type="file" name="certificate_residence" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Birth Certificate</label>
                        <input type="file" name="birth_certificate" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">EPF Document</label>
                        <input type="file" name="epf" class="form-control">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Devices Responsibility Documents</label>
                        <input type="file" name="devices_responsibility[]" class="form-control" multiple>
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Paysheet</label>
                        <input type="file" name="paysheet[]" class="form-control" multiple>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="mt-4 text-end">
                        <button type="submit" name="upload" id="employeeSubmitBtn" class="btn btn-primary">
                        Add Employee
                        </button>
                    </div>
                </div>
                </form>
                </div>
            </div>
        </div>

        <!-- Employee Data Table -->
        <div class="table-responsive mt-5">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr class="text-center">
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr class="text-center">
                            <td><?php echo htmlspecialchars($employee['emp_id']); ?></td>
                            <td><?php echo htmlspecialchars($employee['emp_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['emp_status'] ?? 'Active'); ?></td>
                            <td>
                                <!-- Edit using same Add/Edit modal -->
                                <button type="button" class="btn btn-sm btn-warning edit-btn" data-employee='<?php echo htmlspecialchars(json_encode($employee, JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <i class="fa fa-edit"></i>
                                </button>

                                <!-- Delete form (posts to same page to remove DB records + uploaded files) -->
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this employee and all uploaded files?');">
                                    <input type="hidden" name="delete_employee" value="1">
                                    <input type="hidden" name="delete_emp_id" value="<?php echo htmlspecialchars($employee['emp_id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>

                                <a href="view_employee.php?id=<?php echo urlencode($employee['emp_id']); ?>" class="btn btn-sm btn-info">
                                    <i class="fa fa-info-circle"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<script>
 // Form validation and Edit/Delete handlers
        (function () {
            'use strict';
            var form = document.getElementById('addEmployeeForm');
            var employeeModalLabel = document.getElementById('employeeModalLabel');
            var submitBtn = document.getElementById('employeeSubmitBtn');
            var editEmpId = document.getElementById('edit_emp_id');
            var empIdField = document.getElementById('emp_id');

            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);

            // Reset form when opening Add Employee modal
            var addBtn = document.getElementById('addemployeeBtn');
            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    form.reset();
                    form.classList.remove('was-validated');
                    employeeModalLabel.textContent = 'Add Employee';
                    submitBtn.name = 'upload';
                    submitBtn.textContent = 'Add Employee';
                    editEmpId.value = '';
                    if (empIdField) empIdField.readOnly = false;
                });
            }

            // Edit button handler — populate the same form and switch to update mode
            document.querySelectorAll('.edit-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    var raw = button.getAttribute('data-employee');
                    if (!raw) return;
                    var emp = JSON.parse(raw);

                    // Fill main fields (only those present in the form)
                    document.getElementById('emp_id').value = emp.emp_id || '';
                    document.getElementById('emp_name').value = emp.emp_name || '';
                    document.getElementById('emp_nic').value = emp.emp_nic || '';
                    document.querySelector('[name="emp_address"]').value = emp.emp_address || '';
                    document.getElementById('emp_civil_status').value = emp.emp_civil_status || '';
                    document.getElementById('emp_gender').value = emp.emp_gender || '';
                    document.getElementById('emp_dob').value = emp.emp_dob || '';
                    document.getElementById('emp_contact_number').value = emp.emp_contact_number || '';
                    document.getElementById('emp_epf_number').value = emp.emp_epf_number || '';
                    document.getElementById('emp_department').value = emp.emp_department || '';
                    document.querySelector('[name="emp_designation"]').value = emp.emp_designation || '';
                    document.querySelector('[name="emp_start_date"]').value = emp.emp_start_date || '';
                    document.getElementById('emp_status').value = emp.emp_status || '';

                    // Emergency
                    document.querySelector('[name="emp_emergency_contact_name"]').value = emp.emergency_name || '';
                    document.querySelector('[name="emp_emergency_relationship"]').value = emp.emergency_relationship || '';
                    document.getElementById('emp_emergency_contact_number').value = emp.emergency_contact || '';
                    document.querySelector('[name="emp_emergency_contact_address"]').value = emp.emergency_address || '';

                    // Bank
                    document.getElementById('emp_acc_name').value = emp.emp_acc_name || '';
                    document.getElementById('emp_acc_number').value = emp.emp_acc_no || '';
                    document.getElementById('emp_bank_name').value = emp.emp_bank_name || '';
                    document.getElementById('emp_bank_branch').value = emp.emp_branch || '';
                    document.querySelector('[name="emp_acc_change_date"]').value = emp.emp_details_change || '';

                    // Switch modal to Edit mode
                    employeeModalLabel.textContent = 'Edit Employee';
                    submitBtn.name = 'update';
                    submitBtn.textContent = 'Update Employee';
                    editEmpId.value = emp.emp_id || '';
                    if (empIdField) empIdField.readOnly = true;

                    var modal = new bootstrap.Modal(document.getElementById('addemployeeModal'));
                    modal.show();
                });
            });
        })();
</script>

</body>
</html>