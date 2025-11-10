<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) die("Connection failed");

$ledger_id = isset($_GET['ledger_id']) ? (int)$_GET['ledger_id'] : 0;

// Fetch ledger name
$ledger = $conn->query("SELECT ledger_name FROM ledgers WHERE ledger_id = $ledger_id")->fetch_assoc();
$ledger_name = $ledger ? $ledger['ledger_name'] : "Unknown Bank";

// Fetch voucher entries for that ledger
$q = $conn->query("
    SELECT ve.entry_id, v.date, v.voucher_type, v.narration, ve.type, ve.amount,
           IFNULL(br.is_cleared, 0) AS cleared, br.cleared_date
    FROM voucher_entries ve
    JOIN vouchers v ON v.voucher_id = ve.voucher_id
    LEFT JOIN bank_reconciliation br ON br.voucher_entry_id = ve.entry_id
    WHERE ve.ledger_id = $ledger_id
    ORDER BY v.date DESC
");

echo "<h5 class='text-primary mb-3'>Reconciliation for: " . htmlspecialchars($ledger_name) . "</h5>";

echo "<table class='table table-bordered table-striped'>";
echo "<thead class='table-secondary'><tr>
        <th>Date</th><th>Type</th><th>Narration</th>
        <th>Dr/Cr</th><th>Amount</th><th>Cleared</th>
      </tr></thead><tbody>";

$total = 0;
$cleared_total = 0;

while($row = $q->fetch_assoc()) {
    $checked = $row['cleared'] ? "checked" : "";
    $total += ($row['type'] === 'Dr' ? $row['amount'] : -$row['amount']);
    if ($row['cleared']) $cleared_total += ($row['type'] === 'Dr' ? $row['amount'] : -$row['amount']);

    echo "<tr>
            <td>{$row['date']}</td>
            <td>{$row['voucher_type']}</td>
            <td>{$row['narration']}</td>
            <td>{$row['type']}</td>
            <td class='text-end'>" . number_format($row['amount'],2) . "</td>
            <td><input type='checkbox' name='cleared[]' value='{$row['entry_id']}' $checked></td>
          </tr>";
}

echo "</tbody></table>";

$uncleared_diff = $total - $cleared_total;

echo "<div class='alert alert-info mt-3'>
        <strong>Book Balance:</strong> " . number_format($total,2) . "<br>
        <strong>Cleared Bank Balance:</strong> " . number_format($cleared_total,2) . "<br>
        <strong>Difference (Uncleared):</strong> " . number_format($uncleared_diff,2) . "
      </div>";
?>
