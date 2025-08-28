<?php include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\offel\OffelController.php'); ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <title>Offel Input</title>
    </head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4 text-primary">Offel Records Input</h1>
        <div class="card shadow p-4 mb-4">
            <form method="POST" class="row g-3" id="offelForm" novalidate>
                <div class="col-md-4">
                    <label>Input Date: </label>
                    <input type="date" name="dateOffel" id="dateOffel" class="form-control" required/>
                    <div class="invalid-feedback">Please enter a date.</div>
                </div>
                <div class="col-md-4">
                    <label>Input Product: </label>
                    <input type="text" name="ProductOffel" id="ProductOffel" class="form-control" required/>
                    <div class="invalid-feedback">Please enter Product.</div>
                </div>
                <div class="col-md-4">
                    <label>Input Type: </label>
                    <input type="text" name="TypeOffel" id="TypeOffel" class="form-control" required/>
                    <div class="invalid-feedback">Please enter Product Type.</div>
                </div>
                <div class="col-md-6">
                    <label>Input Buyer: </label>
                    <input type="text" name="BuyerOffel" id="BuyerOffel" class="form-control" required/>
                    <div class="invalid-feedback">Please enter Buyer Name.</div>
                </div>
                <div class="col-md-3">
                    <label>Input KG: </label>
                    <input type="number" name="KGOffel" id="KGOffel" class="form-control" required/>
                    <div class="invalid-feedback">Please enter Kg Available.</div>
                </div>
                <div class="col-md-3">
                    <label>Input Price: </label>
                    <input type="number" name="PriceOffel" id="PriceOffel" class="form-control" required/>
                    <div class="invalid-feedback">Please enter Price per Kg.</div>
                </div>
                <div class="col-md-12">
                    <label>Remarks: </label>
                    <input type="text" name="RemarkOffel" id="RemarkOffel" class="form-control" />
                </div>
                <div class="col-md-12 text-center mt-3">
                    <button type="submit" name="SubmitOffel" class="btn btn-primary px-4">Add</button>
                </div>
            </form>
        </div>
        <?php $result = $conn->query("SELECT * FROM offelsystem ORDER BY Product ASC");?>
        <div class="card shadow p-4 mb-4">
            <h4 class="mb-3 text-secondary">Offel Records Tuna</h4>
            <h5>Total Tuna Income: 
                <?php 
                    $totalIncome = 0;
                    $incomeResult = $conn->query("SELECT Kg, Price FROM offelsystem");
                    while ($incomeRow = $incomeResult->fetch_assoc()) {
                        $totalIncome += $incomeRow['Kg'] * $incomeRow['Price'];
                    }
                    echo 'LKR ' . number_format($totalIncome, 2);
                ?>
                </h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Buyer</th>
                            <th>Kg</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <?php while($row = $result->fetch_assoc()) { ?>
                    <tbody>
                        <tr>
                            <td><?php echo $row['Date']; ?></td>
                            <td><?php echo $row['Product']; ?></td>
                            <td><?php echo $row['Type']; ?></td>
                            <td><?php echo $row['Buyer']; ?></td>
                            <td><?php echo $row['Kg']; ?></td>
                            <td><?php echo $row['Price']; ?></td>
                            <td><?php echo $row['Kg'] * $row['Price']; ?></td>
                            <td><?php echo $row['Remark']; ?></td> 
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<script>
document.getElementById('offelForm').addEventListener('submit', function(event) {
    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    this.classList.add('was-validated');
});
</script>
</body>
</html>
