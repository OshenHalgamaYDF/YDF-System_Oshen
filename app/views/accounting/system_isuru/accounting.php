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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { 
            background: #f8f9fa; 
            font-family: 'Roboto', sans-serif; 
        }
        .container { 
            max-width: 1400px; 
            margin-top: 30px; 
        }
        .card { 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            border: none;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 500;
            padding: 15px 20px;
        }
        h2 { 
            color: #0d6efd; 
            font-weight: 700; 
            text-align: center; 
            margin-bottom: 25px; 
        }
        table th, table td { 
            text-align: center; 
            vertical-align: middle; 
        }
        .btn-group-custom { 
            display: flex; 
            gap: 12px; 
            flex-wrap: wrap; 
            margin-bottom: 25px;
        }
        .btn-group-custom .btn { 
            flex: 1; 
            min-width: 180px;
            padding: 12px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-group-custom .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Alert styles */
        .alert { 
            margin-bottom: 20px; 
            position: relative; 
            padding: 15px 15px 15px 50px; 
            border: none;
            border-radius: 8px;
        }
        .alert .alert-close-left { 
            position: absolute; 
            left: 15px; 
            top: 50%; 
            transform: translateY(-50%); 
            border: none; 
            background: transparent; 
            color: inherit; 
            font-size: 1.1rem; 
            padding: 5px; 
            cursor: pointer; 
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .alert .alert-close-left:hover {
            opacity: 1;
        }
        .alert.fade-out { 
            opacity: 0; 
            max-height: 0; 
            padding-top: 0; 
            padding-bottom: 0; 
            margin-bottom: 0; 
            overflow: hidden; 
            transition: all 0.4s;
        }
        
        /* Graph container */
        .graph-container { 
            height: 450px; 
            margin-bottom: 20px; 
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        
        /* Date range picker */
        .daterange-container { 
            display: flex; 
            gap: 12px; 
            align-items: center; 
        }
        .daterange-btn { 
            cursor: pointer; 
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3); 
            border-radius: 8px; 
            padding: 8px 16px; 
            color: white;
            transition: all 0.3s;
        }
        .daterange-btn:hover { 
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
        }
        
        /* Summary cards */
        .summary-card {
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: white;
            transition: transform 0.3s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .summary-card h5 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 10px 0 5px;
        }
        .summary-card small {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        /* Exchange Rate Styles */
        .badge-api { 
            background-color: #198754; 
            color: white; 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-manual { 
            background-color: #ffc107; 
            color: black; 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 0.75rem;
            font-weight: 600;
        }
        .rate-comparison { 
            display: flex; 
            gap: 20px; 
            margin: 15px 0; 
        }
        .rate-box { 
            flex: 1; 
            border: 2px solid #e9ecef; 
            border-radius: 12px; 
            padding: 15px; 
            cursor: pointer; 
            transition: all 0.3s; 
            background: white;
            position: relative;
            overflow: hidden;
        }
        .rate-box:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 16px rgba(0,0,0,0.1); 
        }
        .rate-box.selected { 
            border-color: #0d6efd; 
            background-color: #f0f7ff; 
        }
        .rate-box.api { 
            border-top: 4px solid #198754; 
        }
        .rate-box.manual { 
            border-top: 4px solid #ffc107; 
        }
        .rate-value { 
            font-size: 1.8rem; 
            font-weight: 700; 
            margin: 10px 0 5px;
        }
        .rate-source { 
            font-size: 0.9rem; 
            font-weight: 500;
            margin-bottom: 5px;
        }
        .rate-date { 
            font-size: 0.8rem; 
            color: #6c757d; 
        }
        .rate-box .badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        /* Exchange rate panel */
        .exchange-rate-panel {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        /* Modal improvements */
        .modal-content {
            border: none;
            border-radius: 15px;
        }
        .modal-header {
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        .modal-body {
            padding: 25px;
        }
        
        /* Form controls */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 10px 15px;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15);
        }
        
        /* Table improvements */
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background-color: #212529;
            color: white;
            font-weight: 500;
            border: none;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Loading spinner */
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* Manual rate input section */
        .manual-rate-input {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .manual-rate-input .form-control {
            background: white;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Page heading -->
    <h2>YDF Accounting System - <?= htmlspecialchars($active_country['country_name']) ?> (<?= htmlspecialchars($active_country['currency_code']) ?>)</h2>

    <!-- Country Selector -->
    <div class="mb-4">
        <form method="POST" class="d-flex align-items-center gap-3">
            <label for="countrySelect" class="form-label fw-bold mb-0">Select Country:</label>
            <select name="country_id" id="countrySelect" class="form-select w-auto" onchange="this.form.submit()">
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

    <!-- Quick action buttons -->
    <div class="btn-group-custom">
        <button id="openAddCombinedBtn" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCombinedModal">
            <i class="fas fa-plus-circle me-2"></i>Add Voucher/Ledger
        </button>
        <a href="view_ledger.php" class="btn btn-info text-white">
            <i class="fas fa-book me-2"></i>View Ledger
        </a>
        <a href="bank_reconciliation.php" class="btn btn-secondary text-white">
            <i class="fas fa-university me-2"></i>Bank Reconciliation
        </a>
        <a href="trial_balance.php" class="btn btn-warning text-dark">
            <i class="fas fa-balance-scale me-2"></i>Trial Balance
        </a>
        <a href="income_statement.php" class="btn btn-danger">
            <i class="fas fa-chart-line me-2"></i>Income Statement
        </a>
        <a href="balance_sheet.php" class="btn btn-primary" style="background-color:#6610f2;">
            <i class="fas fa-file-invoice-dollar me-2"></i>Balance Sheet
        </a>
        <button id="showExchangeRatesBtn" class="btn btn-success" onclick="toggleExchangeRates()">
            <i class="fas fa-exchange-alt me-2"></i>Exchange Rates
        </button>
    </div>

    <!-- Flash messages -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>Voucher deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>Voucher updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>Saved successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <script>
        // remove status parameters from URL so alerts don't reappear on refresh
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            ['updated','deleted','success','error'].forEach(p => url.searchParams.delete(p));
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    </script>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            Error: 
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
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Exchange Rate Panel (Hidden by default) -->
    <div id="exchangeRatesPanel" class="card" style="display: none;">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Current Exchange Rates</h5>
            <button class="btn btn-sm btn-light" onclick="toggleExchangeRates()">Close</button>
        </div>
        <div class="card-body">
            <div class="row" id="exchangeRatesContainer">
                <div class="col-12 text-center py-4">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2">Loading rates...</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-success" onclick="fetchLiveRates()">
                        <i class="fas fa-sync-alt me-2"></i>Update All from API
                    </button>
                    <small class="text-muted ms-3">
                        <span class="badge-api me-1">API</span> Auto-fetched · 
                        <span class="badge-manual ms-1">Manual</span> Manually set
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Profit Graph Section -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Cash Profit Analysis</h5>
            <div class="daterange-container">
                <i class="fas fa-calendar-alt me-2"></i>
                <div id="reportrange" class="daterange-btn">
                    <i class="fa fa-calendar me-2"></i>
                    <span></span> <i class="fa fa-caret-down ms-2"></i>
                </div>
                <button id="refreshChartBtn" class="btn btn-sm btn-light">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Loading indicator -->
            <div id="chartLoading" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3">Loading chart data...</p>
            </div>
            
            <!-- Chart container -->
            <div class="graph-container" id="chartContainer">
                <canvas id="cashProfitChart"></canvas>
            </div>
            
            <!-- Summary Cards -->
            <div class="row mt-4">
                <div class="col-md-3 mb-3">
                    <div class="summary-card" style="background: linear-gradient(135deg, #6c757d, #495057);">
                        <i class="fas fa-calendar-start fa-2x mb-2"></i>
                        <small>Period Start</small>
                        <h5 id="periodStart">-</h5>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="summary-card" style="background: linear-gradient(135deg, #6c757d, #495057);">
                        <i class="fas fa-calendar-end fa-2x mb-2"></i>
                        <small>Period End</small>
                        <h5 id="periodEnd">-</h5>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="summary-card" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                        <i class="fas fa-calendar-week fa-2x mb-2"></i>
                        <small>Days</small>
                        <h5 id="periodDays">-</h5>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="summary-card" style="background: linear-gradient(135deg, #28a745, #218838);">
                        <i class="fas fa-arrow-down fa-2x mb-2"></i>
                        <small>Cash In</small>
                        <h5 id="totalCashIn">0.00</h5>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="summary-card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i>
                        <small>Cash Out</small>
                        <h5 id="totalCashOut">0.00</h5>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 offset-md-4">
                    <div class="summary-card" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7);">
                        <i class="fas fa-chart-pie fa-2x mb-2"></i>
                        <small>Net Cash Profit</small>
                        <h5 id="netCashProfit">0.00</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- All Vouchers table -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Vouchers</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="vouchersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Entries</th>
                            <th>Narration</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vouchers && $vouchers->num_rows > 0): ?>
                            <?php while ($v = $vouchers->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary">#<?= intval($v['voucher_id']); ?></span></td>
                                    <td>
                                        <?php
                                        $badge_color = 'secondary';
                                        if ($v['voucher_type'] == 'Payment') $badge_color = 'danger';
                                        elseif ($v['voucher_type'] == 'Receipt') $badge_color = 'success';
                                        elseif ($v['voucher_type'] == 'Journal') $badge_color = 'info';
                                        elseif ($v['voucher_type'] == 'Contra') $badge_color = 'warning';
                                        ?>
                                        <span class="badge bg-<?= $badge_color ?>"><?= htmlspecialchars($v['voucher_type']); ?></span>
                                    </td>
                                    <td><?= date('d-m-Y', strtotime($v['date'])); ?></td>
                                    <td class="text-start"><?= $v['entries']; ?></td>
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
                            <tr>
                                <td colspan="6" class="text-center py-4">No vouchers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Voucher Modal -->
    <div class="modal fade" id="editVoucherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" class="modal-content" id="editVoucherForm">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editVoucherContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-warning" role="status"></div>
                        <p class="mt-2">Loading voucher details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_voucher" class="btn btn-warning">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Voucher/Ledger Modal -->
    <div class="modal fade" id="addCombinedModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist" style="border-bottom: none;">
                        <li class="nav-item">
                            <a class="nav-link active text-dark" data-bs-toggle="tab" href="#tab-voucher">
                                <i class="fas fa-file-invoice me-2"></i>Voucher
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" data-bs-toggle="tab" href="#tab-ledger">
                                <i class="fas fa-book me-2"></i>Ledger
                            </a>
                        </li>
                    </ul>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="tab-content">
                        <!-- Voucher Tab -->
                        <div class="tab-pane fade show active" id="tab-voucher">
                            <form method="POST" id="addVoucherForm">
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Voucher Type</label>
                                        <select name="voucher_type" class="form-select" required>
                                            <option value="Payment">Payment</option>
                                            <option value="Receipt">Receipt</option>
                                            <option value="Journal">Journal</option>
                                            <option value="Contra">Contra</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Date</label>
                                        <input type="date" id="dateInput" name="date" class="form-control" required value="<?= date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Amount</label>
                                        <input type="number" name="amount" id="amountInput" class="form-control" step="0.01" required value="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Currency</label>
                                        <select name="currency_id" id="currencySelect" class="form-select" onchange="loadExchangeRatesForCurrency()">
                                            <?php
                                            $currencies = $conn->query("SELECT currency_id, code, name FROM currencies ORDER BY code");
                                            while ($c = $currencies->fetch_assoc()) {
                                                $selected = ($c['currency_id'] == 1) ? 'selected' : '';
                                                echo "<option value='{$c['currency_id']}' $selected>{$c['code']} - {$c['name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Exchange Rate Selection -->
                                <div class="exchange-rate-panel" id="exchangeRatePanel" style="display: none;">
                                    <h6 class="mb-3"><i class="fas fa-exchange-alt me-2"></i>Select Exchange Rate</h6>
                                    
                                    <!-- Rate Comparison Boxes -->
                                    <div id="exchangeRateContainer"></div>
                                    
                                    <!-- Manual Rate Input Section (initially hidden) -->
                                    <div id="manualRateSection" class="manual-rate-input" style="display: none;">
                                        <h6 class="mb-3"><i class="fas fa-pencil-alt me-2"></i>Enter Manual Rate</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Exchange Rate (1 FC = ? LKR)</label>
                                                <input type="number" id="manualRateInput" class="form-control" step="0.000001" min="0" placeholder="Enter rate">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Rate Date</label>
                                                <input type="date" id="manualRateDate" class="form-control" value="<?= date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-warning" onclick="saveAndUseManualRate()">
                                                <i class="fas fa-save me-2"></i>Save and Use This Rate
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="cancelManualRate()">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="exchange_rate" id="selectedExchangeRate" value="1">
                                    <input type="hidden" name="rate_source" id="rateSource" value="manual">
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Debit Ledger</label>
                                        <select name="dr_ledger" id="drLedgerSelect" class="form-select" required>
                                            <option value="">-- Select Debit Ledger --</option>
                                            <?php
                                            $ledgers = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name");
                                            while ($l = $ledgers->fetch_assoc()) {
                                                echo "<option value='{$l['ledger_id']}'>{$l['ledger_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Credit Ledger</label>
                                        <select name="cr_ledger" id="crLedgerSelect" class="form-select" required>
                                            <option value="">-- Select Credit Ledger --</option>
                                            <?php
                                            $ledgers->data_seek(0);
                                            while ($l = $ledgers->fetch_assoc()) {
                                                echo "<option value='{$l['ledger_id']}'>{$l['ledger_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Narration</label>
                                    <textarea name="narration" class="form-control" rows="3" placeholder="Enter description..."></textarea>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">LKR Equivalent</label>
                                        <div id="lkrPreview" class="form-control-plaintext fw-bold fs-4 text-primary">0.00 LKR</div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="submit_voucher" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-2"></i>Save Transaction
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Ledger Tab -->
                        <div class="tab-pane fade" id="tab-ledger">
                            <form method="POST" id="addLedgerForm">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Account Group</label>
                                    <select name="group_id" class="form-select" required>
                                        <option value="">-- Select Account Group --</option>
                                        <?php
                                        $groups = $conn->query("SELECT group_id, group_name, group_type FROM account_groups ORDER BY group_name");
                                        while ($g = $groups->fetch_assoc()) {
                                            echo "<option value='{$g['group_id']}'>{$g['group_name']} ({$g['group_type']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ledger Name</label>
                                    <input type="text" name="ledger_name" class="form-control" required placeholder="Enter ledger name">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Opening Balance</label>
                                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Balance Type</label>
                                        <select name="balance_type" class="form-select">
                                            <option value="Dr">Dr (Debit)</option>
                                            <option value="Cr">Cr (Credit)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="add_ledger" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Save Ledger
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // ========== GRAPH AND DATE RANGE FUNCTIONS ==========
    
    document.addEventListener('DOMContentLoaded', function() {
        let currentEditModal = null;
        
        // Initialize DateRangePicker
        let startDate = moment().subtract(6, 'days');
        let endDate = moment();
        
        function updateDateRangeDisplay(start, end) {
            $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            document.getElementById('periodStart').textContent = start.format('YYYY-MM-DD');
            document.getElementById('periodEnd').textContent = end.format('YYYY-MM-DD');
            document.getElementById('periodDays').textContent = end.diff(start, 'days') + 1;
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

        // Load cash profit data
        function loadCashProfitData(startDate, endDate) {
            document.getElementById('chartLoading').style.display = 'block';
            document.getElementById('chartContainer').style.opacity = '0.5';
            
            let url = window.location.pathname + '?action=get_cash_profit';
            if (startDate && endDate) {
                url += '&start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);
            }
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('chartLoading').style.display = 'none';
                    document.getElementById('chartContainer').style.opacity = '1';
                    
                    if (data.success) {
                        document.getElementById('totalCashIn').textContent = data.totals.total_in.toFixed(2);
                        document.getElementById('totalCashOut').textContent = data.totals.total_out.toFixed(2);
                        document.getElementById('netCashProfit').textContent = data.totals.net_profit.toFixed(2);
                        
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
                                        borderColor: '#28a745',
                                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                        tension: 0.1,
                                        fill: true,
                                        pointBackgroundColor: '#28a745',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        borderWidth: 3
                                    },
                                    {
                                        label: 'Cash Out (Payments)',
                                        data: data.cashOut,
                                        borderColor: '#dc3545',
                                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                        tension: 0.1,
                                        fill: true,
                                        pointBackgroundColor: '#dc3545',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        borderWidth: 3
                                    },
                                    {
                                        label: 'Net Profit',
                                        data: data.netProfit,
                                        borderColor: '#0d6efd',
                                        backgroundColor: 'transparent',
                                        borderWidth: 3,
                                        tension: 0.1,
                                        fill: false,
                                        pointBackgroundColor: '#0d6efd',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        borderDash: [5, 5]
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { 
                                        position: 'top',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 20,
                                            font: {
                                                size: 12,
                                                weight: '500'
                                            }
                                        }
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(0,0,0,0.8)',
                                        titleFont: { size: 14, weight: 'bold' },
                                        bodyFont: { size: 13 },
                                        padding: 12,
                                        cornerRadius: 8,
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
                                        grid: {
                                            color: 'rgba(0,0,0,0.05)',
                                            drawBorder: false
                                        },
                                        ticks: {
                                            callback: function(value) {
                                                return value.toFixed(2) + ' <?= htmlspecialchars($active_country['currency_code']) ?>';
                                            },
                                            font: { size: 11 }
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            font: { size: 11 }
                                        }
                                    }
                                },
                                interaction: {
                                    mode: 'nearest',
                                    axis: 'x',
                                    intersect: false
                                }
                            }
                        });
                    } else {
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

        // Load initial cash profit data
        loadCashProfitData(startDate.format('YYYY-MM-DD'), endDate.format('YYYY-MM-DD'));

        // Refresh button handler
        document.getElementById('refreshChartBtn').addEventListener('click', function() {
            const range = $('#reportrange').data('daterangepicker');
            loadCashProfitData(range.startDate.format('YYYY-MM-DD'), range.endDate.format('YYYY-MM-DD'));
        });

        // ========== DATATABLES INITIALIZATION ==========
        
        if (typeof jQuery !== 'undefined' && $.fn.dataTable) {
            $('#vouchersTable').DataTable({
                // sort by ID (first column) descending to show latest first
                order: [[0, 'desc']],
                pageLength: 10,
                responsive: true,
                columnDefs: [
                    { orderable: false, targets: [3,5] }
                ],
                language: {
                    emptyTable: "No vouchers found for this country.",
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }

        // ========== VOUCHER EDIT/DELETE FUNCTIONS ==========
        
        document.addEventListener('click', function(e) {
            let editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                const id = editBtn.dataset.id;
                if (!id) return;

                const modalEl = document.getElementById('editVoucherModal');
                currentEditModal = new bootstrap.Modal(modalEl);
                const content = document.getElementById('editVoucherContent');
                content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-warning" role="status"></div><p class="mt-2">Loading voucher details...</p></div>';

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

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will permanently delete the voucher and its entries.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then(result => {
                    if (result.isConfirmed) {
                        const fd = new FormData();
                        fd.append('delete_voucher', '1');
                        fd.append('voucher_id', voucherId);
                        fetch(window.location.pathname, { method: 'POST', body: fd })
                            .then(() => { 
                                Swal.fire('Deleted!', 'Voucher has been deleted.', 'success')
                                    .then(() => window.location.reload());
                            })
                            .catch(() => { 
                                Swal.fire('Error', 'Delete failed', 'error');
                            });
                    }
                });
            }
        });

        // Edit voucher form submission
        document.getElementById('editVoucherForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('update_voucher', '1');

            const submitBtn = this.querySelector('button[type="submit"]');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
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
                Swal.fire('Error', 'Failed to update voucher: ' + error.message, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save Changes';
                }
            });
        });
    });

    // ========== EXCHANGE RATE FUNCTIONS ==========
    
    function toggleExchangeRates() {
        const panel = document.getElementById('exchangeRatesPanel');
        if (panel.style.display === 'none' || panel.style.display === '') {
            panel.style.display = 'block';
            loadExchangeRates();
        } else {
            panel.style.display = 'none';
        }
    }
    
    async function loadExchangeRates() {
        const container = document.getElementById('exchangeRatesContainer');
        try {
            const response = await fetch(window.location.pathname + '?action=get_current_rates');
            const data = await response.json();
            
            if (data.success && data.rates.length > 0) {
                let html = '';
                data.rates.forEach(rate => {
                    const badgeClass = rate.source === 'API' ? 'badge-api' : 'badge-manual';
                    html += `
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold">${rate.code}</h6>
                                        <span class="${badgeClass}">${rate.source}</span>
                                    </div>
                                    <h5 class="mb-0">${parseFloat(rate.rate_to_lkr).toFixed(4)}</h5>
                                    <small class="text-muted">as of ${rate.rate_date}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = '<div class="row">' + html + '</div>';
            } else {
                container.innerHTML = '<div class="alert alert-info text-center">No exchange rates found. Click "Update All from API" to fetch rates.</div>';
            }
        } catch (error) {
            container.innerHTML = '<div class="alert alert-danger">Failed to load rates</div>';
        }
    }
    
    async function fetchLiveRates() {
        Swal.fire({
            title: 'Updating Rates',
            text: 'Fetching latest rates from API...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        try {
            const response = await fetch('https://v6.exchangerate-api.com/v6/ccd0aba3dbaef425612cd487/latest/USD');
            const data = await response.json();
            
            if (data.result === 'success') {
                const currencies = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY'];
                const lkrRate = data.conversion_rates['LKR'];
                let successCount = 0;
                
                for (let code of currencies) {
                    if (data.conversion_rates[code]) {
                        const rateToLkr = lkrRate / data.conversion_rates[code];
                        
                        const currRes = await fetch(window.location.pathname + '?action=get_currency_id&code=' + code);
                        const currData = await currRes.json();
                        
                        if (currData.currency_id) {
                            await fetch(window.location.pathname, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams({
                                    'update_exchange_rate': '1',
                                    'currency_id': currData.currency_id,
                                    'rate_date': new Date().toISOString().split('T')[0],
                                    'rate_to_lkr': rateToLkr,
                                    'source': 'API'
                                })
                            });
                            successCount++;
                        }
                    }
                }
                
                Swal.fire('Success', `Updated ${successCount} currencies`, 'success');
                loadExchangeRates();
                if (document.getElementById('currencySelect').value !== '1') {
                    loadExchangeRatesForCurrency();
                }
            } else {
                throw new Error('API failed');
            }
        } catch (error) {
            Swal.fire('Error', 'Failed to fetch rates: ' + error.message, 'error');
        }
    }
    
    async function loadExchangeRatesForCurrency() {
        const currencySelect = document.getElementById('currencySelect');
        const currencyId = currencySelect.value;
        const currencyCode = currencySelect.options[currencySelect.selectedIndex].text.split(' ')[0];
        const date = document.getElementById('dateInput').value;
        
        const panel = document.getElementById('exchangeRatePanel');
        const manualSection = document.getElementById('manualRateSection');
        
        if (currencyId === '1') {
            panel.style.display = 'none';
            document.getElementById('selectedExchangeRate').value = '1';
            updateLkrEquivalent();
            return;
        }
        
        panel.style.display = 'block';
        manualSection.style.display = 'none'; // Hide manual section initially
        
        document.getElementById('exchangeRateContainer').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading rates...</p>
            </div>
        `;
        
        try {
            // Fetch API rate
            const apiResponse = await fetch(window.location.pathname + `?action=get_rate&code=${currencyCode}&date=${date}`);
            const apiData = await apiResponse.json();
            
            // Fetch manual rate
            const manualResponse = await fetch(window.location.pathname + `?action=get_manual_rates&code=${currencyCode}&date=${date}`);
            const manualData = await manualResponse.json();
            
            let html = '<div class="rate-comparison">';
            
            // API Rate Box
            if (apiData.rate) {
                html += `
                    <div class="rate-box api" onclick="selectRate(${apiData.rate}, 'api', this)">
                        <span class="badge-api">API</span>
                        <div class="rate-source">Live Exchange Rate</div>
                        <div class="rate-value">${parseFloat(apiData.rate).toFixed(4)}</div>
                        <div class="rate-date">Updated: ${new Date().toLocaleDateString()}</div>
                    </div>
                `;
            } else {
                html += `
                    <div class="rate-box api" onclick="fetchLiveRateForCurrency('${currencyCode}')">
                        <span class="badge-api">API</span>
                        <div class="rate-source">Live Exchange Rate</div>
                        <div class="rate-value text-muted">Not Available</div>
                        <div class="rate-date">Click to fetch</div>
                    </div>
                `;
            }
            
            // Manual Rate Box - Always show option to enter manual rate
            if (manualData.success && manualData.rate) {
                html += `
                    <div class="rate-box manual" onclick="selectRate(${manualData.rate}, 'manual', this)">
                        <span class="badge-manual">Manual</span>
                        <div class="rate-source">Saved Manual Rate</div>
                        <div class="rate-value">${parseFloat(manualData.rate).toFixed(4)}</div>
                        <div class="rate-date">Saved: ${manualData.date}</div>
                    </div>
                `;
            }
            
            // Always add an option to enter new manual rate
            html += `
                <div class="rate-box manual" onclick="showManualRateInput()">
                    <span class="badge-manual">Manual</span>
                    <div class="rate-source">Enter New Manual Rate</div>
                    <div class="rate-value text-muted">Click to enter</div>
                    <div class="rate-date">Set your own rate</div>
                </div>
            `;
            
            html += '</div>';
            
            document.getElementById('exchangeRateContainer').innerHTML = html;
            
        } catch (error) {
            console.error('Error loading rates:', error);
            document.getElementById('exchangeRateContainer').innerHTML = '<div class="alert alert-danger">Failed to load rates</div>';
        }
    }
    
    function showManualRateInput() {
        document.getElementById('manualRateSection').style.display = 'block';
        // Scroll to manual section
        document.getElementById('manualRateSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    function cancelManualRate() {
        document.getElementById('manualRateSection').style.display = 'none';
        document.getElementById('manualRateInput').value = '';
    }
    
    async function saveAndUseManualRate() {
        const rate = parseFloat(document.getElementById('manualRateInput').value);
        const date = document.getElementById('manualRateDate').value;
        const currencySelect = document.getElementById('currencySelect');
        const currencyCode = currencySelect.options[currencySelect.selectedIndex].text.split(' ')[0];
        
        if (!rate || rate <= 0) {
            Swal.fire('Error', 'Please enter a valid rate', 'error');
            return;
        }
        
        try {
            // Get currency_id
            const currRes = await fetch(window.location.pathname + '?action=get_currency_id&code=' + currencyCode);
            const currData = await currRes.json();
            
            if (!currData.currency_id) {
                throw new Error('Currency not found');
            }
            
            // Save to database
            const saveResponse = await fetch(window.location.pathname, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'update_exchange_rate': '1',
                    'currency_id': currData.currency_id,
                    'rate_date': date,
                    'rate_to_lkr': rate,
                    'source': 'Manual'
                })
            });
            
            const result = await saveResponse.json();
            
            if (result.success) {
                // Select the rate
                document.getElementById('selectedExchangeRate').value = rate;
                document.getElementById('rateSource').value = 'manual';
                updateLkrEquivalent();
                
                // Hide manual section
                document.getElementById('manualRateSection').style.display = 'none';
                document.getElementById('manualRateInput').value = '';
                
                // Refresh the rate display
                loadExchangeRatesForCurrency();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Rate Saved',
                    text: `1 ${currencyCode} = ${rate} LKR`,
                    timer: 2000
                });
            } else {
                throw new Error('Failed to save rate');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to save rate: ' + error.message, 'error');
        }
    }
    
    function selectRate(rate, source, element) {
        document.getElementById('selectedExchangeRate').value = rate;
        document.getElementById('rateSource').value = source;
        updateLkrEquivalent();
        
        // Remove selected class from all boxes
        document.querySelectorAll('.rate-box').forEach(box => {
            box.classList.remove('selected');
        });
        // Add selected class to clicked box
        element.classList.add('selected');
        
        // Hide manual section if it's visible
        document.getElementById('manualRateSection').style.display = 'none';
        
        Swal.fire({
            icon: 'success',
            title: 'Rate Selected',
            text: `Using ${source.toUpperCase()} rate: ${rate}`,
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
    
    async function fetchLiveRateForCurrency(currencyCode) {
        Swal.fire({
            title: 'Fetching Rate',
            text: `Getting live rate for ${currencyCode}...`,
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        try {
            const response = await fetch('https://v6.exchangerate-api.com/v6/ccd0aba3dbaef425612cd487/latest/USD');
            const data = await response.json();
            
            if (data.result === 'success' && data.conversion_rates[currencyCode]) {
                const rate = data.conversion_rates['LKR'] / data.conversion_rates[currencyCode];
                
                const currRes = await fetch(window.location.pathname + '?action=get_currency_id&code=' + currencyCode);
                const currData = await currRes.json();
                
                if (currData.currency_id) {
                    await fetch(window.location.pathname, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            'update_exchange_rate': '1',
                            'currency_id': currData.currency_id,
                            'rate_date': document.getElementById('dateInput').value,
                            'rate_to_lkr': rate,
                            'source': 'API'
                        })
                    });
                }
                
                Swal.close();
                loadExchangeRatesForCurrency();
                Swal.fire('Success', `Rate fetched: 1 ${currencyCode} = ${rate.toFixed(4)} LKR`, 'success');
            } else {
                throw new Error('Rate not available');
            }
        } catch (error) {
            Swal.fire('Error', 'Failed to fetch rate: ' + error.message, 'error');
        }
    }
    
    function updateLkrEquivalent() {
        const amount = parseFloat(document.getElementById('amountInput').value) || 0;
        const rate = parseFloat(document.getElementById('selectedExchangeRate').value) || 1;
        const source = document.getElementById('rateSource').value;
        const lkr = amount * rate;
        document.getElementById('lkrPreview').textContent = lkr.toFixed(2) + ' LKR' + (source === 'api' ? ' (Live Rate)' : source === 'manual' ? ' (Manual Rate)' : '');
    }
    
    // Event listeners
    document.getElementById('currencySelect').addEventListener('change', loadExchangeRatesForCurrency);
    document.getElementById('dateInput').addEventListener('change', loadExchangeRatesForCurrency);
    document.getElementById('amountInput').addEventListener('input', updateLkrEquivalent);
    
    // Add Ledger form
    document.getElementById('addLedgerForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('add_ledger', '1');
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Ledger added successfully',
                    timer: 1500
                }).then(() => {
                    $('#addCombinedModal').modal('hide');
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message || 'Failed to add ledger', 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Network error: ' + error.message, 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Ledger';
        });
    });
    
    // Add Voucher form validation
    document.getElementById('addVoucherForm')?.addEventListener('submit', function(e) {
        const currencyId = document.getElementById('currencySelect').value;
        if (currencyId !== '1') {
            const rate = document.getElementById('selectedExchangeRate').value;
            if (!rate || rate == '1') {
                e.preventDefault();
                Swal.fire('Error', 'Please select an exchange rate', 'error');
                return false;
            }
        }
    });
    </script>
</body>
</html>