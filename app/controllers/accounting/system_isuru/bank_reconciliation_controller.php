<?php
// ========================================
// BANK RECONCILIATION (combined view + AJAX endpoint + ledger export)
// Added date range filter (filter_from, filter_to) to AJAX ledger view and export
// ========================================
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// --- Date range filter (from/to) ---
$filter_from = isset($_GET['filter_from']) && $_GET['filter_from'] ? $_GET['filter_from'] : date('Y-01-01');
$filter_to   = isset($_GET['filter_to']) && $_GET['filter_to'] ? $_GET['filter_to'] : date('Y-m-d');

// --- AJAX: Update Reconciliation Status (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brs'])) {
    header('Content-Type: application/json; charset=utf-8');
    $entry_id = intval($_POST['entry_id'] ?? 0);
    $is_cleared = intval($_POST['is_cleared'] ?? 0);
    $cleared_date = $is_cleared ? date('Y-m-d') : null;

    if ($entry_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid entry_id']);
        exit;
    }

    // Check if reconciliation row exists
    $check = $conn->prepare("SELECT reconciliation_id FROM bank_reconciliation WHERE voucher_entry_id = ?");
    $check->bind_param("i", $entry_id);
    $check->execute();
    $check->store_result();
    $exists = $check->num_rows > 0;
    $check->bind_result($recon_id);
    $check->fetch();
    $check->close();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE bank_reconciliation SET is_cleared = ?, cleared_date = ? WHERE voucher_entry_id = ?");
        $stmt->bind_param("isi", $is_cleared, $cleared_date, $entry_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO bank_reconciliation (voucher_entry_id, is_cleared, cleared_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $entry_id, $is_cleared, $cleared_date);
    }

    $success = $stmt->execute();
    $error = $stmt->error;
    $stmt->close();

    echo json_encode(['success' => $success, 'error' => $success ? null : $error]);
    exit;
}

// --- If ledger_id is provided and export=excel => produce CSV for that ledger only (respect date range) ---
if (isset($_GET['export']) && $_GET['export'] === 'excel' && isset($_GET['ledger_id'])) {
    $ledger_id = intval($_GET['ledger_id']);
    if ($ledger_id <= 0) {
        http_response_code(400);
        die('Invalid ledger_id for export.');
    }

    // Prepare SQL to fetch all voucher entries for ledger with reconciliation info, limited by date range
    $sql = "
        SELECT ve.entry_id, v.date, v.narration, ve.type, ve.amount,
               COALESCE(br.is_cleared, 0) AS is_cleared, br.cleared_date
        FROM voucher_entries ve
        JOIN vouchers v ON ve.voucher_id = v.voucher_id
        LEFT JOIN bank_reconciliation br ON br.voucher_entry_id = ve.entry_id
        WHERE ve.ledger_id = ? AND v.date >= ? AND v.date <= ?
        ORDER BY v.date DESC, ve.entry_id DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $ledger_id, $filter_from, $filter_to);
    $stmt->execute();
    $res = $stmt->get_result();

    // Fetch ledger name for file naming
    $lstmt = $conn->prepare("SELECT ledger_name FROM ledgers WHERE ledger_id = ? LIMIT 1");
    $lstmt->bind_param("i", $ledger_id);
    $lstmt->execute();
    $lstmt->bind_result($ledger_name);
    $lstmt->fetch();
    $lstmt->close();

    // CSV headers
    header('Content-Type: text/csv; charset=UTF-8');
    $safeName = $ledger_name ? preg_replace('/[^a-z0-9_\-]/i','_', $ledger_name) : $ledger_id;
    $filename = 'bank_reconciliation_' . $safeName . '_' . $filter_from . '_to_' . $filter_to . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // UTF-8 BOM so Excel recognizes encoding
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Top header
    fputcsv($out, ['Bank Reconciliation Statement']);
    fputcsv($out, ['Ledger', $ledger_name ?: "Ledger #{$ledger_id}"]);
    fputcsv($out, ['Period', $filter_from . ' to ' . $filter_to]);
    fputcsv($out, []); // blank line

    // Columns
    fputcsv($out, ['Date', 'Narration', 'Type', 'Amount (Rs.)', 'Cleared (Yes/No)', 'Cleared Date']);

    $cleared_total = 0.0;
    $uncleared_total = 0.0;

    while ($row = $res->fetch_assoc()) {
        $date = $row['date'];
        $narration = $row['narration'];
        $type = $row['type'];
        $amount = (float)$row['amount'];
        $is_cleared = (int)$row['is_cleared'];
        $cleared_date = $row['cleared_date'] ?: '';

        fputcsv($out, [$date, $narration, $type, number_format($amount, 2, '.', ''), $is_cleared ? 'Yes' : 'No', $cleared_date]);

        if ($is_cleared) $cleared_total += $amount; else $uncleared_total += $amount;
    }

    // Totals
    fputcsv($out, []);
    fputcsv($out, ['Cleared Total', number_format($cleared_total, 2, '.', '')]);
    fputcsv($out, ['Uncleared Total', number_format($uncleared_total, 2, '.', '')]);

    fclose($out);
    exit;
}

// --- Load ledger data (GET) for AJAX view (same behavior as before) but respect date range ---
if (isset($_GET['ledger_id'])) {
    $ledger_id = intval($_GET['ledger_id']);
    if ($ledger_id <= 0) {
        echo '<div class="alert alert-warning">Invalid ledger selected.</div>';
        exit;
    }

    $sql = "
        SELECT ve.entry_id, v.date, v.narration, ve.type, ve.amount,
               COALESCE(br.is_cleared, 0) AS is_cleared, br.cleared_date
        FROM voucher_entries ve
        JOIN vouchers v ON ve.voucher_id = v.voucher_id
        LEFT JOIN bank_reconciliation br ON br.voucher_entry_id = ve.entry_id
        WHERE ve.ledger_id = ? AND v.date >= ? AND v.date <= ?
        ORDER BY v.date DESC, ve.entry_id DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $ledger_id, $filter_from, $filter_to);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo '<p class="text-muted text-center my-3">No entries found for this bank account in the selected period.</p>';
        $stmt->close();
        exit;
    }

    $cleared_total = 0.0;
    $uncleared_total = 0.0;

    echo '<div class="table-responsive">';
    echo '<div class="mb-2 text-muted">Showing entries from <strong>' . htmlspecialchars($filter_from) . '</strong> to <strong>' . htmlspecialchars($filter_to) . '</strong></div>';
    echo '<table class="table table-bordered align-middle shadow-sm">';
    echo '<thead class="table-success text-center">';
    echo '<tr><th>Date</th><th>Narration</th><th>Type</th><th class="text-end">Amount (Rs)</th><th class="no-print">Cleared</th></tr>';
    echo '</thead><tbody>';

    while ($row = $res->fetch_assoc()) {
        $eid = (int)$row['entry_id'];
        $date = htmlspecialchars($row['date']);
        $narration = htmlspecialchars($row['narration']);
        $type = htmlspecialchars($row['type']);
        $amount = (float)$row['amount'];
        $is_cleared = (int)$row['is_cleared'];

        if ($is_cleared) $cleared_total += $amount; else $uncleared_total += $amount;

        echo '<tr class="'.($is_cleared ? 'table-light' : '').'">';
        echo "<td>{$date}</td>";
        echo "<td>{$narration}</td>";
        echo "<td class='text-center'>{$type}</td>";
        echo "<td class='text-end fw-semibold'>" . number_format($amount, 2) . "</td>";
        echo "<td class='text-center no-print'>
                <input type='checkbox' class='form-check-input brs-checkbox' data-entry-id='{$eid}' " . ($is_cleared ? 'checked' : '') . ">
              </td>";
        echo '</tr>';
    }

    echo '</tbody>';
    echo '<tfoot class="fw-bold">';
    echo '<tr><td colspan="3" class="text-end text-success">Cleared Total</td><td class="text-end text-success">' . number_format($cleared_total, 2) . '</td><td></td></tr>';
    echo '<tr><td colspan="3" class="text-end text-danger">Uncleared Total</td><td class="text-end text-danger">' . number_format($uncleared_total, 2) . '</td><td></td></tr>';
    echo '</tfoot></table></div>';

    $stmt->close();
    exit;
}

// --- Fetch all bank ledgers for the select dropdown ---
$bankLedgers = $conn->query("
    SELECT l.ledger_id, l.ledger_name 
    FROM ledgers l
    JOIN account_groups ag ON l.group_id = ag.group_id
    WHERE ag.group_name = 'Bank Accounts'
    ORDER BY l.ledger_name ASC
");
?>