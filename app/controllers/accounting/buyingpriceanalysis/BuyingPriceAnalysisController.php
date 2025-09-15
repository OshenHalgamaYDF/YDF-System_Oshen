<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert when form submitted
if (isset($_POST['SubmitOffel'])) {
    $product_code    = $_POST['product_code'];
    $date            = $_POST['date'];
    $product_name    = $_POST['product_name'];
    $scientific_name = $_POST['scientific_name'];
    $size_range      = $_POST['size_range'];
    $specification   = $_POST['specification'];
    $target_price    = $_POST['target_price'];
    $buyer_name      = $_POST['buyer_name'];
    $sold_price      = $_POST['sold_price'];

    $sql = "INSERT INTO buyingpriceanlaysistable
            (product_code, date, product_name, scientific_name, size_range, specification, target_price, buyer_name, sold_price) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssdsd", 
        $product_code, 
        $date, 
        $product_name, 
        $scientific_name, 
        $size_range, 
        $specification, 
        $target_price, 
        $buyer_name, 
        $sold_price
    );

    if ($stmt->execute()) {
        header("Location: BuyingPriceAnalysis.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch products for dropdown - UPDATED with new column names
$products = [];
$result = $conn->query("SELECT Product_Code, Product_Name, Scientific_Name, Size_Range, Specification, Target_buying_price FROM bpa_template WHERE Product_Name IS NOT NULL ORDER BY Product_Name");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    echo "No products found or error: " . $conn->error;
}

// --- Get selected date (default = latest available date)
$latestDateRes = $conn->query("SELECT MAX(date) AS latest_date FROM buyingpriceanlaysistable");
$latestDateRow = $latestDateRes->fetch_assoc();
$latestDate    = $latestDateRow['latest_date'];

$selectedDate = isset($_GET['date']) ? $_GET['date'] : $latestDate;

// --- Get distinct buyers for this date
$buyers = [];
$resBuyers = $conn->query("SELECT DISTINCT buyer_name FROM buyingpriceanlaysistable WHERE date = '$selectedDate'");
while ($row = $resBuyers->fetch_assoc()) {
    $buyers[] = $row['buyer_name'];
}
?>
