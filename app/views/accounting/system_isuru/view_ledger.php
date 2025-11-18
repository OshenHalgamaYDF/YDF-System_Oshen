<?php
// Combined view_ledger + ledger_view functionality
// - GET with ?id= or ?ledger_id= returns the ledger inner HTML (AJAX partial)
// - no id => renders full page with dropdown and JS that fetches the partial

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

// Accept either 'id' or 'ledger_id' for compatibility
$ledgerIdParam = isset($_GET['ledger_id']) ? 'ledger_id' : (isset($_GET['id']) ? 'id' : null);

// If called as AJAX partial (single ledger), output inner HTML only
if ($ledgerIdParam) {
    $id = intval($_GET[$ledgerIdParam] ?? 0);
    if ($id <= 0) {
        echo "<div class='alert alert-warning'>Invalid ledger selected.</div>";
        exit;
    }

    // Fetch ledger header
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
    $balance_type = $ledger['balance_type'];

    // Transactions
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

    // Running balance initialised with sign of opening balance
    $running_balance = (strtoupper($balance_type) === 'DR') ? $opening_balance : -$opening_balance;
    $totalDr = 0.0;
    $totalCr = 0.0;

    ob_start();
    ?>
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">📘 Ledger: <?= $ledger_name ?></h5>
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
              <tfoot class="table-secondary">
                <tr class="fw-bold">
                  <td colspan="3" class="text-end">Totals:</td>
                  <td class="text-end text-success"><?= number_format($totalDr,2) ?></td>
                  <td class="text-end text-danger"><?= number_format($totalCr,2) ?></td>
                  <td class="text-end"><?= number_format(abs($running_balance),2) ?> <?= ($running_balance >= 0 ? 'Dr' : 'Cr') ?></td>
                  <td></td>
                </tr>
                <tr class="fw-bold table-info">
                  <td colspan="3" class="text-end">Opening Balance:</td>
                  <td class="text-end"><?= (strtoupper($balance_type) === 'DR') ? number_format($opening_balance,2) : '' ?></td>
                  <td class="text-end"><?= (strtoupper($balance_type) === 'CR') ? number_format($opening_balance,2) : '' ?></td>
                  <td class="text-end"><?= number_format($opening_balance,2) ?> <?= htmlspecialchars($balance_type) ?></td>
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

// Otherwise render full page with dropdown and JS that fetches the partial
$ledgers = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>View Ledger</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="ledgerSelect" class="form-label fw-semibold">Select Ledger</label>
            <select id="ledgerSelect" class="form-select">
              <option value="">-- Select a ledger --</option>
              <?php while ($l = $ledgers->fetch_assoc()): ?>
                <option value="<?= (int)$l['ledger_id'] ?>"><?= htmlspecialchars($l['ledger_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-6 d-flex align-items-end justify-content-end">
            <button class="btn btn-primary btn-sm me-2" id="btnPrint">🖨️ Print</button>
            <button class="btn btn-outline-secondary btn-sm" id="btnRefresh">⟳ Refresh</button>
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
  const btnPrint = document.getElementById('btnPrint');
  const btnRefresh = document.getElementById('btnRefresh');

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
  btnRefresh.addEventListener('click', () => loadLedger(select.value));
  btnPrint.addEventListener('click', () => { window.print(); });

  // Auto-load if ?id= or ?ledger_id= in URL
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
// ...existing code...
?>