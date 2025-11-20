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
        (
          (CASE WHEN UPPER(l.balance_type)='DR' THEN l.opening_balance ELSE -l.opening_balance END)
          + COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0)
        ) AS net_balance
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    GROUP BY l.ledger_id, l.ledger_name, l.opening_balance, l.balance_type
");

// ---- Update Bank Reconciliation ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brs'])) {
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
    $is_cleared = isset($_POST['is_cleared']) ? intval($_POST['is_cleared']) : 0;
    
    if ($entry_id > 0) {
        // Check if record exists in bank_reconciliation
        $check_query = $conn->query("SELECT reconciliation_id FROM bank_reconciliation WHERE voucher_entry_id = $entry_id");
        
        if ($check_query && $check_query->num_rows > 0) {
            // Update existing record
            $cleared_date = $is_cleared ? date('Y-m-d') : NULL;
            $update_query = "UPDATE bank_reconciliation SET is_cleared = $is_cleared, cleared_date = " . ($cleared_date ? "'$cleared_date'" : "NULL") . " WHERE voucher_entry_id = $entry_id";
            
            if ($conn->query($update_query)) {
                echo json_encode(['success' => true, 'message' => 'Reconciliation updated']);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        } else {
            // Insert new record
            $cleared_date = $is_cleared ? date('Y-m-d') : NULL;
            $insert_query = "INSERT INTO bank_reconciliation (voucher_entry_id, is_cleared, cleared_date) VALUES ($entry_id, $is_cleared, " . ($cleared_date ? "'$cleared_date'" : "NULL") . ")";
            
            if ($conn->query($insert_query)) {
                echo json_encode(['success' => true, 'message' => 'Reconciliation created']);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid entry ID']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>💼 Mini Accounting System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Optional: Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Optional: Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body { background: #f8f9fa; font-family: 'Roboto', sans-serif; }
        .container { max-width: 1000px; margin-top: 40px; }
        .card { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #0d6efd; font-weight: 700; text-align: center; margin-bottom: 20px; }
        table th, table td { text-align: center; }
        .btn-group-custom { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-group-custom .btn { flex: 1; min-width: 200px; }
    </style>

    <!-- Bootstrap JS Bundle (Popper included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container">
    <h2>YDF Accounting System</h2>

    <!-- Top Buttons -->
    <div class="btn-group-custom mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLedgerModal">➕ Add Ledger</button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVoucherModal">🧾 Add Voucher</button>
        <a href="view_ledger.php" class="btn btn-info text-white">📘 View Ledger</a>
        
        <!-- Changed: open bank reconciliation in a separate page -->
        <a href="bank_reconciliation.php" class="btn btn-secondary text-white">🏦 Bank Reconciliation</a>

        <a href="trial_balance.php" class="btn btn-warning text-dark">📊 Trial Balance</a>
        <a href="income_statement.php" class="btn btn-danger">📈 Income Statement</a>
        <a href="balance_sheet.php" class="btn btn-primary" style="background-color:#6610f2;">💰 Balance Sheet</a>
    </div>

    <!-- Voucher List -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">All Vouchers</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Date</th><th>Entries</th><th>Narration</th></tr>
                </thead>
                <tbody>
                    <?php while($v = $vouchers->fetch_assoc()) { ?>
                        <tr>
                            <td><?= $v['voucher_id']; ?></td>
                            <td><?= $v['date']; ?></td>
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
    <div class="modal-content">
      <form method="POST">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✓ DOM Content Loaded');

    // ===== BANK RECONCILIATION MODAL =====
    const brsLedgerSelect = document.getElementById('brsLedgerSelect');
    if (brsLedgerSelect) {
        brsLedgerSelect.addEventListener('change', function() {
        });
    }

    // Reset BRS modal when opened
    const brsModal = document.getElementById('bankReconciliationModal');
    if (brsModal) {
        brsModal.addEventListener('show.bs.modal', function() {
            if (brsDet) brsDet.innerHTML = '<p class="text-muted">Select a bank account to view entries...</p>';
        });
    }

    // ===== VIEW LEDGER MODAL (ADDED) =====
    const ledgerSelect = document.getElementById('ledgerSelect');
    if (ledgerSelect) {
        ledgerSelect.addEventListener('change', function() {
            const ledgerId = this.value;
            const detailsDiv = document.getElementById('ledgerDetails');
            if (!detailsDiv) return;
            if (!ledgerId) {
                detailsDiv.innerHTML = '<p class="text-muted">Select a ledger to view details...</p>';
                return;
            }
            detailsDiv.innerHTML = '<div class="text-center"><span class="spinner-border spinner-border-sm text-primary me-2"></span> Loading ledger data...</div>';
            const url = 'ledger_view.php?id=' + encodeURIComponent(ledgerId);
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.text();
                })
                .then(html => { detailsDiv.innerHTML = html; })
                .catch(err => {
                    console.error('Ledger Fetch Error:', err);
                    detailsDiv.innerHTML = '<div class="alert alert-danger">Error loading ledger: ' + err.message + '</div>';
                });
        });
    }

    // Reset View Ledger modal when opened
    const viewModal = document.getElementById('viewLedgerModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function() {
            const sel = document.getElementById('ledgerSelect');
            const det = document.getElementById('ledgerDetails');
            if (sel) sel.value = '';
            if (det) det.innerHTML = '<p class="text-muted">Select a ledger to view details...</p>';
        });
    }

    // ===== CHECKBOX HANDLER FOR MARKING CLEARED =====
    document.addEventListener('change', function(e) {
        if (!e.target) return;
        if (e.target.classList && e.target.classList.contains('brs-checkbox')) {
            const entryId = e.target.dataset.entryId;
            const isChecked = e.target.checked ? 1 : 0;
            const formData = new FormData();
            formData.append('update_brs', '1');
            formData.append('entry_id', entryId);
            formData.append('is_cleared', isChecked);
            fetch('accounting.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then data => {
                    if (data.success) {
                        const msg = data.message;
                        Swal.fire({ icon: 'success', title: 'Updated', text: msg });
                    } else {
                        const err = data.error || 'Unknown error';
                        Swal.fire({ icon: 'error', title: 'Error', text: err });
                    }
                })
                .catch(err => {
                    console.error('Fetch Error:', err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Network error: ' + err.message });
                });
        }
    });
});
</script>

<!-- SweetAlert2 (for alerts) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
