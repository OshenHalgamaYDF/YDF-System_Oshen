<?php
// --- DB Connection ---
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";  // change if needed

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// --- Handle form submission ---
if (isset($_POST['SubmitSupplierLedger'])) {
    $name          = mysqli_real_escape_string($conn, $_POST['dename']);
    $nickname      = mysqli_real_escape_string($conn, $_POST['deniname']);
    $bank_branch   = mysqli_real_escape_string($conn, $_POST['debankbranch']);
    $acc_no        = mysqli_real_escape_string($conn, $_POST['deaccno']);
    $swift_code    = mysqli_real_escape_string($conn, $_POST['descode']);
    $remark        = mysqli_real_escape_string($conn, $_POST['deremark']);

    // Insert query
    $sql = "INSERT INTO supplierbankdetails (name, nickname, bankname_branch, acc_no, swift_code, remark) 
            VALUES ('$name', '$nickname', '$bank_branch', '$acc_no', '$swift_code', '$remark')";

    if (mysqli_query($conn, $sql)) {
        header("Location: SupplierBankDetails.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

$supplierdetails = [];
$supplierdetailsarray = $conn->query("SELECT id, name, nickname, bankname_branch, acc_no, swift_code, remark 
                                      FROM supplierbankdetails
                                      ORDER BY id ASC");

if ($supplierdetailsarray) {
    while ($row = $supplierdetailsarray->fetch_assoc()) {
        $supplierdetails[] = $row;   // ✅ store full row
    }
} else {
    die("Error fetching supplier details: " . $conn->error);
}

if (isset($_POST['saveSupplier'])) {
    $id           = $_POST['supplier_id'];
    $name         = mysqli_real_escape_string($conn, $_POST['dename']);
    $nickname     = mysqli_real_escape_string($conn, $_POST['deniname']);
    $bank_branch  = mysqli_real_escape_string($conn, $_POST['debankbranch']);
    $acc_no       = mysqli_real_escape_string($conn, $_POST['deaccno']);
    $swift_code   = mysqli_real_escape_string($conn, $_POST['descode']);
    $remark       = mysqli_real_escape_string($conn, $_POST['deremark']);

    if (!empty($id)) {
        // Update existing
        $sql = "UPDATE supplierbankdetails 
                SET name='$name', nickname='$nickname', bankname_branch='$bank_branch', 
                    acc_no='$acc_no', swift_code='$swift_code', remark='$remark'
                WHERE id='$id'";
    } else {
        // Insert new
        $sql = "INSERT INTO supplierbankdetails (name, nickname, bankname_branch, acc_no, swift_code, remark) 
                VALUES ('$name', '$nickname', '$bank_branch', '$acc_no', '$swift_code', '$remark')";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: SupplierBankDetails.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM supplierbankdetails WHERE id=$id");
    header("Location: SupplierBankDetails.php");
    exit();
}

?>
