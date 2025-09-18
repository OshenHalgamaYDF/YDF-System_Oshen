<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
    $remark          = $_POST['remark'];
    // Check if record already exists (use prepared properly!)
    $checkSql = "SELECT 1 FROM buyingpriceanlaysistable 
                 WHERE date=? AND product_code=? AND buyer_name=? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("sss", $date, $product_code, $buyer_name);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Update existing
        $sql = "UPDATE buyingpriceanlaysistable 
                SET product_name=?, scientific_name=?, size_range=?, specification=?, target_price=?, sold_price=?, remark=? 
                WHERE date=? AND product_code=? AND buyer_name=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssddsss",
            $product_name,
            $scientific_name,
            $size_range,
            $specification,
            $target_price,   // d
            $sold_price,     // d
            $date,
            $product_code,
            $buyer_name,
            $remark
        );
    } else {
        // Insert new
        $sql = "INSERT INTO buyingpriceanlaysistable 
                (product_code, date, product_name, scientific_name, size_range, specification, target_price, buyer_name, sold_price, remark) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssdsss",
            $product_code,
            $date,
            $product_name,
            $scientific_name,
            $size_range,
            $specification,
            $target_price,   // d
            $buyer_name,
            $sold_price,
            $remark
        );
    }
    $checkStmt->close();

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
$result = $conn->query("
SELECT p.*, tbp.*
FROM target_buying_price tbp
JOIN products p ON tbp.product_id = p.id
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    echo "No products found or error: " . $conn->error;
}

$dateselected = [];
$dateResult = $conn->query("
SELECT DISTINCT date 
FROM buyingpriceanlaysistable 
ORDER BY date DESC");

while ($row = $dateResult->fetch_assoc()) {
    $dateselected[] = $row['date'];
}

$count =0;
$avgprices = 0;

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    
    $sql = "DELETE FROM buyingpriceanlaysistable WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    
    $stmt->close();
} 

?>