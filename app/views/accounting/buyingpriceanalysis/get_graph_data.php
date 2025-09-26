<?php
// get_graph_data.php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "DB connection failed"]));
}

header('Content-Type: application/json');

// Get POST parameters
$product_code = isset($_POST['product_code']) ? $conn->real_escape_string($_POST['product_code']) : '';
$buyer_name = isset($_POST['buyer_name']) ? $conn->real_escape_string($_POST['buyer_name']) : '';
$date_from = isset($_POST['date_from']) ? $conn->real_escape_string($_POST['date_from']) : '';
$date_to = isset($_POST['date_to']) ? $conn->real_escape_string($_POST['date_to']) : '';

// Build WHERE conditions
$where_conditions = ["1=1"];

if (!empty($product_code)) {
    $where_conditions[] = "product_code = '$product_code'";
}

if (!empty($buyer_name)) {
    $where_conditions[] = "buyer_name = '$buyer_name'";
}

if (!empty($date_from) && !empty($date_to)) {
    $where_conditions[] = "date BETWEEN '$date_from' AND '$date_to'";
}

$where_clause = implode(' AND ', $where_conditions);

// Get product name for the chart label
$product_name = "All Products";
if (!empty($product_code)) {
    $name_query = $conn->query("SELECT product_name FROM buyingpriceanlaysistable WHERE product_code = '$product_code' LIMIT 1");
    if ($name_query && $name_query->num_rows > 0) {
        $product_name = $name_query->fetch_assoc()['product_name'];
    }
}

// Query to get average prices by date
$sql = "SELECT DATE(date) as transaction_date, AVG(sold_price) as avg_price
        FROM buyingpriceanlaysistable 
        WHERE $where_clause
        GROUP BY DATE(date)
        ORDER BY DATE(date) ASC";

$result = $conn->query($sql);

$labels = [];
$prices = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['transaction_date'];
        $prices[] = (float)$row['avg_price'];
    }
    
    echo json_encode([
        "success" => true,
        "labels" => $labels,
        "prices" => $prices,
        "product_name" => $product_name
    ]);
} else {
    echo json_encode([
        "success" => true,
        "labels" => [],
        "prices" => [],
        "product_name" => $product_name,
        "message" => "No data found for the selected criteria"
    ]);
}

$conn->close();
?>