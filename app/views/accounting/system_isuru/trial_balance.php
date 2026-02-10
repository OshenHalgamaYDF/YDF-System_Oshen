<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\system_isuru\trial_balance_controller.php');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5f7fa; }
        .container { max-width: 1200px; margin-top: 36px; }
        @media print { .no-print { display:none !important; } }
        .muted-small { font-size:0.85rem; color:#666; }
    </style>
</head>
<body class="p-4">
    <div class="container mb-4">
        <?php if ($suspense_message) echo $suspense_message; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-warning d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="bi bi-calculator"></i> Trial Balance - Consolidated (All Countries)</h5>
                <small class="muted-small">Verify debit and credit totals match</small>
            </div>
            <div class="d-flex gap-2 no-print">
                <a href="?export=excel&filter_from=<?= htmlspecialchars($filter_from) ?>&filter_to=<?= htmlspecialchars($filter_to) ?>" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Download Excel</a>
                <a href="accounting.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div>

        <!-- Date Range Filter Section -->
        <div class="card-header bg-light no-print">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label for="filter_from" class="form-label fw-bold">From Date:</label>
                    <input type="date" id="filter_from" name="filter_from" class="form-control" value="<?= htmlspecialchars($filter_from) ?>" required>
                </div>
                <div class="col-md-2">
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
                    <small class="text-muted"><strong><?= date('M d, Y', strtotime($filter_from)) ?></strong> to <strong><?= date('M d, Y', strtotime($filter_to)) ?></strong></small>
                </div>
            </form>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                <th>Account</th>
                <th class="text-end" style="width:180px">Debit (Rs.)</th>
                <th class="text-end" style="width:180px">Credit (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalDr = 0.0;
                $totalCr = 0.0;

                if (!empty($ledgers)) {
                    foreach ($ledgers as $row) {
                        $name = htmlspecialchars($row['ledger_name']);
                        $net  = (float)$row['net_balance'];

                        // net > 0 => Debit balance; net < 0 => Credit balance
                        $debit  = $net > 0 ? $net : 0.0;
                        $credit = $net < 0 ? -$net : 0.0;

                        // accumulate totals
                        $totalDr += $debit;
                        $totalCr += $credit;

                        echo "<tr>";
                        echo "<td>" . $name . "</td>";
                        echo "<td class='text-end'>" . ($debit ? number_format($debit,2) : '') . "</td>";
                        echo "<td class='text-end'>" . ($credit ? number_format($credit,2) : '') . "</td>";
                        echo "</tr>";
                    }

                    // totals
                    $difference = abs($totalDr - $totalCr);
                    $is_balanced = $difference < 0.01;

                    echo "<tr class='fw-bold table-light'>";
                    echo "<td class='text-end'>Total</td>";
                    echo "<td class='text-end'>" . number_format($totalDr,2) . "</td>";
                    echo "<td class='text-end'>" . number_format($totalCr,2) . "</td>";
                    echo "</tr>";

                    echo "<tr>";
                    $cls = $is_balanced ? 'text-success' : 'text-danger';
                    echo "<td colspan='3' class='text-center " . $cls . "'>";
                    if ($is_balanced) {
                        echo "✅ <strong>Trial Balance is BALANCED</strong>";
                    } else {
                        echo "❌ <strong>Imbalance Detected:</strong> Difference: Rs. " . number_format($difference,2);
                    }
                    echo "</td></tr>";
                } else {
                    echo "<tr><td colspan='3' class='text-muted text-center'>No ledger data found.</td></tr>";
                }
                ?>
            </tbody>
            </table>
        </div>

        <?php if (!$is_balanced && isset($difference)): ?>
        <div class="card-footer bg-light no-print">
            <form method="POST" class="row g-2 align-items-end">
            <input type="hidden" name="create_suspense" value="1">
            <input type="hidden" name="difference" value="<?= htmlspecialchars($difference) ?>">
            <div class="col-auto">
                <p class="text-danger mb-0"><strong>Trial Balance is not balanced by Rs. <?= number_format($difference, 2) ?></strong></p>
                <small class="muted-small">If you're unsure why, check recent vouchers or run the ledger detail for unusual entries.</small>
            </div>
            <div class="col-auto ms-auto">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Create suspense account to balance the trial balance? This will post Rs. <?= number_format($difference,2) ?> to a suspense account.')">
                <i class="bi bi-plus-circle"></i> Create Suspense Account
                </button>
            </div>
            </form>
            <small class="text-muted d-block mt-2">
            💡 <strong>Note:</strong> Suspense accounts are temporary. Investigate the imbalance, correct the entries, then delete the suspense voucher.
            </small>
        </div>
        <?php endif; ?>
        </div>
    </div>
</body>
</html>