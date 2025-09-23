<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Input of ledger record
if (isset($_POST['SubmitSupplierLedger']) || isset($_POST['UpdateSupplierLedger'])) {
    // Validate and sanitize inputs
    $id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    $datele = isset($_POST['date']) ? mysqli_real_escape_string($conn, trim($_POST['date'])) : '';
    $supplierle = isset($_POST['supplier_name']) ? mysqli_real_escape_string($conn, trim($_POST['supplier_name'])) : '';
    $refle = isset($_POST['reference_number']) ? mysqli_real_escape_string($conn, trim($_POST['reference_number'])) : '';
    $describle = isset($_POST['description']) ? mysqli_real_escape_string($conn, trim($_POST['description'])) : '';
    $creditle = isset($_POST['credit']) ? floatval($_POST['credit']) : 0;
    $debitle = isset($_POST['debit']) ? floatval($_POST['debit']) : 0;

    // Validate required fields
    if (empty($datele) || empty($supplierle) || empty($refle) || empty($describle)) {
        die("Error: All fields are required.");
    }

    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $datele)) {
        die("Error: Invalid date format.");
    }

    if ($id > 0 && isset($_POST['UpdateSupplierLedger'])) {
        // UPDATE query
        $stmt = $conn->prepare("UPDATE supplierledger SET date=?, supplier_name=?, ref_no=?, description=?, credit=?, debit=? WHERE ID=?");
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("ssssddi", $datele, $supplierle, $refle, $describle, $creditle, $debitle, $id);
        
        if ($stmt->execute()) {
            header("Location: SupplierLedgerInput.php");
            exit();
        } else {
            echo "Error updating record: " . $stmt->error;
        }
    } else {
        // INSERT query
        $stmt = $conn->prepare("INSERT INTO supplierledger (date, supplier_name, ref_no, description, credit, debit) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("ssssdd", $datele, $supplierle, $refle, $describle, $creditle, $debitle);
        
        if ($stmt->execute()) {
            header("Location: SupplierLedgerInput.php");
            exit();
        } else {
            echo "Error adding record: " . $stmt->error;
        }
    }
    
    $stmt->close();
}

// Handle record deletion (if needed)
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

// Output the table of supplier ledger - get all records
$result = $conn->query("SELECT * FROM supplierledger ORDER BY date ASC, supplier_name ASC");
if (!$result) {
    die("Error fetching records: " . $conn->error);
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

// Store all records in an array for tab filtering
$allRecords = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allRecords[] = $row;
    }
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
?>