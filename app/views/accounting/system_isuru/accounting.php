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
        <!-- Exchange Rate Update Button -->
        <button id="fetchExchangeRatesBtn" class="btn btn-success" onclick="fetchAndUpdateRates()">
            <i class="fas fa-sync-alt"></i> 🔄 Update Exchange Rates
        </button>
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
         ========================== -->
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
    
    <!-- ==========================
        Modal: Add (Voucher / Ledger) Combined
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
            <!-- Voucher tab -->
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
                     </select>
                  </div>
               </div>
               <div class="row mb-2">
                  <div class="col-md-6">
                     <label>Exchange Rate (1 FC = ? LKR)</label>
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

            <!-- Ledger tab -->
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
              if (data.ledger && data.ledger.ledger_id) {
                 const drSelect = document.getElementById('drLedgerSelect');
                 const crSelect = document.getElementById('crLedgerSelect');
                 const optionHtml = `<option value="${data.ledger.ledger_id}">${data.ledger.ledger_name}</option>`;
                 if (drSelect) drSelect.insertAdjacentHTML('beforeend', optionHtml);
                 if (crSelect) crSelect.insertAdjacentHTML('beforeend', optionHtml);
              }

              refreshLedgerDropdowns().then(() => {
              }).catch(()=>{});

              var tabVoucher = document.querySelector('#tab-voucher-tab');
              if (tabVoucher) {
                  var tab = new bootstrap.Tab(tabVoucher);
                  tab.show();
              }

              this.reset();

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

    // Function to refresh ledger dropdowns
    function refreshLedgerDropdowns(selectedId = null) {
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

    // ========== FIXED: Exchange Rate Functions ==========
    // Function to fetch exchange rate - Now returns LKR per foreign currency
    async function fetchRate(currencyCode, date) {
        try {
            // First try to get from database
            const res = await fetch(window.location.pathname + `?action=get_rate&code=${encodeURIComponent(currencyCode)}&date=${encodeURIComponent(date)}`);
            const json = await res.json();
            
            if (json.rate) {
                console.log(`Rate from DB: 1 ${currencyCode} = ${json.rate} LKR`);
                return json.rate;
            }
            
            // If not in DB, try API as fallback
            console.log(`Rate not in DB, fetching from API for ${currencyCode}`);
            const apiRes = await fetch(`https://v6.exchangerate-api.com/v6/ccd0aba3dbaef425612cd487/latest/USD`);
            const apiData = await apiRes.json();
            
            if (apiData.result === 'success' && apiData.conversion_rates[currencyCode] && apiData.conversion_rates['LKR']) {
                // Calculate LKR per foreign currency
                const rate = apiData.conversion_rates['LKR'] / apiData.conversion_rates[currencyCode];
                console.log(`Rate from API: 1 ${currencyCode} = ${rate} LKR`);
                return rate;
            }
            
            return null;
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

    // FIXED: Update conversion function
    async function updateConversion() {
        let raw = amountInput.value.trim();
        
        // Detect currency symbols
        if (/^\s*\$/.test(raw)) {
            currencySelect.value = '2'; // USD
            raw = raw.replace(/^\s*\$\s*/, '');
            amountInput.value = raw;
        } else if (/^\s*€/.test(raw)) {
            currencySelect.value = '3'; // EUR
            raw = raw.replace(/^\s*€\s*/, '');
            amountInput.value = raw;
        } else if (/^\s*£/.test(raw)) {
            // Try to find GBP in select options
            const gbpOption = Array.from(currencySelect.options).find(opt => opt.text.includes('GBP'));
            if (gbpOption) {
                currencySelect.value = gbpOption.value;
                raw = raw.replace(/^\s*£\s*/, '');
                amountInput.value = raw;
            }
        }
        
        const amount = parseFloat(raw) || 0;
        const currencyId = currencySelect.value;
        const selectedOption = currencySelect.options[currencySelect.selectedIndex];
        const currencyCode = selectedOption ? selectedOption.text.split(' ')[0] : 'LKR';
        
        // If LKR selected
        if (currencyId === '1' || currencyCode === 'LKR') {
            lkrPreview.textContent = `≈ ${amount.toFixed(2)} LKR`;
            exchangeRateInput.value = 1;
            return;
        }
        
        // Get rate for selected currency (how many LKR per unit)
        const rate = await fetchRate(currencyCode, dateInput.value || new Date().toISOString().slice(0,10));
        
        if (rate) {
            exchangeRateInput.value = parseFloat(rate).toFixed(6);
            const amountLkr = (amount * parseFloat(rate));
            lkrPreview.textContent = `≈ ${amountLkr.toFixed(2)} LKR`;
            console.log(`Conversion: ${amount} ${currencyCode} × ${rate} = ${amountLkr} LKR`);
        } else {
            lkrPreview.textContent = 'Rate not found';
            exchangeRateInput.value = '';
        }
    }

    // Bind events
    amountInput.addEventListener('input', () => { updateConversion().catch(console.error); });
    currencySelect.addEventListener('change', () => { updateConversion().catch(console.error); });
    dateInput.addEventListener('change', () => { updateConversion().catch(console.error); });

    // ========== FIXED: Exchange Rate Update Functions ==========
    // Fetch and update exchange rates from API
    function fetchAndUpdateRates() {
        // Show loading state
        const btn = document.getElementById('fetchExchangeRatesBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Fetching rates...';
        btn.disabled = true;

        // Show SweetAlert loading
        Swal.fire({
            title: 'Fetching Exchange Rates',
            text: 'Please wait while we update rates from the API...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Make API call - Using USD as base to get rates FROM USD
        fetch('https://v6.exchangerate-api.com/v6/ccd0aba3dbaef425612cd487/latest/USD')
            .then(response => response.json())
            .then(data => {
                if (data.result === 'success') {
                    // Process and store the rates
                    return storeExchangeRates(data);
                } else {
                    throw new Error('API returned error: ' + (data['error-type'] || 'Unknown error'));
                }
            })
            .then(result => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: `Updated ${result.updated} exchange rates successfully.`,
                    timer: 3000
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Update Rates',
                    text: error.message || 'Could not fetch exchange rates. Please try again later.'
                });
            })
            .finally(() => {
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }

    // Store exchange rates in database
    async function storeExchangeRates(apiData) {
        const rates = apiData.conversion_rates;
        const rateDate = new Date().toISOString().split('T')[0]; // Today's date
        
        // Get LKR rate from USD base
        const lkrRate = rates['LKR'];
        
        if (!lkrRate) {
            throw new Error('LKR rate not found in API response');
        }
        
        console.log(`Base LKR rate: 1 USD = ${lkrRate} LKR`);
        
        // Prepare data for all currencies we want to store
        // We'll store how many LKR per unit of foreign currency
        const currenciesToStore = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'INR'];
        const updatedRates = [];
        
        for (const currencyCode of currenciesToStore) {
            if (rates[currencyCode]) {
                try {
                    // Get currency_id from currencies table
                    const currencyResponse = await fetch(window.location.pathname + '?action=get_currency_id&code=' + currencyCode);
                    const currencyData = await currencyResponse.json();
                    
                    if (currencyData.currency_id) {
                        // Calculate LKR per unit of foreign currency
                        // If 1 USD = 309 LKR, and 1 USD = 0.85 EUR, then 1 EUR = 309/0.85 = 363.53 LKR
                        const rateToLkr = lkrRate / rates[currencyCode];
                        
                        // Store the rate
                        const storeResponse = await fetch(window.location.pathname, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                'update_exchange_rate': '1',
                                'currency_id': currencyData.currency_id,
                                'rate_date': rateDate,
                                'rate_to_lkr': rateToLkr,
                                'source': 'API'
                            })
                        });
                        
                        const result = await storeResponse.json();
                        if (result.success) {
                            updatedRates.push(currencyCode);
                            console.log(`Stored ${currencyCode}: 1 ${currencyCode} = ${rateToLkr.toFixed(2)} LKR (from USD rate: ${rates[currencyCode]})`);
                        }
                    }
                } catch (error) {
                    console.error(`Failed to update ${currencyCode}:`, error);
                }
            }
        }
        
        return { updated: updatedRates.length };
    }
    </script>

    <!-- ==========================
         Scripts
         ========================== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentEditModal = null;
        
        // Auto-dismiss alerts
        (function() {
            const AUTO_DISMISS_MS = 5000;
            function closeAlert(alert) {
                if (!alert || alert.classList.contains('fading')) return;
                alert.classList.add('fading', 'fade-out');
                setTimeout(() => alert.remove(), 450);
            }

            document.querySelectorAll('.alert').forEach(alert => {
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

        // Fetch cash profit data
        function loadCashProfitData(startDate = null, endDate = null) {
            document.getElementById('chartLoading').style.display = 'block';
            document.getElementById('chartContainer').style.opacity = '0.5';
            
            let url = window.location.pathname + '?action=get_cash_profit';
            if (startDate && endDate) {
                url += '&start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('chartLoading').style.display = 'none';
                    document.getElementById('chartContainer').style.opacity = '1';
                    
                    if (data.success) {
                        document.getElementById('totalCashIn').textContent = data.totals.total_in.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                        document.getElementById('totalCashOut').textContent = data.totals.total_out.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                        document.getElementById('netCashProfit').textContent = data.totals.net_profit.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                        
                        const ctx = document.getElementById('cashProfitChart').getContext('2d');
                        
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
                                    legend: { position: 'top' },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) label += ': ';
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
                                            callback: function(value) {
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

        // Initialize DataTables
        if (typeof jQuery !== 'undefined' && $.fn.dataTable) {
            $('#vouchersTable').DataTable({
                order: [[2, 'desc']],
                pageLength: 10,
                responsive: true,
                columnDefs: [
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

        // Global click listener for Edit and Delete buttons
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

            let deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                const voucherId = deleteBtn.getAttribute('data-id') || deleteBtn.dataset.id;
                if (!voucherId) return;

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

        // Edit voucher form submission handler
        document.getElementById('editVoucherForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('update_voucher', '1');

            const submitBtn = this.querySelector('button[type="submit"]');
            
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

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
            }

            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
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
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save Changes';
                }
            });
        });

        document.addEventListener('submitSuccess', function() {
            if (currentEditModal) {
                currentEditModal.hide();
            }
        });
    });
    </script>

</body>
</html>