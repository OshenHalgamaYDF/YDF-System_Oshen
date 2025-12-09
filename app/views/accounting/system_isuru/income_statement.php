<?php
// =============================
// INCOME STATEMENT (Group-based with Date Range Filter)
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

// --- Read date range filter (GET) similar to trial balance / BRS ---
$filter_from = isset($_GET['filter_from']) && valid_date($_GET['filter_from']) ? $_GET['filter_from'] : date('Y-01-01');
$filter_to   = isset($_GET['filter_to']) && valid_date($_GET['filter_to']) ? $_GET['filter_to'] : date('Y-m-d');

// --- Build SQL date condition ---
$fromEsc = $conn->real_escape_string($filter_from);
$toEsc   = $conn->real_escape_string($filter_to);

$dateExpr = "v.date >= '{$fromEsc}' AND v.date <= '{$toEsc}'";

// =====================
// 1️⃣ Query all ledgers + their group type + totals (date filtered)
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
    LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
    LEFT JOIN account_groups g ON g.group_id = l.group_id
    WHERE g.group_type IN ('Income', 'Expense')
      AND $dateExpr
    GROUP BY l.ledger_id, l.ledger_name, g.group_name, g.group_type
    ORDER BY g.group_type, l.ledger_name
";

$result = $conn->query($query);

$accounts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $accounts[] = $row;
}

// --- Calculate totals ---
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
    $filename = 'income_statement_' . $filter_from . '_to_' . $filter_to . '.csv';
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');

    // Header
    $period_label = $filter_from . ' to ' . $filter_to;

    fputcsv($out, ["Income Statement for $period_label"]);
    fputcsv($out, []);

    // Income Section
    fputcsv($out, ['INCOME']);
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Income') {
            $amount = $acc['total_cr'] - $acc['total_dr'];
            if ($amount != 0) fputcsv($out, [$acc['ledger_name'], number_format($amount, 2, '.', '')]);
        }
    }
    fputcsv($out, ['Total Income', number_format($revenue, 2, '.', '')]);
    fputcsv($out, []);

    // Expenses Section
    fputcsv($out, ['EXPENSES']);
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Expense') {
            $amount = $acc['total_dr'] - $acc['total_cr'];
            if ($amount != 0) fputcsv($out, [$acc['ledger_name'], number_format($amount, 2, '.', '')]);
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

// --- Save net income for SOFP ---
file_put_contents(__DIR__ . '/last_net_income.txt', $net_income);
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
            <h5 class="mb-0">📈 Income Statement</h5>
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
