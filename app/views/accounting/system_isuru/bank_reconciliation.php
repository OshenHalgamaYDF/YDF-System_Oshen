<?php
// ========================================
// BANK RECONCILIATION (combined view + AJAX endpoint + ledger export)
// Option A: Export only the visible ledger entries to Excel (CSV)
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

// --- If ledger_id is provided and export=excel => produce CSV for that ledger only ---
if (isset($_GET['export']) && $_GET['export'] === 'excel' && isset($_GET['ledger_id'])) {
    $ledger_id = intval($_GET['ledger_id']);
    if ($ledger_id <= 0) {
        http_response_code(400);
        die('Invalid ledger_id for export.');
    }

    // Prepare SQL to fetch all voucher entries for ledger with reconciliation info
    $sql = "
        SELECT ve.entry_id, v.date, v.narration, ve.type, ve.amount,
               COALESCE(br.is_cleared, 0) AS is_cleared, br.cleared_date
        FROM voucher_entries ve
        JOIN vouchers v ON ve.voucher_id = v.voucher_id
        LEFT JOIN bank_reconciliation br ON br.voucher_entry_id = ve.entry_id
        WHERE ve.ledger_id = ?
        ORDER BY v.date DESC, ve.entry_id DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ledger_id);
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
    $filename = 'bank_reconciliation_' . ($ledger_name ? preg_replace('/[^a-z0-9_\-]/i','_', $ledger_name) : $ledger_id) . '_' . date('Ymd') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // UTF-8 BOM so Excel recognizes encoding
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Top header
    fputcsv($out, ['Bank Reconciliation Statement']);
    fputcsv($out, ['Ledger', $ledger_name ?: "Ledger #{$ledger_id}"]);
    fputcsv($out, ['As at', date('Y-m-d')]);
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

// --- Load ledger data (GET) for AJAX view (same behavior as before) ---
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
        WHERE ve.ledger_id = ?
        ORDER BY v.date DESC, ve.entry_id DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ledger_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo '<p class="text-muted text-center my-3">No entries found for this bank account.</p>';
        $stmt->close();
        exit;
    }

    $cleared_total = 0.0;
    $uncleared_total = 0.0;

    echo '<div class="table-responsive">';
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
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Bank Reconciliation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
  background: #f5f7fa;
  font-family: "Segoe UI", sans-serif;
}
.container {
  max-width: 1000px;
  margin-top: 36px;
}
.card {
  border: none;
  border-radius: 12px;
}
.card-header {
  background: linear-gradient(90deg, #0d6efd 0%, #6610f2 100%);
  color: #fff;
}
.table thead th {
  background: #e9f7ff;
}
.table tbody tr:hover {
  background-color: #f8f9fa;
}
select.form-select {
  border-radius: 8px;
  border-color: #ced4da;
  padding: 10px;
}
@media print {
  .no-print { display: none !important; }
  body { background: white; }
}
</style>
</head>
<body>

<div class="container mb-5">
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0"><i class="bi bi-bank"></i> Bank Reconciliation Statement</h5>
        <small class="text-white-50">Reconcile bank ledger entries and mark cleared items</small>
      </div>

      <div class="d-flex gap-2 no-print">
        <!-- Download Excel will include ledger_id param dynamically via JS -->
        <a id="brsExportBtn" href="#" class="btn btn-light btn-sm">
          <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
        <a href="accounting.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="card-body">
      <label for="brsLedgerSelect" class="form-label fw-semibold">Select Bank Account:</label>
      <select id="brsLedgerSelect" class="form-select mb-4 no-print">
        <option value="">-- Choose Bank Ledger --</option>
        <?php while ($bank = $bankLedgers->fetch_assoc()): ?>
          <option value="<?= $bank['ledger_id'] ?>"><?= htmlspecialchars($bank['ledger_name']) ?></option>
        <?php endwhile; ?>
      </select>

      <div id="brsDet" class="mt-4 text-center text-muted">
        <p>Select a bank account to view reconciliation entries...</p>
      </div>
    </div>

    <div class="card-footer text-end bg-light no-print">
      <small class="text-muted">Tip: Mark cleared items and then export the ledger to Excel for your records.</small>
    </div>
  </div>
</div>

<script>
const brsLedgerSelect = document.getElementById('brsLedgerSelect');
const brsDet = document.getElementById('brsDet');
const brsExportBtn = document.getElementById('brsExportBtn');

// Build export link when ledger selected
function updateExportLink(ledgerId) {
  if (!ledgerId) {
    brsExportBtn.setAttribute('href', '#');
    brsExportBtn.classList.add('disabled');
  } else {
    // point to the same script with export & ledger_id params
    const url = window.location.pathname + '?export=excel&ledger_id=' + encodeURIComponent(ledgerId);
    brsExportBtn.setAttribute('href', url);
    brsExportBtn.classList.remove('disabled');
  }
}

// Load reconciliation for selected ledger
function loadBankReconciliation(ledgerId) {
  if (!ledgerId) {
    brsDet.innerHTML = '<p class="text-muted">Select a bank account to view entries...</p>';
    updateExportLink(null);
    return;
  }
  updateExportLink(ledgerId);
  brsDet.innerHTML = `
    <div class="text-center text-primary my-4">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Loading reconciliation data...
    </div>`;
  fetch(window.location.pathname + '?ledger_id=' + encodeURIComponent(ledgerId))
    .then(res => {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.text();
    })
    .then(html => { brsDet.innerHTML = html; })
    .catch(err => {
      console.error('BRS Fetch Error:', err);
      brsDet.innerHTML = '<div class="alert alert-danger">Error loading reconciliation: ' + err.message + '</div>';
    });
}

// Handle dropdown change
brsLedgerSelect.addEventListener('change', e => loadBankReconciliation(e.target.value));

// Auto-load if ?ledger_id= present on URL
window.addEventListener('load', () => {
  const ledgerId = new URLSearchParams(window.location.search).get('ledger_id');
  if (ledgerId) {
    brsLedgerSelect.value = ledgerId;
    loadBankReconciliation(ledgerId);
  } else {
    updateExportLink(null);
  }
});

// Handle checkbox updates
document.addEventListener('change', e => {
  if (e.target.classList.contains('brs-checkbox')) {
    const entryId = e.target.dataset.entryId;
    const isChecked = e.target.checked ? 1 : 0;
    const formData = new FormData();
    formData.append('update_brs', '1');
    formData.append('entry_id', entryId);
    formData.append('is_cleared', isChecked);

    fetch(window.location.pathname, { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // refresh visible ledger after a short delay so DB commit completes
          setTimeout(() => loadBankReconciliation(brsLedgerSelect.value), 200);
        } else {
          console.error('Update failed:', data.error);
          alert('⚠️ Error updating reconciliation.');
        }
      })
      .catch(err => {
        console.error('BRS update error:', err);
        alert('⚠️ Network or server error.');
      });
  }
});
</script>
</body>
</html>
