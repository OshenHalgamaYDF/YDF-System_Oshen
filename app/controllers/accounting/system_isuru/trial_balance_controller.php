<?php
// trial_balance.php - Cleaned / optimized + suspense handling + date range filter
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

// --- Handle date range filter ---
$filter_from = isset($_GET['filter_from']) ? $_GET['filter_from'] : date('Y-01-01');
$filter_to = isset($_GET['filter_to']) ? $_GET['filter_to'] : date('Y-m-d');

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

// --- Query ledgers with net balance calculation (with date range filter) ---
$q = $conn->query("
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.opening_balance,
        l.balance_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' AND v.date >= '$filter_from' AND v.date <= '$filter_to' THEN ve.amount ELSE 0 END),0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' AND v.date >= '$filter_from' AND v.date <= '$filter_to' THEN ve.amount ELSE 0 END),0) AS total_cr,
        (
          (CASE WHEN UPPER(l.balance_type)='DR' THEN COALESCE(l.opening_balance,0) ELSE -COALESCE(l.opening_balance,0) END)
          + COALESCE(SUM(CASE WHEN ve.type='Dr' AND v.date >= '$filter_from' AND v.date <= '$filter_to' THEN ve.amount ELSE 0 END),0)
          - COALESCE(SUM(CASE WHEN ve.type='Cr' AND v.date >= '$filter_from' AND v.date <= '$filter_to' THEN ve.amount ELSE 0 END),0)
        ) AS net_balance
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
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
      COALESCE(SUM(CASE WHEN g.group_type='Income' AND v.date >= '$filter_from' AND v.date <= '$filter_to' THEN ve.amount * (CASE WHEN ve.type='Cr' THEN 1 WHEN ve.type='Dr' THEN -1 ELSE 0 END) ELSE 0 END),0) AS income_net,
      COALESCE(SUM(CASE WHEN g.group_type='Expense' AND v.date >= '$filter_from' AND v.date <= '$filter_to' THEN ve.amount * (CASE WHEN ve.type='Dr' THEN 1 WHEN ve.type='Cr' THEN -1 ELSE 0 END) ELSE 0 END),0) AS expense_net
    FROM voucher_entries ve
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    JOIN account_groups g ON l.group_id = g.group_id
    JOIN vouchers v ON ve.voucher_id = v.voucher_id
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
    header('Content-Disposition: attachment; filename="trial_balance_' . $filter_from . '_to_' . $filter_to . '.csv"');
    // UTF-8 BOM for Excel
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');

    // Header row
    fputcsv($out, ['Trial Balance from ' . $filter_from . ' to ' . $filter_to]);
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