<?php
// =============================
// ledger_view.php
// =============================

// ---- DB Connection ----
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}

// ---- Get Ledger ID ----
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<div class='alert alert-danger'>Invalid ledger selected.</div>";
    exit;
}

// ---- Fetch Ledger Details ----
$ledger_info = $conn->query("
    SELECT ledger_name, opening_balance, balance_type 
    FROM ledgers 
    WHERE ledger_id = $id
");

if (!$ledger_info || $ledger_info->num_rows === 0) {
    echo "<div class='alert alert-warning'>Ledger not found.</div>";
    exit;
}

$ledger = $ledger_info->fetch_assoc();
$ledger_name = htmlspecialchars($ledger['ledger_name']);
$opening_balance = $ledger['opening_balance'];
$balance_type = $ledger['balance_type'];

// ---- Fetch Ledger Transactions ----
$transactions_query = $conn->prepare("
    SELECT v.date, v.voucher_type, ve.type, ve.amount, v.narration, v.voucher_id
    FROM voucher_entries ve
    JOIN vouchers v ON ve.voucher_id = v.voucher_id
    WHERE ve.ledger_id = ?
    ORDER BY v.date ASC, v.voucher_id ASC
");

if (!$transactions_query) {
    echo "<div class='alert alert-danger'>Database query error: " . $conn->error . "</div>";
    exit;
}

$transactions_query->bind_param("i", $id);
$transactions_query->execute();
$q = $transactions_query->get_result();

// ---- Calculate Running Balance ----
$running_balance = $balance_type === 'Dr' ? $opening_balance : -$opening_balance;

// ---- Display Ledger Header ----
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h5 class='mb-0'>📘 Ledger: " . $ledger_name . "</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<div class='row'>";
echo "<div class='col-md-6'><strong>Opening Balance:</strong> " . number_format($opening_balance, 2) . " " . $balance_type . "</div>";
echo "<div class='col-md-6'><strong>Current Balance:</strong> <span id='currentBalance'>Calculating...</span></div>";
echo "</div>";
echo "</div>";
echo "</div>";

if (!$q || $q->num_rows === 0) {
    echo "<div class='alert alert-info'>No transactions found for this ledger.</div>";
    $transactions_query->close();
    $conn->close();
    exit;
}

// ---- Output Ledger Transactions ----
echo "<div class='table-responsive'>";
echo "<table class='table table-sm table-bordered table-hover'>";
echo "<thead class='table-light'>";
echo "<tr>
        <th>Date</th>
        <th>Voucher Type</th>
        <th>Voucher ID</th>
        <th>Dr Amount</th>
        <th>Cr Amount</th>
        <th>Balance</th>
        <th>Narration</th>
      </tr>";
echo "</thead>";
echo "<tbody>";

$totalDr = 0;
$totalCr = 0;
$transactions = [];

while ($r = $q->fetch_assoc()) {
    $transactions[] = $r;
    
    if ($r['type'] === 'Dr') {
        $totalDr += $r['amount'];
        $running_balance += $r['amount'];
    } else {
        $totalCr += $r['amount'];
        $running_balance -= $r['amount'];
    }
    
    $balance_type = $running_balance >= 0 ? 'Dr' : 'Cr';
    $display_balance = abs($running_balance);
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($r['date']) . "</td>";
    echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($r['voucher_type']) . "</span></td>";
    echo "<td>#" . htmlspecialchars($r['voucher_id']) . "</td>";
    echo "<td class='text-end'>" . ($r['type'] === 'Dr' ? number_format($r['amount'], 2) : "-") . "</td>";
    echo "<td class='text-end'>" . ($r['type'] === 'Cr' ? number_format($r['amount'], 2) : "-") . "</td>";
    echo "<td class='text-end fw-bold " . ($running_balance >= 0 ? 'text-success' : 'text-danger') . "'>";
    echo number_format($display_balance, 2) . " " . $balance_type;
    echo "</td>";
    echo "<td><small>" . htmlspecialchars($r['narration']) . "</small></td>";
    echo "</tr>";
}

echo "</tbody>";
echo "<tfoot class='table-secondary'>";
echo "<tr class='fw-bold'>";
echo "<td colspan='3' class='text-end'>Totals:</td>";
echo "<td class='text-end text-success'>" . number_format($totalDr, 2) . "</td>";
echo "<td class='text-end text-danger'>" . number_format($totalCr, 2) . "</td>";
echo "<td class='text-end'>" . number_format(abs($running_balance), 2) . " " . ($running_balance >= 0 ? 'Dr' : 'Cr') . "</td>";
echo "<td></td>";
echo "</tr>";

// Opening Balance Row
echo "<tr class='fw-bold table-info'>";
echo "<td colspan='3' class='text-end'>Opening Balance:</td>";
echo "<td class='text-end'>" . ($balance_type === 'Dr' ? number_format($opening_balance, 2) : "-") . "</td>";
echo "<td class='text-end'>" . ($balance_type === 'Cr' ? number_format($opening_balance, 2) : "-") . "</td>";
echo "<td class='text-end'>" . number_format($opening_balance, 2) . " " . $balance_type . "</td>";
echo "<td></td>";
echo "</tr>";

echo "</tfoot>";
echo "</table>";
echo "</div>";

// ---- Summary Card ----
echo "<div class='card mt-3'>";
echo "<div class='card-body'>";
echo "<div class='row text-center'>";
echo "<div class='col-md-3'>";
echo "<div class='border rounded p-2 bg-light'>";
echo "<h6 class='mb-1'>Total Debit</h6>";
echo "<h4 class='text-success mb-0'>" . number_format($totalDr, 2) . "</h4>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<div class='border rounded p-2 bg-light'>";
echo "<h6 class='mb-1'>Total Credit</h6>";
echo "<h4 class='text-danger mb-0'>" . number_format($totalCr, 2) . "</h4>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<div class='border rounded p-2 bg-light'>";
echo "<h6 class='mb-1'>Opening Balance</h6>";
echo "<h4 class='text-info mb-0'>" . number_format($opening_balance, 2) . " " . $balance_type . "</h4>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-3'>";
echo "<div class='border rounded p-2 " . ($running_balance >= 0 ? 'bg-success text-white' : 'bg-danger text-white') . "'>";
echo "<h6 class='mb-1'>Current Balance</h6>";
echo "<h4 class='mb-0'>" . number_format(abs($running_balance), 2) . " " . ($running_balance >= 0 ? 'Dr' : 'Cr') . "</h4>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// JavaScript to update current balance
echo "<script>document.getElementById('currentBalance').textContent = '" . number_format(abs($running_balance), 2) . " " . ($running_balance >= 0 ? 'Dr' : 'Cr') . "';</script>";

$transactions_query->close();
$conn->close();
?>