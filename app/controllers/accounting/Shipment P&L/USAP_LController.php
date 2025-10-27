<?php
// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle API requests first
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_GET['action'] == 'getRecord' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            $sql = "SELECT * FROM usa_shipping_pl WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $record = $result->fetch_assoc();
                echo json_encode(['success' => true, 'record' => $record]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
            }
            $stmt->close();
            exit();
        }
        
        if ($_GET['action'] == 'deleteRecord' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            $sql = "DELETE FROM usa_shipping_pl WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
            } else {
                throw new Exception('Error deleting record: ' . $stmt->error);
            }
            $stmt->close();
            exit();
        }
    } catch (Exception $e) {
        error_log("API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Handle form submissions
if (isset($_POST['usaShipmentSubmit'])) {
    $usadate = $_POST['usashipdate'];
    $usaincome = floatval($_POST['usashipincome']);
    $usakg = floatval($_POST['usashipkg']);
    $usaceylonfreshcost = floatval($_POST['usashipceylonfresh']);
    $usapowerfreightcost = floatval($_POST['usashippowerfreight']);
    $usajccommision = floatval($_POST['usashipjccommision']);
    $usaexchangerate = floatval($_POST['usashipexchangerate']);
    $usafishbill = floatval($_POST['usashipfishbill']);
    $usaprocessingcost = floatval($_POST['usashipprocessingpay']);
    $usafreightpayment = floatval($_POST['usashipfreightpayment']);
    $usaoh = floatval($_POST['usashipoh']);
    $usaairportcost = floatval($_POST['usashipairportcost']);
    $usalabourcost = floatval($_POST['usashipLabour']);

    $sql = "INSERT INTO usa_shipping_pl
        (date, income, kg, ceylonfresh, powerfreight, jc,
         exchangerate, fishbill, processcost, freightcost, oh, airportcost, labourcost)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "sdddddddddddd", 
            $usadate,
            $usaincome,
            $usakg,
            $usaceylonfreshcost,
            $usapowerfreightcost,
            $usajccommision,
            $usaexchangerate,
            $usafishbill,
            $usaprocessingcost,
            $usafreightpayment,
            $usaoh,
            $usaairportcost,
            $usalabourcost
        );
        
        if ($stmt->execute()) {
            header("Location: USAP_L.php");
            exit();
        } else {
            error_log("Database error: " . $stmt->error);
            echo "<script>alert('Error inserting data: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}

if (isset($_POST['usaShipmentUpdate'])) {
    $id = $_POST['editId'];
    $usadate = $_POST['usashipdate'];
    $usaincome = floatval($_POST['usashipincome']);
    $usakg = floatval($_POST['usashipkg']);
    $usaceylonfreshcost = floatval($_POST['usashipceylonfresh']);
    $usapowerfreightcost = floatval($_POST['usashippowerfreight']);
    $usajccommision = floatval($_POST['usashipjccommision']);
    $usaexchangerate = floatval($_POST['usashipexchangerate']);
    $usafishbill = floatval($_POST['usashipfishbill']);
    $usaprocessingcost = floatval($_POST['usashipprocessingpay']);
    $usafreightpayment = floatval($_POST['usashipfreightpayment']);
    $usaoh = floatval($_POST['usashipoh']);
    $usaairportcost = floatval($_POST['usashipairportcost']);
    $usalabourcost = floatval($_POST['usashipLabour']);

    $sql = "UPDATE usa_shipping_pl SET
        date = ?, income = ?, kg = ?, ceylonfresh = ?, powerfreight = ?, jc = ?,
        exchangerate = ?, fishbill = ?, processcost = ?, freightcost = ?, oh = ?, airportcost = ?, labourcost = ?
        WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "sddddddddddddi", 
            $usadate,
            $usaincome,
            $usakg,
            $usaceylonfreshcost,
            $usapowerfreightcost,
            $usajccommision,
            $usaexchangerate,
            $usafishbill,
            $usaprocessingcost,
            $usafreightpayment,
            $usaoh,
            $usaairportcost,
            $usalabourcost,
            $id
        );
        
        if ($stmt->execute()) {
            header("Location: USAP_L.php");
            exit();
        } else {
            error_log("Database error: " . $stmt->error);
            echo "<script>alert('Error updating data: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
?>