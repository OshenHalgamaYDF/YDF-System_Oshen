<?php 
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\offel\OffelController.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offel Income</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <!-- Back Button -->
        <button class="btn btn-primary mb-3" onclick="window.location.href='OffelInput.php'">
            &#x2B05; Back
        </button>

        <h1 class="text-center text-primary mb-4">Offel Income Records</h1>

        <div class="card shadow p-4">
            <h4 class="mb-3">Received Amounts</h4>

            <div class="table-responsive">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table id="incomeTable" class="table table-striped table-bordered text-center align-middle table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Income Received Date</th>
                                <th>Buyer Name</th>
                                <th>Income</th>
                                <th>Running Balance Income</th>
                                <th>Received</th>
                                <th>Running Balance Received</th>
                                <th>Balance</th>
                                <th>Buyer Paid Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $runningTotalincome = 0; 
                            $runningTotalrecived = 0; 

                            while ($row = $result->fetch_assoc()) { 
                                $runningTotalincome += $row['cost']; 
                                $runningTotalrecived += $row['Amount']; 
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Date']) ?></td>
                                    <td><?= htmlspecialchars($row['Buyer']) ?></td>
                                    <td><?= number_format($row['cost'], 2) ?></td>
                                    <td><?= number_format($runningTotalincome, 2) ?></td>
                                    <td><?= number_format($row['Amount'], 2) ?></td>
                                    <td><?= number_format($runningTotalrecived, 2) ?></td>
                                    <td><?= number_format($runningTotalincome - $runningTotalrecived, 2) ?></td>
                                    <td><?= htmlspecialchars($row['BuyerDate']) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        No income records found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#incomeTable').DataTable({
                "pageLength": 10,     // show 10 rows per page
                "ordering": true,     // enable column sorting
                "searching": true,    // enable search
                "lengthChange": true  // allow user to change page size
            });
        });
    </script>
</body>
</html>
