<?php
// InvoicesControllerYDF.php

$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize variables
$dateFilter = $_GET['date'] ?? null;
$invoices = [];
$shipping_discount_value = 0;
$net_total = 0;
$total_price = 0;
$shipping_reason = null;

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'] ?? '';
    
    if ($form_type === 'shipping_discount') {
        // Process shipping discount form
        if (!isset($conn) || !$conn) {
            die("Database connection not found.");
        }

        // Sanitize and validate form inputs
        $date = trim($_POST['discount_date'] ?? '');
        $discount_amount = trim($_POST['discount_amount'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if (empty($date) || empty($discount_amount) || empty($reason)) {
            echo "<script>alert('All fields are required.'); window.history.back();</script>";
            exit;
        }

        // Escape data for SQL safety
        $date = mysqli_real_escape_string($conn, $date);
        $discount_amount = mysqli_real_escape_string($conn, $discount_amount);
        $reason = mysqli_real_escape_string($conn, $reason);

        // Insert query
        $sql = "
            INSERT INTO shipping_discount_details_USA (date, reason, value)
            VALUES ('$date', '$reason', '$discount_amount')
        ";

        if (mysqli_query($conn, $sql)) {
            header("Location: InvoicesYDF.php");
            exit();
        } else {
            echo "<script>alert('❌ Database error: " . addslashes(mysqli_error($conn)) . "'); window.history.back();</script>";
        }
        
    } elseif ($form_type === 'invoice_details') {
        // Process invoice details form
        // Sanitize and get form values
        $date = mysqli_real_escape_string($conn, $_POST['date']);
        $inv_no = mysqli_real_escape_string($conn, $_POST['invoiceno']);
        $awb_no = mysqli_real_escape_string($conn, $_POST['AWBno']);
        $flight_details = mysqli_real_escape_string($conn, $_POST['flightdetails']);
        $destination = mysqli_real_escape_string($conn, $_POST['destination']);
        $fda_reg_no = mysqli_real_escape_string($conn, $_POST['FDAregno']);
        $consignee_address = mysqli_real_escape_string($conn, $_POST['consigneeaddress']);
        $consignee_tele = mysqli_real_escape_string($conn, $_POST['consigneetelephone']);
        $airport_name = mysqli_real_escape_string($conn, $_POST['airportname']);

        // Insert query
        $sql = "INSERT INTO invoices_details_USA
                (date, inv_no, awb_no, flight_details, destination, fda_reg_no, consignee_address, consignee_tele, airport_name)
                VALUES 
                ('$date', '$inv_no', '$awb_no', '$flight_details', '$destination', '$fda_reg_no', '$consignee_address', '$consignee_tele', '$airport_name')";

        if (mysqli_query($conn, $sql)) {
            header("Location: InvoicesYDFUSA.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
        
    } elseif ($form_type === 'unit_price') {
        // Handle unit price submission
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
                header("Location: InvoicesYDFUSA.php");
                exit();
            } else {
                echo "<script>alert('Error updating unit price!');</script>";
            }
        }
    } elseif (isset($_POST['Date'])) {
        // Handle date filter submission
        $dateFilter = $_POST['Date'];
        
        // Redirect to same page with GET parameter to prevent form resubmission
        header("Location: ".$_SERVER['PHP_SELF']."?date=".$dateFilter);
        exit;
    }
    
    // Stop further execution after processing the form
    exit;
}

// Handle date filter from GET request
if (isset($_GET['date'])) {
    $dateFilter = $_GET['date'];
}

// Fetch invoices data for the selected date
if ($dateFilter) {
$sql = "SELECT 
            nd.fish_type,
            nd.product_type,
            SUM(nd.net_weight) AS total_weight,
            (
                SELECT p.scientific_name 
                FROM products p 
                WHERE LOWER(TRIM(REPLACE(p.product_name, 'ies', 'y'))) = LOWER(TRIM(REPLACE(nd.fish_type, 'ies', 'y')))
                   OR LOWER(TRIM(REPLACE(p.product_name, 's', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 's', '')))
                   OR LOWER(TRIM(REPLACE(p.product_name, 'Kingfish', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 'King Fish', '')))
                   OR CONCAT(nd.fish_type) LIKE CONCAT('%', p.product_name, '%')
                   OR CONCAT(p.product_name) LIKE CONCAT('%', nd.fish_type, '%')
                LIMIT 1
            ) AS scientific_name,
            GROUP_CONCAT(DISTINCT nd.box_no ORDER BY nd.box_no ASC SEPARATOR ', ') AS box_numbers,
            nd.unit_price,
            nd.production_date
        FROM invoices_distribution_sheet AS nd
        WHERE nd.production_date = ?
        GROUP BY nd.fish_type, nd.product_type, nd.unit_price, nd.production_date
        ORDER BY nd.fish_type ASC, nd.product_type ASC";

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

// Calculate total price
foreach ($invoices as $invoice) {
    $unit_price = $invoice['unit_price'] ?? 0;
    $total_weight = $invoice['total_weight'] ?? 0;
    $value = $unit_price * $total_weight;
    $total_price += $value;
}

// Fetch shipping discount for the selected date
if ($dateFilter) {
    $sql = "SELECT value, reason FROM shipping_discount_details_USA WHERE date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dateFilter);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $discount = $result->fetch_assoc();
        $shipping_discount_value = (float)$discount['value'];
        $shipping_reason = htmlspecialchars($discount['reason']);
    } else {
        $shipping_reason = null;
    }
}

// Calculate net total
$net_total = $total_price - $shipping_discount_value;
?>