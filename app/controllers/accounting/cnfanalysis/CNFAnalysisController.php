<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$exchangeRateUsdtoGbp = 0;
$rateResult = $conn->query("SELECT UsdToGbp FROM exchangeratefgs ORDER BY id DESC LIMIT 1");
if ($rateResult && $rateRow = $rateResult->fetch_assoc()) {
    $exchangeRateUsdtoGbp = $rateRow['UsdToGbp'];
}
?>