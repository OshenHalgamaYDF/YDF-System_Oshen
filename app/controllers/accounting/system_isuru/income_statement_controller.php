<?php
// =============================
// INCOME STATEMENT (Group-based with Date Range Filter)
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
// COUNTRY SELECTION HANDLING
// ============================================================================
if (!isset($_SESSION['country_id'])) {
    $default_country = $conn->query("SELECT id FROM countries ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $_SESSION['country_id'] = $default_country ? $default_country['id'] : 1;
}

$active_country_id = $_SESSION['country_id'];

// Fetch active country details
$country_stmt = $conn->prepare("SELECT country_name, currency_code FROM countries WHERE id = ?");
$country_stmt->bind_param("i", $active_country_id);
$country_stmt->execute();
$active_country = $country_stmt->get_result()->fetch_assoc();
$country_stmt->close();

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

// =====================
// 1️⃣ Query all ledgers + their group type + totals (date filtered and country filtered)
// =====================
$query = "
    SELECT 
        l.ledger_id,
        l.ledger_name,
        g.group_name,
        g.group_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0) AS total_cr
    FROM ledgers l
    LEFT JOIN voucher_entries ve ON ve.ledger_id = l.ledger_id
    LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
    LEFT JOIN account_groups g ON g.group_id = l.group_id
    WHERE g.group_type IN ('Income', 'Expense')
      AND l.country_id = ?
      AND v.country_id = ?
      AND $dateExpr
    GROUP BY l.ledger_id, l.ledger_name, g.group_name, g.group_type
    ORDER BY g.group_type, l.ledger_name
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $active_country_id, $active_country_id);
$stmt->execute();
$result = $stmt->get_result();

$accounts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $accounts[] = $row;
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
// 2️⃣ EXPORT TO EXCEL (CSV)
// =====================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header('Content-Type: text/csv; charset=UTF-8');
    $filename = 'income_statement_' . $filter_from . '_to_' . $filter_to . '.csv';
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');

    // Header
    $period_label = $filter_from . ' to ' . $filter_to;

    fputcsv($out, ["Income Statement for $period_label"]);
    fputcsv($out, []);

    // Income Section
    fputcsv($out, ['INCOME']);
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Income') {
            $amount = $acc['total_cr'] - $acc['total_dr'];
            if ($amount != 0) fputcsv($out, [$acc['ledger_name'], number_format($amount, 2, '.', '')]);
        }
    }
    fputcsv($out, ['Total Income', number_format($revenue, 2, '.', '')]);
    fputcsv($out, []);

    // Expenses Section
    fputcsv($out, ['EXPENSES']);
    foreach ($accounts as $acc) {
        if ($acc['group_type'] === 'Expense') {
            $amount = $acc['total_dr'] - $acc['total_cr'];
            if ($amount != 0) fputcsv($out, [$acc['ledger_name'], number_format($amount, 2, '.', '')]);
        }
    }
    fputcsv($out, ['Total Expenses', number_format($expenses, 2, '.', '')]);
    fputcsv($out, []);

    // Net Income
    $label = $net_income >= 0 ? 'Net Income' : 'Net Loss';
    fputcsv($out, [$label, number_format($net_income, 2, '.', '')]);

    fclose($out);
    exit;
}
?>