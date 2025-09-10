<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

//Output the table of recieved money
$result = $conn->query("SELECT *, Amount FROM offelrecieved ORDER BY Date ASC");

//running blanace calculation
$runningTotalrecived = 0;
$runningTotalincome = 0;

//input of the submit data
if (isset($_POST['SubmitOffel'])) {
    $id = $_POST['ID'] ?? '';
    $dateOffel = $_POST['dateOffel'] ?? '';
    $ProductOffel = $_POST['ProductOffel'] ?? '';
    $TypeOffel = $_POST['TypeOffel'] ?? '';
    $BuyerOffel = $_POST['BuyerOffel'] ?? '';
    $KGOffel = $_POST['KGOffel'] ?? '';
    $PriceOffel = $_POST['PriceOffel'] ?? '';
    $RemarkOffel = $_POST['RemarkOffel'] ?? '';

    if ($id) {
        // UPDATE query
        $stmt = $conn->prepare("UPDATE offelsystem SET Date=?, Product=?, Type=?, Buyer=?, Kg=?, Price=?, Remark=? WHERE ID=?");
        $stmt->bind_param("sssssssi", $dateOffel, $ProductOffel, $TypeOffel, $BuyerOffel, $KGOffel, $PriceOffel, $RemarkOffel, $id);
    } else {
        // INSERT query
        $stmt = $conn->prepare("INSERT INTO offelsystem (Date, Product, Type, Buyer, Kg, Price, Remark) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $dateOffel, $ProductOffel, $TypeOffel, $BuyerOffel, $KGOffel, $PriceOffel, $RemarkOffel);
    }
    
    if ($stmt->execute()) {
        // Redirect to avoid resubmission on refresh
        header("Location: OffelInput.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

if (isset($_POST['RSubmitOffel'])) {
    $date = $_POST['RdateOffel'] ?? '';
    $buyer = $_POST['RbuyerOffel'] ?? '';
    $buyerDate = $_POST['RdateBuyerOffel'] ?? '';
    $amount = $_POST['RamountOffel'] ?? '';

    if (!empty($date) && !empty($buyer) && !empty($amount)) {
        // ✅ Check for both Date + Buyer
        $checkStmt = $conn->prepare("SELECT ID FROM offelrecieved WHERE Date = ? AND Buyer = ?");
        $checkStmt->bind_param("ss", $date, $buyer);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE offelrecieved 
                                    SET Amount = ?, BuyerDate = ? 
                                    WHERE Date = ? AND Buyer = ?");
            $stmt->bind_param("dsss", $amount, $buyerDate, $date, $buyer);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO offelrecieved (`Date`, `Buyer`, `Amount`, `BuyerDate`) 
                                    VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $date, $buyer, $amount, $buyerDate);
        }

        if ($stmt->execute()) {
            header("Location: OffelInput.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        $checkStmt->close();
    } else {
        echo "Please fill in all required fields.";
    }
}



// Handle Delete
if (isset($_POST['delete_input']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM offelsystem WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // Redirect to avoid resubmission on refresh
    header("Location: OffelInput.php");
    exit();
}

// Get latest date from DB
$latestDateResult = $conn->query("SELECT MAX(Date) as LatestDate FROM offelsystem");
$latestDateRow = $latestDateResult->fetch_assoc();
$latestDate = $latestDateRow['LatestDate'] ?? date('Y-m-d');

// Define all possible products for tabs
$allProducts = ['Tuna', 'Sword', 'Red Snapper', 'Kingfish', 'Other'];

// Get all unique products from DB (not strictly needed since using allProducts)
$productResult = $conn->query("SELECT DISTINCT Product FROM offelsystem");
$productsInDb = [];
while ($row = $productResult->fetch_assoc()) {
    $productsInDb[] = $row['Product'];
}
$products = $allProducts;


if (isset($_POST['costshow'])) {
    // 1. Calculate total cost per row and sum by date
    $sql = "
            SELECT Date, Buyer, SUM(Kg * Price) AS cost
            FROM offelsystem
            GROUP BY Date, Buyer
            ORDER BY Date ASC, Buyer ASC
    ";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $date = $row['Date'];
            $buyer = $row['Buyer'];
            $totalCost = $row['cost'];

            // Insert or update into offelcosttotal Also check if date already exists to avoid duplicates
            $checkSql = "SELECT ID FROM offelrecieved WHERE date = '$date' AND buyer = '$buyer'";
            $checkResult = $conn->query($checkSql);

            if ($checkResult->num_rows > 0) {
                // Update existing record
                $updateSql = "UPDATE offelrecieved SET cost = '$totalCost' WHERE date = '$date' AND buyer = '$buyer'";
                $conn->query($updateSql);
            } else {
                // Insert new record
                $insertSql = "INSERT INTO offelrecieved (date, buyer, cost) VALUES ('$date', '$buyer', '$totalCost')";
                $conn->query($insertSql);
            }
        }
        echo "offelrecieved table updated successfully.";
    } else {
        echo "No data found in offelsystem.";
    }

    // Redirect to another page
    header("Location: OffelIncome.php");
    exit();
}
?>