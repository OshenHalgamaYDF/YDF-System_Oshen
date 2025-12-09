<?php
// =============================
// BALANCE SHEET (Date Range Filter + Suspense Dynamic Fix + Excel Export)
// Updated to use filter_from / filter_to (appearance like Trial Balance)
// =============================
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

// --- Helper: validate date ---
function valid_date($d) {
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

// --- Read date range filter (GET) like Trial Balance / BRS ---
$filter_from = isset($_GET['filter_from']) && valid_date($_GET['filter_from']) ? $_GET['filter_from'] : '';
$filter_to   = isset($_GET['filter_to']) && valid_date($_GET['filter_to']) ? $_GET['filter_to'] : '';

// Support legacy 'from'/'to' query params if present
if (!$filter_from && isset($_GET['from']) && valid_date($_GET['from'])) $filter_from = $_GET['from'];
if (!$filter_to && isset($_GET['to']) && valid_date($_GET['to'])) $filter_to = $_GET['to'];

// Build date condition for SQL
$dateExpr = '1=1';
if ($filter_from && $filter_to) {
    $dateExpr = "v.date BETWEEN '{$conn->real_escape_string($filter_from)}' AND '{$conn->real_escape_string($filter_to)}'";
} elseif ($filter_from) {
    $dateExpr = "v.date >= '{$conn->real_escape_string($filter_from)}'";
} elseif ($filter_to) {
    $dateExpr = "v.date <= '{$conn->real_escape_string($filter_to)}'";
}

// --- Calculate Net Income dynamically based on date filter ---
$income_query = $conn->query("
    SELECT
      COALESCE(SUM(CASE WHEN g.group_type='Income' THEN ve.amount * 
            (CASE WHEN ve.type='Cr' THEN 1 WHEN ve.type='Dr' THEN -1 END) ELSE 0 END),0) AS income_total,
      COALESCE(SUM(CASE WHEN g.group_type='Expense' THEN ve.amount * 
            (CASE WHEN ve.type='Dr' THEN 1 WHEN ve.type='Cr' THEN -1 END) ELSE 0 END),0) AS expense_total
    FROM voucher_entries ve
    JOIN vouchers v ON ve.voucher_id = v.voucher_id
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    JOIN account_groups g ON l.group_id = g.group_id
    WHERE g.group_type IN ('Income','Expense') AND $dateExpr
");
$income_data = $income_query->fetch_assoc();
$net_income = ($income_data['income_total'] ?? 0) - ($income_data['expense_total'] ?? 0);

// --- Fetch all ledgers with balances (respect dateExpr) ---
$query = "
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.balance_type,
        COALESCE(l.opening_balance,0) AS opening_balance,
        ag.group_name,
        ag.group_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' AND $dateExpr THEN ve.amount ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' AND $dateExpr THEN ve.amount ELSE 0 END), 0) AS total_cr
    FROM ledgers l
    LEFT JOIN account_groups ag ON l.group_id = ag.group_id
    LEFT JOIN voucher_entries ve ON ve.ledger_id = l.ledger_id
    LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
    WHERE COALESCE(ag.group_type,'') IN ('Asset','Liability','Equity','Suspense')
    GROUP BY l.ledger_id
    ORDER BY ag.group_type, ag.group_name, l.ledger_name
";
$result = $conn->query($query);

$accounts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Balance calculation
        if (strtoupper($row['balance_type']) === 'DR') {
            $balance = $row['opening_balance'] + $row['total_dr'] - $row['total_cr'];
        } else {
            $balance = $row['opening_balance'] + $row['total_cr'] - $row['total_dr'];
        }
        $row['balance'] = $balance;

        // Dynamic suspense fix
        if ($row['group_type'] === 'Suspense') {
            if ($balance >= 0) $row['group_type'] = 'Asset';
            else $row['group_type'] = 'Liability';
        }

        $accounts[] = $row;
    }
}

// =============================
// EXCEL EXPORT (CSV) - includes filter_from/filter_to in filename and headers
// =============================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header('Content-Type: text/csv; charset=UTF-8');
    $period_part = ($filter_from || $filter_to) ? ($filter_from . '_to_' . $filter_to) : date('Y-m-d');
    header('Content-Disposition: attachment; filename="balance_sheet_period_' . $period_part . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $out = fopen('php://output', 'w');

    // Header
    fputcsv($out, ['Balance Sheet']);
    $period_label = 'All Dates';
    if ($filter_from && $filter_to) $period_label = "{$filter_from} to {$filter_to}";
    elseif ($filter_from) $period_label = "From {$filter_from}";
    elseif ($filter_to) $period_label = "Up to {$filter_to}";
    fputcsv($out, ["As at: $period_label"]);
    fputcsv($out, []);

    // Export helper
    function export_section($out, $accounts, $type, &$total) {
        fputcsv($out, [strtoupper($type)]);
        $total = 0;
        foreach ($accounts as $acc) {
            if (strtolower($acc['group_type']) === strtolower($type)) {
                $amount = ($type === 'asset') ? max($acc['balance'],0) : abs($acc['balance']);
                if ($amount != 0) {
                    fputcsv($out, [$acc['ledger_name'], number_format($amount,2,'.','')]);
                    $total += $amount;
                }
            }
        }
        fputcsv($out, ['Total '.ucfirst($type), number_format($total,2,'.','')]);
        fputcsv($out, []);
    }

    // Assets / Liabilities
    export_section($out, $accounts, 'asset', $asset_total);
    export_section($out, $accounts, 'liability', $liability_total);

    // Equity
    fputcsv($out, ['EQUITY']);
    $equity_total = 0;
    foreach ($accounts as $acc) {
        if (strtolower($acc['group_type']) === 'equity') {
            $amount = abs($acc['balance']);
            if ($amount != 0) {
                fputcsv($out, [$acc['ledger_name'], number_format($amount,2,'.','')]);
                $equity_total += $amount;
            }
        }
    }
    fputcsv($out, ["Retained Earnings / Net Income", number_format(abs($net_income),2,'.','')]);
    $total_equity = $equity_total + $net_income;
    fputcsv($out, ["Total Equity", number_format($total_equity,2,'.','')]);

    fclose($out);
    exit;
}
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
            <h5 class="mb-0">📘 Balance Sheet</h5>
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