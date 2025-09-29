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

// --- Fetch Data ---
$costingData = [];
$sql = "SELECT c.product_id, p.product_name, c.buyingprice, c.volume, c.expectedyield, p.product_code, c.processingcharge
        FROM ydfcosting c
        JOIN products p ON c.product_id = p.id
        ORDER BY c.id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $costingData[] = $row;
    }
}

// Fetch latest exchange rate
$exchangeRate = 0;
$rateResult = $conn->query("SELECT UsdToLkr FROM exchangerate ORDER BY id DESC LIMIT 1");
if ($rateResult && $rateRow = $rateResult->fetch_assoc()) {
    $exchangeRate = $rateRow['UsdToLkr'];
}
?>