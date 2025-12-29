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
    <style> body { background:#f5f7fa } .container{ max-width:1100px; margin-top:32px } .btn.disabled {pointer-events: none;opacity: 0.65;}</style>
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
                    <div class="col-md-6">
                        <label for="ledgerSelect" class="form-label fw-semibold">Select Ledger</label>
                            <select id="ledgerSelect" class="form-select">
                                <option value="">-- Select a ledger --</option>
                                <?php while ($l = $ledgers->fetch_assoc()): ?>
                                    <option value="<?= (int)$l['ledger_id'] ?>"><?= htmlspecialchars($l['ledger_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_from" class="form-label fw-semibold">From</label>
                        <input type="date" id="filter_from" class="form-control" value="<?= htmlspecialchars($filter_from) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filter_to" class="form-label fw-semibold">To</label>
                        <input type="date" id="filter_to" class="form-control" value="<?= htmlspecialchars($filter_to) ?>">
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
            exportBtn.setAttribute('href', '#');
            exportBtn.classList.add('disabled');
            return;
        }
        exportBtn.setAttribute('href', buildExportUrl(id));
        exportBtn.classList.remove('disabled');

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

    select.addEventListener('change', () => loadLedger(select.value));
    applyBtn.addEventListener('click', () => loadLedger(select.value));

    thisYearBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        const y = new Date().getFullYear();
        fromInput.value = y + '-01-01';
        toInput.value = new Date().toISOString().slice(0,10);
        loadLedger(select.value);
    });
    thisMonthBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        const now = new Date();
        fromInput.value = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-01';
        toInput.value = new Date().toISOString().slice(0,10);
        loadLedger(select.value);
    });

    window.addEventListener('load', () => {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id') || params.get('ledger_id');
        const f = params.get('filter_from');
        const t = params.get('filter_to');
        if (f) fromInput.value = f;
        if (t) toInput.value = t;
        if (id) {
            select.value = id;
            loadLedger(id);
        }
    });
})();
</script>
</body>
</html>

