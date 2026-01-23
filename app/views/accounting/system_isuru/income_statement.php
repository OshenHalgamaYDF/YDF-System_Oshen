<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\system_isuru\income_statement_controller.php');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Income Statement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display:none !important; } }
    </style>
</head>
<body class="p-4 bg-light">
<div class="container mb-4">
    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📈 Income Statement - <?= htmlspecialchars($active_country['country_name']) ?> (<?= htmlspecialchars($active_country['currency_code']) ?>)</h5>
            <div class="d-flex gap-2">
                <?php
                $qs = [
                    'filter_from' => $filter_from,
                    'filter_to'   => $filter_to,
                    'export'      => 'excel'
                ];
                ?>
                <a href="?<?= http_build_query($qs) ?>" class="btn btn-success btn-sm">Download Excel</a>
                <a href="accounting.php" class="btn btn-secondary btn-sm">← Back</a>
            </div>
        </div>

        <!-- Date Range Filter (styled like Trial Balance) -->
        <div class="card-header bg-light no-print">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">                         
                    <label for="filter_from" class="form-label fw-bold">From Date:</label>
                    <input type="date" id="filter_from" name="filter_from" class="form-control" value="<?= htmlspecialchars($filter_from) ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="filter_to" class="form-label fw-bold">To Date:</label>
                    <input type="date" id="filter_to" name="filter_to" class="form-control" value="<?= htmlspecialchars($filter_to) ?>" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Apply Filter</button>
                </div>
                <div class="col-auto">
                    <a href="?filter_from=<?= date('Y-01-01') ?>&filter_to=<?= date('Y-m-d') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-calendar-range"></i> This Year</a>
                </div>
                <div class="col-auto">
                    <a href="?filter_from=<?= date('Y-m-01') ?>&filter_to=<?= date('Y-m-d') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-calendar"></i> This Month</a>
                </div>
                <div class="col-auto ms-auto">
                    <small class="text-muted">Showing: <strong><?= htmlspecialchars(date('M d, Y', strtotime($filter_from))) ?></strong> to <strong><?= htmlspecialchars(date('M d, Y', strtotime($filter_to))) ?></strong></small>
                </div>
            </form>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width:60%">Account</th>
                        <th class="text-end">Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary fw-bold"><td colspan="2">INCOME</td></tr>
                    <?php
                    $showIncome = false;
                    foreach ($accounts as $acc) {
                        if ($acc['group_type'] === 'Income') {
                            $amount = $acc['total_cr'] - $acc['total_dr'];
                            if ($amount != 0) {
                                $showIncome = true;
                                echo "<tr><td class='ps-4'>{$acc['ledger_name']}</td><td class='text-end'>" . number_format($amount, 2) . "</td></tr>";
                            }
                        }
                    }
                    if (!$showIncome) echo "<tr><td colspan='2' class='text-center text-muted'>No income accounts available</td></tr>";
                    echo "<tr class='fw-bold'><td class='text-end'>Total Income</td><td class='text-end'>" . number_format($revenue,2) . "</td></tr>";
                    ?>

                    <tr><td colspan="2"></td></tr>
                    <tr class="table-secondary fw-bold"><td colspan="2">EXPENSES</td></tr>
                    <?php
                    $showExpense = false;
                    foreach ($accounts as $acc) {
                        if ($acc['group_type'] === 'Expense') {
                            $amount = $acc['total_dr'] - $acc['total_cr'];
                            if ($amount != 0) {
                                $showExpense = true;
                                echo "<tr><td class='ps-4'>{$acc['ledger_name']}</td><td class='text-end'>" . number_format($amount, 2) . "</td></tr>";
                            }
                        }
                    }
                    if (!$showExpense) echo "<tr><td colspan='2' class='text-center text-muted'>No expense accounts available</td></tr>";
                    echo "<tr class='fw-bold'><td class='text-end'>Total Expenses</td><td class='text-end'>" . number_format($expenses,2) . "</td></tr>";
                    ?>

                    <tr><td colspan="2"></td></tr>
                    <tr class="fw-bold table-warning">
                        <td class="text-end">Net Income (Loss)</td>
                        <td class="text-end <?= $net_income >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($net_income,2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
