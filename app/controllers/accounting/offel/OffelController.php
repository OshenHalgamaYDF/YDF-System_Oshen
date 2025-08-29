<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['SubmitOffel'])) {
    $id = $_POST['ID'] ?? '';
    $dateOffel = $_POST['dateOffel'] ?? '';
    $ProductOffel = $_POST['ProductOffel'] ?? '';
    $TypeOffel = $_POST['TypeOffel'] ?? '';
    $BuyerOffel = $_POST['BuyerOffel'] ?? '';
    $KGOffel = $_POST['KGOffel'] ?? '';
    $PriceOffel = $_POST['PriceOffel'] ?? '';
    $RemarkOffel = $_POST['RemarkOffel'] ?? '';
    
    
    if ($id) {
        // UPDATE query
        $stmt = $conn->prepare("UPDATE offelsystem SET Date=?, Product=?, Type=?, Buyer=?, Kg=?, Price=?, Remark=? WHERE ID=?");
        $stmt->bind_param("sssssssi", $dateOffel, $ProductOffel, $TypeOffel, $BuyerOffel, $KGOffel, $PriceOffel, $RemarkOffel, $id);
    } else {
        // INSERT query
        $stmt = $conn->prepare("INSERT INTO offelsystem (Date, Product, Type, Buyer, Kg, Price, Remark) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $dateOffel, $ProductOffel, $TypeOffel, $BuyerOffel, $KGOffel, $PriceOffel, $RemarkOffel);
    }
    
    if ($stmt->execute()) {
        // Redirect to avoid resubmission on refresh
        header("Location: OffelInput.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
?>