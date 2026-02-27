<?php
include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\system_isuru\view_ledger_controller.php');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Ledger</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style> 
        body { background:#f5f7fa } 
        .container{ max-width:1100px; margin-top:32px } 
        .btn.disabled {pointer-events: none;opacity: 0.65;}
        .debug-info { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-left: 4px solid #17a2b8; font-size: 13px; display: none; }
        .debug-info pre { margin: 5px 0 0; background: #fff; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Debug toggle (Ctrl+Shift+D) -->
        <div class="debug-info">
            <div class="d-flex justify-content-between align-items-center">
                <strong>🔍 Debug Information:</strong>
                <button class="btn btn-sm btn-outline-secondary" onclick="this.parentElement.parentElement.style.display='none'">Hide</button>
            </div>
            <pre><?= htmlspecialchars(print_r($debug_info, true)) ?></pre>
            <small class="text-muted">Press Ctrl+Shift+D to toggle this panel</small>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
                <div>
                    <h5 class="mb-0">
                        📘 View Ledger - 
                        <?= htmlspecialchars($active_country['country_name'] ?? 'N/A') ?> 
                        (<?= htmlspecialchars($active_country['currency_code'] ?? 'N/A') ?>)
                    </h5>
                    <small class="text-white-50">Select a ledger to view detailed transactions</small>
                </div>
                <a class="btn btn-light btn-sm" href="accounting.php">← Back</a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3 align-items-end">
                    <div class="col-md-6">
                        <label for="ledgerSelect" class="form-label fw-semibold">Select Ledger</label>
                        <select id="ledgerSelect" class="form-select">
                            <option value="">-- Select a ledger --</option>
                            <?php if (isset($ledgers) && $ledgers && $ledgers->num_rows > 0): ?>
                                <?php while ($l = $ledgers->fetch_assoc()): ?>
                                    <option value="<?= (int)$l['ledger_id'] ?>"><?= htmlspecialchars($l['ledger_name']) ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No ledgers available</option>
                            <?php endif; ?>
                        </select>
                        
                        <?php if (!isset($ledgers) || !$ledgers || $ledgers->num_rows === 0): ?>
                            <div class="alert alert-warning mt-2">
                                <strong>⚠️ No ledgers found!</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Country ID: <?= $active_country_id ?></li>
                                    <li>Countries table exists: <?= $debug_info['countries_exist'] ?></li>
                                    <li>Ledgers table exists: <?= $debug_info['ledgers_exist'] ?></li>
                                    <?php if ($debug_info['ledgers_exist'] === 'No'): ?>
                                        <li>The ledgers table doesn't exist. Please create it first.</li>
                                    <?php elseif ($debug_info['ledgers_found'] == 0): ?>
                                        <li>No ledgers found for country ID <?= $active_country_id ?>. Please add some ledgers.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_from" class="form-label fw-semibold">From</label>
                        <input type="date" id="filter_from" class="form-control" value="<?= htmlspecialchars($filter_from ?? date('Y-01-01')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filter_to" class="form-label fw-semibold">To</label>
                        <input type="date" id="filter_to" class="form-control" value="<?= htmlspecialchars($filter_to ?? date('Y-m-d')) ?>">
                    </div>
                </div>
                <div class="d-flex gap-2 mb-3 no-print">
                    <button id="applyFilter" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Apply Filter</button>
                    <button id="thisYearBtn" class="btn btn-secondary btn-sm">This Year</button>
                    <button id="thisMonthBtn" class="btn btn-secondary btn-sm">This Month</button>
                    <a id="exportAllBtn" class="btn btn-success btn-sm ms-auto disabled" href="#" target="_blank"><i class="bi bi-file-earmark-spreadsheet"></i> Export Selected</a>
                </div>
                <div id="ledgerDetails">
                    <p class="text-muted">Select a ledger to view details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- SQL to create sample data if needed -->
    <?php if ($debug_info['ledgers_found'] == 0 && $debug_info['ledgers_exist'] === 'Yes'): ?>
    <div class="container mt-2">
        <div class="card">
            <div class="card-header bg-light">
                <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#sqlHelp">
                    📋 Click to show SQL for creating sample ledgers
                </button>
            </div>
            <div class="collapse" id="sqlHelp">
                <div class="card-body">
                    <pre class="bg-light p-3"><code>-- Insert sample ledgers for country ID <?= $active_country_id ?>
INSERT INTO ledgers (ledger_name, opening_balance, balance_type, country_id) VALUES
('Cash Account', 1000.00, 'DR', <?= $active_country_id ?>),
('Bank Account', 5000.00, 'DR', <?= $active_country_id ?>),
('Sales Account', 0.00, 'CR', <?= $active_country_id ?>),
('Purchase Account', 0.00, 'DR', <?= $active_country_id ?>),
('Capital Account', 10000.00, 'CR', <?= $active_country_id ?>),
('Accounts Receivable', 2000.00, 'DR', <?= $active_country_id ?>),
('Accounts Payable', 1500.00, 'CR', <?= $active_country_id ?>),
('Inventory', 3000.00, 'DR', <?= $active_country_id ?>),
('Salary Expense', 0.00, 'DR', <?= $active_country_id ?>),
('Rent Expense', 0.00, 'DR', <?= $active_country_id ?>);</code></pre>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

<script>
(function(){
    const select = document.getElementById('ledgerSelect');
    const details = document.getElementById('ledgerDetails');
    const fromInput = document.getElementById('filter_from');
    const toInput = document.getElementById('filter_to');
    const applyBtn = document.getElementById('applyFilter');
    const thisYearBtn = document.getElementById('thisYearBtn');
    const thisMonthBtn = document.getElementById('thisMonthBtn');
    const exportBtn = document.getElementById('exportAllBtn');

    function buildAjaxUrl(id) {
        const base = window.location.pathname;
        const params = new URLSearchParams();
        params.set('id', id);
        params.set('filter_from', fromInput.value);
        params.set('filter_to', toInput.value);
        return base + '?' + params.toString();
    }

    function buildExportUrl(id) {
        const base = window.location.pathname;
        const params = new URLSearchParams();
        params.set('id', id);
        params.set('export', 'excel');
        params.set('filter_from', fromInput.value);
        params.set('filter_to', toInput.value);
        return base + '?' + params.toString();
    }

    async function loadLedger(id) {
        if (!id) {
            details.innerHTML = '<p class="text-muted">Select a ledger to view details...</p>';
            if (exportBtn) {
                exportBtn.setAttribute('href', '#');
                exportBtn.classList.add('disabled');
            }
            return;
        }
        
        if (exportBtn) {
            exportBtn.setAttribute('href', buildExportUrl(id));
            exportBtn.classList.remove('disabled');
        }

        details.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-info spinner-border-sm" role="status"></div> Loading...</div>';
        try {
            const resp = await fetch(buildAjaxUrl(id), { cache: 'no-store' });
            if (!resp.ok) throw new Error('Server returned ' + resp.status);
            const html = await resp.text();
            details.innerHTML = html;
            window.scrollTo({ top: details.offsetTop - 20, behavior: 'smooth' });
        } catch (err) {
            console.error(err);
            details.innerHTML = '<div class="alert alert-danger">Error loading ledger: ' + err.message + '</div>';
        }
    }

    if (select) {
        select.addEventListener('change', () => loadLedger(select.value));
    }
    
    if (applyBtn) {
        applyBtn.addEventListener('click', () => loadLedger(select.value));
    }

    if (thisYearBtn) {
        thisYearBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            const y = new Date().getFullYear();
            fromInput.value = y + '-01-01';
            toInput.value = new Date().toISOString().slice(0,10);
            loadLedger(select.value);
        });
    }
    
    if (thisMonthBtn) {
        thisMonthBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            const now = new Date();
            fromInput.value = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-01';
            toInput.value = new Date().toISOString().slice(0,10);
            loadLedger(select.value);
        });
    }

    window.addEventListener('load', () => {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id') || params.get('ledger_id');
        const f = params.get('filter_from');
        const t = params.get('filter_to');
        if (f) fromInput.value = f;
        if (t) toInput.value = t;
        if (id && select) {
            // Check if the option exists
            let optionExists = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == id) {
                    optionExists = true;
                    break;
                }
            }
            if (optionExists) {
                select.value = id;
                loadLedger(id);
            }
        }
    });
})();
</script>

<!-- Bootstrap JS for collapse functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toggle debug info (Ctrl+Shift+D) -->
<script>
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'D') {
            const debugDiv = document.querySelector('.debug-info');
            if (debugDiv) {
                debugDiv.style.display = debugDiv.style.display === 'none' ? 'block' : 'none';
            }
            e.preventDefault();
        }
    });
</script>
</body>
</html>