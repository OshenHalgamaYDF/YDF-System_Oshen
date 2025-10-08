<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$dateFilter = $_POST['Date'] ?? null;
$invoices = [];

if ($dateFilter) {
    $sql = "SELECT * FROM newdistribution_sheet WHERE production_date = '$dateFilter' ORDER BY id ASC";
} else {
    $sql = "SELECT * FROM newdistribution_sheet ORDER BY id ASC";
}

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
}
?>
