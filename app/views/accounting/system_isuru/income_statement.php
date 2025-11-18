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
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0">📈 Income Statement</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-danger">
            <tr>
              <th>Account</th>
              <th class="text-end">Amount (Rs.)</th>
            </tr>
          </thead>
          <tbody>
<?php
$revenue = $expenses = 0;

if ($result && $result->num_rows > 0) {
    $accounts = [];
    while ($row = $result->fetch_assoc()) $accounts[] = $row;

    // --- INCOME ---
    echo "<tr class='fw-bold bg-light'><td colspan='2'>💵 INCOME</td></tr>";
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Income') {
            $name = htmlspecialchars($acc['ledger_name']);
            $amount = $acc['total_cr'] - $acc['total_dr'];
            if ($amount != 0) {
                $revenue += $amount;
                echo "<tr><td class='ps-4'>{$name}</td><td class='text-end'>" . number_format($amount, 2) . "</td></tr>";
            }
        }
    }
    echo "<tr class='fw-bold'><td class='text-end'>Total Income</td><td class='text-end'>" . number_format($revenue, 2) . "</td></tr>";
    echo "<tr><td colspan='2'></td></tr>";

    // --- EXPENSES ---
    echo "<tr class='fw-bold bg-light'><td colspan='2'>🏢 EXPENSES</td></tr>";
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Expense') {
            $name = htmlspecialchars($acc['ledger_name']);
            $amount = $acc['total_dr'] - $acc['total_cr'];
            if ($amount != 0) {
                $expenses += $amount;
                echo "<tr><td class='ps-4'>{$name}</td><td class='text-end'>" . number_format($amount, 2) . "</td></tr>";
            }
        }
    }
    echo "<tr class='fw-bold'><td class='text-end'>Total Expenses</td><td class='text-end'>" . number_format($expenses, 2) . "</td></tr>";

    echo "<tr><td colspan='2'></td></tr>";

    // --- NET PROFIT / LOSS ---
    $net_income = $revenue - $expenses;
    $cls = $net_income >= 0 ? 'text-success' : 'text-danger';
    echo "<tr class='fw-bold table-warning'><td class='text-end'>Net Income (Loss)</td><td class='text-end {$cls}'>" . number_format($net_income, 2) . "</td></tr>";

    // Save net income for future use (e.g., SOFP)
    file_put_contents(__DIR__ . '/last_net_income.txt', $net_income);
} else {
    echo "<tr><td colspan='2' class='text-center text-muted'>No income statement data found.</td></tr>";
}
?>
          </tbody>
        </table>
      </div>
      <div class="card-footer text-end">
        <a href="accounting.php" class="btn btn-secondary">← Back</a>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
      </div>
    </div>
  </div>
</body>
</html>
