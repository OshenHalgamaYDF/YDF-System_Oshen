<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from form
$table_name = $_POST['table_name'];
$col1 = $_POST['col1'];
$col2 = $_POST['col2'];
$col3 = $_POST['col3'];

// Build SQL
$sql = "CREATE TABLE $table_name (
    $col1,
    $col2,
    $col3
)";

// Execute query
if ($conn->query($sql) === TRUE) {
    echo "Table '$table_name' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
