<?php
// --- DB Connection ---
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// --- Handle Add / Update ---
if (isset($_POST['SubmitPowerFreightInvoice'])) {
    $id            = $_POST['pfinvoice_id'] ?? '';
    $shipmentDate  = mysqli_real_escape_string($conn, $_POST['pfshipdate']);
    $invoiceno     = mysqli_real_escape_string($conn, $_POST['pfrinvoice']);
    $pfgrossweight  = mysqli_real_escape_string($conn, $_POST['pfgross']);
    $ourgrossweight  = mysqli_real_escape_string($conn, $_POST['ourgross']);
    $remark        = mysqli_real_escape_string($conn, $_POST['dremark']);

    if (!empty($id)) {
        // Update
        $sql = "UPDATE powerfreightinvoicerecheck 
                SET ship_date ='$shipmentDate', invoice_no='$invoiceno', 
                    pfgrossweight='$pfgrossweight', ourgrossweight='$ourgrossweight', remark='$remark'
                WHERE id='$id'";
    } else {
        // Insert
        $sql = "INSERT INTO powerfreightinvoicerecheck (ship_date , invoice_no, pfgrossweight, ourgrossweight, remark) 
                VALUES ('$shipmentDate', '$invoiceno', '$pfgrossweight', '$ourgrossweight', '$remark')";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: PowerFreightInvoiceRecheck.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// --- Fetch Records ---
$pfinvoicedetails = [];
$result = $conn->query("SELECT id, ship_date, invoice_no, pfgrossweight, ourgrossweight, remark 
                        FROM powerfreightinvoicerecheck
                        ORDER BY id ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pfinvoicedetails[] = $row;
    }
}

// --- Handle Delete ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM powerfreightinvoicerecheck WHERE id=$id");
    header("Location: PowerFreightInvoiceRecheck.php");
    exit();
}
?>