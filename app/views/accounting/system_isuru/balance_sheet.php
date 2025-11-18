<?php
// =============================
// BALANCE SHEET (Optimized + Fixed Equity)
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
      COALESCE(SUM(CASE WHEN g.group_type='Income' THEN ve.amount * (CASE WHEN ve.type='Cr' THEN 1 WHEN ve.type='Dr' THEN -1 ELSE 0 END) ELSE 0 END),0) AS income_total,
      COALESCE(SUM(CASE WHEN g.group_type='Expense' THEN ve.amount * (CASE WHEN ve.type='Dr' THEN 1 WHEN ve.type='Cr' THEN -1 ELSE 0 END) ELSE 0 END),0) AS expense_total
    FROM voucher_entries ve
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    JOIN account_groups g ON l.group_id = g.group_id
    WHERE g.group_type IN ('Income','Expense')
");
$income_data = $income_query->fetch_assoc();
$net_income = ($income_data['income_total'] ?? 0) - ($income_data['expense_total'] ?? 0);

// --- Fetch ledgers (Assets, Liabilities, Equity) ---
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
    WHERE COALESCE(ag.group_type,'') IN ('Asset','Liability','Equity')
    GROUP BY l.ledger_id
    ORDER BY ag.group_type, ag.group_name, l.ledger_name
";
$result = $conn->query($query);

$accounts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // --- FIXED BALANCE CALCULATION ---
        if (strtoupper($row['balance_type']) === 'DR') {
            // DR accounts (Assets & Expenses)
            $balance = $row['opening_balance'] + $row['total_dr'] - $row['total_cr'];
        } else {
            // CR accounts (Liabilities & Equity)
            $balance = $row['opening_balance'] + $row['total_cr'] - $row['total_dr'];
        }
        $row['balance'] = $balance;
        $accounts[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Balance Sheet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f5f7fa; }
    .container { max-width: 1000px; margin-top: 36px; }
    @media print { .no-print { display: none !important; } }
  </style>
</head>
<body class="p-4">
<div class="container mb-4">
  <div class="card shadow-sm">
    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#6610f2;">
      <div>
        <h5 class="mb-0"><i class="bi bi-receipt"></i> Balance Sheet</h5>
        <small class="text-white-50">As at <?= date('Y-m-d') ?></small>
      </div>
      <div class="d-flex gap-2 no-print">
        <button class="btn btn-light btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <a href="accounting.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="card-body table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>Account</th>
            <th class="text-end" style="width:180px">Amount (Rs.)</th>
          </tr>
        </thead>
        <tbody>
<?php
function render_section($accounts, $type, &$total) {
    echo "<tr class='fw-bold bg-light'><td colspan='2'><i class='bi'></i> " . strtoupper($type) . "</td></tr>";
    $total = 0;
    foreach ($accounts as $acc) {
        if (strtolower($acc['group_type']) === strtolower($type)) {
            $amount = ($type === 'asset') ? max($acc['balance'],0) : abs($acc['balance']);
            if ($amount != 0) {
                echo "<tr><td class='ps-4'>" . htmlspecialchars($acc['ledger_name']) . "</td>";
                echo "<td class='text-end'>" . number_format($amount,2) . "</td></tr>";
                $total += $amount;
            }
        }
    }
    echo "<tr class='fw-bold'><td class='text-end ps-4'>Total " . ucfirst($type) . "</td>";
    $color = ($type === 'asset') ? 'text-primary' : ($type === 'liability' ? 'text-warning' : 'text-success');
    echo "<td class='text-end $color'>" . number_format($total,2) . "</td></tr>";
    echo "<tr><td colspan='2'></td></tr>";
}

// --- ASSETS ---
render_section($accounts,'asset',$asset_total);

// --- LIABILITIES ---
render_section($accounts,'liability',$liability_total);

// --- EQUITY ---
$equity_accounts = array_filter($accounts, fn($a) => strtolower($a['group_type'])==='equity' && $a['ledger_id']!=44);
$equity_total = 0;
echo "<tr class='fw-bold bg-light'><td colspan='2'><i class='bi bi-piggy-bank'></i> EQUITY</td></tr>";
foreach ($equity_accounts as $acc) {
    $amount = abs($acc['balance']);
    if($amount!=0){
        echo "<tr><td class='ps-4'>" . htmlspecialchars($acc['ledger_name']) . "</td>";
        echo "<td class='text-end'>" . number_format($amount,2) . "</td></tr>";
        $equity_total += $amount;
    }
}
// --- Net Income / Retained Earnings ---
if ($net_income != 0) {
    echo "<tr><td class='ps-4'><strong>Retained Earnings / Net Income</strong></td>";
    echo "<td class='text-end " . ($net_income>=0?'text-success fw-bold':'text-danger fw-bold') . "'>" . number_format(abs($net_income),2) . "</td></tr>";
}
$total_equity = $equity_total + $net_income;
echo "<tr class='fw-bold'><td class='text-end ps-4'>Total Equity</td>";
echo "<td class='text-end text-success'>" . number_format($total_equity,2) . "</td></tr>";
echo "<tr><td colspan='2'></td></tr>";

// --- Balance Check ---
$liabilities_and_equity = $liability_total + $total_equity;
$difference = abs($asset_total - $liabilities_and_equity);
$is_balanced = $difference < 0.01;
echo "<tr class='fw-bold table-light'><td class='text-end'>Total Liabilities + Equity</td>";
echo "<td class='text-end'>" . number_format($liabilities_and_equity,2) . "</td></tr>";
echo "<tr><td colspan='2' class='text-center " . ($is_balanced?'text-success fw-bold':'text-danger fw-bold') . "'>";
echo $is_balanced ? "✅ Balance Sheet is BALANCED" : "❌ Imbalance: Rs. " . number_format($difference,2);
echo "</td></tr>";
?>
        </tbody>
      </table>
    </div>

    <div class="card-footer bg-light">
      <div class="row g-2 text-sm">
        <div class="col-md-4"><strong>Total Assets:</strong><br><span class="text-primary">Rs. <?= number_format($asset_total ?? 0,2) ?></span></div>
        <div class="col-md-4"><strong>Total Liabilities:</strong><br><span class="text-warning">Rs. <?= number_format($liability_total ?? 0,2) ?></span></div>
        <div class="col-md-4"><strong>Total Equity:</strong><br><span class="text-success">Rs. <?= number_format($total_equity ?? 0,2) ?></span></div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
