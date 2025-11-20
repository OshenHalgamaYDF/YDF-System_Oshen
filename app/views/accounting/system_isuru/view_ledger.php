<?php
// ========================================
// VIEW LEDGER (combined AJAX partial + full page) with Export only
// ========================================
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

// Accept either 'id' or 'ledger_id' for compatibility
$ledgerIdParam = isset($_GET['ledger_id']) ? 'ledger_id' : (isset($_GET['id']) ? 'id' : null);

// ---------- EXPORT CSV for a single ledger ----------
if ($ledgerIdParam && isset($_GET['export']) && $_GET['export'] === 'excel') {
    $id = intval($_GET[$ledgerIdParam] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        die("Invalid ledger id for export.");
    }

    $stmtL = $conn->prepare("SELECT ledger_name, opening_balance, balance_type FROM ledgers WHERE ledger_id = ? LIMIT 1");
    $stmtL->bind_param("i", $id);
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
        WHERE ve.ledger_id = ?
        ORDER BY v.date ASC, v.voucher_id ASC
    ");
    $tstmt->bind_param("i", $id);
    $tstmt->execute();
    $q = $tstmt->get_result();

    header('Content-Type: text/csv; charset=UTF-8');
    $safeName = preg_replace('/[^a-z0-9_\-]/i','_', $ledger_name ?: 'ledger_'.$id);
    $filename = "ledger_{$safeName}_" . date('Ymd') . ".csv";
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');

    fputcsv($out, ["Ledger:",$ledger_name]);
    fputcsv($out, ["As at:", date('Y-m-d')]);
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
        WHERE ve.ledger_id = ?
        ORDER BY v.date ASC, v.voucher_id ASC
    ");
    if (!$tstmt) {
        echo "<div class='alert alert-danger'>Query error: " . htmlspecialchars($conn->error) . "</div>";
        exit;
    }
    $tstmt->bind_param("i", $id);
    $tstmt->execute();
    $q = $tstmt->get_result();

    $running_balance = ($balance_type === 'DR') ? $opening_balance : -$opening_balance;
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
        <!-- Export only -->
        <a href="?id=<?= $id ?>&export=excel" class="btn btn-light btn-sm" title="Download ledger as Excel (CSV)">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
        </a>
      </div>

      <div class="card-body">
        <div class="row mb-2">
          <div class="col-md-6"><strong>Opening Balance:</strong> <?= number_format($opening_balance,2) ?> <?= htmlspecialchars($balance_type) ?></div>
          <div class="col-md-6 text-end"><strong>Current Balance:</strong> <span id="ledgerCurrentBalance"><?= number_format(abs($running_balance),2) ?> <?= ($running_balance >= 0 ? 'Dr' : 'Cr') ?></span></div>
        </div>

        <?php if (!$q || $q->num_rows === 0): ?>
          <div class="alert alert-info">No transactions found for this ledger.</div>
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
$ledgers = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>View Ledger</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style> body { background:#f5f7fa } .container{ max-width:1100px; margin-top:32px }</style>
</head>
<body>
  <div class="container">
    <div class="card shadow-sm mb-3">
      <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
        <div>
          <h5 class="mb-0">📘 View Ledger</h5>
          <small class="text-white-50">Select a ledger to view detailed transactions</small>
        </div>
        <a class="btn btn-light btn-sm" href="accounting.php">← Back</a>
      </div>
      <div class="card-body">
        <div class="row g-3 mb-3 align-items-end">
          <div class="col-md-12">
            <label for="ledgerSelect" class="form-label fw-semibold">Select Ledger</label>
            <select id="ledgerSelect" class="form-select">
              <option value="">-- Select a ledger --</option>
              <?php while ($l = $ledgers->fetch_assoc()): ?>
                <option value="<?= (int)$l['ledger_id'] ?>"><?= htmlspecialchars($l['ledger_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <div id="ledgerDetails">
          <p class="text-muted">Select a ledger to view details...</p>
        </div>
      </div>
    </div>
  </div>

<script>
(function(){
  const select = document.getElementById('ledgerSelect');
  const details = document.getElementById('ledgerDetails');

  async function loadLedger(id) {
    if (!id) {
      details.innerHTML = '<p class="text-muted">Select a ledger to view details...</p>';
      return;
    }
    details.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-info spinner-border-sm" role="status"></div> Loading...</div>';
    try {
      const resp = await fetch(window.location.pathname + '?id=' + encodeURIComponent(id), { cache: 'no-store' });
      if (!resp.ok) throw new Error('Server returned ' + resp.status);
      const html = await resp.text();
      details.innerHTML = html;
      window.scrollTo({ top: details.offsetTop - 20, behavior: 'smooth' });
    } catch (err) {
      console.error(err);
      details.innerHTML = '<div class="alert alert-danger">Error loading ledger: ' + err.message + '</div>';
    }
  }

  select.addEventListener('change', () => loadLedger(select.value));

  window.addEventListener('load', () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id') || params.get('ledger_id');
    if (id) {
      select.value = id;
      loadLedger(id);
    }
  });
})();
</script>
</body>
</html>
<?php
// end of file
?>
