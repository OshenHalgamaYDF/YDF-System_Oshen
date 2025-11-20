<?php
// trial_balance.php - Cleaned / optimized + suspense handling
// ---------------------------------------------------------
// Assumes tables: account_groups(group_id, group_name, group_type),
//                 ledgers(ledger_id, ledger_name, opening_balance, balance_type, group_id),
//                 vouchers(voucher_id, voucher_type, date, narration),
//                 voucher_entries(entry_id, voucher_id, ledger_id, type, amount)
// ---------------------------------------------------------

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($servername, $username, $password, $database);
$conn->set_charset('utf8mb4');

$suspense_message = '';

// --- Handle create suspense POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_suspense'])) {
    // read posted difference
    $difference = isset($_POST['difference']) ? (float) $_POST['difference'] : 0.0;
    if (abs($difference) < 0.01) {
        $suspense_message = '<div class="alert alert-info">✓ Trial Balance is already balanced. No suspense needed.</div>';
    } else {
        $amount = round(abs($difference), 2);
        $today  = date('Y-m-d');
        $narration = 'Trial Balance Suspense Adjustment - Difference: ' . number_format($amount, 2);
        $suspense_name = 'Suspense Account';
        $group_name = 'Suspense';

        try {
            $conn->begin_transaction();

            // 1) ensure account_groups entry exists (group_type = 'Suspense')
            $gstmt = $conn->prepare("SELECT group_id FROM account_groups WHERE group_name = ? LIMIT 1");
            $gstmt->bind_param("s", $group_name);
            $gstmt->execute();
            $gstmt->bind_result($group_id);
            $gstmt->fetch();
            $gstmt->close();

            if (empty($group_id)) {
                $gin = $conn->prepare("INSERT INTO account_groups (group_name, group_type) VALUES (?, 'Suspense')");
                $gin->bind_param("s", $group_name);
                $gin->execute();
                $group_id = $gin->insert_id;
                $gin->close();
            }

            // 2) ensure suspense ledger exists
            $lstmt = $conn->prepare("SELECT ledger_id FROM ledgers WHERE ledger_name = ? LIMIT 1");
            $lstmt->bind_param("s", $suspense_name);
            $lstmt->execute();
            $lstmt->bind_result($suspense_id);
            $lstmt->fetch();
            $lstmt->close();

            if (empty($suspense_id)) {
                // opening_balance 0, keep balance_type 'Dr' (neutral for zero)
                $lin = $conn->prepare("INSERT INTO ledgers (ledger_name, opening_balance, balance_type, group_id) VALUES (?, 0, 'Dr', ?)");
                $lin->bind_param("si", $suspense_name, $group_id);
                $lin->execute();
                $suspense_id = $lin->insert_id;
                $lin->close();
                $suspense_message .= '<div class="alert alert-success">✓ Suspense account created (ID: ' . intval($suspense_id) . ')</div>';
            }

            // 3) create voucher & entry to balance TB
            $vstmt = $conn->prepare("INSERT INTO vouchers (voucher_type, date, narration) VALUES (?, ?, ?)");
            $vtype = 'Journal';
            $vstmt->bind_param("sss", $vtype, $today, $narration);
            $vstmt->execute();
            $voucher_id = $vstmt->insert_id;
            $vstmt->close();

            // Post to suspense on correct side: If totalDr > totalCr then difference >0 -> CREDIT suspense
            if ($difference > 0) {
                $e1 = $conn->prepare("INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount) VALUES (?, ?, 'Cr', ?)");
                $e1->bind_param("iid", $voucher_id, $suspense_id, $amount);
                $e1->execute();
                $e1->close();
                $suspense_message .= '<div class="alert alert-warning">⚠️ Suspense account CREDITED with Rs. ' . number_format($amount,2) . ' (Voucher #' . intval($voucher_id) . ')</div>';
            } else {
                $e1 = $conn->prepare("INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount) VALUES (?, ?, 'Dr', ?)");
                $e1->bind_param("iid", $voucher_id, $suspense_id, $amount);
                $e1->execute();
                $e1->close();
                $suspense_message .= '<div class="alert alert-warning">⚠️ Suspense account DEBITED with Rs. ' . number_format($amount,2) . ' (Voucher #' . intval($voucher_id) . ')</div>';
            }

            $suspense_message .= '<div class="alert alert-info"><strong>Next Step:</strong> Investigate the suspense account and correct the underlying entries. To remove suspense, delete the voucher #' . intval($voucher_id) . ' and post corrected entries.</div>';

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $suspense_message = '<div class="alert alert-danger">❌ Error creating suspense: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// --- Query ledgers with net balance calculation ---
// We'll show only non-zero balances (ABS > 0.009) to keep TB clean, but always include Suspense Account if present.
$q = $conn->query("
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.opening_balance,
        l.balance_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END),0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END),0) AS total_cr,
        (
          (CASE WHEN UPPER(l.balance_type)='DR' THEN COALESCE(l.opening_balance,0) ELSE -COALESCE(l.opening_balance,0) END)
          + COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END),0)
          - COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END),0)
        ) AS net_balance
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    GROUP BY l.ledger_id, l.ledger_name, l.opening_balance, l.balance_type
    HAVING ABS(net_balance) > 0.009 OR LOWER(l.ledger_name) = 'suspense account'
    ORDER BY l.ledger_name ASC
");

$ledgers = [];
if ($q && $q->num_rows > 0) {
    while ($row = $q->fetch_assoc()) {
        $row['net_balance'] = (float) $row['net_balance'];
        $ledgers[] = $row;
    }
}

// --- Income / Expense summary for Net Income (Retained earnings) ---
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
$income_net  = floatval($summaryData['income_net'] ?? 0);
$expense_net = floatval($summaryData['expense_net'] ?? 0);
$net_income   = $income_net - $expense_net;

// --- Export to Excel (CSV) if requested ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Send a UTF-8 CSV that Excel can open
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="trial_balance_as_at_' . date('Y-m-d') . '.csv"');
    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');

    // Header row
    fputcsv($out, ['Trial Balance as at ' . date('Y-m-d')]);
    fputcsv($out, ['Account', 'Debit (Rs.)', 'Credit (Rs.)']);

    $totalDr = 0.0;
    $totalCr = 0.0;

    foreach ($ledgers as $row) {
        $name = $row['ledger_name'];
        $net  = (float)$row['net_balance'];

        $debit  = $net > 0 ? $net : 0.0;
        $credit = $net < 0 ? -$net : 0.0;

        $totalDr += $debit;
        $totalCr += $credit;

        fputcsv($out, [$name, number_format($debit, 2, '.', ''), number_format($credit, 2, '.', '')]);
    }

    // Totals
    fputcsv($out, ['Total', number_format($totalDr, 2, '.', ''), number_format($totalCr, 2, '.', '')]);

    $difference = abs($totalDr - $totalCr);
    $is_balanced = $difference < 0.01;
    $status = $is_balanced ? 'Trial Balance is BALANCED' : 'Imbalance Detected: Difference Rs. ' . number_format($difference, 2);

    fputcsv($out, ['Status', $status, '']);

    fclose($out);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Trial Balance</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f5f7fa; }
    .container { max-width: 1000px; margin-top: 36px; }
    @media print { .no-print { display:none !important; } }
    .muted-small { font-size:0.85rem; color:#666; }
  </style>
</head>
<body class="p-4">
  <div class="container mb-4">
    <?php if ($suspense_message) echo $suspense_message; ?>

    <div class="card shadow-sm">
      <div class="card-header bg-warning d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0"><i class="bi bi-calculator"></i> Trial Balance</h5>
          <small class="muted-small">Verify debit and credit totals match</small>
        </div>
        <div class="d-flex gap-2 no-print">
          <!-- Changed: Download Excel instead of browser print -->
          <a href="?export=excel" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Download Excel</a>
          <a href="accounting.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
      </div>

      <div class="card-body table-responsive">
        <table class="table table-bordered table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Account</th>
              <th class="text-end" style="width:180px">Debit (Rs.)</th>
              <th class="text-end" style="width:180px">Credit (Rs.)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $totalDr = 0.0;
            $totalCr = 0.0;

            if (!empty($ledgers)) {
                foreach ($ledgers as $row) {
                    $name = htmlspecialchars($row['ledger_name']);
                    $net  = (float)$row['net_balance'];

                    // net > 0 => Debit balance; net < 0 => Credit balance
                    $debit  = $net > 0 ? $net : 0.0;
                    $credit = $net < 0 ? -$net : 0.0;

                    // accumulate totals
                    $totalDr += $debit;
                    $totalCr += $credit;

                    echo "<tr>";
                    echo "<td>" . $name . "</td>";
                    echo "<td class='text-end'>" . ($debit ? number_format($debit,2) : '') . "</td>";
                    echo "<td class='text-end'>" . ($credit ? number_format($credit,2) : '') . "</td>";
                    echo "</tr>";
                }

                // totals
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
          <input type="hidden" name="difference" value="<?= htmlspecialchars($difference) ?>">
          <div class="col-auto">
            <p class="text-danger mb-0"><strong>Trial Balance is not balanced by Rs. <?= number_format($difference, 2) ?></strong></p>
            <small class="muted-small">If you're unsure why, check recent vouchers or run the ledger detail for unusual entries.</small>
          </div>
          <div class="col-auto ms-auto">
            <button type="submit" class="btn btn-warning" onclick="return confirm('Create suspense account to balance the trial balance? This will post Rs. <?= number_format($difference,2) ?> to a suspense account.')">
              <i class="bi bi-plus-circle"></i> Create Suspense Account
            </button>
          </div>
        </form>
        <small class="text-muted d-block mt-2">
          💡 <strong>Note:</strong> Suspense accounts are temporary. Investigate the imbalance, correct the entries, then delete the suspense voucher.
        </small>
      </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>