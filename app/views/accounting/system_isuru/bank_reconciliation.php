<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\system_isuru\bank_reconciliation_controller.php');
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
        <!-- Download Excel will include ledger_id + date range params dynamically via JS -->
        <a id="brsExportBtn" href="#" class="btn btn-light btn-sm">
          <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
        </a>
        <a href="accounting.php" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="card-body">
      <div class="row g-2 align-items-end mb-3 no-print">
        <div class="col-md-4">
          <label for="brsLedgerSelect" class="form-label fw-semibold">Select Bank Account:</label>
          <select id="brsLedgerSelect" class="form-select">
            <option value="">-- Choose Bank Ledger --</option>
            <?php while ($bank = $bankLedgers->fetch_assoc()): ?>
              <option value="<?= $bank['ledger_id'] ?>"><?= htmlspecialchars($bank['ledger_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label for="filter_from" class="form-label fw-semibold">From:</label>
          <input type="date" id="filter_from" name="filter_from" class="form-control" value="<?= htmlspecialchars($filter_from) ?>">
        </div>
        <div class="col-md-2">
          <label for="filter_to" class="form-label fw-semibold">To:</label>
          <input type="date" id="filter_to" name="filter_to" class="form-control" value="<?= htmlspecialchars($filter_to) ?>">
        </div>

        <div class="col-auto">
          <button id="brsApplyBtn" class="btn btn-primary mt-2"><i class="bi bi-funnel"></i> Apply Filter</button>
        </div>

        <div class="col-auto mt-2">
          <a id="thisYearBtn" href="#" class="btn btn-secondary btn-sm mt-2"><i class="bi bi-calendar-range"></i> This Year</a>
          <a id="thisMonthBtn" href="#" class="btn btn-secondary btn-sm mt-2"><i class="bi bi-calendar"></i> This Month</a>
        </div>

        <div class="col-auto ms-auto align-self-end">
          <small class="text-muted">Showing period: <strong id="brsPeriod"><?= htmlspecialchars(date('M d, Y', strtotime($filter_from))) ?> to <?= htmlspecialchars(date('M d, Y', strtotime($filter_to))) ?></strong></small>
        </div>
      </div>

      <div id="brsDet" class="mt-4 text-center text-muted">
        <p>Select a bank account and period to view reconciliation entries...</p>
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
const brsApplyBtn = document.getElementById('brsApplyBtn');
const filterFromInput = document.getElementById('filter_from');
const filterToInput = document.getElementById('filter_to');
const brsPeriod = document.getElementById('brsPeriod');
const thisYearBtn = document.getElementById('thisYearBtn');
const thisMonthBtn = document.getElementById('thisMonthBtn');

// Build export link when ledger selected and period set
function updateExportLink(ledgerId) {
  const from = filterFromInput.value;
  const to = filterToInput.value;
  if (!ledgerId) {
    brsExportBtn.setAttribute('href', '#');
    brsExportBtn.classList.add('disabled');
  } else {
    const url = window.location.pathname + '?export=excel&ledger_id=' + encodeURIComponent(ledgerId)
                + '&filter_from=' + encodeURIComponent(from)
                + '&filter_to=' + encodeURIComponent(to);
    brsExportBtn.setAttribute('href', url);
    brsExportBtn.classList.remove('disabled');
  }
}

// Load reconciliation for selected ledger and period
function loadBankReconciliation(ledgerId) {
  const from = filterFromInput.value;
  const to = filterToInput.value;
  if (!ledgerId) {
    brsDet.innerHTML = '<p class="text-muted">Select a bank account to view entries...</p>';
    updateExportLink(null);
    return;
  }
  updateExportLink(ledgerId);
  brsPeriod.textContent = new Date(from).toLocaleDateString() + ' to ' + new Date(to).toLocaleDateString();
  brsDet.innerHTML = `
    <div class="text-center text-primary my-4">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Loading reconciliation data...
    </div>`;
  fetch(window.location.pathname + '?ledger_id=' + encodeURIComponent(ledgerId)
        + '&filter_from=' + encodeURIComponent(from)
        + '&filter_to=' + encodeURIComponent(to))
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
brsLedgerSelect.addEventListener('change', e => {
  const ledgerId = e.target.value;
  loadBankReconciliation(ledgerId);
});

// Apply filter button
brsApplyBtn.addEventListener('click', () => {
  const ledgerId = brsLedgerSelect.value;
  if (!ledgerId) {
    // update export link anyway
    updateExportLink(null);
    brsDet.innerHTML = '<p class="text-muted">Select a bank account to view entries...</p>';
    return;
  }
  loadBankReconciliation(ledgerId);
});

// Quick buttons
thisYearBtn.addEventListener('click', (ev) => {
  ev.preventDefault();
  const y = new Date().getFullYear();
  filterFromInput.value = y + '-01-01';
  filterToInput.value = new Date().toISOString().slice(0,10);
  brsApplyBtn.click();
});
thisMonthBtn.addEventListener('click', (ev) => {
  ev.preventDefault();
  const now = new Date();
  const first = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-01';
  filterFromInput.value = first;
  filterToInput.value = new Date().toISOString().slice(0,10);
  brsApplyBtn.click();
});

// Auto-load if ?ledger_id= present on URL (and optional date params)
window.addEventListener('load', () => {
  const params = new URLSearchParams(window.location.search);
  const ledgerId = params.get('ledger_id');
  const f = params.get('filter_from');
  const t = params.get('filter_to');
  if (f) filterFromInput.value = f;
  if (t) filterToInput.value = t;
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
