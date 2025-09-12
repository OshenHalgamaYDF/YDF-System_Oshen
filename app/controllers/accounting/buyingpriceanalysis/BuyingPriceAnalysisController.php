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

// Fetch products for dropdown
$products = [];
$result = $conn->query("SELECT product_code, product_name, scientific_name FROM products WHERE product_name IS NOT NULL ORDER BY product_name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
