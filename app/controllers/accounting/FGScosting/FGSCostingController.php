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
$costingDatafgs = [];
$sql = "SELECT c.id, c.product_id, p.product_name, p.product_code, p.scientific_name, c.specification, c.size, 
               c.buyingprice, c.volume, c.expectedyield, c.buyingcostandlogic, c.processingcharge, c.packagingcost, 
               c.freightcost, c.estgrosstonet, c.type, p.category
        FROM fgscosting c
        JOIN products p ON c.product_id = p.id
        ORDER BY c.id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $costingDatafgs[] = $row;
    }
}

// Fetch latest exchange rate
$exchangeRateUsdtoLkr = 0;
$rateResult = $conn->query("SELECT UsdToLkr FROM exchangeratefgs ORDER BY id DESC LIMIT 1");
if ($rateResult && $rateRow = $rateResult->fetch_assoc()) {
    $exchangeRateUsdtoLkr = $rateRow['UsdToLkr'];
}
$exchangeRateUsdtoGbp = 0;
$rateResult = $conn->query("SELECT UsdToGbp FROM exchangeratefgs ORDER BY id DESC LIMIT 1");
if ($rateResult && $rateRow = $rateResult->fetch_assoc()) {
    $exchangeRateUsdtoGbp = $rateRow['UsdToGbp'];
}

// --- Handle AJAX fetch for edit ---
if (isset($_POST['get_costing']) && isset($_POST['costing_id'])) {
    $id = intval($_POST['costing_id']);
    $result = $conn->query("SELECT c.*, p.product_code, p.scientific_name 
                           FROM fgscosting c 
                           JOIN products p ON c.product_id = p.id 
                           WHERE c.id = $id LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'costing' => $row]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// --- Handle form submission ---
if (isset($_POST['add_fgs_costing'])) {
    // Get form data with proper field names
    $product_id = intval($_POST['product_id']);
    $type = $_POST['type'];
    $size = $_POST['size'];
    $specification = $_POST['specification'];
    $buying_price = floatval($_POST['buying_price']);
    $volume = floatval($_POST['volume']);
    $expected_yield = floatval($_POST['expected_yield']);
    $buyingandlogistic_cost = floatval($_POST['buyingandlogistic_cost']);
    $processing_charge = floatval($_POST['processing_charge']);
    $packaging_cost = floatval($_POST['packaging_cost']);
    $freight_cost = floatval($_POST['freightcost']);
    $estimate_gross_to_net = floatval($_POST['estimate_gross_to_net']);

    // Prepare INSERT query
    $sql = "INSERT INTO fgscosting 
        (product_id, type, size, specification, buyingprice, volume, 
         expectedyield, buyingcostandlogic, processingcharge, packagingcost, 
         freightcost, estgrosstonet)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "isssdddddddd", 
            $product_id,  
            $type,
            $size, 
            $specification, 
            $buying_price, 
            $volume, 
            $expected_yield, 
            $buyingandlogistic_cost, 
            $processing_charge, 
            $packaging_cost, 
            $freight_cost, 
            $estimate_gross_to_net
        );
        
        if ($stmt->execute()) {
            header("Location: FGSCosting.php");
            exit();
        } else {
            error_log("Database error: " . $stmt->error);
            echo "<script>alert('Error inserting data: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
        echo "<script>alert('Prepare failed: " . $conn->error . "');</script>";
    }
}

// --- Handle edit (update) ---
if (isset($_POST['edit_fgs_costing']) && isset($_POST['costing_id'])) {
    $id = intval($_POST['costing_id']);
    $product_id = intval($_POST['product_id']);
    $type = $_POST['type'];
    $size = $_POST['size'];
    $specification = $_POST['specification'];
    $buying_price = floatval($_POST['buying_price']);
    $volume = floatval($_POST['volume']);
    $expected_yield = floatval($_POST['expected_yield']);
    $processing_charge = floatval($_POST['processing_charge']);
    $packaging_cost = floatval($_POST['packaging_cost']);
    $freightcost = floatval($_POST['freightcost']);
    $buyingandlogistic_cost = floatval($_POST['buyingandlogistic_cost']);
    $estimate_gross_to_net = floatval($_POST['estimate_gross_to_net']);

    // Fixed UPDATE query - only update fields that exist in fgscosting table
    $sql = "UPDATE fgscosting SET 
        product_id=?, type=?, size=?, specification=?, buyingprice=?, volume=?, 
        expectedyield=?, processingcharge=?, packagingcost=?, freightcost=?, 
        buyingcostandlogic=?, estgrosstonet=?
        WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isssddddddddi", 
            $product_id, $type, $size, $specification, $buying_price, $volume, 
            $expected_yield, $processing_charge, $packaging_cost, $freightcost, 
            $buyingandlogistic_cost, $estimate_gross_to_net, $id
        );
        
        if ($stmt->execute()) {
            header("Location: FGSCosting.php");
            exit();
        } else {
            error_log("Update error: " . $stmt->error);
            echo "<script>alert('Error updating data: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
        echo "<script>alert('Prepare failed: " . $conn->error . "');</script>";
    }
}

// --- Handle delete ---
if (isset($_POST['delete_fgs_costing']) && isset($_POST['costing_id'])) {
    $id = intval($_POST['costing_id']);
    $result = $conn->query("DELETE FROM fgscosting WHERE id = $id");
    if ($result) {
        header("Location: FGSCosting.php");
        exit();
    } else {
        echo "<script>alert('Error deleting record: " . $conn->error . "');</script>";
    }
}

// ===== Fetch Current Data to Prefill Modal =====
$sql = "SELECT UsdToLkr, EurToLkr, GbpToLkr, UsdToGbp 
        FROM exchangeratefgs WHERE id = 1 LIMIT 1";
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
        echo "<script>alert('No changes detected in exchange rates.');window.location.href = 'FGSCosting.php';</script>";
        exit();
    }

    $sql = "UPDATE exchangeratefgs
            SET UsdToLkr = ?, EurToLkr = ?, GbpToLkr = ?, UsdToGbp = ?
            WHERE id = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dddd", $usdToLkr, $eurToLkr, $gbpToLkr, $usdToGbp);

    if ($stmt->execute()) {
        header("Location: FGSCosting.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>