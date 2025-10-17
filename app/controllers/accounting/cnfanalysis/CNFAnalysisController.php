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
 * Dropdown for cnf form
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
 * Insert / Update (submitCnf)
 * --------------------------
 */
if (isset($_POST['Submitcnf'])) {
    $date            = $_POST['date'];
    $product_code    = $_POST['product_code'];
    $product_name    = $_POST['product_name'];
    $size_range      = $_POST['size_range'];
    $specification   = $_POST['specification'];
    $cnf             = $_POST['cnf'];

    // Check if record already exists
    $checkSql = "SELECT 1 FROM cnf_analysis
                 WHERE date=? AND product_code=? AND buyer_name=? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("sss", $date, $product_code);
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
?>