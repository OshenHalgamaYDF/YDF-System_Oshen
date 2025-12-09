<?php
// accounting.php (single-file) - Full fixed version
// ---- Database Connection ----
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// --------------------------
// AJAX endpoint: return voucher edit HTML
// --------------------------
if (isset($_GET['action']) && $_GET['action'] === 'get_voucher' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Fetch voucher
    $vstmt = $conn->prepare("SELECT voucher_id, voucher_type, date, narration FROM vouchers WHERE voucher_id = ?");
    $vstmt->bind_param("i", $id);
    $vstmt->execute();
    $vres = $vstmt->get_result();
    $voucher = $vres->fetch_assoc();
    $vstmt->close();

    // Fetch voucher entries (expecting two: Dr & Cr)
    $estmt = $conn->prepare("SELECT entry_id, ledger_id, type, amount FROM voucher_entries WHERE voucher_id = ? ORDER BY FIELD(type, 'Dr','Cr')");
    $estmt->bind_param("i", $id);
    $estmt->execute();
    $entries = $estmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $estmt->close();

    // Fetch ledgers for selects - SEPARATE QUERIES FOR EACH DROPDOWN
    $ledgers_dr = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
    $ledgers_cr = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
    // Output HTML fragment (returned to AJAX)
    ?>
    <div class="row mb-2">
        <div class="col-md-4">
            <label>Voucher Type</label>
            <select name="voucher_type" class="form-select" required>
                <?php
                $types = ['Payment','Receipt','Journal','Contra'];
                foreach ($types as $t) {
                    $sel = ($voucher['voucher_type'] === $t) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($t) . "\" $sel>" . htmlspecialchars($t) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label>Date</label>
            <input type="date" name="date" class="form-control" required value="<?= htmlspecialchars($voucher['date']); ?>">
        </div>
        <div class="col-md-4">
            <label>Amount</label>
            <?php
            // If entries exist pick amount from first Dr entry or first entry
            $amt = 0;
            foreach ($entries as $e) { if ($e['type'] === 'Dr') { $amt = $e['amount']; break; } }
            if ($amt == 0 && isset($entries[0])) $amt = $entries[0]['amount'];
            ?>
            <input type="number" name="amount" class="form-control" step="0.01" required value="<?= htmlspecialchars(number_format((float)$amt,2,'.','')); ?>">
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-md-6">
            <label>Debit Ledger (Dr)</label>
            <select name="dr_ledger" class="form-select" required>
                <option value="">-- Select --</option>
                <?php
                // find selected dr ledger id
                $dr_id = null;
                foreach ($entries as $e) if ($e['type'] === 'Dr') $dr_id = $e['ledger_id'];
                
                if ($ledgers_dr && $ledgers_dr->num_rows > 0) {
                    while ($l = $ledgers_dr->fetch_assoc()) {
                        $sel = ($l['ledger_id'] == $dr_id) ? 'selected' : '';
                        echo "<option value='{$l['ledger_id']}' $sel>" . htmlspecialchars($l['ledger_name']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="col-md-6">
            <label>Credit Ledger (Cr)</label>
            <select name="cr_ledger" class="form-select" required>
                <option value="">-- Select --</option>
                <?php
                $cr_id = null;
                foreach ($entries as $e) if ($e['type'] === 'Cr') $cr_id = $e['ledger_id'];
                
                if ($ledgers_cr && $ledgers_cr->num_rows > 0) {
                    while ($l = $ledgers_cr->fetch_assoc()) {
                        $sel = ($l['ledger_id'] == $cr_id) ? 'selected' : '';
                        echo "<option value='{$l['ledger_id']}' $sel>" . htmlspecialchars($l['ledger_name']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label>Narration</label>
        <textarea name="narration" class="form-control" rows="2"><?= htmlspecialchars($voucher['narration']); ?></textarea>
    </div>

    <input type="hidden" name="voucher_id" value="<?= $voucher['voucher_id']; ?>">

    <?php
    // also include the entry ids so update knows which to update
    foreach ($entries as $e) {
        echo "<input type='hidden' name='entry_id_map[{$e['type']}]' value='" . intval($e['entry_id']) . "'>";
    }
    exit;
}

// --------------------------
// ---- Add Ledger ----
if (isset($_POST['add_ledger'])) {
    $ledger_name = $_POST['ledger_name'] ?? '';
    $opening_balance = floatval($_POST['opening_balance'] ?? 0);
    $balance_type = $_POST['balance_type'] ?? 'Dr';
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;

    if (empty($group_id)) {
        echo "<script>alert('⚠️ Please select an Account Group before saving the ledger.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO ledgers (ledger_name, opening_balance, balance_type, group_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $ledger_name, $opening_balance, $balance_type, $group_id);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    }
}

// --------------------------
// ---- Add Voucher ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_voucher'])) {
    $voucher_type = $_POST['voucher_type'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $narration = $_POST['narration'] ?? '';
    $dr_ledger = intval($_POST['dr_ledger'] ?? 0);
    $cr_ledger = intval($_POST['cr_ledger'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);

    // Basic validation
    if ($dr_ledger <= 0 || $cr_ledger <= 0 || $amount <= 0) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=invalid_input");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO vouchers (voucher_type, date, narration, created_at) VALUES (?, ?, ?, NOW())");
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

    header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
    exit();
}

// --------------------------
// ---- UPDATE VOUCHER ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_voucher'])) {
    // Expected: voucher_id, voucher_type, date, narration, dr_ledger, cr_ledger, amount, entry_id_map[Dr], entry_id_map[Cr]
    $id = intval($_POST['voucher_id'] ?? 0);
    $voucher_type = $_POST['voucher_type'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $narration = $_POST['narration'] ?? '';
    $dr_ledger = intval($_POST['dr_ledger'] ?? 0);
    $cr_ledger = intval($_POST['cr_ledger'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);

    // Debug logging (remove in production)
    error_log("UPDATE VOUCHER: id=$id, type=$voucher_type, dr=$dr_ledger, cr=$cr_ledger, amount=$amount");

    if ($id <= 0) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=bad_id");
        exit();
    }

    // Validate ledgers are not the same
    if ($dr_ledger === $cr_ledger) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=same_ledgers");
        exit();
    }

    // Start transaction for data consistency
    $conn->begin_transaction();
    
    try {
        // Update voucher row
        $ustmt = $conn->prepare("UPDATE vouchers SET voucher_type = ?, date = ?, narration = ? WHERE voucher_id = ?");
        $ustmt->bind_param("sssi", $voucher_type, $date, $narration, $id);
        $updateVoucher = $ustmt->execute();
        $ustmt->close();
        
        if (!$updateVoucher) {
            throw new Exception("Failed to update voucher");
        }

        // Update entries
        $entry_map = $_POST['entry_id_map'] ?? [];
        
        // Debug entry map
        error_log("Entry map: " . print_r($entry_map, true));
        
        // Update Dr entry
        if (isset($entry_map['Dr']) && intval($entry_map['Dr']) > 0) {
            $eid = intval($entry_map['Dr']);
            $estmt = $conn->prepare("UPDATE voucher_entries SET ledger_id = ?, amount = ? WHERE entry_id = ?");
            $estmt->bind_param("idi", $dr_ledger, $amount, $eid);
            $updateDr = $estmt->execute();
            $estmt->close();
            
            if (!$updateDr) {
                throw new Exception("Failed to update Dr entry");
            }
            error_log("Updated Dr entry: $eid with ledger $dr_ledger, amount $amount");
        } else {
            // If Dr entry doesn't exist, insert one
            if ($dr_ledger > 0 && $amount > 0) {
                $ist = $conn->prepare("INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount) VALUES (?, ?, 'Dr', ?)");
                $ist->bind_param("iid", $id, $dr_ledger, $amount);
                $ist->execute();
                $ist->close();
                error_log("Inserted new Dr entry");
            }
        }
        
        // Update Cr entry  
        if (isset($entry_map['Cr']) && intval($entry_map['Cr']) > 0) {
            $eid = intval($entry_map['Cr']);
            $estmt = $conn->prepare("UPDATE voucher_entries SET ledger_id = ?, amount = ? WHERE entry_id = ?");
            $estmt->bind_param("idi", $cr_ledger, $amount, $eid);
            $updateCr = $estmt->execute();
            $estmt->close();
            
            if (!$updateCr) {
                throw new Exception("Failed to update Cr entry");
            }
            error_log("Updated Cr entry: $eid with ledger $cr_ledger, amount $amount");
        } else {
            // If Cr entry doesn't exist, insert one
            if ($cr_ledger > 0 && $amount > 0) {
                $ist = $conn->prepare("INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount) VALUES (?, ?, 'Cr', ?)");
                $ist->bind_param("iid", $id, $cr_ledger, $amount);
                $ist->execute();
                $ist->close();
                error_log("Inserted new Cr entry");
            }
        }

        // Commit transaction
        $conn->commit();
        error_log("Voucher update successful");
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Voucher update failed: " . $e->getMessage());
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=update_failed&message=" . urlencode($e->getMessage()));
        exit();
    }
}

// --------------------------
// ---- Update Bank Reconciliation ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brs'])) {
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
    $is_cleared = isset($_POST['is_cleared']) ? intval($_POST['is_cleared']) : 0;
    
    if ($entry_id > 0) {
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

// --------------------------
// ---- DELETE VOUCHER ----
if (isset($_POST['delete_voucher'])) {
    $voucher_id = intval($_POST['voucher_id']);
    if ($voucher_id > 0) {
        // Use transaction to ensure atomicity
        $conn->begin_transaction();
        try {
            // Delete entries first
            $delEntries = $conn->prepare("DELETE FROM voucher_entries WHERE voucher_id = ?");
            $delEntries->bind_param("i", $voucher_id);
            $delEntries->execute();
            $delEntries->close();

            // Delete voucher row
            $delVoucher = $conn->prepare("DELETE FROM vouchers WHERE voucher_id = ?");
            $delVoucher->bind_param("i", $voucher_id);
            $delVoucher->execute();
            $delVoucher->close();

            $conn->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Delete failed: " . $e->getMessage());
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=delete_failed");
            exit();
        }
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=invalid_id");
        exit();
    }
}

// --------------------------
// ---- Fetch Ledgers / Vouchers / Balances for page display ----
$ledgers = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");

// Vouchers: include entries as formatted string
$vouchers = $conn->query("
    SELECT v.voucher_id, v.voucher_type, v.date, v.narration,
           GROUP_CONCAT(CONCAT(l.ledger_name, ' ', ve.type, ' ', ve.amount) SEPARATOR '<br>') AS entries
    FROM vouchers v
    JOIN voucher_entries ve ON v.voucher_id = ve.voucher_id
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    GROUP BY v.voucher_id
    ORDER BY v.voucher_id DESC
");

// Corrected ledger balances
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>💼 Mini Accounting System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        body { background: #f8f9fa; font-family: 'Roboto', sans-serif; }
        .container { max-width: 1000px; margin-top: 40px; }
        .card { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #0d6efd; font-weight: 700; text-align: center; margin-bottom: 20px; }
        table th, table td { text-align: center; vertical-align: middle; }
        .btn-group-custom { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-group-custom .btn { flex: 1; min-width: 200px; }
        .alert { margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container mb-4">
    <h2>YDF Accounting System</h2>

    <div class="btn-group-custom mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLedgerModal">➕ Add Ledger</button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVoucherModal">🧾 Add Voucher</button>
        <a href="view_ledger.php" class="btn btn-info text-white">📘 View Ledger</a>
        <a href="bank_reconciliation.php" class="btn btn-secondary text-white">🏦 Bank Reconciliation</a>
        <a href="trial_balance.php" class="btn btn-warning text-dark">📊 Trial Balance</a>
        <a href="income_statement.php" class="btn btn-danger">📈 Income Statement</a>
        <a href="balance_sheet.php" class="btn btn-primary" style="background-color:#6610f2;">💰 Balance Sheet</a>
    </div>

    <!-- Flash messages -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success" id="flashAlert">✅ Voucher deleted successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success" id="flashAlert">✅ Voucher updated successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" id="flashAlert">✅ Saved successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ Error: 
            <?php 
            switch($_GET['error']) {
                case 'invalid_input': echo 'Invalid input data'; break;
                case 'bad_id': echo 'Invalid voucher ID'; break;
                case 'same_ledgers': echo 'Debit and Credit ledgers cannot be the same'; break;
                case 'delete_failed': echo 'Failed to delete voucher'; break;
                case 'update_failed': echo 'Failed to update voucher'; break;
                default: echo htmlspecialchars($_GET['error']);
            }
            ?>
            <?php if (isset($_GET['message'])): ?>
                <br><small>Details: <?= htmlspecialchars($_GET['message']); ?></small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Voucher List -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">All Vouchers</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Type</th><th>Date</th><th>Entries</th><th>Narration</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if ($vouchers && $vouchers->num_rows > 0): ?>
                        <?php while ($v = $vouchers->fetch_assoc()): ?>
                            <tr>
                                <td><?= intval($v['voucher_id']); ?></td>
                                <td><?= htmlspecialchars($v['voucher_type']); ?></td>
                                <td><?= htmlspecialchars($v['date']); ?></td>
                                <td class="text-start"><?= $v['entries']; // entries contain HTML <br> ?></td>
                                <td class="text-start"><?= htmlspecialchars($v['narration']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-btn" data-id="<?= intval($v['voucher_id']); ?>" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?= intval($v['voucher_id']); ?>" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-muted">No vouchers found</td></tr>
                    <?php endif; ?>
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
                    <?php if ($balances && $balances->num_rows > 0): ?>
                        <?php while ($b = $balances->fetch_assoc()): 
                            $net_balance = $b['net_balance'];
                            $balance_type = $net_balance >= 0 ? 'Dr' : 'Cr';
                            $balance_amount = abs($net_balance);
                        ?>
                            <tr>
                                <td class="text-start"><?= htmlspecialchars($b['ledger_name']); ?></td>
                                <td><?= htmlspecialchars($b['balance_type']) . " " . number_format($b['opening_balance'], 2); ?></td>
                                <td class="text-success"><?= number_format($b['total_dr'], 2); ?></td>
                                <td class="text-danger"><?= number_format($b['total_cr'], 2); ?></td>
                                <td class="fw-bold <?= $net_balance >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $balance_type . ' ' . number_format($balance_amount, 2); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-muted">No ledger balances found</td></tr>
                    <?php endif; ?>
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
              $groups = $conn->query("SELECT group_id, group_name FROM account_groups ORDER BY group_name ASC");
              if ($groups && $groups->num_rows > 0) {
                  while ($g = $groups->fetch_assoc()) {
                      echo "<option value='" . intval($g['group_id']) . "'>" . htmlspecialchars($g['group_name']) . "</option>";
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

<!-- Modal: Edit Voucher -->
<div class="modal fade" id="editVoucherModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" id="editVoucherForm" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Edit Voucher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="editVoucherContent">
        Loading...
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="update_voucher" class="btn btn-warning">Save Changes</button>
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
                    if ($ledgersDr && $ledgersDr->num_rows > 0) {
                        while ($l = $ledgersDr->fetch_assoc()) {
                            echo "<option value='" . intval($l['ledger_id']) . "'>" . htmlspecialchars($l['ledger_name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <label>Credit Ledger (Cr)</label>
                <select name="cr_ledger" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php
                    $ledgersCr = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
                    if ($ledgersCr && $ledgersCr->num_rows > 0) {
                        while ($l = $ledgersCr->fetch_assoc()) {
                            echo "<option value='" . intval($l['ledger_id']) . "'>" . htmlspecialchars($l['ledger_name']) . "</option>";
                        }
                    }
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 (for nice alerts) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentEditModal = null;

    // Edit button handler
    document.addEventListener('click', function(e) {
        let editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            if (!id) return;
            
            const modalEl = document.getElementById('editVoucherModal');
            currentEditModal = new bootstrap.Modal(modalEl);
            const content = document.getElementById('editVoucherContent');
            content.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div> Loading voucher details...</div>';

            fetch(window.location.pathname + '?action=get_voucher&id=' + encodeURIComponent(id))
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(html => {
                    content.innerHTML = html;
                    currentEditModal.show();
                })
                .catch(err => {
                    content.innerHTML = '<div class="alert alert-danger">Error loading voucher: ' + err.message + '</div>';
                    currentEditModal.show();
                });
            return;
        }

        // Delete button handler
        let deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const voucherId = deleteBtn.getAttribute('data-id') || deleteBtn.dataset.id;
            if (!voucherId) return;

            // Confirm deletion
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will permanently delete the voucher and its entries.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const fd = new FormData();
                    fd.append('delete_voucher', '1');
                    fd.append('voucher_id', voucherId);
                    fetch(window.location.pathname, { method: 'POST', body: fd })
                        .then(() => { window.location.href = window.location.pathname + '?deleted=1'; })
                        .catch(() => { Swal.fire('Error','Delete failed','error'); });
                });
            } else {
                if (!confirm('Delete voucher? This cannot be undone.')) return;
                const fd = new FormData();
                fd.append('delete_voucher', '1');
                fd.append('voucher_id', voucherId);
                fetch(window.location.pathname, { method: 'POST', body: fd })
                    .then(() => { window.location.href = window.location.pathname + '?deleted=1'; })
                    .catch(() => { alert('Delete failed'); });
            }
        }
    });

    // Edit form submission handler - FIXED VERSION
    document.getElementById('editVoucherForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('update_voucher', '1'); // <-- Add this line so PHP sees the update_voucher field

        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Basic validation
        const drLedger = formData.get('dr_ledger');
        const crLedger = formData.get('cr_ledger');
        const amount = formData.get('amount');
        const voucherType = formData.get('voucher_type');
        const date = formData.get('date');
        
        if (!drLedger || !crLedger || !amount || amount <= 0 || !voucherType || !date) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please fill all required fields with valid values.'
            });
            return;
        }
        
        if (drLedger === crLedger) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Debit and Credit ledgers cannot be the same.'
            });
            return;
        }

        // Show loading state
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
        }

        // Use fetch for form submission to handle errors better
        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text().then(text => {
                    // If not redirected, assume success and reload
                    window.location.href = window.location.pathname + '?updated=1';
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Submission Error',
                text: 'Failed to update voucher: ' + error.message
            });
            // Reset button state
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Changes';
            }
        });
    });

    // Close modal on successful submission
    document.addEventListener('submitSuccess', function() {
        if (currentEditModal) {
            currentEditModal.hide();
        }
    });

});

// Auto-hide flash alert
document.addEventListener('DOMContentLoaded', function() {
    const a = document.getElementById('flashAlert');
    if (a) setTimeout(() => { a.classList.remove('show'); a.remove(); }, 3000);
});
</script>

</body>
</html>