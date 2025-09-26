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

// Fetch distinct years once
$years = [];
$yearResult = $conn->query("SELECT DISTINCT YEAR(date) as yr FROM supplierledger ORDER BY yr DESC");
if ($yearResult) {
    while ($row = $yearResult->fetch_assoc()) {
        $years[] = intval($row['yr']);
    }
}

// Determine selected year
$selected_year = isset($_GET['year']) && in_array(intval($_GET['year']), $years) 
                 ? intval($_GET['year']) 
                 : (isset($years[0]) ? intval($years[0]) : date("Y"));

// Use $selected_year for all queries
$stmt = $conn->prepare("SELECT * FROM supplierledger WHERE YEAR(date) = ? ORDER BY date ASC, supplier_name ASC");
$stmt->bind_param("i", $selected_year);
$stmt->execute();
$result = $stmt->get_result();

// Group records by supplier
$allRecords = [];
while ($row = $result->fetch_assoc()) {
    $allRecords[] = $row;
}
$recordsBySupplier = [];
foreach ($allRecords as $record) {
    $recordsBySupplier[$record['supplier_name']][] = $record;
}

// Handle DELETE operation first
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM supplierledger WHERE id = ?");
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

// Get distinct supplier names
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
$supplierDDcollection = $conn->query(" SELECT name 
                                        FROM suppliers
                                        ORDER BY name ASC");

if ($supplierDDcollection){
    while ($row = $supplierDDcollection-> fetch_assoc()){
        $supplierDD[] = $row['name'];
    }
}else{
    die("Error fetching suppliers: " . $conn->error);
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

// SLS php
// Get current year and all distinct years from supplierledger
$years = [];
$result = $conn->query("SELECT DISTINCT YEAR(date) AS year FROM supplierledger ORDER BY year DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['year'];
    }
}
// Get selected year (default current year if none selected)
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$suppliersummary = []; // initialize
$sql = "
    SELECT supplier_name,
           SUM(`credit(LKR)`) AS total_credit_lkr,
           SUM(`debit(LKR)`)  AS total_debit_lkr,
           (SUM(`credit(LKR)`) - SUM(`debit(LKR)`)) AS balance_lkr,
           SUM(`credit($)`)   AS total_credit_usd,
           SUM(`debit($)`)    AS total_debit_usd,
           (SUM(`credit($)`) - SUM(`debit($)`)) AS balance_usd
    FROM supplierledger
    WHERE YEAR(date) = $selected_year
    GROUP BY supplier_name
    ORDER BY supplier_name ASC
";
$suppliersummarydata = $conn->query($sql);

if ($suppliersummarydata){
    $id = 1;
    while ($row = $suppliersummarydata->fetch_assoc()){
        $row['id'] = $id;   
        $suppliersummary[] = $row;
        $id++;
    }
}else{
    die("Error fetching suppliers: " . $conn->error);
}

$f_blanace_LKR = 0;
$f_balance_dollar = 0;




?>