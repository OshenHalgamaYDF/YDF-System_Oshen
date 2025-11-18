<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle suspense account creation/update
$suspense_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_suspense'])) {
    $difference = floatval($_POST['difference'] ?? 0);
    $action = $_POST['action'] ?? 'create';

    if (abs($difference) < 0.01) {
        $suspense_message = '<div class="alert alert-info">✓ Trial Balance is already balanced. No suspense needed.</div>';
    } else {
        // Find or create suspense account
        $suspense_name = 'Suspense Account';
        $stmt = $conn->prepare("SELECT ledger_id FROM ledgers WHERE ledger_name = ? LIMIT 1");
        $stmt->bind_param("s", $suspense_name);
        $stmt->execute();
        $stmt->bind_result($suspense_id);
        $stmt->fetch();
        $stmt->close();

        if (empty($suspense_id)) {
            // Create suspense ledger in a control/suspense group
            $group_name = 'Suspense';
            $gstmt = $conn->prepare("SELECT group_id FROM account_groups WHERE group_name = ? LIMIT 1");
            $gstmt->bind_param("s", $group_name);
            $gstmt->execute();
            $gstmt->bind_result($group_id);
            $gstmt->fetch();
            $gstmt->close();

            if (empty($group_id)) {
                $gin = $conn->prepare("INSERT INTO account_groups (group_name, group_type) VALUES (?, 'Control')");
                $gin->bind_param("s", $group_name);
                $gin->execute();
                $group_id = $gin->insert_id;
                $gin->close();
            }

            $lin = $conn->prepare("INSERT INTO ledgers (ledger_name, opening_balance, balance_type, group_id) VALUES (?, 0, 'Dr', ?)");
            $lin->bind_param("si", $suspense_name, $group_id);
            $lin->execute();
            $suspense_id = $lin->insert_id;
            $lin->close();
            $suspense_message = '<div class="alert alert-success">✓ Suspense account created (ID: ' . $suspense_id . ')</div>';
        }

        // Create voucher to balance
        $today = date('Y-m-d');
        $narration = 'Trial Balance Suspense Adjustment - Difference: ' . number_format(abs($difference), 2);
        $vtype = 'Journal';

        $vstmt = $conn->prepare("INSERT INTO vouchers (voucher_type, date, narration) VALUES (?, ?, ?)");
        $vstmt->bind_param("sss", $vtype, $today, $narration);
        $vstmt->execute();
        $voucher_id = $vstmt->insert_id;
        $vstmt->close();

        // Determine which side is short and post to suspense
        $amount = abs($difference);
        if ($difference > 0) {
            // Dr side has more, so Cr suspense to balance
            $e1 = $conn->prepare("INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount) VALUES (?, ?, 'Cr', ?)");
            $e1->bind_param("iid", $voucher_id, $suspense_id, $amount);
            $e1->execute();
            $e1->close();
            $suspense_message .= '<div class="alert alert-warning">⚠️ Suspense account CREDITED with Rs. ' . number_format($amount, 2) . ' (Voucher #' . $voucher_id . ')</div>';
        } else {
            // Cr side has more, so Dr suspense to balance
            $e1 = $conn->prepare("INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount) VALUES (?, ?, 'Dr', ?)");
            $e1->bind_param("iid", $voucher_id, $suspense_id, $amount);
            $e1->execute();
            $e1->close();
            $suspense_message .= '<div class="alert alert-warning">⚠️ Suspense account DEBITED with Rs. ' . number_format($amount, 2) . ' (Voucher #' . $voucher_id . ')</div>';
        }

        $suspense_message .= '<div class="alert alert-info"><strong>Next Step:</strong> Review the suspense account and investigate the imbalance. Once resolved, delete voucher #' . $voucher_id . ' and re-post the correct entries.</div>';
    }
}

$q = $conn->query("
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.opening_balance,
        l.balance_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END),0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END),0) AS total_cr,
        (
          (CASE WHEN UPPER(l.balance_type)='DR' THEN l.opening_balance ELSE -l.opening_balance END)
          + COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END),0)
          - COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END),0)
        ) AS net_balance
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    GROUP BY l.ledger_id, l.ledger_name, l.opening_balance, l.balance_type
    ORDER BY l.ledger_name ASC
");

$summary = $conn->query("
    SELECT
      COALESCE(SUM(CASE WHEN g.group_type='Income' THEN ve.amount * (CASE WHEN ve.type='Cr' THEN 1 WHEN ve.type='Dr' THEN -1 ELSE 0 END) ELSE 0 END),0) AS income_net,
      COALESCE(SUM(CASE WHEN g.group_type='Expense' THEN ve.amount * (CASE WHEN ve.type='Dr' THEN 1 WHEN ve.type='Cr' THEN -1 ELSE 0 END) ELSE 0 END),0) AS expense_net
    FROM voucher_entries ve
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    JOIN account_groups g ON l.group_id = g.group_id
    WHERE g.group_type IN ('Income','Expense')
");
$summaryData = $summary->fetch_assoc();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Trial Balance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f5f7fa; }
    .container { max-width: 1000px; margin-top: 36px; }
    @media print { .no-print { display:none !important; } }
  </style>
</head>
<body class="p-4">
  <div class="container mb-4">
    <?php if ($suspense_message) echo $suspense_message; ?>

    <div class="card shadow-sm">
      <div class="card-header bg-warning d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0"><i class="bi bi-calculator"></i> Trial Balance</h5>
          <small class="text-muted">Verify debit and credit totals match</small>
        </div>
        <button class="btn btn-light btn-sm no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      </div>

      <div class="card-body table-responsive">
        <table class="table table-bordered table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>Account</th>
              <th class="text-end">Debit (Rs.)</th>
              <th class="text-end">Credit (Rs.)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $totalDr = 0;
            $totalCr = 0;

            if ($q && $q->num_rows > 0) {
                while ($row = $q->fetch_assoc()) {
                    $name = htmlspecialchars($row['ledger_name']);
                    $net = (float)$row['net_balance'];
                    $debit = $net > 0 ? $net : 0;
                    $credit = $net < 0 ? -$net : 0;
                    $totalDr += $debit;
                    $totalCr += $credit;
                    echo "<tr>";
                    echo "<td>{$name}</td>";
                    echo "<td class='text-end'>" . ($debit ? number_format($debit,2) : '') . "</td>";
                    echo "<td class='text-end'>" . ($credit ? number_format($credit,2) : '') . "</td>";
                    echo "</tr>";
                }

                $difference = abs($totalDr - $totalCr);
                $is_balanced = $difference < 0.01;

                echo "<tr class='fw-bold table-light'>";
                echo "<td class='text-end'>Total</td>";
                echo "<td class='text-end'>" . number_format($totalDr,2) . "</td>";
                echo "<td class='text-end'>" . number_format($totalCr,2) . "</td>";
                echo "</tr>";

                echo "<tr>";
                $cls = $is_balanced ? 'text-success' : 'text-danger';
                echo "<td colspan='3' class='text-center " . $cls . "'>";
                if ($is_balanced) {
                    echo "✅ <strong>Trial Balance is BALANCED</strong>";
                } else {
                    echo "❌ <strong>Imbalance Detected:</strong> Difference: Rs. " . number_format($difference,2);
                }
                echo "</td></tr>";
            } else {
                echo "<tr><td colspan='3' class='text-muted text-center'>No ledger data found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>

      <?php if (!$is_balanced && isset($difference)): ?>
      <div class="card-footer bg-light no-print">
        <form method="POST" class="row g-2 align-items-end">
          <input type="hidden" name="create_suspense" value="1">
          <input type="hidden" name="difference" value="<?= $difference ?>">
          <div class="col-auto">
            <p class="text-danger mb-0"><strong>Trial Balance is not balanced by Rs. <?= number_format($difference, 2) ?></strong></p>
          </div>
          <div class="col-auto ms-auto">
            <button type="submit" class="btn btn-warning" onclick="return confirm('Create suspense account to balance the trial balance?\n\nThis will post Rs. ' + '<?= number_format($difference, 2) ?>' + ' to a suspense account.')">
              <i class="bi bi-plus-circle"></i> Create Suspense Account
            </button>
          </div>
        </form>
        <small class="text-muted d-block mt-2">
          💡 <strong>Note:</strong> Suspense accounts are temporary. Investigate the imbalance, correct the entries, then delete the suspense voucher.
        </small>
      </div>
      <?php endif; ?>

      <div class="card-footer text-end no-print">
        <a href="accounting.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>
  </div>
</body>
</html>