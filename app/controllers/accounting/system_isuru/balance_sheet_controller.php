<?php
// =============================
// BALANCE SHEET (Date Range Filter + Suspense Dynamic Fix + Excel Export)
// Updated with Country Selection and "All Countries" Option - FIXED URL ERROR
// =============================
session_start(); // Start session for country selection

$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

// ============================================================================
// COUNTRY SELECTION HANDLING (with All Countries option) - FIXED
// ============================================================================
if (isset($_POST['select_country'])) {
    $_SESSION['balance_sheet_country_id'] = intval($_POST['country_id']); // 0 for All Countries
    
    // Build redirect URL preserving filter parameters
    $redirect_url = $_SERVER['PHP_SELF'];
    $params = [];
    
    if (isset($_POST['filter_from']) && !empty($_POST['filter_from'])) {
        $params['filter_from'] = $_POST['filter_from'];
    }
    if (isset($_POST['filter_to']) && !empty($_POST['filter_to'])) {
        $params['filter_to'] = $_POST['filter_to'];
    }
    
    if (!empty($params)) {
        $redirect_url .= '?' . http_build_query($params);
    }
    
    header("Location: " . $redirect_url);
    exit;
}

// Default to first country if not set
if (!isset($_SESSION['balance_sheet_country_id'])) {
    $default_country = $conn->query("SELECT id FROM countries ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $_SESSION['balance_sheet_country_id'] = $default_country ? $default_country['id'] : 1;
}

$active_country_id = $_SESSION['balance_sheet_country_id'];

// Fetch active country details (handle All Countries option)
if ($active_country_id == 0) {
    $active_country = ['country_name' => 'All Countries', 'currency_code' => 'Multiple'];
} else {
    $country_stmt = $conn->prepare("SELECT country_name, currency_code FROM countries WHERE id = ?");
    $country_stmt->bind_param("i", $active_country_id);
    $country_stmt->execute();
    $active_country = $country_stmt->get_result()->fetch_assoc();
    $country_stmt->close();
}

// --- Helper: validate date ---
function valid_date($d) {
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

// --- Read date range filter (GET) like Trial Balance / BRS ---
$filter_from = isset($_GET['filter_from']) && valid_date($_GET['filter_from']) ? $_GET['filter_from'] : '';
$filter_to   = isset($_GET['filter_to']) && valid_date($_GET['filter_to']) ? $_GET['filter_to'] : '';

// Support legacy 'from'/'to' query params if present
if (!$filter_from && isset($_GET['from']) && valid_date($_GET['from'])) $filter_from = $_GET['from'];
if (!$filter_to && isset($_GET['to']) && valid_date($_GET['to'])) $filter_to = $_GET['to'];

// Build date condition for SQL
$dateExpr = '1=1';
if ($filter_from && $filter_to) {
    $dateExpr = "v.date BETWEEN '{$conn->real_escape_string($filter_from)}' AND '{$conn->real_escape_string($filter_to)}'";
} elseif ($filter_from) {
    $dateExpr = "v.date >= '{$conn->real_escape_string($filter_from)}'";
} elseif ($filter_to) {
    $dateExpr = "v.date <= '{$conn->real_escape_string($filter_to)}'";
}

// Build country condition for SQL
if ($active_country_id == 0) {
    // All Countries - no country filter, but include both country-specific and global (0) vouchers
    $countryCondition = "1=1"; // Show all countries
    $ledgerCountryCondition = "1=1"; // Show all ledgers
} else {
    // Specific country - show that country's vouchers AND global vouchers (country_id = 0)
    $countryCondition = "(v.country_id = {$active_country_id} OR v.country_id = 0)";
    $ledgerCountryCondition = "l.country_id = {$active_country_id}";
}

// --- Calculate Net Income dynamically based on date filter and country filter ---
$income_query = "
    SELECT
      COALESCE(SUM(CASE WHEN g.group_type='Income' THEN ve.amount_lkr * 
            (CASE WHEN ve.type='Cr' THEN 1 WHEN ve.type='Dr' THEN -1 END) ELSE 0 END),0) AS income_total,
      COALESCE(SUM(CASE WHEN g.group_type='Expense' THEN ve.amount_lkr * 
            (CASE WHEN ve.type='Dr' THEN 1 WHEN ve.type='Cr' THEN -1 END) ELSE 0 END),0) AS expense_total
    FROM voucher_entries ve
    JOIN vouchers v ON ve.voucher_id = v.voucher_id
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    JOIN account_groups g ON l.group_id = g.group_id
    WHERE g.group_type IN ('Income','Expense') AND $dateExpr AND $countryCondition
";

$income_stmt = $conn->prepare($income_query);
$income_stmt->execute();
$income_data = $income_stmt->get_result()->fetch_assoc();
$income_stmt->close();
$net_income = ($income_data['income_total'] ?? 0) - ($income_data['expense_total'] ?? 0);

// --- Fetch all ledgers with balances (respect dateExpr and country filter) ---
$query = "
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.balance_type,
        COALESCE(l.opening_balance,0) AS opening_balance,
        ag.group_name,
        ag.group_type,
        c.country_name,
        COALESCE(SUM(CASE WHEN ve.type='Dr' AND $dateExpr AND $countryCondition THEN ve.amount_lkr ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' AND $dateExpr AND $countryCondition THEN ve.amount_lkr ELSE 0 END), 0) AS total_cr
    FROM ledgers l
    LEFT JOIN account_groups ag ON l.group_id = ag.group_id
    LEFT JOIN countries c ON l.country_id = c.id
    LEFT JOIN voucher_entries ve ON ve.ledger_id = l.ledger_id
    LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
    WHERE COALESCE(ag.group_type,'') IN ('Asset','Liability','Equity','Suspense')
    AND $ledgerCountryCondition
    GROUP BY l.ledger_id
    ORDER BY c.country_name, ag.group_type, ag.group_name, l.ledger_name
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$accounts = [];
$country_totals = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Balance calculation
        if (strtoupper($row['balance_type']) === 'DR') {
            $balance = $row['opening_balance'] + $row['total_dr'] - $row['total_cr'];
        } else {
            $balance = $row['opening_balance'] + $row['total_cr'] - $row['total_dr'];
        }
        $row['balance'] = $balance;

        // Dynamic suspense fix
        if ($row['group_type'] === 'Suspense') {
            if ($balance >= 0) $row['group_type'] = 'Asset';
            else $row['group_type'] = 'Liability';
        }

        $accounts[] = $row;
        
        // Track country totals
        $country = $row['country_name'] ?? 'Unknown';
        if (!isset($country_totals[$country])) {
            $country_totals[$country] = ['asset' => 0, 'liability' => 0, 'equity' => 0];
        }
        
        if (strtolower($row['group_type']) === 'asset') {
            $country_totals[$country]['asset'] += max($balance, 0);
        } elseif (strtolower($row['group_type']) === 'liability') {
            $country_totals[$country]['liability'] += abs($balance);
        } elseif (strtolower($row['group_type']) === 'equity') {
            $country_totals[$country]['equity'] += abs($balance);
        }
    }
}

// =============================
// EXCEL EXPORT (CSV) - includes country, filter_from/filter_to in filename and headers
// =============================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header('Content-Type: text/csv; charset=UTF-8');
    $country_part = $active_country_id == 0 ? 'All_Countries' : preg_replace('/[^a-zA-Z0-9]/', '_', $active_country['country_name']);
    $period_part = ($filter_from || $filter_to) ? ($filter_from . '_to_' . $filter_to) : date('Y-m-d');
    header('Content-Disposition: attachment; filename="balance_sheet_' . $country_part . '_' . $period_part . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $out = fopen('php://output', 'w');

    // Header
    fputcsv($out, ['Balance Sheet - ' . $active_country['country_name']]);
    $period_label = 'All Dates';
    if ($filter_from && $filter_to) $period_label = "{$filter_from} to {$filter_to}";
    elseif ($filter_from) $period_label = "From {$filter_from}";
    elseif ($filter_to) $period_label = "Up to {$filter_to}";
    fputcsv($out, ["As at: $period_label"]);
    fputcsv($out, []);

    // Export helper
    function export_section($out, $accounts, $type, &$total) {
        fputcsv($out, [strtoupper($type) . 'S']);
        $total = 0;
        foreach ($accounts as $acc) {
            if (strtolower($acc['group_type']) === strtolower($type)) {
                $amount = ($type === 'asset') ? max($acc['balance'],0) : abs($acc['balance']);
                if ($amount != 0) {
                    fputcsv($out, [$acc['ledger_name'] . ($acc['country_name'] ? ' [' . $acc['country_name'] . ']' : ''), number_format($amount,2,'.','')]);
                    $total += $amount;
                }
            }
        }
        fputcsv($out, ['Total ' . ucfirst($type) . 's', number_format($total,2,'.','')]);
        fputcsv($out, []);
    }

    // Assets / Liabilities
    export_section($out, $accounts, 'asset', $asset_total);
    export_section($out, $accounts, 'liability', $liability_total);

    // Equity
    fputcsv($out, ['EQUITY']);
    $equity_total = 0;
    foreach ($accounts as $acc) {
        if (strtolower($acc['group_type']) === 'equity') {
            $amount = abs($acc['balance']);
            if ($amount != 0) {
                fputcsv($out, [$acc['ledger_name'] . ($acc['country_name'] ? ' [' . $acc['country_name'] . ']' : ''), number_format($amount,2,'.','')]);
                $equity_total += $amount;
            }
        }
    }
    fputcsv($out, ["Retained Earnings / Net Income", number_format(abs($net_income),2,'.','')]);
    $total_equity = $equity_total + $net_income;
    fputcsv($out, ["Total Equity", number_format($total_equity,2,'.','')]);
    fputcsv($out, []);
    
    // Summary by Country (if All Countries selected)
    if ($active_country_id == 0 && !empty($country_totals)) {
        fputcsv($out, ['SUMMARY BY COUNTRY']);
        fputcsv($out, ['Country', 'Assets', 'Liabilities', 'Equity', 'Net Income', 'Total']);
        $grand_assets = $grand_liabilities = $grand_equity = 0;
        
        foreach ($country_totals as $country => $totals) {
            $total_liab_equity = $totals['liability'] + $totals['equity'] + $net_income;
            fputcsv($out, [
                $country,
                number_format($totals['asset'], 2),
                number_format($totals['liability'], 2),
                number_format($totals['equity'], 2),
                number_format($net_income, 2),
                number_format($total_liab_equity, 2)
            ]);
            $grand_assets += $totals['asset'];
            $grand_liabilities += $totals['liability'];
            $grand_equity += $totals['equity'];
        }
        
        $grand_total = $grand_liabilities + $grand_equity + $net_income;
        fputcsv($out, [
            'GRAND TOTAL',
            number_format($grand_assets, 2),
            number_format($grand_liabilities, 2),
            number_format($grand_equity, 2),
            number_format($net_income, 2),
            number_format($grand_total, 2)
        ]);
    }

    fclose($out);
    exit;
}
?>