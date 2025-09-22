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
    
    // Check if record already exists
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
            "ssssddssss",
            $product_name,
            $scientific_name,
            $size_range,
            $specification,
            $target_price,
            $sold_price,
            $remark,
            $date,
            $product_code,
            $buyer_name
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
            $target_price,
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

// Handle update from edit modal
if (isset($_POST['UpdateRecord'])) {
    $record_id       = $_POST['record_id'];
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
    
    $sql = "UPDATE buyingpriceanlaysistable 
            SET product_code=?, date=?, product_name=?, scientific_name=?, size_range=?, specification=?, 
            target_price=?, buyer_name=?, sold_price=?, remark=?
            WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssdsssi",
        $product_code,
        $date,
        $product_name,
        $scientific_name,
        $size_range,
        $specification,
        $target_price,
        $buyer_name,
        $sold_price,
        $remark,
        $record_id
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

$count = 0;
$avgprices = 0;

/**
 * Distinct Product IDs used for grouping tabs
 */
$productIds = [];
$idResult = $conn->query("SELECT DISTINCT product_code FROM buyingpriceanlaysistable");
while ($row = $idResult->fetch_assoc()) {
    $productIds[] = $row['product_code'];
}

// Get current date for edit modal
$currentDate = isset($dateselected[0]) ? $dateselected[0] : date('Y-m-d');

// Handle delete operation before any HTML output
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $sql = "DELETE FROM buyingpriceanlaysistable WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting record: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}

// Get all available months from the database
$monthResult = $conn->query("
    SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month 
    FROM buyingpriceanlaysistable 
    ORDER BY month DESC
");
$availableMonths = [];
while ($row = $monthResult->fetch_assoc()) {
    $availableMonths[] = $row['month'];
}

// Get selected month from request or use current month
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get dates for the selected month
$dateResult = $conn->query("
    SELECT DISTINCT date 
    FROM buyingpriceanlaysistable 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$selectedMonth'
    ORDER BY date DESC
");
$dateselected = [];
while ($row = $dateResult->fetch_assoc()) {
    $dateselected[] = $row['date'];
}
?>