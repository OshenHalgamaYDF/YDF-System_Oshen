<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\buyingpriceanalysis\BuyingPriceAnalysisController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buying Price Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Buying Price Summary</h1>
        <button class="btn btn-primary mb-3" onclick="window.location.href='BuyingPriceAnalysis.php'">
            &#x2B05; Back
        </button>
        <table id="buyingPriceTable" class="table table-striped table-bordered table-dark">
            <thead>
                <tr>
                    <th rowspan="2" class="text-center align-middle">Date</th>
                    <th rowspan="2" class="text-center align-middle">Product Name</th>
                    <th rowspan="2" class="text-center align-middle">Product Code</th>
                    <th rowspan="2" class="text-center align-middle">Scientific Name</th>
                    <th colspan="<?php 
                        $monthName = [];
                        $sql = "SELECT DISTINCT DATE_FORMAT(STR_TO_DATE(date, '%d/%m/%Y'), '%M') AS month_name
                                FROM buyingpriceanlaysistable";
                        
                        $res = $conn->query($sql);
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $monthName[] = $row['month_name'];
                            }
                        }
                        echo count($monthName);
                    ?>" class="text-center align-middle">Monthly Average</th>
                </tr>
                <tr>
                    <th colspan="4"></th>
                    <?php 
                    if (!empty($monthName)) {
                        foreach ($monthName as $avgmonth) {
                            echo "<th class='text-center'>{$avgmonth}</th>";
                        }
                    } else {
                        echo "<th class='text-center'>No months found</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT date, product_name, product_code, scientific_name, AVG(sold_price) AS buying_price 
                        FROM buyingpriceanlaysistable 
                        GROUP BY date, product_name, product_code, scientific_name
                        ORDER BY date DESC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) { 
                    while ($row = $result->fetch_assoc()) { 
                        echo "<tr>
                                <td>{$row['date']}</td>
                                <td>{$row['product_name']}</td>
                                <td>{$row['product_code']}</td>
                                <td>{$row['scientific_name']}</td>
                                <td>" . number_format($row['buying_price'], 2) . "</td>
                              </tr>";
                    }
                } else { 
                    echo "<tr><td colspan='5' class='text-center'>No records found</td></tr>"; 
                }?>
            </tbody>
        </table>
    </div>
    <script>
        $(document).ready(function() {
            $('#buyingPriceTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true
            });
        });
    </script>
</body>
</html>
