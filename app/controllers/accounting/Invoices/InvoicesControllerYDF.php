<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle unit price submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unit_price'])) {
    $fish_type = $_POST['fish_type'] ?? '';
    $product_type = $_POST['product_type'] ?? '';
    $production_date = $_POST['production_date'] ?? '';
    $unit_price = $_POST['unit_price'] ?? 0;
    
    if ($fish_type && $production_date && $unit_price > 0) {
        // Update unit price in the database
        $update_sql = "UPDATE invoices_distribution_sheet
                      SET unit_price = ? 
                      WHERE fish_type = ? 
                      AND production_date = ? 
                      AND product_type = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("dsss", $unit_price, $fish_type, $production_date, $product_type);
        
        if ($stmt->execute()) {
            echo "<script>alert('Unit price updated successfully!');</script>";
            // Refresh the page to show updated values
            echo "<script>window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('Error updating unit price!');</script>";
        }
    }
}

// Handle date filter submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Date'])) {
    $dateFilter = $_POST['Date'];
    
    // Redirect to same page with GET parameter to prevent form resubmission
    header("Location: ".$_SERVER['PHP_SELF']."?date=".$dateFilter);
    exit;
}

$dateFilter = $_GET['date'] ?? null;
$invoices = [];

if ($dateFilter) {
    $sql = "SELECT nd.fish_type,
            SUM(nd.net_weight) AS total_weight,
            (SELECT p.scientific_name FROM products p WHERE p.product_name = nd.fish_type LIMIT 1) AS scientific_name,
            GROUP_CONCAT(DISTINCT nd.box_no SEPARATOR ', ') AS box_numbers,
            GROUP_CONCAT(DISTINCT nd.product_type SEPARATOR ', ') AS product_type, 
            nd.unit_price
            FROM invoices_distribution_sheet AS nd
            WHERE nd.production_date = ?
            GROUP BY nd.fish_type
            ORDER BY nd.fish_type ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dateFilter);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $invoices[] = $row;
        }
    }
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get form values
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $inv_no = mysqli_real_escape_string($conn, $_POST['invoiceno']);
    $awb_no = mysqli_real_escape_string($conn, $_POST['AWBno']);
    $flight_details = mysqli_real_escape_string($conn, $_POST['flightdetails']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $fda_reg_no = mysqli_real_escape_string($conn, $_POST['FDAregno']);
    $consignee_address = mysqli_real_escape_string($conn, $_POST['consigneeaddress']);
    $consignee_tele = mysqli_real_escape_string($conn, $_POST['consigneetelephone']);

    // Insert query
    $sql = "INSERT INTO invoices_details 
            (date, inv_no, awb_no, flight_details, destination, fda_reg_no, consignee_address, consignee_tele)
            VALUES 
            ('$date', '$inv_no', '$awb_no', '$flight_details', '$destination', '$fda_reg_no', '$consignee_address', '$consignee_tele')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Invoice details added successfully!'); window.location.href='InvoicesYDF.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

?>
