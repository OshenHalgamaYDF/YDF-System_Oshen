<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * --------------------------
 * Insert / Update (SubmitOffel)
 * --------------------------
 */
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

/**
 * --------------------------
 * Update from Edit Modal
 * --------------------------
 */
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

/**
 * --------------------------
 * Fetch products (for dropdown)
 * --------------------------
 */
$products = [];
$result = $conn->query("
    SELECT product_name, product_code, scientific_name, size_range, specification, target_buying_price
    FROM target_buying_price tbp
    JOIN products p ON tbp.product_id = p.id
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'product_name'        => $row['product_name'] ?? '',
            'product_code'        => $row['product_code'] ?? '',
            'scientific_name'     => $row['scientific_name'] ?? '',
            'size_range'          => $row['size_range'] ?? '',
            'specification'       => $row['specification'] ?? '',
            'target_buying_price' => $row['target_buying_price'] ?? '',
        ];
    }
}

/**
 * --------------------------
 * Fetch available dates / months / years
 * --------------------------
 */
$dateselected = [];
$dateResult = $conn->query("
    SELECT DISTINCT date 
    FROM buyingpriceanlaysistable 
    ORDER BY date DESC
");
while ($row = $dateResult->fetch_assoc()) {
    $dateselected[] = $row['date'];
}

$productIds = [];
$idResult = $conn->query("SELECT DISTINCT product_code FROM buyingpriceanlaysistable");
while ($row = $idResult->fetch_assoc()) {
    $productIds[] = $row['product_code'];
}

$currentDate = isset($dateselected[0]) ? $dateselected[0] : date('Y-m-d');

// Delete handler
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

// Months
$monthResult = $conn->query("
    SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month 
    FROM buyingpriceanlaysistable 
    ORDER BY month DESC
");
$availableMonths = [];
while ($row = $monthResult->fetch_assoc()) {
    $availableMonths[] = $row['month'];
}
$selectedMonth = $_GET['month'] ?? date('Y-m');

// Dates for selected month
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

// Years - FIXED: Remove STR_TO_DATE since date is already in proper format
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$yearSql = "SELECT DISTINCT YEAR(date) as year 
            FROM buyingpriceanlaysistable 
            ORDER BY year DESC";
$yearResult = $conn->query($yearSql);
$availableYears = [];
if ($yearResult && $yearResult->num_rows > 0) {
    while ($yearRow = $yearResult->fetch_assoc()) {
        $availableYears[] = $yearRow['year'];
    }
} else {
    // If no years found, add current year
    $availableYears[] = date('Y');
}

// Months for selected year - FIXED: Remove STR_TO_DATE
$monthSql = "SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month_code,
                     DATE_FORMAT(date, '%M %Y') as month_name 
             FROM buyingpriceanlaysistable 
             WHERE YEAR(date) = $selectedYear
             ORDER BY month_code ASC";
$monthResult = $conn->query($monthSql);
$months = [];
if ($monthResult && $monthResult->num_rows > 0) {
    while ($monthRow = $monthResult->fetch_assoc()) {
        $months[] = $monthRow;
    }
}

// NEW: Fetch products for summary - Get distinct products from buying price analysis table
$summaryProducts = [];
$productSql = "SELECT DISTINCT product_code, product_name, scientific_name 
               FROM buyingpriceanlaysistable 
               WHERE YEAR(date) = $selectedYear
               ORDER BY product_name";
$productResult = $conn->query($productSql);
if ($productResult && $productResult->num_rows > 0) {
    while ($productRow = $productResult->fetch_assoc()) {
        $summaryProducts[] = $productRow;
    }
}
?>