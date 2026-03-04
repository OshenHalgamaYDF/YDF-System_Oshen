<?php
// ============================================================================
// accountingcontroller.php – COMPLETE VERSION WITH SIMPLIFIED EXCHANGE RATE SYSTEM
// ============================================================================

session_start(); // Start session for country selection

// ============================================================================
// 1) DATABASE CONNECTION
// ============================================================================
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

// Connect to MySQL using MySQLi
$conn = new mysqli($servername, $username, $password, $database);

// Stop everything if connection fails
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4'); // Always use UTF-8 for safety

// ============================================================================
// COUNTRY SELECTION HANDLING (with All Countries option)
// ============================================================================
if (isset($_POST['select_country'])) {
    $_SESSION['country_id'] = intval($_POST['country_id']); // 0 for All Countries
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Default to first country if not set
if (!isset($_SESSION['country_id'])) {
    $default_country = $conn->query("SELECT id FROM countries ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $_SESSION['country_id'] = $default_country ? $default_country['id'] : 1;
}

$active_country_id = $_SESSION['country_id'];

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

// ============================================================================
// 2) AJAX ENDPOINT — LOAD VOUCHER FORM FOR EDITING
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_voucher' && isset($_GET['id'])) {

    $id = intval($_GET['id']);

    // Fetch Voucher Header
    if ($active_country_id == 0) {
        $vstmt = $conn->prepare("
            SELECT voucher_id, voucher_type, date, narration, currency_id, exchange_rate
            FROM vouchers
            WHERE voucher_id = ?
        ");
        $vstmt->bind_param("i", $id);
    } else {
        $vstmt = $conn->prepare("
            SELECT voucher_id, voucher_type, date, narration, currency_id, exchange_rate
            FROM vouchers
            WHERE voucher_id = ? AND (country_id = ? OR country_id = 0)
        ");
        $vstmt->bind_param("ii", $id, $active_country_id);
    }
    $vstmt->execute();
    $voucher = $vstmt->get_result()->fetch_assoc();
    $vstmt->close();

    if (!$voucher) {
        echo "<div class='alert alert-danger'>Voucher not found or access denied.</div>";
        exit;
    }

    // Fetch Dr/Cr Entries
    $estmt = $conn->prepare("
        SELECT entry_id, ledger_id, type, amount, amount_lkr
        FROM voucher_entries
        WHERE voucher_id = ?
        ORDER BY FIELD(type, 'Dr', 'Cr')
    ");
    $estmt->bind_param("i", $id);
    $estmt->execute();
    $entries = $estmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $estmt->close();

    // Get the amount from the first entry (Dr or Cr)
    $voucher_amount = 0;
    foreach ($entries as $e) {
        $voucher_amount = floatval($e['amount']);
        break;
    }

    // Fetch Ledgers For Dropdowns
    $ledgers_stmt = $conn->prepare("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
    $ledgers_stmt->execute();
    $ledgers_result = $ledgers_stmt->get_result();
    $ledgers_dr = [];
    $ledgers_cr = [];
    while ($l = $ledgers_result->fetch_assoc()) {
        $ledgers_dr[] = $l;
        $ledgers_cr[] = $l;
    }
    $ledgers_stmt->close();

    // Return HTML
    ?>
    
    <div class="row mb-2">
        <div class="col-md-4">
            <label>Voucher Type</label>
            <select name="voucher_type" class="form-select" required>
                <?php
                $types = ['Payment','Receipt','Journal','Contra'];
                foreach ($types as $t) {
                    $sel = ($voucher['voucher_type'] === $t) ? 'selected' : '';
                    echo "<option value=\"$t\" $sel>$t</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label>Date</label>
            <input type="date" id="editDateInput" name="date" class="form-control"
                   value="<?= htmlspecialchars($voucher['date']) ?>" required>
        </div>
        <div class="col-md-4">
            <label>Amount <?= ($active_country_id == 0) ? '' : '(' . htmlspecialchars($active_country['currency_code']) . ')' ?></label>
            <input type="number" name="amount" id="editAmountInput" class="form-control" step="0.01"
                   required value="<?= number_format($voucher_amount, 2, '.', '') ?>">
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-md-6">
            <label>Currency</label>
            <select name="currency_id" id="editCurrencySelect" class="form-select">
                <?php
                $currencies = $conn->query("SELECT currency_id, code, name FROM currencies ORDER BY code");
                while ($c = $currencies->fetch_assoc()) {
                    $sel = ($c['currency_id'] == ($voucher['currency_id'] ?? 1)) ? 'selected' : '';
                    echo "<option value='{$c['currency_id']}' $sel>{$c['code']} ({$c['name']})</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-6">
            <label>Exchange Rate (1 FC = ? LKR)</label>
            <input type="number" name="exchange_rate" id="editExchangeRateInput" class="form-control" step="0.000001" value="<?= number_format((float)($voucher['exchange_rate'] ?? 1.0), 6, '.', '') ?>" required>
            <small class="text-muted">Current rate from voucher</small>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-md-6">
            <label>Debit Ledger (Dr)</label>
            <select name="dr_ledger" class="form-select" required>
                <option value="">-- Select --</option>
                <?php
                $dr_id = null;
                foreach ($entries as $e) if ($e['type'] === 'Dr') $dr_id = $e['ledger_id'];

                foreach ($ledgers_dr as $l) {
                    $sel = ($l['ledger_id'] == $dr_id) ? 'selected' : '';
                    echo "<option value='{$l['ledger_id']}' $sel>{$l['ledger_name']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-6">
            <label>Credit Ledger (Cr)</label>
            <select name="cr_ledger" class="form-select" required>
                <option value="">-- Select --</option>
                <?php
                $cr_id = null;
                foreach ($entries as $e) if ($e['type'] === 'Cr') $cr_id = $e['ledger_id'];

                foreach ($ledgers_cr as $l) {
                    $sel = ($l['ledger_id'] == $cr_id) ? 'selected' : '';
                    echo "<option value='{$l['ledger_id']}' $sel>{$l['ledger_name']}</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label>Narration</label>
        <textarea name="narration" class="form-control" rows="2"><?= htmlspecialchars($voucher['narration']) ?></textarea>
    </div>

    <input type="hidden" name="voucher_id" value="<?= $voucher['voucher_id'] ?>">

    <?php
    foreach ($entries as $e) {
        echo "<input type='hidden' name='entry_id_map[{$e['type']}]' value='{$e['entry_id']}'>";
    }
    exit;
}

// ============================================================================
// AJAX: GET LEDGERS
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_ledgers') {
    $ledgers = [];
    $stmt = $conn->prepare("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $ledgers[] = $r;
    }
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['ledgers' => $ledgers]);
    exit;
}

// ============================================================================
// AJAX: GET EXCHANGE RATE - Returns LKR per foreign currency
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_rate' && isset($_GET['code'])) {
    $code = $_GET['code'];
    $date = $_GET['date'] ?? date('Y-m-d');
    $rate = null;
    $stmt = $conn->prepare("
        SELECT er.rate_to_lkr 
        FROM exchange_rates er 
        JOIN currencies c ON er.currency_id = c.currency_id 
        WHERE c.code = ? AND er.rate_date <= ? 
        ORDER BY er.rate_date DESC LIMIT 1
    ");
    $stmt->bind_param("ss", $code, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($r = $result->fetch_assoc()) {
        $rate = $r['rate_to_lkr'];
    }
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['rate' => $rate]);
    exit;
}

// ============================================================================
// AJAX: GET MANUAL RATES FOR CURRENCY
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_manual_rates' && isset($_GET['code'])) {
    $code = $_GET['code'];
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Get the most recent manual rate for this currency
    $stmt = $conn->prepare("
        SELECT er.rate_to_lkr, er.rate_date
        FROM exchange_rates er
        JOIN currencies c ON er.currency_id = c.currency_id
        WHERE c.code = ? AND er.source = 'Manual' AND er.rate_date <= ?
        ORDER BY er.rate_date DESC
        LIMIT 1
    ");
    $stmt->bind_param("ss", $code, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'rate' => $row['rate_to_lkr'],
            'date' => $row['rate_date']
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false]);
    }
    $stmt->close();
    exit;
}

// ============================================================================
// AJAX: GET CURRENT RATES FOR DASHBOARD
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_current_rates') {
    $query = "
        SELECT er1.*, c.code, c.name 
        FROM exchange_rates er1
        JOIN currencies c ON er1.currency_id = c.currency_id
        WHERE er1.rate_date = (
            SELECT MAX(rate_date) 
            FROM exchange_rates er2 
            WHERE er2.currency_id = er1.currency_id
        )
        ORDER BY c.code
        LIMIT 8
    ";
    
    $result = $conn->query($query);
    $rates = [];
    
    while ($row = $result->fetch_assoc()) {
        $rates[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'rates' => $rates]);
    exit;
}

// ============================================================================
// AJAX: GET CURRENCY ID BY CODE
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_currency_id' && isset($_GET['code'])) {
    $code = $_GET['code'];
    $stmt = $conn->prepare("SELECT currency_id FROM currencies WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['currency_id' => $row['currency_id']]);
    } else {
        echo json_encode(['currency_id' => null]);
    }
    $stmt->close();
    exit;
}

// ============================================================================
// 3) ADD LEDGER
// ============================================================================
if (isset($_POST['add_ledger'])) {

    $ledger_name      = $_POST['ledger_name'] ?? '';
    $opening_balance  = floatval($_POST['opening_balance'] ?? 0);
    $balance_type     = $_POST['balance_type'] ?? 'Dr';
    $group_id         = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;

    if (empty($group_id)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please select an Account Group.']);
            exit;
        }
        echo "<script>alert('⚠️ Please select an Account Group.');</script>";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO ledgers (ledger_name, opening_balance, balance_type, group_id, country_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sdsii", $ledger_name, $opening_balance, $balance_type, $group_id, $active_country_id);
        $stmt->execute();
        $newId = $conn->insert_id;
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'ledger' => ['ledger_id' => $newId, 'ledger_name' => $ledger_name]]);
            exit;
        }
        header("Location: ?success=1");
        exit;
    }
}

// ============================================================================
// 4) ADD VOUCHER
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_voucher'])) {

    $voucher_type = $_POST['voucher_type'] ?? '';
    $date         = $_POST['date'] ?? date('Y-m-d');
    $narration    = $_POST['narration'] ?? '';
    $dr_ledger    = intval($_POST['dr_ledger'] ?? 0);
    $cr_ledger    = intval($_POST['cr_ledger'] ?? 0);
    $amount       = floatval($_POST['amount'] ?? 0);
    $currency_id  = intval($_POST['currency_id'] ?? 1);
    $exchange_rate = floatval($_POST['exchange_rate'] ?? 1.0);

    if ($dr_ledger <= 0 || $cr_ledger <= 0 || $amount <= 0) {
        header("Location: ?error=invalid_input");
        exit;
    }

    $ledger_check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM ledgers WHERE ledger_id IN (?, ?)");
    $ledger_check_stmt->bind_param("ii", $dr_ledger, $cr_ledger);
    $ledger_check_stmt->execute();
    $ledger_check = $ledger_check_stmt->get_result()->fetch_assoc();
    $ledger_check_stmt->close();
    if ($ledger_check['count'] != 2) {
        header("Location: ?error=invalid_ledgers");
        exit;
    }

    $amount_lkr = round($amount * $exchange_rate, 2);
    $country_id_to_save = ($active_country_id == 0) ? 0 : $active_country_id;

    $conn->begin_transaction();

    $stmt = $conn->prepare("
        INSERT INTO vouchers (voucher_type, date, narration, currency_id, exchange_rate, country_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssidi", $voucher_type, $date, $narration, $currency_id, $exchange_rate, $country_id_to_save);
    $stmt->execute();
    $voucher_id = $conn->insert_id;

    $stmt2 = $conn->prepare("
        INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount, amount_lkr)
        VALUES (?, ?, 'Dr', ?, ?)
    ");
    $stmt2->bind_param("iidd", $voucher_id, $dr_ledger, $amount, $amount_lkr);
    $stmt2->execute();

    $stmt3 = $conn->prepare("
        INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount, amount_lkr)
        VALUES (?, ?, 'Cr', ?, ?)
    ");
    $stmt3->bind_param("iidd", $voucher_id, $cr_ledger, $amount, $amount_lkr);
    $stmt3->execute();

    $conn->commit();
    header("Location: ?success=1");
    exit;
}

// ============================================================================
// 5) UPDATE EXISTING VOUCHER
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_voucher'])) {

    $id           = intval($_POST['voucher_id'] ?? 0);
    $voucher_type = $_POST['voucher_type'] ?? '';
    $date         = $_POST['date'] ?? date('Y-m-d');
    $narration    = $_POST['narration'] ?? '';
    $dr_ledger    = intval($_POST['dr_ledger'] ?? 0);
    $cr_ledger    = intval($_POST['cr_ledger'] ?? 0);
    $amount       = floatval($_POST['amount'] ?? 0);
    $currency_id  = intval($_POST['currency_id'] ?? 1);
    $exchange_rate = floatval($_POST['exchange_rate'] ?? 1.0);
    $entry_map    = $_POST['entry_id_map'] ?? [];

    if ($id <= 0) {
        header("Location: ?error=bad_id");
        exit;
    }

    $ledger_check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM ledgers WHERE ledger_id IN (?, ?)");
    $ledger_check_stmt->bind_param("ii", $dr_ledger, $cr_ledger);
    $ledger_check_stmt->execute();
    $ledger_check = $ledger_check_stmt->get_result()->fetch_assoc();
    $ledger_check_stmt->close();
    if ($ledger_check['count'] != 2) {
        header("Location: ?error=invalid_ledgers");
        exit;
    }

    $amount_lkr = round($amount * $exchange_rate, 2);

    if ($dr_ledger === $cr_ledger) {
        header("Location: ?error=same_ledgers");
        exit;
    }

    $conn->begin_transaction();

    try {
        if ($active_country_id == 0) {
            $ustmt = $conn->prepare("
                UPDATE vouchers
                SET voucher_type = ?, date = ?, narration = ?, currency_id = ?, exchange_rate = ?
                WHERE voucher_id = ?
            ");
            $ustmt->bind_param("sssidi", $voucher_type, $date, $narration, $currency_id, $exchange_rate, $id);
        } else {
            $ustmt = $conn->prepare("
                UPDATE vouchers
                SET voucher_type = ?, date = ?, narration = ?, currency_id = ?, exchange_rate = ?
                WHERE voucher_id = ? AND (country_id = ? OR country_id = 0)
            ");
            $ustmt->bind_param("sssidii", $voucher_type, $date, $narration, $currency_id, $exchange_rate, $id, $active_country_id);
        }
        $ustmt->execute();
        $ustmt->close();

        if (isset($entry_map['Dr']) && intval($entry_map['Dr']) > 0) {
            $eid = intval($entry_map['Dr']);
            $estmt = $conn->prepare("
                UPDATE voucher_entries
                SET ledger_id = ?, amount = ?, amount_lkr = ?
                WHERE entry_id = ?
            ");
            $estmt->bind_param("iddi", $dr_ledger, $amount, $amount_lkr, $eid);
            $estmt->execute();
            $estmt->close();
        } else {
            $ist = $conn->prepare("
                INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount, amount_lkr)
                VALUES (?, ?, 'Dr', ?, ?)
            ");
            $ist->bind_param("iidd", $id, $dr_ledger, $amount, $amount_lkr);
            $ist->execute();
            $ist->close();
        }

        if (isset($entry_map['Cr']) && intval($entry_map['Cr']) > 0) {
            $eid = intval($entry_map['Cr']);
            $estmt = $conn->prepare("
                UPDATE voucher_entries
                SET ledger_id = ?, amount = ?, amount_lkr = ?
                WHERE entry_id = ?
            ");
            $estmt->bind_param("iddi", $cr_ledger, $amount, $amount_lkr, $eid);
            $estmt->execute();
            $estmt->close();
        } else {
            $ist = $conn->prepare("
                INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount, amount_lkr)
                VALUES (?, ?, 'Cr', ?, ?)
            ");
            $ist->bind_param("iidd", $id, $cr_ledger, $amount, $amount_lkr);
            $ist->execute();
            $ist->close();
        }

        $conn->commit();
        header("Location: ?updated=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ?error=update_failed&message=" . urlencode($e->getMessage()));
        exit;
    }
}

// ============================================================================
// 6) DELETE VOUCHER
// ============================================================================
if (isset($_POST['delete_voucher'])) {

    $voucher_id = intval($_POST['voucher_id']);

    if ($voucher_id > 0) {

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("DELETE FROM voucher_entries WHERE voucher_id = ?");
            $stmt->bind_param("i", $voucher_id);
            $stmt->execute();
            $stmt->close();

            if ($active_country_id == 0) {
                $stmt2 = $conn->prepare("DELETE FROM vouchers WHERE voucher_id = ?");
                $stmt2->bind_param("i", $voucher_id);
            } else {
                $stmt2 = $conn->prepare("DELETE FROM vouchers WHERE voucher_id = ? AND (country_id = ? OR country_id = 0)");
                $stmt2->bind_param("ii", $voucher_id, $active_country_id);
            }
            $stmt2->execute();
            $stmt2->close();

            $conn->commit();
            header("Location: ?deleted=1");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: ?error=delete_failed");
            exit;
        }

    } else {
        header("Location: ?error=invalid_id");
        exit;
    }
}

// ============================================================================
// 7) UPDATE EXCHANGE RATE (POST)
// ============================================================================
if (isset($_POST['update_exchange_rate'])) {
    $currency_id = intval($_POST['currency_id']);
    $rate_date = $_POST['rate_date'];
    $rate_to_lkr = floatval($_POST['rate_to_lkr']);
    $source = $_POST['source'] ?? 'Manual';
    
    // Check if rate for this currency and date already exists
    $check_stmt = $conn->prepare("SELECT rate_id FROM exchange_rates WHERE currency_id = ? AND rate_date = ?");
    $check_stmt->bind_param("is", $currency_id, $rate_date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing rate
        $update_stmt = $conn->prepare("UPDATE exchange_rates SET rate_to_lkr = ?, source = ? WHERE currency_id = ? AND rate_date = ?");
        $update_stmt->bind_param("dsis", $rate_to_lkr, $source, $currency_id, $rate_date);
        $success = $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new rate
        $insert_stmt = $conn->prepare("INSERT INTO exchange_rates (currency_id, rate_date, rate_to_lkr, source) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("isds", $currency_id, $rate_date, $rate_to_lkr, $source);
        $success = $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// ============================================================================
// 8) GET CASH PROFIT DATA FOR GRAPH
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_cash_profit') {
    
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 days'));
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    if (!strtotime($start_date) || !strtotime($end_date)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }
    
    if ($active_country_id == 0) {
        $stmt = $conn->prepare("
            SELECT 
                DATE(v.date) as trans_date,
                v.voucher_type,
                SUM(ve.amount_lkr) as total_amount
            FROM vouchers v
            JOIN voucher_entries ve ON v.voucher_id = ve.voucher_id
            WHERE v.voucher_type IN ('Payment', 'Receipt')
                AND v.date BETWEEN ? AND ?
            GROUP BY DATE(v.date), v.voucher_type
            ORDER BY v.date ASC
        ");
        $stmt->bind_param("ss", $start_date, $end_date);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                DATE(v.date) as trans_date,
                v.voucher_type,
                SUM(ve.amount_lkr) as total_amount
            FROM vouchers v
            JOIN voucher_entries ve ON v.voucher_id = ve.voucher_id
            WHERE (v.country_id = ? OR v.country_id = 0)
                AND v.voucher_type IN ('Payment', 'Receipt')
                AND v.date BETWEEN ? AND ?
            GROUP BY DATE(v.date), v.voucher_type
            ORDER BY v.date ASC
        ");
        $stmt->bind_param("iss", $active_country_id, $start_date, $end_date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
    
    $dates = [];
    $cashIn = [];
    $cashOut = [];
    $netProfit = [];
    $totals = ['total_in' => 0, 'total_out' => 0, 'net_profit' => 0];
    
    foreach ($dateRange as $date) {
        $dateKey = $date->format('Y-m-d');
        $dates[] = $date->format('M d');
        $cashIn[$dateKey] = 0;
        $cashOut[$dateKey] = 0;
    }
    
    while ($row = $result->fetch_assoc()) {
        $date = $row['trans_date'];
        if (array_key_exists($date, $cashIn)) {
            if ($row['voucher_type'] === 'Receipt') {
                $cashIn[$date] = floatval($row['total_amount']);
                $totals['total_in'] += floatval($row['total_amount']);
            } else if ($row['voucher_type'] === 'Payment') {
                $cashOut[$date] = floatval($row['total_amount']);
                $totals['total_out'] += floatval($row['total_amount']);
            }
        }
    }
    
    $result->free();
    $stmt->close();
    
    $cashInValues = [];
    $cashOutValues = [];
    $netProfitValues = [];
    
    foreach ($dateRange as $date) {
        $dateKey = $date->format('Y-m-d');
        $inValue = $cashIn[$dateKey] ?? 0;
        $outValue = $cashOut[$dateKey] ?? 0;
        
        $cashInValues[] = $inValue;
        $cashOutValues[] = $outValue;
        $netValue = $inValue - $outValue;
        $netProfitValues[] = $netValue;
    }
    
    $totals['net_profit'] = $totals['total_in'] - $totals['total_out'];
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'labels' => $dates,
        'cashIn' => $cashInValues,
        'cashOut' => $cashOutValues,
        'netProfit' => $netProfitValues,
        'totals' => $totals,
        'date_range' => [
            'start' => $start_date,
            'end' => $end_date
        ]
    ]);
    exit;
}

// ============================================================================
// 9) FETCH DATA FOR FRONTEND TABLES
// ============================================================================

function clearStoredResults($conn) {
    while ($conn->next_result()) {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
}

// Fetch ledgers list
$ledgers_stmt = $conn->prepare("
    SELECT ledger_id, ledger_name
    FROM ledgers
    ORDER BY ledger_name ASC
");
$ledgers_stmt->execute();
$ledgers = $ledgers_stmt->get_result();
$ledgers_stmt->close();
clearStoredResults($conn);

// Fetch vouchers with entries combined
if ($active_country_id == 0) {
    $vouchers_stmt = $conn->prepare("
        SELECT v.voucher_id, v.voucher_type, v.date, v.narration, 
               COALESCE(c.country_name, 'All Countries') AS country_name,
               GROUP_CONCAT(CONCAT(l.ledger_name, ' ', ve.type, ' ', ve.amount_lkr)
               SEPARATOR '<br>') AS entries
        FROM vouchers v
        JOIN voucher_entries ve ON v.voucher_id = ve.voucher_id
        JOIN ledgers l ON ve.ledger_id = l.ledger_id
        LEFT JOIN countries c ON v.country_id = c.id
        GROUP BY v.voucher_id
        ORDER BY v.voucher_id DESC
    ");
    $vouchers_stmt->execute();
} else {
    $vouchers_stmt = $conn->prepare("
        SELECT v.voucher_id, v.voucher_type, v.date, v.narration, 
               COALESCE(c.country_name, 'All Countries') AS country_name,
               GROUP_CONCAT(CONCAT(l.ledger_name, ' ', ve.type, ' ', ve.amount_lkr)
               SEPARATOR '<br>') AS entries
        FROM vouchers v
        JOIN voucher_entries ve ON v.voucher_id = ve.voucher_id
        JOIN ledgers l ON ve.ledger_id = l.ledger_id
        LEFT JOIN countries c ON v.country_id = c.id
        WHERE v.country_id = ? OR v.country_id = 0
        GROUP BY v.voucher_id
        ORDER BY v.voucher_id DESC
    ");
    $vouchers_stmt->bind_param("i", $active_country_id);
}
$vouchers_stmt->execute();
$vouchers = $vouchers_stmt->get_result();
$vouchers_stmt->close();
clearStoredResults($conn);

// Fetch ledger balances
if ($active_country_id == 0) {
    $balances_stmt = $conn->prepare("
        SELECT 
            l.ledger_id,
            l.ledger_name,
            l.opening_balance,
            l.balance_type,
            ag.group_name,
            ag.group_type,
            ag.sub_type,
            c.country_name,
            COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount_lkr ELSE 0 END), 0) AS total_dr,
            COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount_lkr ELSE 0 END), 0) AS total_cr,
            (
              (CASE WHEN UPPER(l.balance_type)='DR'
                    THEN l.opening_balance
                    ELSE -l.opening_balance END)
              + COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount_lkr ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount_lkr ELSE 0 END), 0)
            ) AS net_balance
        FROM ledgers l
        JOIN account_groups ag ON l.group_id = ag.group_id
        JOIN countries c ON l.country_id = c.id
        LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
        LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
        GROUP BY l.ledger_id
        ORDER BY c.country_name, ag.group_type, ag.sub_type, l.ledger_name
    ");
} else {
    $balances_stmt = $conn->prepare("
        SELECT 
            l.ledger_id,
            l.ledger_name,
            l.opening_balance,
            l.balance_type,
            ag.group_name,
            ag.group_type,
            ag.sub_type,
            c.country_name,
            COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount_lkr ELSE 0 END), 0) AS total_dr,
            COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount_lkr ELSE 0 END), 0) AS total_cr,
            (
              (CASE WHEN UPPER(l.balance_type)='DR'
                    THEN l.opening_balance
                    ELSE -l.opening_balance END)
              + COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount_lkr ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount_lkr ELSE 0 END), 0)
            ) AS net_balance
        FROM ledgers l
        JOIN account_groups ag ON l.group_id = ag.group_id
        JOIN countries c ON l.country_id = c.id
        LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
        LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
        WHERE l.country_id = ? AND (v.country_id = ? OR v.country_id = 0 OR v.country_id IS NULL)
        GROUP BY l.ledger_id
        ORDER BY ag.group_type, ag.sub_type, l.ledger_name
    ");
    $balances_stmt->bind_param("ii", $active_country_id, $active_country_id);
}
$balances_stmt->execute();
$balances = $balances_stmt->get_result();
$balances_stmt->close();
clearStoredResults($conn);

// ============================================================================
// END OF CONTROLLER
// ============================================================================
?>