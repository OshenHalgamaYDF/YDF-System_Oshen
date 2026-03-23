<?php
// ========================================
// BANK RECONCILIATION (combined view + AJAX endpoint + ledger export + file upload)
// Added date range filter (filter_from, filter_to) to AJAX ledger view and export
// Added automatic reconciliation via bank statement upload
// ========================================
session_start(); // Start session for country selection

// Load composer autoload if available
$autoloadPath = __DIR__ . '/../../../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ============================================================================
// COUNTRY SELECTION HANDLING (Removed - now shows consolidated data)
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

// --- Date range filter (from/to) ---
$filter_from = isset($_GET['filter_from']) && $_GET['filter_from'] ? $_GET['filter_from'] : date('Y-01-01');
$filter_to   = isset($_GET['filter_to']) && $_GET['filter_to'] ? $_GET['filter_to'] : date('Y-m-d');

// --- Handle Bank Statement Upload and Auto-Reconciliation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_statement'])) {
    header('Content-Type: application/json; charset=utf-8');
    $ledger_id = intval($_POST['ledger_id'] ?? 0);
    if ($ledger_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid bank account selected.']);
        exit;
    }

    if (!isset($_FILES['bank_statement']) || $_FILES['bank_statement']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'File upload error.']);
        exit;
    }

    $file = $_FILES['bank_statement'];
    $allowed_types = ['text/csv', 'application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only CSV, PDF, or Excel files are allowed.']);
        exit;
    }

    $allowed_extensions = ['csv', 'xls', 'xlsx', 'pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_extensions)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file extension. Please upload CSV, PDF, or Excel.']);
        exit;
    }

    // Create upload directory if not exists
    $upload_dir = __DIR__ . '/../../../uploads/bank_statements/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = 'bank_statement_' . $ledger_id . '_' . time() . '_' . basename($file['name']);
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file.']);
        exit;
    }

    $transactions = [];
    if ($ext === 'csv') {
        if (($handle = fopen($filepath, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) < 3) {
                    continue;
                }

                $rawDate = trim($data[0]);
                $rawAmount = trim($data[2]);
                $date = date('Y-m-d', strtotime(str_replace('/', '-', $rawDate)));
                if (!$date || $date === '1970-01-01') {
                    continue;
                }

                $cleanAmount = str_replace([',', ' ', '₹', 'Rs', 'USD', 'LKR'], '', $rawAmount);
                $cleanAmount = preg_replace('/[^0-9\.-]/', '', $cleanAmount);
                if ($cleanAmount === '' || !is_numeric($cleanAmount)) {
                    continue;
                }

                $amount = (float)$cleanAmount;
                if ($amount == 0) {
                    continue;
                }

                $type = $amount < 0 ? 'Dr' : 'Cr';
                $amount = abs($amount);
                $description = trim($data[1]);

                $transactions[] = [
                    'date' => $date,
                    'description' => $description,
                    'amount' => $amount,
                    'type' => $type,
                ];
            }
            fclose($handle);
        }
    } elseif ($ext === 'pdf') {
        require_once __DIR__ . '/../../../../vendor/autoload.php';

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filepath);
            $text = $pdf->getText();

            // Debug logging
            error_log("Original PDF Text length: " . strlen($text));
            error_log("First 500 chars: " . substr($text, 0, 500));

            // Clean up the text - remove HTML-like tags and normalize
            $text = strip_tags($text);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = str_replace(['>', '<'], '', $text);

            // Split into lines based on dateable patterns
            $lines = [];
            $parts = preg_split('/(?=\d{2}\/\d{2}\/\d{4})/', $text);
            foreach ($parts as $part) {
                $part = trim($part);
                if (!empty($part) && strlen($part) > 10) {
                    $lines[] = $part;
                }
            }
            if (count($lines) < 5) {
                $lines = explode("\n", $text);
            }
            error_log("Number of potential transaction lines: " . count($lines));

            $transactions = [];
            $processed = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strlen($line) < 15 ||
                    stripos($line, 'statement') !== false ||
                    stripos($line, 'report') !== false ||
                    stripos($line, 'page') !== false ||
                    stripos($line, 'balance') !== false ||
                    stripos($line, 'client') !== false ||
                    stripos($line, 'bank') !== false ||
                    stripos($line, 'account') !== false) {
                    continue;
                }

                if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $line, $dateMatch)) {
                    $dateStr = $dateMatch[1];

                    if (preg_match_all('/(\d{1,3}(?:,\d{3})*\.\d{2})/', $line, $amountMatches)) {
                        $amountStr = end($amountMatches[1]);
                        $amount = (float)str_replace(',', '', $amountStr);
                        $dateParts = explode('/', $dateStr);
                        if (count($dateParts) == 3) {
                            $date = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                            $type = 'Dr';
                            if (preg_match('/(SD|CREDIT|INWARD|TRF FROM|DEPOSIT)/i', $line)) {
                                $type = 'Cr';
                            }
                            $description = str_replace($dateStr, '', $line);
                            $description = str_replace($amountStr, '', $description);
                            $description = preg_replace('/\d{1,3}(?:,\d{3})*\.\d{2}/', '', $description);
                            $description = trim(preg_replace('/\s+/', ' ', $description));
                            $description = str_replace(['DC', 'SD', 'CEFT', 'HNB', 'TXB', 'CHG'], '', $description);
                            $description = preg_replace('/[0-9]{10,}/', '', $description);
                            $description = trim($description);
                            if (empty($description)) {
                                $description = 'Bank Transaction';
                            }

                            $key = $date . '|' . $amount . '|' . substr($description, 0, 20);
                            if (!isset($processed[$key])) {
                                $processed[$key] = true;
                                $transactions[] = [
                                    'date' => $date,
                                    'description' => substr($description, 0, 255),
                                    'amount' => $amount,
                                    'type' => $type,
                                ];
                                error_log("Found transaction: $date | $amount | $type | $description");
                            }
                        }
                    }
                }
            }

            // Aggressive fallback pattern
            if (count($transactions) === 0 && preg_match_all('/(\d{2}\/\d{2}\/\d{4})([A-Z]{2}\d+)([\d,]+\.\d{2})/', $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $dateParts = explode('/', $match[1]);
                    $date = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                    $amount = (float)str_replace(',', '', $match[3]);
                    $key = $date . '|' . $amount;
                    if (!isset($processed[$key])) {
                        $processed[$key] = true;
                        $transactions[] = [
                            'date' => $date,
                            'description' => 'Bank Transaction Ref: ' . $match[2],
                            'amount' => $amount,
                            'type' => (strpos($match[2], 'SD') !== false) ? 'Cr' : 'Dr',
                        ];
                    }
                }
            }

            // Deduplicate
            $unique = [];
            foreach ($transactions as $txn) {
                $key = $txn['date'] . '|' . $txn['amount'] . '|' . $txn['type'];
                if (!isset($unique[$key])) {
                    $unique[$key] = $txn;
                }
            }
            $transactions = array_values($unique);

            error_log("Final transaction count: " . count($transactions));

            if (count($transactions) === 0) {
                $debug_file = __DIR__ . '/../../../uploads/bank_statements/debug_' . time() . '.txt';
                file_put_contents($debug_file, $text);
                echo json_encode([
                    'success' => false,
                    'error' => 'Could not parse PDF transactions. The file has been saved for debugging. Please try exporting as CSV instead.'
                ]);
                exit;
            }

        } catch (Exception $e) {
            error_log("PDF Parser Exception: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'PDF parsing failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Auto-reconcile: Match with voucher entries
    $matched = 0;
    $unmatched = 0;
    foreach ($transactions as $txn) {
        $match_sql = "
            SELECT ve.entry_id
            FROM voucher_entries ve
            JOIN vouchers v ON ve.voucher_id = v.voucher_id
            LEFT JOIN bank_reconciliation br ON br.voucher_entry_id = ve.entry_id
            WHERE ve.ledger_id = ? AND v.date = ? AND ve.amount = ? AND ve.type = ? AND (br.is_cleared IS NULL OR br.is_cleared = 0)
            LIMIT 1
        ";
        $match_stmt = $conn->prepare($match_sql);
        $match_stmt->bind_param("isds", $ledger_id, $txn['date'], $txn['amount'], $txn['type']);
        $match_stmt->execute();
        $match_res = $match_stmt->get_result();

        if ($match_res->num_rows === 0) {
            // Fallback: If type mismatch or missing type, try date+amount only
            $fallback_sql = "
                SELECT ve.entry_id
                FROM voucher_entries ve
                JOIN vouchers v ON ve.voucher_id = v.voucher_id
                LEFT JOIN bank_reconciliation br ON br.voucher_entry_id = ve.entry_id
                WHERE ve.ledger_id = ? AND v.date = ? AND ve.amount = ? AND (br.is_cleared IS NULL OR br.is_cleared = 0)
                LIMIT 1
            ";
            $fallback_stmt = $conn->prepare($fallback_sql);
            $fallback_stmt->bind_param("isd", $ledger_id, $txn['date'], $txn['amount']);
            $fallback_stmt->execute();
            $match_res = $fallback_stmt->get_result();
            $fallback_stmt->close();
        }

        if ($match_res->num_rows > 0) {
            $entry = $match_res->fetch_assoc();
            $entry_id = $entry['entry_id'];
            $update_stmt = $conn->prepare("INSERT INTO bank_reconciliation (voucher_entry_id, is_cleared, cleared_date) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE is_cleared = 1, cleared_date = ?");
            $update_stmt->bind_param("iss", $entry_id, date('Y-m-d'), date('Y-m-d'));
            $update_stmt->execute();
            $update_stmt->close();
            $matched++;
        } else {
            $unmatched++;
        }

        $match_stmt->close();
    }

    // Optionally, store the statement in DB (create table if needed)
    // For now, just log the upload
    $log_stmt = $conn->prepare("INSERT INTO bank_statement_uploads (ledger_id, filename, uploaded_at, matched, unmatched) VALUES (?, ?, NOW(), ?, ?)");
    $log_stmt->bind_param("isii", $ledger_id, $filename, $matched, $unmatched);
    $log_stmt->execute();
    $log_stmt->close();

    echo json_encode(['success' => true, 'matched' => $matched, 'unmatched' => $unmatched]);
    exit;
}

// --- AJAX: Update Reconciliation Status (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brs'])) {
    header('Content-Type: application/json; charset=utf-8');
    $entry_id = intval($_POST['entry_id'] ?? 0);
    $is_cleared = intval($_POST['is_cleared'] ?? 0);
    $cleared_date = $is_cleared ? date('Y-m-d') : null;

    if ($entry_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid entry_id']);
        exit;
    }

    // Check if reconciliation row exists
    $check = $conn->prepare("SELECT reconciliation_id FROM bank_reconciliation WHERE voucher_entry_id = ?");
    $check->bind_param("i", $entry_id);
    $check->execute();
    $check->store_result();
    $exists = $check->num_rows > 0;
    $check->bind_result($recon_id);
    $check->fetch();
    $check->close();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE bank_reconciliation SET is_cleared = ?, cleared_date = ? WHERE voucher_entry_id = ?");
        $stmt->bind_param("isi", $is_cleared, $cleared_date, $entry_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO bank_reconciliation (voucher_entry_id, is_cleared, cleared_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $entry_id, $is_cleared, $cleared_date);
    }

    $success = $stmt->execute();
    $error = $stmt->error;
    $stmt->close();

    echo json_encode(['success' => $success, 'error' => $success ? null : $error]);
    exit;
}

// --- If ledger_id is provided and export=excel => produce CSV for that ledger only (respect date range) ---
if (isset($_GET['export']) && $_GET['export'] === 'excel' && isset($_GET['ledger_id'])) {
    $ledger_id = intval($_GET['ledger_id']);
    if ($ledger_id <= 0) {
        http_response_code(400);
        die('Invalid ledger_id for export.');
    }

    // Prepare SQL to fetch all voucher entries for ledger with reconciliation info, limited by date range
    $sql = "
        SELECT ve.entry_id, v.date, v.narration, ve.type, ve.amount,
               COALESCE(br.is_cleared, 0) AS is_cleared, br.cleared_date
        FROM voucher_entries ve
        JOIN vouchers v ON ve.voucher_id = v.voucher_id
        LEFT JOIN bank_reconciliation br ON br.voucher_entry_id = ve.entry_id
        WHERE ve.ledger_id = ? AND v.date >= ? AND v.date <= ?
        ORDER BY v.date DESC, ve.entry_id DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $ledger_id, $filter_from, $filter_to);
    $stmt->execute();
    $res = $stmt->get_result();

    // Fetch ledger name for file naming
    $lstmt = $conn->prepare("SELECT ledger_name FROM ledgers WHERE ledger_id = ? LIMIT 1");
    $lstmt->bind_param("i", $ledger_id);
    $lstmt->execute();
    $lstmt->bind_result($ledger_name);
    $lstmt->fetch();
    $lstmt->close();

    // CSV headers
    header('Content-Type: text/csv; charset=UTF-8');
    $safeName = $ledger_name ? preg_replace('/[^a-z0-9_\-]/i','_', $ledger_name) : $ledger_id;
    $filename = 'bank_reconciliation_' . $safeName . '_' . $filter_from . '_to_' . $filter_to . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // UTF-8 BOM so Excel recognizes encoding
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Top header
    fputcsv($out, ['Bank Reconciliation Statement']);
    fputcsv($out, ['Ledger', $ledger_name ?: "Ledger #{$ledger_id}"]);
    fputcsv($out, ['Period', $filter_from . ' to ' . $filter_to]);
    fputcsv($out, []); // blank line

    // Columns
    fputcsv($out, ['Date', 'Narration', 'Type', 'Amount (Rs.)', 'Cleared (Yes/No)', 'Cleared Date']);

    $cleared_total = 0.0;
    $uncleared_total = 0.0;

    while ($row = $res->fetch_assoc()) {
        $date = $row['date'];
        $narration = $row['narration'];
        $type = $row['type'];
        $amount = (float)$row['amount'];
        $is_cleared = (int)$row['is_cleared'];
        $cleared_date = $row['cleared_date'] ?: '';

        fputcsv($out, [$date, $narration, $type, number_format($amount, 2, '.', ''), $is_cleared ? 'Yes' : 'No', $cleared_date]);

        if ($is_cleared) $cleared_total += $amount; else $uncleared_total += $amount;
    }

    // Totals
    fputcsv($out, []);
    fputcsv($out, ['Cleared Total', number_format($cleared_total, 2, '.', '')]);
    fputcsv($out, ['Uncleared Total', number_format($uncleared_total, 2, '.', '')]);

    fclose($out);
    exit;
}

// --- Load ledger data (GET) for AJAX view (same behavior as before) but respect date range ---
if (isset($_GET['ledger_id'])) {
    $ledger_id = intval($_GET['ledger_id']);
    if ($ledger_id <= 0) {
        echo '<div class="alert alert-warning">Invalid ledger selected.</div>';
        exit;
    }

    $sql = "
        SELECT ve.entry_id, v.date, v.narration, ve.type, ve.amount,
               COALESCE(br.is_cleared, 0) AS is_cleared, br.cleared_date
        FROM voucher_entries ve
        JOIN vouchers v ON ve.voucher_id = v.voucher_id
        LEFT JOIN bank_reconciliation br ON br.voucher_entry_id = ve.entry_id
        WHERE ve.ledger_id = ? AND v.date >= ? AND v.date <= ?
        ORDER BY v.date DESC, ve.entry_id DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $ledger_id, $filter_from, $filter_to);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo '<p class="text-muted text-center my-3">No entries found for this bank account in the selected period.</p>';
        $stmt->close();
        exit;
    }

    $cleared_total = 0.0;
    $uncleared_total = 0.0;

    echo '<div class="table-responsive">';
    echo '<div class="mb-2 text-muted">Showing entries from <strong>' . htmlspecialchars($filter_from) . '</strong> to <strong>' . htmlspecialchars($filter_to) . '</strong></div>';
    echo '<table class="table table-bordered align-middle shadow-sm">';
    echo '<thead class="table-success text-center">';
    echo '<tr><th>Date</th><th>Narration</th><th>Type</th><th class="text-end">Amount (Rs)</th><th class="no-print">Cleared</th></tr>';
    echo '</thead><tbody>';

    while ($row = $res->fetch_assoc()) {
        $eid = (int)$row['entry_id'];
        $date = htmlspecialchars($row['date']);
        $narration = htmlspecialchars($row['narration']);
        $type = htmlspecialchars($row['type']);
        $amount = (float)$row['amount'];
        $is_cleared = (int)$row['is_cleared'];

        if ($is_cleared) $cleared_total += $amount; else $uncleared_total += $amount;

        echo '<tr class="'.($is_cleared ? 'table-light' : '').'">';
        echo "<td>{$date}</td>";
        echo "<td>{$narration}</td>";
        echo "<td class='text-center'>{$type}</td>";
        echo "<td class='text-end fw-semibold'>" . number_format($amount, 2) . "</td>";
        echo "<td class='text-center no-print'>
                <input type='checkbox' class='form-check-input brs-checkbox' data-entry-id='{$eid}' " . ($is_cleared ? 'checked' : '') . ">
              </td>";
        echo '</tr>';
    }

    echo '</tbody>';
    echo '<tfoot class="fw-bold">';
    echo '<tr><td colspan="3" class="text-end text-success">Cleared Total</td><td class="text-end text-success">' . number_format($cleared_total, 2) . '</td><td></td></tr>';
    echo '<tr><td colspan="3" class="text-end text-danger">Uncleared Total</td><td class="text-end text-danger">' . number_format($uncleared_total, 2) . '</td><td></td></tr>';
    echo '</tfoot></table></div>';

    $stmt->close();
    exit;
}

// --- Fetch all bank ledgers for the select dropdown ---
$bankLedgers = $conn->query("
    SELECT l.ledger_id, l.ledger_name 
    FROM ledgers l
    JOIN account_groups ag ON l.group_id = ag.group_id
    WHERE ag.group_name = 'Bank Accounts'
    ORDER BY l.ledger_name ASC
");
?> 