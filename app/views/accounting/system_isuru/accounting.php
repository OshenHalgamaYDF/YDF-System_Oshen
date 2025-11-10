<?php
// ---- Database Connection ----
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// ---- Add Ledger ----
if (isset($_POST['add_ledger'])) {
    $ledger_name = $_POST['ledger_name'];
    $opening_balance = $_POST['opening_balance'];
    $balance_type = $_POST['balance_type']; // using balance_type as you requested
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;

    // Basic validation: ensure group_id provided
    if (empty($group_id)) {
        echo "<script>alert('⚠️ Please select an Account Group before saving the ledger.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO ledgers (ledger_name, opening_balance, balance_type, group_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $ledger_name, $opening_balance, $balance_type, $group_id);
        $stmt->execute();
        // ✅ Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    }
}

// ---- Add Voucher ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_voucher'])) {
    $voucher_type = $_POST['voucher_type'];
    $date = $_POST['date'];
    $narration = $_POST['narration'];
    $dr_ledger = $_POST['dr_ledger'];
    $cr_ledger = $_POST['cr_ledger'];
    $amount = $_POST['amount'];

    // Insert into vouchers table
    $stmt = $conn->prepare("INSERT INTO vouchers (voucher_type, date, narration) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $voucher_type, $date, $narration);
    $stmt->execute();
    $voucher_id = $conn->insert_id;

    // Debit Entry
    $stmt2 = $conn->prepare("INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount) VALUES (?, ?, 'Dr', ?)");
    $stmt2->bind_param("iid", $voucher_id, $dr_ledger, $amount);
    $stmt2->execute();

    // Credit Entry
    $stmt3 = $conn->prepare("INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount) VALUES (?, ?, 'Cr', ?)");
    $stmt3->bind_param("iid", $voucher_id, $cr_ledger, $amount);
    $stmt3->execute();

    // ✅ Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// ---- Fetch Ledgers ----
$ledgers = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");

// ---- Fetch Vouchers ----
$vouchers = $conn->query("
    SELECT v.voucher_id, v.voucher_type, v.date, v.narration,
           GROUP_CONCAT(CONCAT(l.ledger_name, ' ', ve.type, ' ', ve.amount) SEPARATOR '<br>') AS entries
    FROM vouchers v
    JOIN voucher_entries ve ON v.voucher_id = ve.voucher_id
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    GROUP BY v.voucher_id
    ORDER BY v.voucher_id DESC
");

// ---- CORRECTED Ledger Balances ----
$balances = $conn->query("
    SELECT 
        l.ledger_id, 
        l.ledger_name,
        l.opening_balance, 
        l.balance_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0) AS total_cr,
        (l.opening_balance + 
         COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0) - 
         COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0)) AS net_balance
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    GROUP BY l.ledger_id, l.ledger_name, l.opening_balance, l.balance_type
");

// ---- CORRECTED Trial Balance ----
$trial_balance = $conn->query("
    SELECT 
        l.ledger_name,
        (CASE WHEN l.balance_type='Dr' THEN l.opening_balance ELSE 0 END) + 
        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0) AS Debit,
        (CASE WHEN l.balance_type='Cr' THEN l.opening_balance ELSE 0 END) + 
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0) AS Credit
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    GROUP BY l.ledger_id, l.ledger_name, l.opening_balance, l.balance_type
");

// ---- Update Bank Reconciliation ----
if (isset($_POST['update_brs'])) {
    $cleared_entries = isset($_POST['cleared']) ? $_POST['cleared'] : [];
    
    // Reset all entries first
    $conn->query("UPDATE bank_reconciliation SET is_cleared = 0, cleared_date = NULL");

    // Mark selected ones as cleared
    foreach ($cleared_entries as $entry_id) {
        $stmt = $conn->prepare("INSERT INTO bank_reconciliation (voucher_entry_id, is_cleared, cleared_date)
                                VALUES (?, 1, CURDATE())
                                ON DUPLICATE KEY UPDATE is_cleared=1, cleared_date=CURDATE()");
        $stmt->bind_param("i", $entry_id);
        $stmt->execute();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?brs_updated=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>💼 Mini Accounting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 1000px; margin-top: 40px; }
        .card { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #0d6efd; font-weight: 700; text-align: center; margin-bottom: 20px; }
        table th, table td { text-align: center; }
        .btn-group-custom { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-group-custom .btn { flex: 1; min-width: 150px; }
    </style>
</head>
<body>

<div class="container">
    <h2>💼 Mini Accounting System</h2>

    <!-- Top Buttons -->
    <div class="btn-group-custom mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLedgerModal">➕ Add Ledger</button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVoucherModal">🧾 Add Voucher</button>
        <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewLedgerModal">📘 View Ledger</button>
        <button class="btn btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#trialBalanceModal">📊 Trial Balance</button>
        <button class="btn btn-secondary text-white" data-bs-toggle="modal" data-bs-target="#bankReconciliationModal">🏦 Bank Reconciliation</button>
    </div>

    <!-- Voucher List -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">All Vouchers</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Date</th><th>Type</th><th>Entries</th><th>Narration</th></tr>
                </thead>
                <tbody>
                    <?php while($v = $vouchers->fetch_assoc()) { ?>
                        <tr>
                            <td><?= $v['voucher_id']; ?></td>
                            <td><?= $v['date']; ?></td>
                            <td><?= $v['voucher_type']; ?></td>
                            <td><?= $v['entries']; ?></td>
                            <td><?= $v['narration']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- CORRECTED Ledger Balances -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Ledger Balances</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-success">
                    <tr><th>Ledger</th><th>Opening</th><th>Total Dr</th><th>Total Cr</th><th>Balance</th></tr>
                </thead>
                <tbody>
                    <?php 
                    if ($balances && $balances->num_rows > 0) {
                        while($b = $balances->fetch_assoc()) { 
                            $net_balance = $b['net_balance'];
                            $balance_type = $net_balance >= 0 ? 'Dr' : 'Cr';
                            $balance_amount = abs($net_balance);
                    ?>
                        <tr>
                            <td class="text-start"><?= $b['ledger_name']; ?></td>
                            <td><?= $b['balance_type'] . " " . number_format($b['opening_balance'], 2); ?></td>
                            <td class="text-success"><?= number_format($b['total_dr'], 2); ?></td>
                            <td class="text-danger"><?= number_format($b['total_cr'], 2); ?></td>
                            <td class="fw-bold <?= $net_balance >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $balance_type . ' ' . number_format($balance_amount, 2); ?>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else { 
                    ?>
                        <tr>
                            <td colspan="5" class="text-muted">No ledger balances found</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Ledger -->
<div class="modal fade" id="addLedgerModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Add New Ledger</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label>Ledger Name</label>
        <input type="text" name="ledger_name" class="form-control mb-2" required>

        <label>Account Group</label>
        <select name="group_id" class="form-select mb-2" required>
            <option value="">-- Select Account Group --</option>
            <?php
            // Fetch account groups to allow selection
            $groups = $conn->query("SELECT group_id, group_name FROM account_groups ORDER BY group_name ASC");
            if ($groups && $groups->num_rows > 0) {
                while($g = $groups->fetch_assoc()) {
                    echo "<option value='{$g['group_id']}'>" . htmlspecialchars($g['group_name']) . "</option>";
                }
            } else {
                echo "<option value=''>No account groups found - please create one first</option>";
            }
            ?>
        </select>

        <label>Opening Balance</label>
        <input type="number" step="0.01" name="opening_balance" class="form-control mb-2" value="0">

        <label>Type</label>
        <select name="balance_type" class="form-select mb-2">
            <option value="Dr">Dr</option>
            <option value="Cr">Cr</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" name="add_ledger" class="btn btn-success">💾 Save Ledger</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add Voucher -->
<div class="modal fade" id="addVoucherModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Add New Voucher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-2">
            <div class="col-md-4">
                <label>Voucher Type</label>
                <select name="voucher_type" class="form-select" required>
                    <option value="Payment">Payment</option>
                    <option value="Receipt">Receipt</option>
                    <option value="Journal">Journal</option>
                    <option value="Contra">Contra</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>Date</label>
                <input type="date" name="date" class="form-control" required value="<?= date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4">
                <label>Amount</label>
                <input type="number" name="amount" class="form-control" step="0.01" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6">
                <label>Debit Ledger (Dr)</label>
                <select name="dr_ledger" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php
                    $ledgersDr = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
                    while($l = $ledgersDr->fetch_assoc()) echo "<option value='{$l['ledger_id']}'>" . htmlspecialchars($l['ledger_name']) . "</option>";
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <label>Credit Ledger (Cr)</label>
                <select name="cr_ledger" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php
                    $ledgersCr = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
                    while($l = $ledgersCr->fetch_assoc()) echo "<option value='{$l['ledger_id']}'>" . htmlspecialchars($l['ledger_name']) . "</option>";
                    ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label>Narration</label>
            <textarea name="narration" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" name="submit_voucher" class="btn btn-success">💾 Save Transaction</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: View Ledger -->
<div class="modal fade" id="viewLedgerModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">View Ledger</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="ledgerViewForm">
          <label>Select Ledger</label>
          <select name="ledger_id" id="ledgerSelect" class="form-select mb-3">
            <?php
            $ledgers3 = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
            while($l = $ledgers3->fetch_assoc()) echo "<option value='{$l['ledger_id']}'>" . htmlspecialchars($l['ledger_name']) . "</option>";
            ?>
          </select>
        </form>
        <div id="ledgerDetails" class="mt-3">
          <p class="text-muted">Select a ledger to view details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Trial Balance -->
<div class="modal fade" id="trialBalanceModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">📊 Trial Balance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-warning">
            <tr><th>Ledger Name</th><th>Debit (Dr)</th><th>Credit (Cr)</th></tr>
          </thead>
          <tbody>
            <?php 
            $totalDr = 0; $totalCr = 0;
            if ($trial_balance && $trial_balance->num_rows > 0) {
                while($t = $trial_balance->fetch_assoc()) { 
                    $totalDr += $t['Debit'];
                    $totalCr += $t['Credit'];
            ?>
              <tr>
                <td class="text-start"><?= htmlspecialchars($t['ledger_name']); ?></td>
                <td class="text-end"><?= number_format($t['Debit'], 2); ?></td>
                <td class="text-end"><?= number_format($t['Credit'], 2); ?></td>
              </tr>
            <?php 
                }
            } else { 
            ?>
              <tr>
                <td colspan="3" class="text-muted">No trial balance data found</td>
              </tr>
            <?php } ?>
            <tr class="fw-bold table-secondary">
              <td>Total</td>
              <td class="text-end"><?= number_format($totalDr, 2); ?></td>
              <td class="text-end"><?= number_format($totalCr, 2); ?></td>
            </tr>
          </tbody>
        </table>
        <p class="text-center mt-2 <?= abs($totalDr - $totalCr) < 0.01 ? 'text-success' : 'text-danger' ?>">
            <?= abs($totalDr - $totalCr) < 0.01 ? "✅ Trial Balance Matches!" : "⚠️ Difference Found: " . number_format(abs($totalDr - $totalCr), 2) ?>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Bank Reconciliation -->
<div class="modal fade" id="bankReconciliationModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title">🏦 Bank Reconciliation Statement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body table-responsive">
        <p><strong>Select your Bank Ledger:</strong></p>
        <select name="bank_ledger" id="bankLedger" class="form-select mb-3" required>
          <option value="">-- Select Bank Account --</option>
          <?php
          $banks = $conn->query("SELECT l.ledger_id, l.ledger_name
                                FROM ledgers l
                                JOIN account_groups g ON l.group_id = g.group_id
                                WHERE g.group_name = 'Bank Accounts'
                                AND l.ledger_name NOT LIKE '%cash%'
                                ");
          while($b = $banks->fetch_assoc()) {
              echo "<option value='{$b['ledger_id']}'>" . htmlspecialchars($b['ledger_name']) . "</option>";
          }
          ?>
        </select>

        <div id="bankEntriesTable">
          <p class="text-muted">Select a bank account to view entries...</p>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" name="update_brs" class="btn btn-success">💾 Update Reconciliation</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fix for ledger view functionality
document.addEventListener('DOMContentLoaded', function() {
    const ledgerSelect = document.getElementById("ledgerSelect");
    const bankLedger = document.getElementById("bankLedger");
    if (ledgerSelect) {
        ledgerSelect.addEventListener("change", function() {
            const id = this.value;
            if (id) {
                // Show loading
                document.getElementById("ledgerDetails").innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading ledger details...</p>
                    </div>
                `;
                
                // Fetch ledger details
                fetch("ledger_view.php?id=" + id)
                    .then(res => res.text())
                    .then(data => {
                        document.getElementById("ledgerDetails").innerHTML = data;
                    })
                    .catch(err => {
                        document.getElementById("ledgerDetails").innerHTML = `
                            <div class="alert alert-danger">
                                Error loading ledger details: ${err.message}
                            </div>
                        `;
                    });
            }
        });
    }

    if (bankLedger) {
        bankLedger.addEventListener("change", function() {
            const ledger_id = this.value;
            const target = document.getElementById("bankEntriesTable");
            if (!ledger_id) return;

            target.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading entries...</p>
                </div>`;

            fetch("brs_view.php?ledger_id=" + ledger_id)
                .then(res => res.text())
                .then(html => target.innerHTML = html)
                .catch(err => target.innerHTML = `<div class='alert alert-danger'>Error loading data: ${err}</div>`);
        });
    }
    
    // Refresh page after modal form submissions to show updated data
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            if (document.querySelector('[name="add_ledger"]') || document.querySelector('[name="submit_voucher"]')) {
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            }
        });
    });
});

</script>
</body>
</html>
