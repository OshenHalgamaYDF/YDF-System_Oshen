<?php
// ============================================================================
// accounting.php (single-file) – FULLY COMMENTED VERSION WITH MULTI-COUNTRY SUPPORT
// ============================================================================
// This file handles:
//  • Adding ledgers
//  • Adding vouchers (Dr/Cr)
//  • Editing vouchers (AJAX-loaded form)
//  • Updating vouchers with transactions
//  • Deleting vouchers
//  • Bank reconciliation (mark entries as cleared)
//  • Fetching data for display
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
// COUNTRY SELECTION HANDLING
// ============================================================================
if (isset($_POST['select_country'])) {
    $_SESSION['country_id'] = intval($_POST['country_id']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Default to first country if not set
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

// ============================================================================
// 2) AJAX ENDPOINT — LOAD VOUCHER FORM FOR EDITING
//    (Frontend sends AJAX ?action=get_voucher&id=##)
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_voucher' && isset($_GET['id'])) {

    $id = intval($_GET['id']); // Prevent SQL injection, convert to number

    // ------------------ Fetch Voucher Header ------------------
    $vstmt = $conn->prepare("
        SELECT voucher_id, voucher_type, date, narration, amount, currency_id, exchange_rate
        FROM vouchers
        WHERE voucher_id = ? AND country_id = ?
    ");
    $vstmt->bind_param("ii", $id, $active_country_id);
    $vstmt->execute();
    $voucher = $vstmt->get_result()->fetch_assoc();
    $vstmt->close();

    if (!$voucher) {
        echo "<div class='alert alert-danger'>Voucher not found or access denied.</div>";
        exit;
    }

    // ------------------ Fetch Dr/Cr Entries -------------------
    // Expect exactly 2 entries (1 Dr, 1 Cr)
    $estmt = $conn->prepare("
        SELECT entry_id, ledger_id, type, amount
        FROM voucher_entries
        WHERE voucher_id = ?
        ORDER BY FIELD(type, 'Dr', 'Cr')
    ");
    $estmt->bind_param("i", $id);
    $estmt->execute();
    $entries = $estmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $estmt->close();

    // ------------------ Fetch Ledgers For Dropdowns (Global) ------------
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

    // ------------------ Return HTML (AJAX fragment) -------------
    ?>
    
    <!-- Voucher Type, Date, Amount -->
    <div class="row mb-2">

        <!-- Voucher Type -->
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

        <!-- Date -->
        <div class="col-md-4">
            <label>Date</label>
            <input type="date" name="date" class="form-control"
                   value="<?= htmlspecialchars($voucher['date']) ?>" required>
        </div>

        <!-- Amount -->
        <div class="col-md-4">
            <label>Amount (<?= htmlspecialchars($active_country['currency_code']) ?>)</label>
            <input type="text" name="amount" id="editAmountInput" class="form-control"
                   required placeholder="e.g., $100 or 100"
                   value="<?= number_format((float)($voucher['amount'] ?? 0), 2, '.', '') ?>">
        </div>
    </div>

    <!-- Currency and Exchange Rate -->
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
            <label>Exchange Rate (to LKR)</label>
            <input type="number" name="exchange_rate" id="editExchangeRateInput" class="form-control" step="0.000001" value="<?= number_format((float)($voucher['exchange_rate'] ?? 1.0), 6, '.', '') ?>" readonly>
        </div>
    </div>

    <!-- Debit Ledger -->
    <div class="row mb-2">
        <div class="col-md-6">
            <label>Debit Ledger (Dr)</label>
            <select name="dr_ledger" class="form-select" required>
                <option value="">-- Select --</option>
                <?php
                // Pick selected Dr entry ledger
                $dr_id = null;
                foreach ($entries as $e) if ($e['type'] === 'Dr') $dr_id = $e['ledger_id'];

                foreach ($ledgers_dr as $l) {
                    $sel = ($l['ledger_id'] == $dr_id) ? 'selected' : '';
                    echo "<option value='{$l['ledger_id']}' $sel>{$l['ledger_name']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- Credit Ledger -->
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

    <!-- Narration -->
    <div class="mb-3">
        <label>Narration</label>
        <textarea name="narration" class="form-control" rows="2">
            <?= htmlspecialchars($voucher['narration']) ?>
        </textarea>
    </div>

    <!-- Hidden Voucher ID -->
    <input type="hidden" name="voucher_id" value="<?= $voucher['voucher_id'] ?>">

    <!-- Hidden Entry IDs (Dr, Cr) -->
    <?php
    foreach ($entries as $e) {
        echo "<input type='hidden' name='entry_id_map[{$e['type']}]' value='{$e['entry_id']}'>";
    }
    exit; // Must exit so the rest of page does not load
}


// ============================================================================
// AJAX: GET LEDGERS (Global - not filtered by Country)
// Clients can call ?action=get_ledgers to receive JSON list of ledgers
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
// AJAX: GET EXCHANGE RATE
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_rate' && isset($_GET['code'])) {
    $code = $_GET['code'];
    $date = $_GET['date'] ?? date('Y-m-d');
    $rate = null;
    $stmt = $conn->prepare("SELECT er.rate_to_lkr FROM exchange_rates er JOIN currencies c ON er.currency_id = c.currency_id WHERE c.code = ? AND er.rate_date <= ? ORDER BY er.rate_date DESC LIMIT 1");
    $stmt->bind_param("ss", $code, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($r = $result->fetch_assoc()) {
        $rate = $r['rate_to_lkr'];
    }
    header('Content-Type: application/json');
    echo json_encode(['rate' => $rate]);
    exit;
}


// ============================================================================
// 3) ADD LEDGER (Filtered by Country)
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
        // Keep country_id for data separation when viewing
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
// 4) ADD VOUCHER (Insert Dr & Cr Entries, Validate Country)
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

    // Basic validation
    if ($dr_ledger <= 0 || $cr_ledger <= 0 || $amount <= 0) {
        header("Location: ?error=invalid_input");
        exit;
    }

    // Validate ledgers exist (no country check needed - ledgers are global)
    $ledger_check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM ledgers WHERE ledger_id IN (?, ?)");
    $ledger_check_stmt->bind_param("ii", $dr_ledger, $cr_ledger);
    $ledger_check_stmt->execute();
    $ledger_check = $ledger_check_stmt->get_result()->fetch_assoc();
    $ledger_check_stmt->close();
    if ($ledger_check['count'] != 2) {
        header("Location: ?error=invalid_ledgers");
        exit;
    }

    // Compute LKR equivalent
    $amount_lkr = round($amount * $exchange_rate, 2);

    // Insert voucher header with country_id
    $stmt = $conn->prepare("
        INSERT INTO vouchers (voucher_type, date, narration, currency_id, exchange_rate, country_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssidi", $voucher_type, $date, $narration, $currency_id, $exchange_rate, $active_country_id);
    $stmt->execute();
    $voucher_id = $conn->insert_id;

    // Insert Debit entry
    $stmt2 = $conn->prepare("
        INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount, amount_lkr)
        VALUES (?, ?, 'Dr', ?, ?)
    ");
    $stmt2->bind_param("iidd", $voucher_id, $dr_ledger, $amount, $amount_lkr);
    $stmt2->execute();

    // Insert Credit entry
    $stmt3 = $conn->prepare("
        INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount, amount_lkr)
        VALUES (?, ?, 'Cr', ?, ?)
    ");
    $stmt3->bind_param("iidd", $voucher_id, $cr_ledger, $amount, $amount_lkr);
    $stmt3->execute();

    header("Location: ?success=1");
    exit;
}


// ============================================================================
// 5) UPDATE EXISTING VOUCHER (WITH TRANSACTION, Validate Country)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_voucher'])) {

    // Collect posted data
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

    // Basic validation
    if ($id <= 0) {
        header("Location: ?error=bad_id");
        exit;
    }

    // Validate ledgers exist (no country check needed - ledgers are global)
    $ledger_check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM ledgers WHERE ledger_id IN (?, ?)");
    $ledger_check_stmt->bind_param("ii", $dr_ledger, $cr_ledger);
    $ledger_check_stmt->execute();
    $ledger_check = $ledger_check_stmt->get_result()->fetch_assoc();
    $ledger_check_stmt->close();
    if ($ledger_check['count'] != 2) {
        header("Location: ?error=invalid_ledgers");
        exit;
    }

    // Compute LKR equivalent
    $amount_lkr = round($amount * $exchange_rate, 2);

    if ($dr_ledger === $cr_ledger) {
        header("Location: ?error=same_ledgers");
        exit;
    }

    // Begin transaction (so updates happen together)
    $conn->begin_transaction();

    try {
        //------------------ Update Voucher Header ------------------
        $ustmt = $conn->prepare("
            UPDATE vouchers
            SET voucher_type = ?, date = ?, narration = ?, currency_id = ?, exchange_rate = ?
            WHERE voucher_id = ? AND country_id = ?
        ");
        $ustmt->bind_param("sssidii", $voucher_type, $date, $narration, $currency_id, $exchange_rate, $id, $active_country_id);
        $ustmt->execute();
        $ustmt->close();


        //------------------ Update Debit Entry ------------------
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
            // Insert if missing
            $ist = $conn->prepare("
                INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount, amount_lkr)
                VALUES (?, ?, 'Dr', ?, ?)
            ");
            $ist->bind_param("iidd", $id, $dr_ledger, $amount, $amount_lkr);
            $ist->execute();
            $ist->close();
        }


        //------------------ Update Credit Entry ------------------
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
            // Insert if missing
            $ist = $conn->prepare("
                INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount, amount_lkr)
                VALUES (?, ?, 'Cr', ?, ?)
            ");
            $ist->bind_param("iidd", $id, $cr_ledger, $amount, $amount_lkr);
            $ist->execute();
            $ist->close();
        }

        // Commit transaction
        $conn->commit();

        header("Location: ?updated=1");
        exit;

    } catch (Exception $e) {
        // Rollback if anything fails
        $conn->rollback();

        header("Location: ?error=update_failed&message=" . urlencode($e->getMessage()));
        exit;
    }
}


// ============================================================================
// 6) BANK RECONCILIATION — AJAX UPDATE
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brs'])) {

    $entry_id   = intval($_POST['entry_id'] ?? 0);
    $is_cleared = intval($_POST['is_cleared'] ?? 0);

    if ($entry_id > 0) {

        // Check existing reconciliation
        $check = $conn->query("
            SELECT reconciliation_id
            FROM bank_reconciliation
            WHERE voucher_entry_id = $entry_id
        ");

        $cleared_date = $is_cleared ? date('Y-m-d') : NULL;

        if ($check->num_rows > 0) {
            // Update existing
            $query = "
                UPDATE bank_reconciliation
                SET is_cleared = $is_cleared,
                    cleared_date = " . ($cleared_date ? "'$cleared_date'" : "NULL") . "
                WHERE voucher_entry_id = $entry_id
            ";
            $conn->query($query);

        } else {
            // Insert new
            $query = "
                INSERT INTO bank_reconciliation (voucher_entry_id, is_cleared, cleared_date)
                VALUES ($entry_id, $is_cleared, " . ($cleared_date ? "'$cleared_date'" : "NULL") . ")
            ";
            $conn->query($query);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid entry ID']);
    }
    exit;
}


// ============================================================================
// 7) DELETE VOUCHER (AND ITS ENTRIES)
// ============================================================================
if (isset($_POST['delete_voucher'])) {

    $voucher_id = intval($_POST['voucher_id']);

    if ($voucher_id > 0) {

        $conn->begin_transaction();

        try {
            // Delete child entries
            $stmt = $conn->prepare("DELETE FROM voucher_entries WHERE voucher_id = ?");
            $stmt->bind_param("i", $voucher_id);
            $stmt->execute();
            $stmt->close();

            // Delete parent voucher
            $stmt2 = $conn->prepare("DELETE FROM vouchers WHERE voucher_id = ? AND country_id = ?");
            $stmt2->bind_param("ii", $voucher_id, $active_country_id);
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
// 8) FETCH DATA FOR FRONTEND TABLES (Filtered by Country for Vouchers, Global for Ledgers)
// ============================================================================

// Fetch ledgers list (global - not filtered by country)
$ledgers_stmt = $conn->prepare("
    SELECT ledger_id, ledger_name
    FROM ledgers
    ORDER BY ledger_name ASC
");
$ledgers_stmt->execute();
$ledgers = $ledgers_stmt->get_result();
$ledgers_stmt->close();

// Fetch vouchers with entries combined (filtered)
$vouchers_stmt = $conn->prepare("
    SELECT v.voucher_id, v.voucher_type, v.date, v.narration,
           GROUP_CONCAT(CONCAT(l.ledger_name, ' ', ve.type, ' ', ve.amount_lkr)
           SEPARATOR '<br>') AS entries
    FROM vouchers v
    JOIN voucher_entries ve ON v.voucher_id = ve.voucher_id
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    WHERE v.country_id = ?
    GROUP BY v.voucher_id
    ORDER BY v.voucher_id DESC
");
$vouchers_stmt->bind_param("i", $active_country_id);
$vouchers_stmt->execute();
$vouchers = $vouchers_stmt->get_result();
$vouchers_stmt->close();

// Fetch ledger balances using Opening + Dr - Cr (filtered) with group info
$balances_stmt = $conn->prepare("
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.opening_balance,
        l.balance_type,
        ag.group_name,
        ag.group_type,
        ag.sub_type,

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
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    LEFT JOIN vouchers v ON ve.voucher_id = v.voucher_id
    WHERE l.country_id = ? AND (v.country_id = ? OR v.country_id IS NULL)
    GROUP BY l.ledger_id
    ORDER BY ag.group_type, ag.sub_type, l.ledger_name
");
$balances_stmt->bind_param("ii", $active_country_id, $active_country_id);
$balances_stmt->execute();
$balances = $balances_stmt->get_result();
$balances_stmt->close();

// ============================================================================
// 9) GET EXCHANGE RATE (AJAX)
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_rate' && isset($_GET['code'])) {
    $code = $_GET['code'];
    $date = $_GET['date'] ?? date('Y-m-d');
    // Find currency_id by code and pick most recent rate <= $date
    // Return json: { rate: 35.48 }
    header('Content-Type: application/json');
    $stmt = $conn->prepare("SELECT rate_to_lkr FROM exchange_rates er JOIN currencies c ON er.currency_id=c.currency_id WHERE c.code=? AND er.rate_date<=? ORDER BY er.rate_date DESC LIMIT 1");
    $stmt->bind_param("ss", $code, $date);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    echo json_encode(['rate' => $r['rate_to_lkr'] ?? null]);
    exit;
}
