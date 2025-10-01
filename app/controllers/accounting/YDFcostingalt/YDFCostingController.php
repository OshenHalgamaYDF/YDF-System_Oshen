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
$sql = "SELECT c.id, c.product_id, p.product_name, p.product_code, p.scientific_name, c.specification,
               c.buyingprice, c.volume, c.expectedyield, c.buying_logistic, c.processingcharge, c.packagecost, 
               c.freightcost, c.estimategrosstonet, c.margin, c.500groundedprice, c.500grounded_MCO
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
    $product_id = intval($_POST['product_id']);
    $specification = $_POST['specification'];
    $buyingprice = floatval($_POST['buyingprice']);
    $volume = floatval($_POST['volume']);
    $expectedyield = floatval($_POST['expectedyield']);
    $buying_logistic = floatval($_POST['buying_logistic']);
    $processingcharge = floatval($_POST['processingcharge']);
    $packagecost = floatval($_POST['packagecost']);
    $freightcost = floatval($_POST['freightcost']);
    $estimategrosstonet = floatval($_POST['estimategrosstonet']);
    $margin = floatval($_POST['margin']);

    $sql = "UPDATE ydfcosting SET 
                product_id = ?, 
                specification = ?,
                buyingprice = ?, 
                volume = ?, 
                expectedyield = ?, 
                buying_logistic = ?, 
                processingcharge = ?, 
                packagecost = ?, 
                freightcost = ?, 
                estimategrosstonet = ?, 
                margin = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isdddddddddi",
        $product_id,
        $specification,
        $buyingprice,
        $volume,
        $expectedyield,
        $buying_logistic,
        $processingcharge,
        $packagecost,
        $freightcost,
        $estimategrosstonet,
        $margin,
        $id
    );

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

// ===== Handle Add Costing Submission =====
if (isset($_POST['addCosting'])) {
    $product_id = intval($_POST['product_id']);
    $specification = $_POST['specification'];
    $buyingprice = floatval($_POST['buyingprice']);
    $volume = floatval($_POST['volume']);
    $expectedyield = floatval($_POST['expectedyield']);
    $buying_logistic = floatval($_POST['buying_logistic']);
    $processingcharge = floatval($_POST['processingcharge']);
    $packagecost = floatval($_POST['packagecost']);
    $freightcost = floatval($_POST['freightcost']);
    $estimategrosstonet = floatval($_POST['estimategrosstonet']);
    $margin = floatval($_POST['margin']);

    $sql = "INSERT INTO ydfcosting 
        (product_id, specification, buyingprice, volume, expectedyield, buying_logistic, processingcharge, packagecost, freightcost, estimategrosstonet, margin)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isddddddddd", $product_id, $specification, $buyingprice, $volume, $expectedyield, $buying_logistic, $processingcharge, $packagecost, $freightcost, $estimategrosstonet, $margin);
    $stmt->execute();
    $stmt->close();
    header("Location: YDFCosting.php");
    exit();
}

// ===== Handle Save Rounded MCO Submission =====
if (isset($_POST['save_rounded_mco'])) {
    $costing_id = intval($_POST['costing_id']);
    $rounded_price_500g = floatval($_POST['rounded_price_500g']);
    $mco_plus_price = floatval($_POST['mco_plus_price']);

    $sql = "UPDATE ydfcosting SET 500groundedprice = ?, 500grounded_MCO = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddi", $rounded_price_500g, $mco_plus_price, $costing_id);
    $stmt->execute();
    $stmt->close();
    header("Location: YDFCosting.php");
    exit();
}

?>