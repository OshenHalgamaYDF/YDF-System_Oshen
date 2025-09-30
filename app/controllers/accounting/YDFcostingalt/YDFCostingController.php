<?php
// --- DB Connection ---
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// --- Fetch Data ---
$costingData = [];
$sql = "SELECT c.product_id, p.product_name, c.buyingprice, c.volume, c.expectedyield, p.product_code, c.processingcharge, c.packagecost
        FROM ydfcosting c
        JOIN products p ON c.product_id = p.id
        ORDER BY c.id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $costingData[] = $row;
    }
}

// Fetch latest exchange rate
$exchangeRate = 0;
$rateResult = $conn->query("SELECT UsdToLkr FROM exchangerate ORDER BY id DESC LIMIT 1");
if ($rateResult && $rateRow = $rateResult->fetch_assoc()) {
    $exchangeRate = $rateRow['UsdToLkr'];
}

// ===== Fetch Current Data to Prefill Modal =====
$sql = "SELECT UsdToLkr, EurToLkr, GbpToLkr, UsdToGbp 
        FROM exchangerate WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);
$current = $result->fetch_assoc();

// ===== Handle Update Form Submission =====
if (isset($_POST['exchangeratereplace'])) {
    $usdToLkr = $_POST['newUsdToLkrRate'];
    $eurToLkr = $_POST['newEurToLkrRate'];
    $gbpToLkr = $_POST['newGbpToLkrRate'];
    $usdToGbp = $_POST['newUsdToGbpRate'];

    if (
        $usdToLkr == $current['UsdToLkr'] &&
        $eurToLkr == $current['EurToLkr'] &&
        $gbpToLkr == $current['GbpToLkr'] &&
        $usdToGbp == $current['UsdToGbp']
    ) {
        echo "<script>alert('No changes detected in exchange rates.');window.location.href = 'YDFCosting.php';</script>";
        exit();
    }

    $sql = "UPDATE exchangerate 
            SET UsdToLkr = ?, EurToLkr = ?, GbpToLkr = ?, UsdToGbp = ?
            WHERE id = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dddd", $usdToLkr, $eurToLkr, $gbpToLkr, $usdToGbp);

    if ($stmt->execute()) {
        header("Location: YDFCosting.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// ===== Handle Edit Form Submission =====
if (isset($_POST['edit_costing'])) {
    $id = intval($_POST['costing_id']);
    $product_id = intval($_POST['edit_product_id']);
    $buyingprice = floatval($_POST['edit_buyingprice']);
    $volume = floatval($_POST['edit_volume']);
    $expectedyield = floatval($_POST['edit_expectedyield']);
    $processingcharge = floatval($_POST['edit_processingcharge']);
    $packagecost = floatval($_POST['edit_packagecost']);

    $sql = "UPDATE ydfcosting SET 
                product_id = ?, 
                buyingprice = ?, 
                volume = ?, 
                expectedyield = ?, 
                processingcharge = ?, 
                packagecost = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idddddi", $product_id, $buyingprice, $volume, $expectedyield, $processingcharge, $packagecost, $id);

    if ($stmt->execute()) {
        header("Location: YDFCosting.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
    $stmt->close();
}

// ===== Handle Delete Request =====
if (isset($_POST['delete_costing'])) {
    $id = intval($_POST['costing_id']);
    $sql = "DELETE FROM ydfcosting WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: YDFCosting.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}

?>