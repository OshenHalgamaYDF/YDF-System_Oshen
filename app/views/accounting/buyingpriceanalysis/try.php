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
 * Insert or Update Record
 */
if (isset($_POST['SubmitOffel'])) {
    $id             = $_POST['record_id'] ?? null;
    $product_code   = $_POST['product_code'];
    $date           = $_POST['date'];
    $product_name   = $_POST['product_name'];
    $scientific_name= $_POST['scientific_name'];
    $size_range     = $_POST['size_range'];
    $specification  = $_POST['specification'];
    $target_price   = $_POST['target_price'];
    $buyer_name     = $_POST['buyer_name'];
    $sold_price     = $_POST['sold_price'];
    $remark         = $_POST['remark'];

    if ($id) {
        // Update existing record by ID
        $sql = "UPDATE buyingpriceanlaysistable 
                SET product_code=?, product_name=?, scientific_name=?, size_range=?, specification=?, 
                    target_price=?, buyer_name=?, sold_price=?, remark=?, date=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssdsdssi",
            $product_code,
            $product_name,
            $scientific_name,
            $size_range,
            $specification,
            $target_price,
            $buyer_name,
            $sold_price,
            $remark,
            $date,
            $id
        );
    } else {
        // Insert new record
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

    if ($stmt->execute()) {
        header("Location: BuyingPriceAnalysis.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

/**
 * Fetch Products for Dropdown
 */
$products = [];
$result = $conn->query("
    SELECT p.id as product_id, p.product_code, p.product_name, p.scientific_name, 
           tbp.size_range, tbp.specification, tbp.target_buying_price
    FROM target_buying_price tbp
    JOIN products p ON tbp.product_id = p.id
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

/**
 * Distinct Product IDs used for grouping tabs
 */
$productIds = [];
$idResult = $conn->query("SELECT DISTINCT product_code FROM buyingpriceanlaysistable");
while ($row = $idResult->fetch_assoc()) {
    $productIds[] = $row['product_code'];
}

/**
 * Delete Record
 */
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
