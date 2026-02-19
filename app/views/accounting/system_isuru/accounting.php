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
    <!-- Chart.js for profit graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Date Range Picker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <!-- DataTables CSS (Bootstrap 5 integration) for searchable/sortable tables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* Basic page styling */
        body { background: #f8f9fa; font-family: 'Roboto', sans-serif; }
        .container { max-width: 1200px; margin-top: 40px; }
        .card { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #0d6efd; font-weight: 700; text-align: center; margin-bottom: 20px; }
        table th, table td { text-align: center; vertical-align: middle; }
        .btn-group-custom { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-group-custom .btn { flex: 1; min-width: 200px; }
        /* Alerts: space for left close button and smooth fade-out */
        .alert { margin-bottom: 20px; position: relative; padding-left: 48px; transition: opacity .4s ease, max-height .4s ease, padding .3s ease; }
        /* Left-positioned close button for alerts (keeps it on the left side) */
        .alert .alert-close-left { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: inherit; font-size: 1.05rem; padding: 2px 6px; cursor: pointer; }
        .alert.fade-out { opacity: 0; max-height: 0; padding-top: 0; padding-bottom: 0; margin-bottom: 0; overflow: hidden; }
        /* Graph container styling */
        .graph-container { height: 400px; margin-bottom: 20px; }
        /* Date range picker styling */
        .daterange-container { display: flex; gap: 10px; align-items: center; }
        .daterange-btn { cursor: pointer; background-color: #141414; border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.375rem 0.75rem; }
        .daterange-btn:hover { background-color: #6e757c; }
    </style>
</head>
<body>

<div class="container mb-4">
    <!-- Page heading -->
    <h2>YDF Accounting System - <?= htmlspecialchars($active_country['country_name']) ?> (<?= htmlspecialchars($active_country['currency_code']) ?>)</h2>

    <!-- Country Selector with All Countries option -->
    <div class="mb-4">
        <form method="POST" class="d-inline">
            <label for="countrySelect" class="form-label">Select Country:</label>
            <select name="country_id" id="countrySelect" class="form-select d-inline w-auto" onchange="this.form.submit()">
                <option value="0" <?= ($active_country_id == 0) ? 'selected' : '' ?>>🌍 All Countries</option>
                <?php
                $countries = $conn->query("SELECT id, country_name FROM countries ORDER BY country_name ASC");
                while ($c = $countries->fetch_assoc()) {
                    $sel = ($c['id'] == $active_country_id) ? 'selected' : '';
                    echo "<option value='{$c['id']}' $sel>{$c['country_name']}</option>";
                }
                ?>
            </select>
            <input type="hidden" name="select_country" value="1">
        </form>
    </div>

    <!-- Quick action buttons (open modals or navigate to reports) -->
    <div class="btn-group-custom mb-4">
        <button id="openAddCombinedBtn" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCombinedModal">➕ Add (Voucher / Ledger)</button>
        <a href="view_ledger.php" class="btn btn-info text-white">📘 View Ledger</a>
        <a href="bank_reconciliation.php" class="btn btn-secondary text-white">🏦 Bank Reconciliation</a>
        <a href="trial_balance.php" class="btn btn-warning text-dark">📊 Trial Balance</a>
        <a href="income_statement.php" class="btn btn-danger">📈 Income Statement</a>
        <a href="balance_sheet.php" class="btn btn-primary" style="background-color:#6610f2;">💰 Balance Sheet</a>
    </div>

    <!-- Flash messages: simple feedback after actions (delete/update/save) -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success custom-alert" role="alert">
            <button type="button" class="alert-close-left" aria-label="Close"><i class="fas fa-times"></i></button>
            ✅ Voucher deleted successfully.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success custom-alert" role="alert">
            <button type="button" class="alert-close-left" aria-label="Close"><i class="fas fa-times"></i></button>
            ✅ Voucher updated successfully.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success custom-alert" role="alert">
            <button type="button" class="alert-close-left" aria-label="Close"><i class="fas fa-times"></i></button>
            ✅ Saved successfully.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger custom-alert" role="alert">
            <button type="button" class="alert-close-left" aria-label="Close"><i class="fas fa-times"></i></button>
            ❌ Error:
            <?php
            switch($_GET['error']) {
                case 'invalid_input': echo 'Invalid input data'; break;
                case 'bad_id': echo 'Invalid voucher ID'; break;
                case 'same_ledgers': echo 'Debit and Credit ledgers cannot be the same'; break;
                case 'cross_country_posting': echo 'Cannot post between ledgers from different countries'; break;
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

    <!-- Cash Profit Graph Section with Date Range Selector -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-chart-line me-2"></i>Cash Profit Analysis
            </div>
            <div class="daterange-container">
                <i class="fas fa-calendar-alt me-2"></i>
                <div id="reportrange" class="daterange-btn">
                    <i class="fa fa-calendar"></i>
                    <span></span> <i class="fa fa-caret-down"></i>
                </div>
                <button id="refreshChartBtn" class="btn btn-sm btn-light">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Loading indicator -->
            <div id="chartLoading" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading chart data...</p>
            </div>
            
            <!-- Chart container -->
            <div class="graph-container" id="chartContainer">
                <canvas id="cashProfitChart"></canvas>
            </div>
            
            <!-- Summary Cards -->
            <div class="row mt-4 text-center">
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <small class="text-muted">Period Start</small>
                        <h6 id="periodStart" class="mb-0">-</h6>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <small class="text-muted">Period End</small>
                        <h6 id="periodEnd" class="mb-0">-</h6>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="border rounded p-3 bg-light">
                        <small class="text-muted">Days</small>
                        <h6 id="periodDays" class="mb-0">-</h6>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="border rounded p-3 bg-success text-white">
                        <small>Total Cash In</small>
                        <h5 id="totalCashIn" class="mb-0">0.00</h5>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="border rounded p-3 bg-danger text-white">
                        <small>Total Cash Out</small>
                        <h5 id="totalCashOut" class="mb-0">0.00</h5>
                    </div>
                </div>
            </div>
            <div class="row mt-2 text-center">
                <div class="col-md-4 offset-md-4">
                    <div class="border rounded p-3 bg-primary text-white">
                        <small>Net Cash Profit</small>
                        <h4 id="netCashProfit" class="mb-0">0.00</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- All Vouchers table -->
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
                            <td><?= intval($v['voucher_id']); ?></td>
                            <td><?= htmlspecialchars($v['voucher_type']); ?></td>
                            <td><?= htmlspecialchars($v['date']); ?></td>
                            <td class="text-start">
                                <?= $v['entries']; ?>
                                <?php if (isset($v['country_name']) && $v['country_name'] == 'All Countries'): ?>
                                    <br><span class="badge bg-info mt-1">🌍 Global</span>
                                <?php endif; ?>
                            </td>
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
                <?php endif; ?>
            </tbody>
        </table>
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
    Modal: Add (Voucher / Ledger) Combined
    - Single modal with tabs to add either a Voucher or a Ledger
    - Keeps original form IDs so existing JS continues to work
    ========================== -->
<div class="modal fade" id="addCombinedModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <ul class="nav nav-tabs" role="tablist" style="flex:1">
          <li class="nav-item" role="presentation"><a class="nav-link active" id="tab-voucher-tab" data-bs-toggle="tab" href="#tab-voucher" role="tab">🧾 Voucher</a></li>
          <li class="nav-item" role="presentation"><a class="nav-link" id="tab-ledger-tab" data-bs-toggle="tab" href="#tab-ledger" role="tab">➕ Ledger</a></li>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body tab-content">
        <!-- Voucher tab (keeps original form) -->
        <div class="tab-pane fade show active" id="tab-voucher" role="tabpanel">
          <form method="POST" id="addVoucherForm">
           <div class="row mb-2">
              <div class="col-md-3">
                 <label>Voucher Type</label>
                 <select name="voucher_type" class="form-select" required>
                    <option value="Payment">Payment</option>
                    <option value="Receipt">Receipt</option>
                    <option value="Journal">Journal</option>
                    <option value="Contra">Contra</option>
                 </select>
              </div>
              <div class="col-md-3">
                 <label>Date</label>
                 <input type="date" name="date" class="form-control" required value="<?= date('Y-m-d'); ?>">
              </div>
              <div class="col-md-3">
                 <label>Amount (<?= htmlspecialchars($active_country['currency_code']) ?>)</label>
                 <input type="text" name="amount" id="amountInput" class="form-control" required placeholder="e.g., $100 or 100">
              </div>
              <div class="col-md-3">
                 <label>Currency</label>
                 <select name="currency_id" id="currencySelect" class="form-select">
                    <option value="1">LKR (Sri Lankan Rupee)</option>
                    <option value="2">USD (US Dollar)</option>
                    <option value="3">EUR (Euro)</option>
                    <!-- Add more currencies as needed -->
                 </select>
              </div>
           </div>
           <div class="row mb-2">
              <div class="col-md-6">
                 <label>Exchange Rate (to LKR)</label>
                 <input type="number" name="exchange_rate" id="exchangeRateInput" class="form-control" step="0.000001" value="1.000000" readonly>
              </div>
              <div class="col-md-6">
                 <label>LKR Equivalent</label>
                 <div id="lkrPreview" class="form-control-plaintext">≈ 0.00 LKR</div>
              </div>
           </div>
           <div class="row mb-2">
              <div class="col-md-6">
                 <label>Debit Ledger (Dr)</label>
                 <select name="dr_ledger" id="drLedgerSelect" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php
                    $ledgersDr = $conn->prepare("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
                    $ledgersDr->execute();
                    $resDr = $ledgersDr->get_result();
                    while ($l = $resDr->fetch_assoc()) {
                       echo "<option value='" . intval($l['ledger_id']) . "'>" . htmlspecialchars($l['ledger_name']) . "</option>";
                    }
                    $ledgersDr->close();
                    ?>
                </select>
                </div>
                <div class="col-md-6">
                   <label>Credit Ledger (Cr)</label>
                   <select name="cr_ledger" id="crLedgerSelect" class="form-select" required>
                      <option value="">-- Select --</option>
                      <?php
                      $ledgersCr = $conn->prepare("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
                      $ledgersCr->execute();
                      $resCr = $ledgersCr->get_result();
                      while ($l = $resCr->fetch_assoc()) {
                         echo "<option value='" . intval($l['ledger_id']) . "'>" . htmlspecialchars($l['ledger_name']) . "</option>";
                      }
                      $ledgersCr->close();
                      ?>
                   </select>
                </div>
           </div>
           <div class="mb-3">
              <label>Narration</label>
              <textarea name="narration" class="form-control" rows="2"></textarea>
           </div>
           <div class="text-end mt-3">
             <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
             <button type="submit" name="submit_voucher" class="btn btn-success">💾 Save Transaction</button>
           </div>
          </form>
        </div>

        <!-- Ledger tab (keeps original form and id so JS works) -->
        <div class="tab-pane fade" id="tab-ledger" role="tabpanel">
          <form method="POST" id="addLedgerForm">
           <div class="mb-2">
            <label>Account Group</label>
            <select name="group_id" class="form-select mb-2" required>
                <option value="">-- Select Account Group --</option>
                <?php
              $groups = $conn->prepare("SELECT group_id, group_name, group_type FROM account_groups ORDER BY group_name ASC");
              $groups->execute();
              $resGroups = $groups->get_result();
              while ($g = $resGroups->fetch_assoc()) {
                 echo "<option value='" . intval($g['group_id']) . "'>" . htmlspecialchars($g['group_name']) . " (" . htmlspecialchars($g['group_type']) . ")</option>";
              }
              $groups->close();
              ?>
            </select>
            <label>Ledger Name</label>
            <input type="text" name="ledger_name" class="form-control mb-2" required>

            <label>Opening Balance</label>
            <input type="number" step="0.01" name="opening_balance" class="form-control mb-2" value="0">

            <label>Type</label>
            <select name="balance_type" class="form-select mb-2">
                <option value="Dr">Dr</option>
                <option value="Cr">Cr</option>
            </select>
           </div>
           <div class="text-end mt-3">
             <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
             <button type="submit" name="add_ledger" class="btn btn-success">💾 Save Ledger</button>
           </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div> 

<script>
// Handle Add Ledger form submission via AJAX
document.getElementById('addLedgerForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('add_ledger', '1');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
       submitBtn.disabled = true;
       submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
    }
    
    fetch(window.location.pathname, {
       method: 'POST',
       headers: { 'X-Requested-With': 'XMLHttpRequest' },
       body: formData
    })
    .then(response => response.json())
.then(data => {
       if (data.success) {
          // If server returned the new ledger, append it immediately for snappy UX
          if (data.ledger && data.ledger.ledger_id) {
             const drSelect = document.getElementById('drLedgerSelect');
             const crSelect = document.getElementById('crLedgerSelect');
             const optionHtml = `<option value="${data.ledger.ledger_id}">${data.ledger.ledger_name}</option>`;
             if (drSelect) drSelect.insertAdjacentHTML('beforeend', optionHtml);
             if (crSelect) crSelect.insertAdjacentHTML('beforeend', optionHtml);
          }

          // Refresh complete list but do NOT auto-select the newly created ledger — keep "-- Select --" so user chooses explicitly
          refreshLedgerDropdowns().then(() => {
              // No automatic selection; dropdowns remain at default
          }).catch(()=>{});

          // Keep the combined modal open so user can continue working
          // Switch to the Voucher tab to let them immediately create a voucher
          var tabVoucher = document.querySelector('#tab-voucher-tab');
          if (tabVoucher) {
              var tab = new bootstrap.Tab(tabVoucher);
              tab.show();
          }

          // Reset ledger part of the form
          this.reset();

          // Show success toast and keep modal open
          Swal.fire({toast: true, position: 'top-end', icon: 'success', title: 'Ledger added', showConfirmButton: false, timer: 1400});
       } else {
          Swal.fire('Error', data.message || 'Failed to add ledger', 'error');
       }
    })
    .catch(error => {
       console.error('Error:', error);
       Swal.fire('Error', 'Network error: ' + error.message, 'error');
    })
    .finally(() => {
       if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '💾 Save Ledger';
       }
    });
});

// Function to refresh ledger dropdowns in real-time
function refreshLedgerDropdowns(selectedId = null) {
    // return a promise so callers can wait for completion
    return fetch(window.location.pathname + '?action=get_ledgers')
       .then(response => response.json())
       .then(data => {
          if (data.ledgers) {
             const drSelect = document.getElementById('drLedgerSelect');
             const crSelect = document.getElementById('crLedgerSelect');
             
             const defaultOption = '<option value="">-- Select --</option>';
             let ledgerOptions = '';
             
             data.ledgers.forEach(ledger => {
                ledgerOptions += `<option value="${ledger.ledger_id}">${ledger.ledger_name}</option>`;
             });
             
             if (drSelect) drSelect.innerHTML = defaultOption + ledgerOptions;
             if (crSelect) crSelect.innerHTML = defaultOption + ledgerOptions;

             // If caller requested a selectedId, set it (if present in list)
             if (selectedId) {
                 if (drSelect) drSelect.value = selectedId;
                 if (crSelect) crSelect.value = selectedId;
             }
          }
       })
       .catch(error => {
           console.error('Error refreshing ledgers:', error);
           throw error;
       });
}

// Preload currencies/rates or fetch dynamically:
async function fetchRate(currencyCode, date) {
  // Example: server endpoint ?action=get_rate&code=USD&date=2026-01-14
  const res = await fetch(window.location.pathname + `?action=get_rate&code=${encodeURIComponent(currencyCode)}&date=${encodeURIComponent(date)}`);
  const json = await res.json();
  return json.rate || null;
}

// Function to fetch exchange rate
async function fetchRate(currencyCode, date) {
  try {
    const res = await fetch(window.location.pathname + `?action=get_rate&code=${encodeURIComponent(currencyCode)}&date=${encodeURIComponent(date)}`);
    const json = await res.json();
    return json.rate || null;
  } catch (e) {
    console.error('Error fetching rate:', e);
    return null;
  }
}

const amountInput = document.getElementById('amountInput');
const currencySelect = document.getElementById('currencySelect');
const exchangeRateInput = document.getElementById('exchangeRateInput');
const lkrPreview = document.getElementById('lkrPreview');
const dateInput = document.querySelector('input[name="date"]');

async function updateConversion() {
  let raw = amountInput.value.trim();
  // Detect $ (USD) or other symbols (extend as needed)
  if (/^\s*\$/.test(raw)) {
    currencySelect.value = '2'; // Assuming USD is value 2
    raw = raw.replace(/^\s*\$\s*/, '');
    amountInput.value = raw;
  }
  const amount = parseFloat(raw) || 0;
  const currencyId = currencySelect.value;
  const currencyCode = currencySelect.options[currencySelect.selectedIndex].text.split(' ')[0]; // e.g., 'USD'
  if (currencyId === '1' || currencyCode === 'LKR') { // Assuming LKR is 1
    lkrPreview.textContent = `≈ ${amount.toFixed(2)} LKR`;
    exchangeRateInput.value = 1;
    return;
  }
  // get rate for selected currency and date
  const rate = await fetchRate(currencyCode, dateInput.value || new Date().toISOString().slice(0,10));
  if (rate) {
    exchangeRateInput.value = parseFloat(rate).toFixed(6);
    const amountLkr = (amount * parseFloat(rate));
    lkrPreview.textContent = `≈ ${amountLkr.toFixed(2)} LKR`;
  } else {
    lkrPreview.textContent = 'Rate not found';
  }
}

// Bind events
amountInput.addEventListener('input', () => { updateConversion().catch(console.error); });
currencySelect.addEventListener('change', () => { updateConversion().catch(console.error); });
dateInput.addEventListener('change', () => { updateConversion().catch(console.error); });
</script>

<!-- ==========================
     Scripts: Bootstrap, jQuery, DataTables, SweetAlert, DateRangePicker
     - DOM-ready JS initializes DataTables and handles edit/delete via AJAX/fetch
     ========================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (required by DataTables and DateRangePicker) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Moment.js (required by DateRangePicker) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<!-- Date Range Picker JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- DataTables core + Bootstrap integration -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- SweetAlert2 used for nicer confirmations -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentEditModal = null;
    // Auto-dismiss and left-close behavior for flash alerts (5s)
    (function() {
        const AUTO_DISMISS_MS = 5000;
        function closeAlert(alert) {
            if (!alert || alert.classList.contains('fading')) return;
            alert.classList.add('fading', 'fade-out');
            setTimeout(() => alert.remove(), 450);
        }

        document.querySelectorAll('.alert').forEach(alert => {
            // attach click handler for left close
            const btn = alert.querySelector('.alert-close-left');
            if (btn) {
                btn.addEventListener('click', () => closeAlert(alert));
            } else {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'alert-close-left';
                b.setAttribute('aria-label', 'Close');
                b.innerHTML = '<i class="fas fa-times"></i>';
                b.addEventListener('click', () => closeAlert(alert));
                alert.prepend(b);
            }

            // schedule auto-dismiss unless marked sticky via data-sticky="1"
            if (!alert.dataset.sticky) {
                setTimeout(() => closeAlert(alert), AUTO_DISMISS_MS);
            }
        });
    })();

    // Initialize DateRangePicker
    let startDate = moment().subtract(6, 'days');
    let endDate = moment();
    
    function updateDateRangeDisplay(start, end) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        document.getElementById('periodStart').textContent = start.format('YYYY-MM-DD');
        document.getElementById('periodEnd').textContent = end.format('YYYY-MM-DD');
        document.getElementById('periodDays').textContent = end.diff(start, 'days') + 1 + ' days';
    }
    
    $('#reportrange').daterangepicker({
        startDate: startDate,
        endDate: endDate,
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
           'Last 3 Months': [moment().subtract(3, 'months').startOf('month'), moment().endOf('month')],
           'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
           'This Year': [moment().startOf('year'), moment().endOf('year')],
           'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        }
    }, function(start, end, label) {
        updateDateRangeDisplay(start, end);
        loadCashProfitData(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
    });
    
    updateDateRangeDisplay(startDate, endDate);

    // Fetch cash profit data for the graph with date range
    function loadCashProfitData(startDate = null, endDate = null) {
        // Show loading indicator
        document.getElementById('chartLoading').style.display = 'block';
        document.getElementById('chartContainer').style.opacity = '0.5';
        
        let url = window.location.pathname + '?action=get_cash_profit';
        if (startDate && endDate) {
            url += '&start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Hide loading indicator
                document.getElementById('chartLoading').style.display = 'none';
                document.getElementById('chartContainer').style.opacity = '1';
                
                if (data.success) {
                    // Update summary numbers
                    document.getElementById('totalCashIn').textContent = data.totals.total_in.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                    document.getElementById('totalCashOut').textContent = data.totals.total_out.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                    document.getElementById('netCashProfit').textContent = data.totals.net_profit.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                    
                    // Create/update chart
                    const ctx = document.getElementById('cashProfitChart').getContext('2d');
                    
                    // Destroy existing chart if it exists
                    if (window.cashProfitChart instanceof Chart) {
                        window.cashProfitChart.destroy();
                    }
                    
                    window.cashProfitChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Cash In (Receipts)',
                                    data: data.cashIn,
                                    borderColor: 'rgb(40, 167, 69)',
                                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                    tension: 0.1,
                                    fill: true,
                                    pointBackgroundColor: 'rgb(40, 167, 69)'
                                },
                                {
                                    label: 'Cash Out (Payments)',
                                    data: data.cashOut,
                                    borderColor: 'rgb(220, 53, 69)',
                                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                    tension: 0.1,
                                    fill: true,
                                    pointBackgroundColor: 'rgb(220, 53, 69)'
                                },
                                {
                                    label: 'Net Profit',
                                    data: data.netProfit,
                                    borderColor: 'rgb(13, 110, 253)',
                                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                    borderWidth: 3,
                                    tension: 0.1,
                                    fill: false,
                                    pointBackgroundColor: 'rgb(13, 110, 253)'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: false
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += context.parsed.y.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value, index, values) {
                                            return value.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    console.error('Failed to load cash profit data');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load chart data: ' + (data.message || 'Unknown error')
                    });
                }
            })
            .catch(error => {
                console.error('Error loading cash profit data:', error);
                document.getElementById('chartLoading').style.display = 'none';
                document.getElementById('chartContainer').style.opacity = '1';
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to load chart data. Please try again.'
                });
            });
    }

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
            ],
            language: {
                emptyTable: "No vouchers found for this country."
            }
        });
    }

    // Load initial cash profit data
    loadCashProfitData(startDate.format('YYYY-MM-DD'), endDate.format('YYYY-MM-DD'));

    // Refresh button handler
    document.getElementById('refreshChartBtn').addEventListener('click', function() {
        const range = $('#reportrange').data('daterangepicker');
        loadCashProfitData(range.startDate.format('YYYY-MM-DD'), range.endDate.format('YYYY-MM-DD'));
    });

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