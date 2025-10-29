<?php
// DB connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---- Add New Entry ----
if (isset($_POST['ydfoutstandingsubmit'])) {
    $ydfdate = $_POST['ydfDate'];
    $ydfactivity = $_POST['ydfActivity'];
    $ydfreference = $_POST['ydfReference'];
    $ydftype = $_POST['ydftype'];
    $ydfvalue = floatval($_POST['ydfValue']);

    $sql = "INSERT INTO ydf_outstanding (`date`, activity, reference, type, value) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssd", $ydfdate, $ydfactivity, $ydfreference, $ydftype, $ydfvalue);
        $stmt->execute();
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

// ---- Update Entry ----
if (isset($_POST['ydfoutstandingupdate'])) {
    $id = intval($_POST['ydfId']);
    $ydfdate = $_POST['ydfDate'];
    $ydfactivity = $_POST['ydfActivity'];
    $ydfreference = $_POST['ydfReference'];
    $ydftype = $_POST['ydftype'];
    $ydfvalue = floatval($_POST['ydfValue']);

    $sql = "UPDATE ydf_outstanding SET `date`=?, activity=?, reference=?, type=?, value=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssdi", $ydfdate, $ydfactivity, $ydfreference, $ydftype, $ydfvalue, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

// ---- Delete Entry ----
if (isset($_POST['deleteId'])) {
    $id = intval($_POST['deleteId']);
    $sql = "DELETE FROM ydf_outstanding WHERE id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success']);
        exit();
    }
    echo json_encode(['status' => 'error']);
    exit();
}

// ---- Fetch single entry (for editing via AJAX) ----
if (isset($_GET['fetchId'])) {
    $id = intval($_GET['fetchId']);
    $sql = "SELECT * FROM ydf_outstanding WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    echo json_encode($row);
    $stmt->close();
    exit();
}
?>