<?php
// ==== Database Connection ====
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ==== Handle Delete ====
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $delete_sql = "DELETE FROM shipping_discount_details_UK WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>alert('Discount deleted successfully!'); window.location.href=window.location.href;</script>";
    exit;
}

// ==== Handle Edit/Update ====
if (isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $date = $_POST['edit_date'];
    $reason = $_POST['edit_reason'];
    $value = $_POST['edit_value'];

    $update_sql = "UPDATE shipping_discount_details_UK SET date=?, reason=?, value=? WHERE id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssdi", $date, $reason, $value, $id);
    $stmt->execute();
    echo "<script>alert('Discount updated successfully!'); window.location.href=window.location.href;</script>";
    exit;
}

// ==== Fetch All Records ====
$sql = "SELECT id, date, reason, value FROM shipping_discount_details_UK ORDER BY date DESC";
$result = mysqli_query($conn, $sql);
?>