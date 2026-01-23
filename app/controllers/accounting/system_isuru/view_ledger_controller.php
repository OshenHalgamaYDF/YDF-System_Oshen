<?php
// ========================================
// VIEW LEDGER (combined AJAX partial + full page) with Export + Date range filter
// ========================================
session_start(); // Start session for country selection

$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo "<div class='alert alert-danger'>Database connection failed: " . htmlspecialchars($conn->connect_error) . "</div>";
    exit;
}
$conn->set_charset('utf8mb4');

// ============================================================================
// COUNTRY SELECTION HANDLING
// ============================================================================
if (!isset($_SESSION['country_id'])) {
    $default_country = $conn->query("SELECT id FROM countries ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $_SESSION['country_id'] = $default_country ? $default_country['id'] : 1;
}

$active_country_id = $_SESSION['country_id'];

// Fetch active country details
$country_stmt = $conn->prepare("SELECT country_name, currency_code FROM countries WHERE id = ?");
$country_stmt->bind_param("i", $active_country_id);
$country_stmt->execute();
$active_country = $country_stmt->get_result()->fetch_assoc();
$country_stmt->close();

// --- Helper: validate date ---
function valid_date($d) {
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

// --- Read date range filter (GET) similar to Trial Balance / BRS ---
$filter_from = isset($_GET['filter_from']) && valid_date($_GET['filter_from']) ? $_GET['filter_from'] : date('Y-01-01');
$filter_to   = isset($_GET['filter_to']) && valid_date($_GET['filter_to']) ? $_GET['filter_to'] : date('Y-m-d');

// Accept either 'id' or 'ledger_id' for compatibility
$ledgerIdParam = isset($_GET['ledger_id']) ? 'ledger_id' : (isset($_GET['id']) ? 'id' : null);

// ---------- EXPORT CSV for a single ledger ----------
if ($ledgerIdParam && isset($_GET['export']) && $_GET['export'] === 'excel') {
    $id = intval($_GET[$ledgerIdParam] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        die("Invalid ledger id for export.");
    }

    $stmtL = $conn->prepare("SELECT ledger_name, opening_balance, balance_type FROM ledgers WHERE ledger_id = ? AND country_id = ? LIMIT 1");
    $stmtL->bind_param("ii", $id, $active_country_id);
    $stmtL->execute();
    $resL = $stmtL->get_result();
    if (!$resL || $resL->num_rows === 0) {
        $stmtL->close();
        http_response_code(404);
        die("Ledger not found.");
    }
    $ledger = $resL->fetch_assoc();
    $stmtL->close();

    $ledger_name = $ledger['ledger_name'];
    $opening_balance = (float)$ledger['opening_balance'];
    $balance_type = strtoupper($ledger['balance_type'] ?? 'DR');

    $tstmt = $conn->prepare("
        SELECT v.date, v.voucher_type, ve.type, ve.amount, v.narration, v.voucher_id
        FROM voucher_entries ve
        JOIN vouchers v ON ve.voucher_id = v.voucher_id
        WHERE ve.ledger_id = ? AND v.date >= ? AND v.date <= ? AND v.country_id = ?
        ORDER BY v.date ASC, v.voucher_id ASC
    ");
    $tstmt->bind_param("issi", $id, $filter_from, $filter_to, $active_country_id);
    $safeName = preg_replace('/[^a-z0-9_\-]/i','_', $ledger_name ?: 'ledger_'.$id);
    $filename = "ledger_{$safeName}_" . $filter_from . "_to_" . $filter_to . ".csv";
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');

    fputcsv($out, ["Ledger:",$ledger_name]);
    fputcsv($out, ["Period:", $filter_from . ' to ' . $filter_to]);
    fputcsv($out, []);
    fputcsv($out, ['Date','Voucher Type','Voucher ID','Dr Amount','Cr Amount','Running Balance','Balance Type','Narration']);

    $running_balance = ($balance_type === 'DR') ? $opening_balance : -$opening_balance;
    $totalDr = 0.0;
    $totalCr = 0.0;

    while ($r = $q->fetch_assoc()) {
        $type = $r['type'];
        $amount = (float)$r['amount'];
        $date = $r['date'];
        $voucher_type = $r['voucher_type'];
        $voucher_id = $r['voucher_id'];
        $narration = $r['narration'];

        if ($type === 'Dr') {
            $totalDr += $amount;
            $running_balance += $amount;
            $drAmt = number_format($amount,2,'.','');
            $crAmt = '';
        } else {
            $totalCr += $amount;
            $running_balance -= $amount;
            $drAmt = '';
            $crAmt = number_format($amount,2,'.','');
        }

        $display_balance = number_format(abs($running_balance),2,'.','');
        $balance_side = ($running_balance >= 0) ? 'Dr' : 'Cr';

        fputcsv($out, [$date, $voucher_type, $voucher_id, $drAmt, $crAmt, $display_balance, $balance_side, $narration]);
    }

    fputcsv($out, []);
    fputcsv($out, ['Totals', '', '', number_format($totalDr,2,'.',''), number_format($totalCr,2,'.',''), '', '', '']);
    fputcsv($out, ['Opening Balance', '', '', ($balance_type==='DR' ? number_format($opening_balance,2,'.','') : ''), ($balance_type==='CR' ? number_format($opening_balance,2,'.','') : ''), number_format(abs(($balance_type==='DR'? $opening_balance : -$opening_balance)),2,'.',''), $balance_type, '']);
    fputcsv($out, ['Closing Running Balance', '', '', '', '', number_format(abs($running_balance),2,'.',''), ($running_balance>=0? 'Dr':'Cr'), '']);

    fclose($out);
    $tstmt->close();
    $conn->close();
    exit;
}

// ---------- AJAX partial view (single ledger) ----------
if ($ledgerIdParam) {
    $id = intval($_GET[$ledgerIdParam] ?? 0);
    if ($id <= 0) {
        echo "<div class='alert alert-warning'>Invalid ledger selected.</div>";
        exit;
    }

    $stmtL = $conn->prepare("SELECT ledger_name, opening_balance, balance_type FROM ledgers WHERE ledger_id = ? LIMIT 1");
    $stmtL->bind_param("i", $id);
    $stmtL->execute();
    $resL = $stmtL->get_result();
    if (!$resL || $resL->num_rows === 0) {
        echo "<div class='alert alert-warning'>Ledger not found.</div>";
        $stmtL->close();
        exit;
    }
    $ledger = $resL->fetch_assoc();
    $stmtL->close();

    $ledger_name = htmlspecialchars($ledger['ledger_name']);
    $opening_balance = (float)$ledger['opening_balance'];
    $balance_type = strtoupper($ledger['balance_type'] ?? 'DR');

    $tstmt = $conn->prepare("
        SELECT v.date, v.voucher_type, ve.type, ve.amount, v.narration, v.voucher_id
        FROM voucher_entries ve
        JOIN vouchers v ON ve.voucher_id = v.voucher_id
        WHERE ve.ledger_id = ? AND v.date >= ? AND v.date <= ? AND v.country_id = ?
        ORDER BY v.date ASC, v.voucher_id ASC
    ");
    if (!$tstmt) {
        echo "<div class='alert alert-danger'>Query error: " . htmlspecialchars($conn->error) . "</div>";
        exit;
    }
    $tstmt->bind_param("issi", $id, $filter_from, $filter_to, $active_country_id);
    $totalDr = 0.0;
    $totalCr = 0.0;

    ob_start();
    ?>
    <div class="card mb-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">📘 Ledger: <?= $ledger_name ?></h5>
                <small class="text-white-50">Opening: <?= number_format($opening_balance,2) ?> <?= $balance_type ?></small>
            </div>
            <!-- Export removed here — use the Export Selected (bottom) button with date filter -->
        </div>

        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-6"><strong>Opening Balance:</strong> <?= number_format($opening_balance,2) ?> <?= htmlspecialchars($balance_type) ?></div>
                <div class="col-md-6 text-end"><strong>Current Balance:</strong> <span id="ledgerCurrentBalance"><?= number_format(abs($running_balance),2) ?> <?= ($running_balance >= 0 ? 'Dr' : 'Cr') ?></span></div>
            </div>

            <div class="mb-2 text-muted">Showing entries from <strong><?= htmlspecialchars($filter_from) ?></strong> to <strong><?= htmlspecialchars($filter_to) ?></strong></div>

            <?php if (!$q || $q->num_rows === 0): ?>
            <div class="alert alert-info">No transactions found for this ledger in the selected period.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Voucher Type</th>
                            <th>Voucher ID</th>
                            <th class="text-end">Dr Amount</th>
                            <th class="text-end">Cr Amount</th>
                            <th class="text-end">Balance</th>
                            <th>Narration</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php while ($r = $q->fetch_assoc()):
                        if ($r['type'] === 'Dr') {
                            $totalDr += (float)$r['amount'];
                            $running_balance += (float)$r['amount'];
                        } else {
                            $totalCr += (float)$r['amount'];
                            $running_balance -= (float)$r['amount'];
                        }
                        $display_balance = number_format(abs($running_balance), 2) . ' ' . ($running_balance >= 0 ? 'Dr' : 'Cr');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($r['date']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['voucher_type']) ?></span></td>
                        <td>#<?= htmlspecialchars($r['voucher_id']) ?></td>
                        <td class="text-end"><?= $r['type'] === 'Dr' ? number_format($r['amount'],2) : '' ?></td>
                        <td class="text-end"><?= $r['type'] === 'Cr' ? number_format($r['amount'],2) : '' ?></td>
                        <td class="text-end fw-bold <?= $running_balance >= 0 ? 'text-success' : 'text-danger' ?>"><?= $display_balance ?></td>
                        <td><small><?= htmlspecialchars($r['narration']) ?></small></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
                <tfoot class="fw-bold">
                    <tr>
                        <td colspan="3" class="text-end">Totals</td>
                        <td class="text-end"><?= number_format($totalDr,2) ?></td>
                        <td class="text-end"><?= number_format($totalCr,2) ?></td>
                        <td class="text-end"><?= number_format(abs($running_balance),2) ?> <?= $running_balance>=0?'Dr':'Cr' ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                </table>
            </div>
        <?php endif; ?>
      </div>
    </div>
    <?php

    $html = ob_get_clean();
    echo $html;

    $tstmt->close();
    $conn->close();
    exit;
}

// ---------- Full page ----------
$ledgers_stmt = $conn->prepare("SELECT ledger_id, ledger_name FROM ledgers WHERE country_id = ? ORDER BY ledger_name ASC");
$ledgers_stmt->bind_param("i", $active_country_id);
$ledgers_stmt->execute();
$ledgers = $ledgers_stmt->get_result();
$ledgers_stmt->close();
?>