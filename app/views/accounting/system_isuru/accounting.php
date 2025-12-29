<?php
// include controller that prepares $conn, $vouchers, $balances and handles POST actions
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\system_isuru\accountingcontroller.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>💼 Mini Accounting System</title>

    <!-- Styles & libraries -->
    <!-- Bootstrap for layout and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons used in buttons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Roboto font for nicer typography -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- DataTables CSS (Bootstrap 5 integration) for searchable/sortable tables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* Basic page styling */
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
    <!-- Page heading -->
    <h2>YDF Accounting System</h2>

    <!-- Quick action buttons (open modals or navigate to reports) -->
    <div class="btn-group-custom mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLedgerModal">➕ Add Ledger</button>
        <button id="addVoucherBtn" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVoucherModal" aria-label="Add Voucher (Ctrl+X)">🧾 Add Voucher </button>
        <a href="view_ledger.php" class="btn btn-info text-white">📘 View Ledger</a>
        <a href="bank_reconciliation.php" class="btn btn-secondary text-white">🏦 Bank Reconciliation</a>
        <a href="trial_balance.php" class="btn btn-warning text-dark">📊 Trial Balance</a>
        <a href="income_statement.php" class="btn btn-danger">📈 Income Statement</a>
        <a href="balance_sheet.php" class="btn btn-primary" style="background-color:#6610f2;">💰 Balance Sheet</a>
    </div>

    <!-- Flash messages: simple feedback after actions (delete/update/save) -->
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
            // Map known error codes to friendly messages
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

    <!-- ==========================
         All Vouchers table
         - Displays vouchers fetched by the controller ($vouchers)
         - DataTables is initialized in the script section to add sorting/search/pagination
         - We render entries HTML (prepared by controller) in the "Entries" cell
         ========================== -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">All Vouchers</div>
        <div class="card-body table-responsive">
            <table id="vouchersTable" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Type</th><th>Date</th><th>Entries</th><th>Narration</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if ($vouchers && $vouchers->num_rows > 0): ?>
                        <?php while ($v = $vouchers->fetch_assoc()): ?>
                            <tr>
                                <!-- voucher id, type and date are escaped as needed -->
                                <td><?= intval($v['voucher_id']); ?></td>
                                <td><?= htmlspecialchars($v['voucher_type']); ?></td>
                                <td><?= htmlspecialchars($v['date']); ?></td>

                                <!-- entries column may contain formatted HTML (e.g. ledger lines) prepared by controller -->
                                <td class="text-start"><?= $v['entries']; // entries contain HTML <br> ?></td>
                                <td class="text-start"><?= htmlspecialchars($v['narration']); ?></td>

                                <!-- action buttons: edit opens modal, delete triggers confirmation -->
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

    <!-- ==========================
         Ledger Balances table
         - Shows opening balances, totals and computed net balance per ledger
         - Controller provides $balances with computed fields
         - Also enhanced by DataTables for search/sort/pagination
         ========================== -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Ledger Balances</div>
        <div class="card-body table-responsive">
            <table id="balancesTable" class="table table-bordered table-hover">
                <thead class="table-success">
                    <tr><th>Ledger</th><th>Opening</th><th>Total Dr</th><th>Total Cr</th><th>Balance</th></tr>
                </thead>
                <tbody>
                    <?php if ($balances && $balances->num_rows > 0): ?>
                        <?php while ($b = $balances->fetch_assoc()): 
                            // the controller should provide net_balance and opening_balance and balance_type
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

<!-- ==========================
     Modal: Add Ledger
     - Simple form to create a ledger
     - Controller listens for POST field 'add_ledger'
     ========================== -->
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
              // Populate account groups from DB (controller provides $conn)
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

<!-- ==========================
     Modal: Edit Voucher
     - Content is loaded via AJAX when user clicks Edit
     - Form posts back with name="update_voucher" (JS appends this) so controller can detect update
     ========================== -->
<div class="modal fade" id="editVoucherModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" id="editVoucherForm" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Edit Voucher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- this container will be replaced by AJAX response containing form inputs -->
      <div class="modal-body" id="editVoucherContent">
        Loading...
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <!-- submit button — JS appends update_voucher to form data on submit -->
        <button type="submit" name="update_voucher" class="btn btn-warning">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ==========================
     Modal: Add Voucher
     - Simple voucher creation form
     - Controller listens for POST field 'submit_voucher'
     ========================== -->
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
                    // Populate ledgers for debit select
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
                    // Populate ledgers for credit select
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

<!-- ==========================
     Scripts: Bootstrap, jQuery, DataTables, SweetAlert
     - DOM-ready JS initializes DataTables and handles edit/delete via AJAX/fetch
     - Inline comments explain important logic
     ========================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables core + Bootstrap integration -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- SweetAlert2 used for nicer confirmations -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentEditModal = null;

    // Initialize DataTables if available
    // vouchersTable: sort by Date (column index 2) descending so newest vouchers appear on top
    if (typeof jQuery !== 'undefined' && $.fn.dataTable) {
        $('#vouchersTable').DataTable({
            order: [[2, 'desc']],
            pageLength: 10,
            responsive: true,
            columnDefs: [
                // Entries column (3) and Actions column (5) should not be orderable
                { orderable: false, targets: [3,5] }
            ]
        });

        // balancesTable: sorted by ledger name by default
        $('#balancesTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 10,
            responsive: true
        });
    }

    // Global click listener to capture Edit and Delete button clicks inside table rows
    document.addEventListener('click', function(e) {
        // Edit button handler
        let editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            if (!id) return;

            // Show modal with loading placeholder then fetch voucher edit form
            const modalEl = document.getElementById('editVoucherModal');
            currentEditModal = new bootstrap.Modal(modalEl);
            const content = document.getElementById('editVoucherContent');
            content.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div> Loading voucher details...</div>';

            // Request partial (controller should respond to ?action=get_voucher&id=...)
            fetch(window.location.pathname + '?action=get_voucher&id=' + encodeURIComponent(id))
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.text();
                })
                .then(html => {
                    // Replace modal body with returned form HTML
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

            // Confirm deletion with SweetAlert if available, otherwise fallback to native confirm
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
                    // Submit deletion to the same page; controller should handle delete_voucher POST
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

    // Edit voucher form submission handler (the edit form is loaded into modal body)
    // We attach listener to the form element if present on initial page load (may be replaced later).
    document.getElementById('editVoucherForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Build form data and let controller know this is an update action
        const formData = new FormData(this);
        formData.append('update_voucher', '1'); // controller expects this field for updates

        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Basic client-side validation to help user (server still authoritative)
        const drLedger = formData.get('dr_ledger');
        const crLedger = formData.get('cr_ledger');
        const amount = parseFloat(formData.get('amount'));
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

        // Show saving state on submit button
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
        }

        // Send update request to controller; controller should return redirect or handle success
        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                // If controller issues Redirect, follow it
                window.location.href = response.url;
            } else {
                // Otherwise reload with updated flag so flash message shows
                window.location.href = window.location.pathname + '?updated=1';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Submission Error',
                text: 'Failed to update voucher: ' + error.message
            });
            // Reset submit button so user can retry
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Changes';
            }
        });
    });

    // Event used by other code to close modal after successful submission
    document.addEventListener('submitSuccess', function() {
        if (currentEditModal) {
            currentEditModal.hide();
        }
    });

    document.addEventListener('keydown', function (e) {
        const active = document.activeElement;
        const tag = active?.tagName;

        // Don't trigger while typing
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || active?.isContentEditable) {
            return;
        }

        // ALT + SHIFT + V  (Firefox-safe)
        if (e.altKey && e.shiftKey && !e.ctrlKey && !e.metaKey && e.code === 'KeyV') {
            e.preventDefault();

            const modalEl = document.getElementById('addVoucherModal');
            if (!modalEl) return;

            bootstrap.Modal.getOrCreateInstance(modalEl).show();

            setTimeout(() => {
                modalEl.querySelector('input, select, textarea')?.focus();
            }, 200);
        }

        // ALT + SHIFT + T  (Firefox-safe)
        if (e.altKey && e.shiftKey && !e.ctrlKey && !e.metaKey && e.code === 'KeyT') {
            e.preventDefault();
            window.location.href = 'trial_balance.php';

            setTimeout(() => {
                modalEl.querySelector('input, select, textarea')?.focus();
            }, 200);
        }
    });
});
</script>

</body>
</html>