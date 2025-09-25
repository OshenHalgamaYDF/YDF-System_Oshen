<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\supplierledger\SupplierLedgerController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Ledger Summary</title>
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
        <h1 class="text-center mb-4 text-primary">Supplier Ledger Summary</h1>
        <button class="btn btn-primary mb-3" onclick="window.location.href='SupplierLedgerInput.php'">
            <i class="fas fa-arrow-left"></i> Back to Add Record
        </button>

        <form method="get" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Select a Year</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php if ($year == $selected_year) echo "selected"; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Supplier Name</th>
                        <th>Balance (LKR)</th>
                        <th>Balance ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliersummary as $row): ?>
                        <?php
                            $f_blanace_LKR += $row['balance_lkr'];
                            $f_balance_dollar += $row['balance_usd'];
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['supplier_name']; ?></td>
                            <td><?php echo number_format($row['balance_lkr'], 2); ?></td>
                            <td><?php echo number_format($row['balance_usd'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tfoot>
                        <tr class="table-info">
                            <td colspan="2" class="fw-bold text-end">Final Balance :</td>
                            <td><?php echo number_format($f_blanace_LKR, 2); ?></td>
                            <td><?php echo number_format($f_balance_dollar, 2); ?></td>
                        </tr>
                    </tfoot>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>