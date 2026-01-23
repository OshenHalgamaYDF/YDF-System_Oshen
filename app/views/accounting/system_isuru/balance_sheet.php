<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\system_isuru\balance_sheet_controller.php');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Balance Sheet</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
@media print { .no-print { display:none !important; } }
</style>
</head>
<body class="p-4 bg-light">
<div class="container mb-4">
    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📘 Balance Sheet - <?= htmlspecialchars($active_country['country_name']) ?> (<?= htmlspecialchars($active_country['currency_code']) ?>)</h5>
            <div>
                <?php
                    $queryParams = [];
                    if ($filter_from) $queryParams['filter_from'] = $filter_from;
                    if ($filter_to) $queryParams['filter_to'] = $filter_to;
                    $export_qs = http_build_query(array_merge($queryParams, ['export'=>'excel']));
                ?>
                <a href="?<?= htmlspecialchars($export_qs) ?>" class="btn btn-success btn-sm">Download Excel</a>
                <a href="accounting.php" class="btn btn-secondary btn-sm">← Back</a>
            </div>
        </div>    
        <!-- Date Range Filter (Trial Balance style) -->
        <div class="card-header bg-light no-print">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="filter_from" class="form-label fw-bold">From Date:</label>
                    <input type="date" id="filter_from" name="filter_from" class="form-control" value="<?= htmlspecialchars($filter_from ?: date('Y-01-01')) ?>">
                </div>
                <div class="col-md-3">
                    <label for="filter_to" class="form-label fw-bold">To Date:</label>
                    <input type="date" id="filter_to" name="filter_to" class="form-control" value="<?= htmlspecialchars($filter_to ?: date('Y-m-d')) ?>">
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
                    <small class="text-muted">Showing: <strong><?= htmlspecialchars($filter_from ? date('M d, Y', strtotime($filter_from)) : 'All') ?></strong> to <strong><?= htmlspecialchars($filter_to ? date('M d, Y', strtotime($filter_to)) : 'All') ?></strong></small>
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
                    <?php
                    function section_ui($accounts, $type, &$total) {
                        echo "<tr class='table-secondary fw-bold'><td colspan='2'>".strtoupper($type)."</td></tr>";
                        $total = 0;
                        foreach ($accounts as $acc) {
                            if (strtolower($acc['group_type']) === strtolower($type)) {
                                $amount = ($type === 'asset') ? max($acc['balance'],0) : abs($acc['balance']);
                                if ($amount != 0) {
                                    echo "<tr><td class='ps-4'>{$acc['ledger_name']}</td>
                                          <td class='text-end'>".number_format($amount,2)."</td></tr>";
                                    $total += $amount;
                                }
                            }
                        }
                        echo "<tr class='fw-bold'><td class='text-end'>Total ".ucfirst($type)."</td>
                              <td class='text-end'>".number_format($total,2)."</td></tr>";
                        echo "<tr><td colspan='2'></td></tr>";
                    }

                    section_ui($accounts,'asset',$asset_total);
                    section_ui($accounts,'liability',$liability_total);

                    echo "<tr class='table-secondary fw-bold'><td colspan='2'>EQUITY</td></tr>";
                    $equity_total = 0;
                    foreach ($accounts as $acc) {
                        if (strtolower($acc['group_type'])==='equity') {
                            $amount = abs($acc['balance']);
                            if ($amount!=0) {
                                echo "<tr><td class='ps-4'>{$acc['ledger_name']}</td>
                                      <td class='text-end'>".number_format($amount,2)."</td></tr>";
                                $equity_total += $amount;
                            }
                        }
                    }

                    // Net Income - dynamic based on filter_from/filter_to
                    echo "<tr><td class='ps-4 fw-bold'>Retained Earnings / Net Income</td>
                          <td class='text-end fw-bold ".($net_income>=0?'text-success':'text-danger')."'>".number_format(abs($net_income),2)."</td></tr>";
                    $total_equity = $equity_total + $net_income;

                    echo "<tr class='fw-bold'><td class='text-end'>Total Equity</td>
                          <td class='text-end'>".number_format($total_equity,2)."</td></tr><tr><td colspan='2'></td></tr>";

                    // Balance check
                    $liabilities_equity = $liability_total + $total_equity;
                    $difference = abs($asset_total - $liabilities_equity);
                    $balanced = $difference < 0.01;

                    echo "<tr class='fw-bold table-warning'><td class='text-end'>Total Liabilities + Equity</td>
                          <td class='text-end'>".number_format($liabilities_equity,2)."</td></tr>";
                    echo "<tr><td colspan='2' class='text-center fw-bold ".($balanced?'text-success':'text-danger')."'>";
                    echo $balanced ? "✔ BALANCED" : "❌ Difference: Rs. ".number_format($difference,2);
                    echo "</td></tr>";
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>