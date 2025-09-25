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
if (isset($_POST['SubmitFreightDelay'])) {
    $id            = $_POST['delay_id'] ?? '';
    $invoiceno     = mysqli_real_escape_string($conn, $_POST['frinvoice']);
    $shipmentDate  = mysqli_real_escape_string($conn, $_POST['shipdate']);
    $delayCharges  = mysqli_real_escape_string($conn, $_POST['delaych']);
    $reason        = mysqli_real_escape_string($conn, $_POST['dreson']);
    $remark        = mysqli_real_escape_string($conn, $_POST['dremark']);

    if (!empty($id)) {
        // Update
        $sql = "UPDATE freightdelaytbl 
                SET invoice_no='$invoiceno', shipment_date='$shipmentDate', 
                    delay_charges='$delayCharges', reason='$reason', remark='$remark'
                WHERE id='$id'";
    } else {
        // Insert
        $sql = "INSERT INTO freightdelaytbl (invoice_no, shipment_date, delay_charges, reason, remark) 
                VALUES ('$invoiceno', '$shipmentDate', '$delayCharges', '$reason', '$remark')";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: FreightDelayStoring.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// --- Handle Delete ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM freightdelaytbl WHERE id=$id");
    header("Location: FreightDelayStoring.php");
    exit();
}

// --- Fetch Records ---
$delaydetails = [];
$result = $conn->query("SELECT id, invoice_no, shipment_date, delay_charges, reason, remark 
                        FROM freightdelaytbl
                        ORDER BY id ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $delaydetails[] = $row;
    }
}
?>
