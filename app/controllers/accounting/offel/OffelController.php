<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

$result = $conn->query("SELECT Date, Amount FROM offelrecieved ORDER BY Date DESC");

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

if (isset($_POST['RSubmitOffel'])) {
    // Get values safely
    $date = $_POST['RdateOffel'] ?? '';
    $amount = $_POST['RamountOffel'] ?? '';

    // Basic validation
    if (!empty($date) && !empty($amount)) {
        // Prepare insert
        $stmt = $conn->prepare("INSERT INTO offelrecieved (`Date`, `Amount`) VALUES (?, ?)");
        $stmt->bind_param("sd", $date, $amount);  // s = string (date), d = double (amount)

        if ($stmt->execute()) {
            header("Location: OffelInput.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } 
}



// Handle Delete
if (isset($_POST['delete_input']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM offelsystem WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // Redirect to avoid resubmission on refresh
    header("Location: OffelInput.php");
    exit();
}

// Get latest date from DB
$latestDateResult = $conn->query("SELECT MAX(Date) as LatestDate FROM offelsystem");
$latestDateRow = $latestDateResult->fetch_assoc();
$latestDate = $latestDateRow['LatestDate'] ?? date('Y-m-d');

// Define all possible products for tabs
$allProducts = ['Tuna', 'Sword', 'Red Snapper', 'Kingfish', 'Other'];

// Get all unique products from DB (not strictly needed since using allProducts)
$productResult = $conn->query("SELECT DISTINCT Product FROM offelsystem");
$productsInDb = [];
while ($row = $productResult->fetch_assoc()) {
    $productsInDb[] = $row['Product'];
}
$products = $allProducts;

?>