<?php
// =============================
// BALANCE SHEET (Date Range Filter + Suspense Dynamic Fix + Excel Export)
// Updated to use filter_from / filter_to (appearance like Trial Balance)
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

// --- Calculate Net Income dynamically based on date filter ---
$income_stmt = $conn->prepare("
    SELECT
      COALESCE(SUM(CASE WHEN g.group_type='Income' THEN ve.amount * 
            (CASE WHEN ve.type='Cr' THEN 1 WHEN ve.type='Dr' THEN -1 END) ELSE 0 END),0) AS income_total,
      COALESCE(SUM(CASE WHEN g.group_type='Expense' THEN ve.amount * 
            (CASE WHEN ve.type='Dr' THEN 1 WHEN ve.type='Cr' THEN -1 END) ELSE 0 END),0) AS expense_total
    FROM voucher_entries ve
    JOIN vouchers v ON ve.voucher_id = v.voucher_id
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    JOIN account_groups g ON l.group_id = g.group_id
    WHERE g.group_type IN ('Income','Expense') AND l.country_id = ? AND v.country_id = ? AND $dateExpr
");
$income_stmt->bind_param("ii", $active_country_id, $active_country_id);
$income_stmt->execute();
$income_data = $income_stmt->get_result()->fetch_assoc();
$income_stmt->close();
$net_income = ($income_data['income_total'] ?? 0) - ($income_data['expense_total'] ?? 0);

// --- Fetch all ledgers with balances (respect dateExpr) ---
$query = "
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.balance_type,
        COALESCE(l.opening_balance,0) AS opening_balance,
        ag.group_name,
        ag.group_type,
        COALESCE(SUM(CASE WHEN ve.type='Dr' AND $dateExpr THEN ve.amount ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' AND $dateExpr THEN ve.amount ELSE 0 END), 0) AS total_cr
    FROM ledgers l
    LEFT JOIN account_groups ag ON l.group_id = ag.group_id
    LEFT JOIN voucher_entries ve ON ve.ledger_id = l.ledger_id
    LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
    WHERE COALESCE(ag.group_type,'') IN ('Asset','Liability','Equity','Suspense')
      AND l.country_id = ?
      AND (v.country_id = ? OR v.country_id IS NULL)
    GROUP BY l.ledger_id
    ORDER BY ag.group_type, ag.group_name, l.ledger_name
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $active_country_id, $active_country_id);
$stmt->execute();
$result = $stmt->get_result();

$accounts = [];
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
    }
}

// =============================
// EXCEL EXPORT (CSV) - includes filter_from/filter_to in filename and headers
// =============================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header('Content-Type: text/csv; charset=UTF-8');
    $period_part = ($filter_from || $filter_to) ? ($filter_from . '_to_' . $filter_to) : date('Y-m-d');
    header('Content-Disposition: attachment; filename="balance_sheet_period_' . $period_part . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    $out = fopen('php://output', 'w');

    // Header
    fputcsv($out, ['Balance Sheet']);
    $period_label = 'All Dates';
    if ($filter_from && $filter_to) $period_label = "{$filter_from} to {$filter_to}";
    elseif ($filter_from) $period_label = "From {$filter_from}";
    elseif ($filter_to) $period_label = "Up to {$filter_to}";
    fputcsv($out, ["As at: $period_label"]);
    fputcsv($out, []);

    // Export helper
    function export_section($out, $accounts, $type, &$total) {
        fputcsv($out, [strtoupper($type)]);
        $total = 0;
        foreach ($accounts as $acc) {
            if (strtolower($acc['group_type']) === strtolower($type)) {
                $amount = ($type === 'asset') ? max($acc['balance'],0) : abs($acc['balance']);
                if ($amount != 0) {
                    fputcsv($out, [$acc['ledger_name'], number_format($amount,2,'.','')]);
                    $total += $amount;
                }
            }
        }
        fputcsv($out, ['Total '.ucfirst($type), number_format($total,2,'.','')]);
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
                fputcsv($out, [$acc['ledger_name'], number_format($amount,2,'.','')]);
                $equity_total += $amount;
            }
        }
    }
    fputcsv($out, ["Retained Earnings / Net Income", number_format(abs($net_income),2,'.','')]);
    $total_equity = $equity_total + $net_income;
    fputcsv($out, ["Total Equity", number_format($total_equity,2,'.','')]);

    fclose($out);
    exit;
}
?>