<?php
// =============================
// INCOME STATEMENT (Group-based classification)
// =============================
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// =====================
// 1️⃣ Query all ledgers + their group type + totals
// =====================
$query = "
    SELECT 
        l.ledger_id,
        l.ledger_name,
        g.group_name,
        g.group_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0) AS total_cr
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON ve.ledger_id = l.ledger_id
    LEFT JOIN account_groups g ON g.group_id = l.group_id
    WHERE g.group_type IN ('Income', 'Expense') 
    GROUP BY l.ledger_id, l.ledger_name, g.group_name, g.group_type
    ORDER BY g.group_type, l.ledger_name
";

$result = $conn->query($query);

$accounts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $accounts[] = $row;
}

// Calculate totals for export
$revenue = $expenses = 0;
foreach ($accounts as $acc) {
    if ($acc['group_type'] === 'Income') {
        $amount = $acc['total_cr'] - $acc['total_dr'];
        if ($amount != 0) $revenue += $amount;
    }
    if ($acc['group_type'] === 'Expense') {
        $amount = $acc['total_dr'] - $acc['total_cr'];
        if ($amount != 0) $expenses += $amount;
    }
}
$net_income = $revenue - $expenses;

// =====================
// 2️⃣ EXPORT TO EXCEL (CSV)
// =====================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="income_statement_as_at_' . date('Y-m-d') . '.csv"');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $out = fopen('php://output', 'w');

    // Header
    fputcsv($out, ['Income Statement']);
    fputcsv($out, []); // blank line

    // Income Section
    fputcsv($out, ['INCOME']);
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Income') {
            $amount = $acc['total_cr'] - $acc['total_dr'];
            if ($amount != 0) {
                fputcsv($out, [$acc['ledger_name'], number_format($amount, 2, '.', '')]);
            }
        }
    }
    fputcsv($out, ['Total Income', number_format($revenue, 2, '.', '')]);
    fputcsv($out, []);

    // Expenses Section
    fputcsv($out, ['EXPENSES']);
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Expense') {
            $amount = $acc['total_dr'] - $acc['total_cr'];
            if ($amount != 0) {
                fputcsv($out, [$acc['ledger_name'], number_format($amount, 2, '.', '')]);
            }
        }
    }
    fputcsv($out, ['Total Expenses', number_format($expenses, 2, '.', '')]);
    fputcsv($out, []);

    // Net Income
    $label = $net_income >= 0 ? 'Net Income' : 'Net Loss';
    fputcsv($out, [$label, number_format($net_income, 2, '.', '')]);

    fclose($out);
    exit;
}

// Save net income for SOFP
file_put_contents(__DIR__ . '/last_net_income.txt', $net_income);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Income Statement</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4 bg-light">

<div class="container mb-4">

    <div class="card shadow-lg">

        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📈 Income Statement</h5>

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

                    <!-- INCOME SECTION -->
                    <tr class="table-secondary fw-bold">
                        <td colspan="2">INCOME</td>
                    </tr>

                    <?php
                    $showIncome = false;
                    foreach ($accounts as $acc) {
                        if ($acc['group_type'] === 'Income') {
                            $amount = $acc['total_cr'] - $acc['total_dr'];
                            if ($amount != 0) {
                                $showIncome = true;
                                echo "
                                <tr>
                                    <td class='ps-4'>{$acc['ledger_name']}</td>
                                    <td class='text-end'>" . number_format($amount, 2) . "</td>
                                </tr>";
                            }
                        }
                    }
                    if (!$showIncome) {
                        echo "<tr><td colspan='2' class='text-center text-muted'>No income accounts available</td></tr>";
                    }

                    echo "
                    <tr class='fw-bold'>
                        <td class='text-end'>Total Income</td>
                        <td class='text-end'>" . number_format($revenue, 2) . "</td>
                    </tr>";
                    ?>

                    <tr><td colspan="2"></td></tr>

                    <!-- EXPENSE SECTION -->
                    <tr class="table-secondary fw-bold">
                        <td colspan="2">EXPENSES</td>
                    </tr>

                    <?php
                    $showExpense = false;
                    foreach ($accounts as $acc) {
                        if ($acc['group_type'] === 'Expense') {
                            $amount = $acc['total_dr'] - $acc['total_cr'];
                            if ($amount != 0) {
                                $showExpense = true;
                                echo "
                                <tr>
                                    <td class='ps-4'>{$acc['ledger_name']}</td>
                                    <td class='text-end'>" . number_format($amount, 2) . "</td>
                                </tr>";
                            }
                        }
                    }
                    if (!$showExpense) {
                        echo "<tr><td colspan='2' class='text-center text-muted'>No expense accounts available</td></tr>";
                    }

                    echo "
                    <tr class='fw-bold'>
                        <td class='text-end'>Total Expenses</td>
                        <td class='text-end'>" . number_format($expenses, 2) . "</td>
                    </tr>";
                    ?>

                    <tr><td colspan="2"></td></tr>

                    <!-- NET INCOME -->
                    <?php
                    $cls = $net_income >= 0 ? "text-success" : "text-danger";
                    ?>

                    <tr class="fw-bold table-warning">
                        <td class="text-end">Net Income (Loss)</td>
                        <td class="text-end <?= $cls ?>"><?= number_format($net_income, 2) ?></td>
                    </tr>

                </tbody>
            </table>

        </div>
    </div>
</div>

</body>
</html>
