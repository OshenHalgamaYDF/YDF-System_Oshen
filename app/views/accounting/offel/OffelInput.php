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
        <!-- Add Offel Record Button -->
        <div class="mb-2">
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#offelModal" id="addOffelBtn"> + Offel Record</button>
            <div class="mx-2 d-inline">
                <button type="button" class="btn btn-danger px-4"> View Summary</button>
            </div>
        </div>


        <!-- Modal for Add/Edit Offel Record -->
        <div class="modal fade" id="offelModal" tabindex="-1" aria-labelledby="offelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="offelModalLabel">Form to Add Offel Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <!-- Modal Body with Form -->
                    <div class="modal-body">
                        <form method="POST" class="row g-3" id="offelForm" novalidate>
                            <!-- Hidden ID for Edit -->
                            <input type="hidden" name="ID" id="ID" />
                            <div class="col-md-12">
                                <label>Input Date: </label>
                                <input type="date" name="dateOffel" id="dateOffel" class="form-control" required/>
                                <div class="invalid-feedback">Please enter a date.</div>
                            </div>
                            <div class="col-md-12">
                                <label>Input Product: </label>
                                <input type="text" name="ProductOffel" id="ProductOffel" class="form-control" required/>
                                <div class="invalid-feedback">Please enter Product.</div>
                            </div>
                            <div class="col-md-12">
                                <label>Input Type: </label>
                                <input type="text" name="TypeOffel" id="TypeOffel" class="form-control" required/>
                                <div class="invalid-feedback">Please enter Product Type.</div>
                            </div>
                            <div class="col-md-12">
                                <label>Input Buyer: </label>
                                <input type="text" name="BuyerOffel" id="BuyerOffel" class="form-control" required/>
                                <div class="invalid-feedback">Please enter Buyer Name.</div>
                            </div>
                            <div class="col-md-12">
                                <label>Input KG: </label>
                                <input type="number" name="KGOffel" id="KGOffel" class="form-control" required/>
                                <div class="invalid-feedback">Please enter Kg Available.</div>
                            </div>
                            <div class="col-md-12">
                                <label>Input Price: </label>
                                <input type="number" name="PriceOffel" id="PriceOffel" class="form-control" required/>
                                <div class="invalid-feedback">Please enter Price per Kg.</div>
                            </div>
                            <div class="col-md-12">
                                <label>Remarks: </label>
                                <input type="text" name="RemarkOffel" id="RemarkOffel" class="form-control" />
                            </div>
                        </div>
                        <!-- Modal Footer with Submit Button -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="SubmitOffel" class="btn btn-primary px-4" id="modalSubmitBtn">Add</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Offel Records Table -->
        <div class="container mt-4">
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
                    <table class="table table-striped table-hover text-center align-middle table-bordered">
                        <thead class="table-dark ">
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Buyer</th>
                                <th>Kg</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Remark</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($row = $result->fetch_assoc()) { ?>
                            <tr 
                                data-id="<?= $row['ID'] ?>"
                                data-date="<?= htmlspecialchars($row['Date']) ?>"
                                data-product="<?= htmlspecialchars($row['Product']) ?>"
                                data-type="<?= htmlspecialchars($row['Type']) ?>"
                                data-buyer="<?= htmlspecialchars($row['Buyer']) ?>"
                                data-kg="<?= htmlspecialchars($row['Kg']) ?>"
                                data-price="<?= htmlspecialchars($row['Price']) ?>"
                                data-remark="<?= htmlspecialchars($row['Remark']) ?>"
                            >
                                <td><?php echo $row['Date']; ?></td>
                                <td><?php echo $row['Product']; ?></td>
                                <td><?php echo $row['Type']; ?></td>
                                <td><?php echo $row['Buyer']; ?></td>
                                <td><?php echo $row['Kg']; ?></td>
                                <td><?php echo $row['Price']; ?></td>
                                <td><?php echo $row['Kg'] * $row['Price']; ?></td>
                                <td><?php echo $row['Remark']; ?></td>
                                <td>
                                    <!-- Edit Button: triggers modal and fills form -->
                                    <button type="button" class="btn btn-warning btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#offelModal">Edit</button>
                                    <!-- Delete Button: submits form to delete record -->
                                    <form method='post' class='d-inline'>
                                        <input type='hidden' name='id' value='<?= $row['ID'] ?>'>
                                        <button type='submit' name='delete_input' class="btn btn-danger btn-sm" onclick="return confirm('Delete this record?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<script>
// Bootstrap validation for form
document.getElementById('offelForm').addEventListener('submit', function(event) {
    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    this.classList.add('was-validated');
});

// Reset modal for Add (clear form and set modal title/button)
document.getElementById('addOffelBtn').addEventListener('click', function() {
    document.getElementById('offelModalLabel').textContent = 'Form to Add Offel Record';
    document.getElementById('modalSubmitBtn').textContent = 'Add';
    document.getElementById('ID').value = '';
    document.getElementById('offelForm').reset();
    document.getElementById('offelForm').classList.remove('was-validated');
});

// Edit button logic: fill modal with row data and set modal title/button
document.querySelectorAll('.btn-edit').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var tr = btn.closest('tr');
        document.getElementById('offelModalLabel').textContent = 'Edit Offel Record';
        document.getElementById('modalSubmitBtn').textContent = 'Update';
        document.getElementById('ID').value = tr.getAttribute('data-id');
        document.getElementById('dateOffel').value = tr.getAttribute('data-date');
        document.getElementById('ProductOffel').value = tr.getAttribute('data-product');
        document.getElementById('TypeOffel').value = tr.getAttribute('data-type');
        document.getElementById('BuyerOffel').value = tr.getAttribute('data-buyer');
        document.getElementById('KGOffel').value = tr.getAttribute('data-kg');
        document.getElementById('PriceOffel').value = tr.getAttribute('data-price');
        document.getElementById('RemarkOffel').value = tr.getAttribute('data-remark');
    });
});
</script>
</body>
</html>