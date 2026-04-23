<?php
// =============================
// INCOME STATEMENT (Group-based with Date Range Filter)
// Updated with Country Selection and "All Countries" Option - FIXED URL ERROR
// =============================
session_start(); // Start session for country selection

require_once __DIR__ . '/../../../config/config.php';
$conn = getDBConnection();
$conn->set_charset('utf8mb4');

// ============================================================================
// COUNTRY SELECTION HANDLING (with All Countries option) - FIXED
// ============================================================================
if (isset($_POST['select_country'])) {
    $_SESSION['income_statement_country_id'] = intval($_POST['country_id']); // 0 for All Countries
    
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
if (!isset($_SESSION['income_statement_country_id'])) {
    $default_country = $conn->query("SELECT id FROM countries ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $_SESSION['income_statement_country_id'] = $default_country ? $default_country['id'] : 1;
}

$active_country_id = $_SESSION['income_statement_country_id'];

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

// --- Read date range filter (GET) similar to trial balance / BRS ---
$filter_from = isset($_GET['filter_from']) && valid_date($_GET['filter_from']) ? $_GET['filter_from'] : date('Y-01-01');
$filter_to   = isset($_GET['filter_to']) && valid_date($_GET['filter_to']) ? $_GET['filter_to'] : date('Y-m-d');

// --- Build SQL date condition ---
$fromEsc = $conn->real_escape_string($filter_from);
$toEsc   = $conn->real_escape_string($filter_to);

$dateExpr = "v.date >= '{$fromEsc}' AND v.date <= '{$toEsc}'";

// Build country condition for SQL
if ($active_country_id == 0) {
    // All Countries - include both country-specific and global (0) vouchers
    $countryCondition = "1=1"; // Show all countries
} else {
    // Specific country - show that country's vouchers AND global vouchers (country_id = 0)
    $countryCondition = "(v.country_id = {$active_country_id} OR v.country_id = 0)";
}

// =====================
// 1️⃣ Query all ledgers + their group type + totals (date and country filtered)
// =====================
$query = "
    SELECT 
        l.ledger_id,
        l.ledger_name,
        g.group_name,
        g.group_type,
        c.country_name,
        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount_lkr ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount_lkr ELSE 0 END), 0) AS total_cr
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON ve.ledger_id = l.ledger_id
    LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
    LEFT JOIN account_groups g ON g.group_id = l.group_id
    LEFT JOIN countries c ON l.country_id = c.id
    WHERE g.group_type IN ('Income', 'Expense')
      AND $dateExpr
      AND $countryCondition
    GROUP BY l.ledger_id, l.ledger_name, g.group_name, g.group_type, c.country_name
    ORDER BY c.country_name, g.group_type, l.ledger_name
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$accounts = [];
$country_totals = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
        
        // Track country totals
        $country = $row['country_name'] ?? 'Unknown';
        if (!isset($country_totals[$country])) {
            $country_totals[$country] = ['income' => 0, 'expense' => 0];
        }
        
        if ($row['group_type'] === 'Income') {
            $amount = $row['total_cr'] - $row['total_dr'];
            $country_totals[$country]['income'] += $amount;
        } else if ($row['group_type'] === 'Expense') {
            $amount = $row['total_dr'] - $row['total_cr'];
            $country_totals[$country]['expense'] += $amount;
        }
    }
}

// --- Calculate totals ---
$revenue = $expenses = 0;
foreach ($accounts as $acc) {
    if ($acc['group_type'] === 'Income') {
        $amount = $acc['total_cr'] - $acc['total_dr'];
        if ($amount != 0) $revenue += $amount;
    }
    if ($acc['group_type'] === 'Expense') {
        $amount = $acc['total_dr'] - $acc['total_cr'];
        if ($amount != 0) $expenses += $amount;
    }
}
$net_income = $revenue - $expenses;

// =====================
// 2️⃣ EXPORT TO EXCEL (CSV) - includes country in filename
// =====================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header('Content-Type: text/csv; charset=UTF-8');
    $country_part = $active_country_id == 0 ? 'All_Countries' : preg_replace('/[^a-zA-Z0-9]/', '_', $active_country['country_name']);
    $filename = 'income_statement_' . $country_part . '_' . $filter_from . '_to_' . $filter_to . '.csv';
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');

    // Header
    $period_label = $filter_from . ' to ' . $filter_to;

    fputcsv($out, ["Income Statement - " . $active_country['country_name'] . " for $period_label"]);
    fputcsv($out, []);

    // Income Section
    fputcsv($out, ['INCOME']);
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Income') {
            $amount = $acc['total_cr'] - $acc['total_dr'];
            if ($amount != 0) {
                $country_suffix = ($active_country_id == 0 && isset($acc['country_name'])) ? ' [' . $acc['country_name'] . ']' : '';
                fputcsv($out, [$acc['ledger_name'] . $country_suffix, number_format($amount, 2, '.', '')]);
            }
        }
    }
    fputcsv($out, ['Total Income', number_format($revenue, 2, '.', '')]);
    fputcsv($out, []);

    // Expenses Section
    fputcsv($out, ['EXPENSES']);
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Expense') {
            $amount = $acc['total_dr'] - $acc['total_cr'];
            if ($amount != 0) {
                $country_suffix = ($active_country_id == 0 && isset($acc['country_name'])) ? ' [' . $acc['country_name'] . ']' : '';
                fputcsv($out, [$acc['ledger_name'] . $country_suffix, number_format($amount, 2, '.', '')]);
            }
        }
    }
    fputcsv($out, ['Total Expenses', number_format($expenses, 2, '.', '')]);
    fputcsv($out, []);

    // Net Income
    $label = $net_income >= 0 ? 'Net Income' : 'Net Loss';
    fputcsv($out, [$label, number_format($net_income, 2, '.', '')]);
    fputcsv($out, []);
    
    // Summary by Country (if All Countries selected)
    if ($active_country_id == 0 && !empty($country_totals)) {
        fputcsv($out, ['SUMMARY BY COUNTRY']);
        fputcsv($out, ['Country', 'Income', 'Expenses', 'Net Income']);
        
        foreach ($country_totals as $country => $totals) {
            $net = $totals['income'] - $totals['expense'];
            fputcsv($out, [
                $country,
                number_format($totals['income'], 2),
                number_format($totals['expense'], 2),
                number_format($net, 2)
            ]);
        }
        
        fputcsv($out, ['TOTAL', number_format($revenue, 2), number_format($expenses, 2), number_format($net_income, 2)]);
    }

    fclose($out);
    exit;
}
?>