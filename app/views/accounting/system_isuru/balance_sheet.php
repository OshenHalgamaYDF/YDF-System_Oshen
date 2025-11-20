<?php
// =============================
// BALANCE SHEET (Suspense Dynamic Fix + Excel Export)
// =============================
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- Calculate Net Income dynamically ---
$income_query = $conn->query("
    SELECT
      COALESCE(SUM(CASE WHEN g.group_type='Income' THEN ve.amount * 
            (CASE WHEN ve.type='Cr' THEN 1 WHEN ve.type='Dr' THEN -1 END) ELSE 0 END),0) AS income_total,
      COALESCE(SUM(CASE WHEN g.group_type='Expense' THEN ve.amount * 
            (CASE WHEN ve.type='Dr' THEN 1 WHEN ve.type='Cr' THEN -1 END) ELSE 0 END),0) AS expense_total
    FROM voucher_entries ve
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    JOIN account_groups g ON l.group_id = g.group_id
    WHERE g.group_type IN ('Income','Expense')
");
$income_data = $income_query->fetch_assoc();
$net_income = ($income_data['income_total'] ?? 0) - ($income_data['expense_total'] ?? 0);

// --- Fetch all ledgers ---
$query = "
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.balance_type,
        COALESCE(l.opening_balance,0) AS opening_balance,
        ag.group_name,
        ag.group_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0) AS total_cr
    FROM ledgers l
    LEFT JOIN account_groups ag ON l.group_id = ag.group_id
    LEFT JOIN voucher_entries ve ON ve.ledger_id = l.ledger_id
    WHERE COALESCE(ag.group_type,'') IN ('Asset','Liability','Equity','Suspense')
    GROUP BY l.ledger_id
    ORDER BY ag.group_type, ag.group_name, l.ledger_name
";
$result = $conn->query($query);

$accounts = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // Balance Calculation
        if (strtoupper($row['balance_type']) === 'DR') {
            $balance = $row['opening_balance'] + $row['total_dr'] - $row['total_cr'];
        } else {
            $balance = $row['opening_balance'] + $row['total_cr'] - $row['total_dr'];
        }
        $row['balance'] = $balance;

        // Dynamic SUSPENSE FIX
        if ($row['group_type'] === 'Suspense') {
            if ($balance >= 0) $row['group_type'] = 'Asset';
            else $row['group_type'] = 'Liability';
        }

        $accounts[] = $row;
    }
}

// =============================
// EXCEL EXPORT (CSV)
// =============================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="balance_sheet_as_at_' . date('Y-m-d') . '.csv"');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

    $out = fopen('php://output', 'w');

    // Header
    fputcsv($out, ['Balance Sheet']);
    fputcsv($out, ['As at ' . date('Y-m-d')]);
    fputcsv($out, []);

    // Section function for export
    function export_section($out, $accounts, $type, &$total) {
        fputcsv($out, [strtoupper($type)]);
        $total = 0;

        foreach ($accounts as $acc) {
            if (strtolower($acc['group_type']) === strtolower($type)) {
                $amount = ($type === 'asset') ? max($acc['balance'],0) : abs($acc['balance']);
                if ($amount != 0) {
                    fputcsv($out, [$acc['ledger_name'], number_format($amount, 2, '.', '')]);
                    $total += $amount;
                }
            }
        }

        fputcsv($out, ['Total ' . ucfirst($type), number_format($total, 2, '.', '')]);
        fputcsv($out, []);
    }

    // Export: Assets / Liabilities
    export_section($out, $accounts, 'asset', $asset_total);
    export_section($out, $accounts, 'liability', $liability_total);

    // Export Equity
    fputcsv($out, ['EQUITY']);
    $equity_total = 0;

    foreach ($accounts as $acc) {
        if (strtolower($acc['group_type']) === 'equity') {
            $amount = abs($acc['balance']);
            if ($amount != 0) {
                fputcsv($out, [$acc['ledger_name'], number_format($amount, 2, '.', '')]);
                $equity_total += $amount;
            }
        }
    }

    // Net Income line
    fputcsv($out, ["Retained Earnings / Net Income", number_format(abs($net_income), 2, '.', '')]);
    $total_equity = $equity_total + $net_income;

    fputcsv($out, ["Total Equity", number_format($total_equity, 2, '.', '')]);
    fwrite($out, "\n");

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
</head>

<body class="p-4 bg-light">

<div class="container mb-4">

    <div class="card shadow-lg">

        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📘 Balance Sheet</h5>

            <div>
                <a href="?export=excel" class="btn btn-success">
                    Download Excel
                </a>
                <a href="accounting.php" class="btn btn-secondary">← Back</a>
            </div>
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
                    // Helper for UI output
                    function section_ui($accounts, $type, &$total) {
                        echo "<tr class='table-secondary fw-bold'><td colspan='2'>".strtoupper($type)."</td></tr>";
                        $total = 0;

                        foreach ($accounts as $acc) {
                            if (strtolower($acc['group_type']) === strtolower($type)) {
                                $amount = ($type === 'asset') ? max($acc['balance'],0) : abs($acc['balance']);
                                if ($amount != 0) {
                                    echo "<tr>
                                            <td class='ps-4'>{$acc['ledger_name']}</td>
                                            <td class='text-end'>".number_format($amount,2)."</td>
                                          </tr>";
                                    $total += $amount;
                                }
                            }
                        }

                        echo "<tr class='fw-bold'>
                                <td class='text-end'>Total ".ucfirst($type)."</td>
                                <td class='text-end'>".number_format($total,2)."</td>
                              </tr>";
                        echo "<tr><td colspan='2'></td></tr>";
                    }

                    // UI: ASSETS + LIABILITIES
                    section_ui($accounts, 'asset', $asset_total);
                    section_ui($accounts, 'liability', $liability_total);

                    // UI: EQUITY
                    echo "<tr class='table-secondary fw-bold'><td colspan='2'>EQUITY</td></tr>";
                    $equity_total = 0;

                    foreach ($accounts as $acc) {
                        if (strtolower($acc['group_type']) === 'equity') {
                            $amount = abs($acc['balance']);
                            if ($amount != 0) {
                                echo "<tr>
                                        <td class='ps-4'>{$acc['ledger_name']}</td>
                                        <td class='text-end'>".number_format($amount,2)."</td>
                                      </tr>";
                                $equity_total += $amount;
                            }
                        }
                    }

                    // Net Income inside Equity
                    echo "<tr>
                            <td class='ps-4 fw-bold'>Retained Earnings / Net Income</td>
                            <td class='text-end fw-bold ".($net_income>=0?'text-success':'text-danger')."'>
                                ".number_format(abs($net_income),2)."
                            </td>
                          </tr>";

                    $total_equity = $equity_total + $net_income;

                    echo "<tr class='fw-bold'>
                            <td class='text-end'>Total Equity</td>
                            <td class='text-end'>".number_format($total_equity,2)."</td>
                          </tr>";

                    echo "<tr><td colspan='2'></td></tr>";

                    // BALANCE CHECK
                    $liabilities_equity = $liability_total + $total_equity;
                    $difference = abs($asset_total - $liabilities_equity);
                    $balanced = $difference < 0.01;

                    echo "<tr class='fw-bold table-warning'>
                            <td class='text-end'>Total Liabilities + Equity</td>
                            <td class='text-end'>".number_format($liabilities_equity,2)."</td>
                          </tr>";

                    echo "<tr>
                            <td colspan='2' class='text-center fw-bold ".($balanced?'text-success':'text-danger')."'>";
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
