<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\system_isuru\balance_sheet_controller.php');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Balance Sheet</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
@media print { .no-print { display:none !important; } }
.country-badge { font-size: 0.8rem; background-color: #e9ecef; padding: 2px 6px; border-radius: 4px; margin-left: 5px; }
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
        
        <!-- Country Selector with All Countries option - FIXED -->
        <div class="card-header bg-light no-print">
            <form method="POST" class="row g-2 align-items-end" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="col-md-3">
                    <label for="countrySelect" class="form-label fw-bold">Select Country:</label>
                    <select name="country_id" id="countrySelect" class="form-select" onchange="this.form.submit()">
                        <option value="0" <?= ($active_country_id == 0) ? 'selected' : '' ?>>🌍 All Countries</option>
                        <?php
                        $countries = $conn->query("SELECT id, country_name FROM countries ORDER BY country_name ASC");
                        while ($c = $countries->fetch_assoc()) {
                            $sel = ($c['id'] == $active_country_id) ? 'selected' : '';
                            echo "<option value='{$c['id']}' $sel>{$c['country_name']}</option>";
                        }
                        ?>
                    </select>
                    <input type="hidden" name="select_country" value="1">
                    
                    <!-- Preserve existing GET parameters -->
                    <?php if (isset($_GET['filter_from'])): ?>
                        <input type="hidden" name="filter_from" value="<?= htmlspecialchars($_GET['filter_from']) ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_to'])): ?>
                        <input type="hidden" name="filter_to" value="<?= htmlspecialchars($_GET['filter_to']) ?>">
                    <?php endif; ?>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-arrow-repeat"></i> Switch Country</button>
                </div>
            </form>
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
                        <th class="text-end">Amount (<?= htmlspecialchars($active_country['currency_code']) ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    function section_ui($accounts, $type, &$total, $showCountry = false) {
                        echo "<tr class='table-secondary fw-bold'><td colspan='2'>" . strtoupper($type) . "S</td></tr>";
                        $total = 0;
                        foreach ($accounts as $acc) {
                            if (strtolower($acc['group_type']) === strtolower($type)) {
                                $amount = ($type === 'asset') ? max($acc['balance'],0) : abs($acc['balance']);
                                if ($amount != 0) {
                                    $country_html = $showCountry && isset($acc['country_name']) ? " <span class='country-badge'>{$acc['country_name']}</span>" : '';
                                    echo "<tr><td class='ps-4'>{$acc['ledger_name']}{$country_html}</td>
                                          <td class='text-end'>" . number_format($amount,2) . "</td></tr>";
                                    $total += $amount;
                                }
                            }
                        }
                        echo "<tr class='fw-bold'><td class='text-end'>Total " . ucfirst($type) . "s</td>
                              <td class='text-end'>" . number_format($total,2) . "</td></tr>";
                        echo "<tr><td colspan='2'></td></tr>";
                    }

                    $showCountry = ($active_country_id == 0); // Show country badges only in All Countries view
                    section_ui($accounts,'asset',$asset_total, $showCountry);
                    section_ui($accounts,'liability',$liability_total, $showCountry);

                    echo "<tr class='table-secondary fw-bold'><td colspan='2'>EQUITY</td></tr>";
                    $equity_total = 0;
                    foreach ($accounts as $acc) {
                        if (strtolower($acc['group_type'])==='equity') {
                            $amount = abs($acc['balance']);
                            if ($amount!=0) {
                                $country_html = $showCountry && isset($acc['country_name']) ? " <span class='country-badge'>{$acc['country_name']}</span>" : '';
                                echo "<tr><td class='ps-4'>{$acc['ledger_name']}{$country_html}</td>
                                      <td class='text-end'>" . number_format($amount,2) . "</td></tr>";
                                $equity_total += $amount;
                            }
                        }
                    }

                    // Net Income - dynamic based on filter_from/filter_to
                    echo "<tr><td class='ps-4 fw-bold'>Retained Earnings / Net Income</td>
                          <td class='text-end fw-bold " . ($net_income>=0?'text-success':'text-danger') . "'>" . number_format(abs($net_income),2) . "</td></tr>";
                    $total_equity = $equity_total + $net_income;

                    echo "<tr class='fw-bold'><td class='text-end'>Total Equity</td>
                          <td class='text-end'>" . number_format($total_equity,2) . "</td></tr><tr><td colspan='2'></td></tr>";

                    // Balance check
                    $liabilities_equity = $liability_total + $total_equity;
                    $difference = abs($asset_total - $liabilities_equity);
                    $balanced = $difference < 0.01;

                    echo "<tr class='fw-bold table-warning'><td class='text-end'>Total Liabilities + Equity</td>
                          <td class='text-end'>" . number_format($liabilities_equity,2) . "</td></tr>";
                    echo "<tr><td colspan='2' class='text-center fw-bold " . ($balanced?'text-success':'text-danger') . "'>";
                    echo $balanced ? "✔ BALANCED" : "❌ Difference: " . number_format($difference,2);
                    echo "</td></tr>";
                    
                    // Show country breakdown if All Countries selected
                    if ($active_country_id == 0 && !empty($country_totals)) {
                        echo "<tr><td colspan='2'><hr></td></tr>";
                        echo "<tr class='table-info fw-bold'><td colspan='2'>SUMMARY BY COUNTRY</td></tr>";
                        echo "<tr><td colspan='2'>";
                        echo "<table class='table table-sm table-bordered mt-2'>";
                        echo "<thead><tr><th>Country</th><th class='text-end'>Assets</th><th class='text-end'>Liabilities</th><th class='text-end'>Equity</th><th class='text-end'>Net Income</th><th class='text-end'>Total</th></tr></thead>";
                        echo "<tbody>";
                        
                        $grand_assets = $grand_liabilities = $grand_equity = 0;
                        foreach ($country_totals as $country => $totals) {
                            $total_liab_equity = $totals['liability'] + $totals['equity'] + $net_income;
                            echo "<tr>";
                            echo "<td>{$country}</td>";
                            echo "<td class='text-end'>" . number_format($totals['asset'], 2) . "</td>";
                            echo "<td class='text-end'>" . number_format($totals['liability'], 2) . "</td>";
                            echo "<td class='text-end'>" . number_format($totals['equity'], 2) . "</td>";
                            echo "<td class='text-end'>" . number_format($net_income, 2) . "</td>";
                            echo "<td class='text-end'>" . number_format($total_liab_equity, 2) . "</td>";
                            echo "</tr>";
                            
                            $grand_assets += $totals['asset'];
                            $grand_liabilities += $totals['liability'];
                            $grand_equity += $totals['equity'];
                        }
                        
                        $grand_total = $grand_liabilities + $grand_equity + $net_income;
                        echo "<tr class='fw-bold'><td>GRAND TOTAL</td>";
                        echo "<td class='text-end'>" . number_format($grand_assets, 2) . "</td>";
                        echo "<td class='text-end'>" . number_format($grand_liabilities, 2) . "</td>";
                        echo "<td class='text-end'>" . number_format($grand_equity, 2) . "</td>";
                        echo "<td class='text-end'>" . number_format($net_income, 2) . "</td>";
                        echo "<td class='text-end'>" . number_format($grand_total, 2) . "</td></tr>";
                        
                        echo "</tbody></table>";
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>