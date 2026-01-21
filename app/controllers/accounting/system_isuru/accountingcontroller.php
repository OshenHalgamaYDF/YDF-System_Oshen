<?php
// ============================================================================
// accounting.php (single-file) – FULLY COMMENTED VERSION
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
// 2) AJAX ENDPOINT — LOAD VOUCHER FORM FOR EDITING
//    (Frontend sends AJAX ?action=get_voucher&id=##)
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_voucher' && isset($_GET['id'])) {

    $id = intval($_GET['id']); // Prevent SQL injection, convert to number

    // ------------------ Fetch Voucher Header ------------------
    $vstmt = $conn->prepare("
        SELECT voucher_id, voucher_type, date, narration, amount, currency_id, exchange_rate
        FROM vouchers
        WHERE voucher_id = ?
    ");
    $vstmt->bind_param("i", $id);
    $vstmt->execute();
    $voucher = $vstmt->get_result()->fetch_assoc();
    $vstmt->close();

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

    // ------------------ Fetch Ledgers For Dropdowns ------------
    // Done separately to avoid pointer issues during looping
    $ledgers_dr = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
    $ledgers_cr = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");

    // ------------------ Return HTML (AJAX fragment) -------------
    // No closing PHP tag until bottom so HTML can mix easily
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
            <label>Amount</label>
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

                while ($l = $ledgers_dr->fetch_assoc()) {
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

                while ($l = $ledgers_cr->fetch_assoc()) {
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
// AJAX: GET LEDGERS
// Clients can call ?action=get_ledgers to receive JSON list of ledgers
// ============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_ledgers') {
    $ledgers = [];
    $res = $conn->query("SELECT ledger_id, ledger_name FROM ledgers ORDER BY ledger_name ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $ledgers[] = $r;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['ledgers' => $ledgers]);
    exit;
}

// ============================================================================
// AJAX: GET EXCHANGE RATE
// Clients can call ?action=get_rate&code=USD&date=2026-01-16 to get rate
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
            INSERT INTO ledgers (ledger_name, opening_balance, balance_type, group_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sdsi", $ledger_name, $opening_balance, $balance_type, $group_id);
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
// 4) ADD VOUCHER (Insert Dr & Cr Entries)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_voucher'])) {

    $voucher_type = $_POST['voucher_type'] ?? '';
    $date         = $_POST['date'] ?? date('Y-m-d');
    $narration    = $_POST['narration'] ?? '';
    $dr_ledger    = intval($_POST['dr_ledger'] ?? 0);
    $cr_ledger    = intval($_POST['cr_ledger'] ?? 0);
    $amount       = floatval($_POST['amount'] ?? 0);
    $currency_id  = intval($_POST['currency_id'] ?? 1); // Default to LKR
    $exchange_rate = floatval($_POST['exchange_rate'] ?? 1.0);

    // Basic validation
    if ($dr_ledger <= 0 || $cr_ledger <= 0 || $amount <= 0) {
        header("Location: ?error=invalid_input");
        exit;
    }

    // Compute LKR equivalent
    $amount_lkr = round($amount * $exchange_rate, 2);

    // Insert voucher header
    $stmt = $conn->prepare("
        INSERT INTO vouchers (voucher_type, date, narration, currency_id, exchange_rate, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssid", $voucher_type, $date, $narration, $currency_id, $exchange_rate);
    $stmt->execute();
    /**
     * Retrieves the ID of the last inserted row from the database connection
     * and assigns it to the $voucher_id variable.
     * 
     * This is typically used after an INSERT operation to get the auto-generated
     * primary key of the newly created voucher record.
     * 
     * @var int $voucher_id The auto-incremented ID of the inserted voucher
     */
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
// 5) UPDATE EXISTING VOUCHER (WITH TRANSACTION)
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
            WHERE voucher_id = ?
        ");
        $ustmt->bind_param("sssidi", $voucher_type, $date, $narration, $currency_id, $exchange_rate, $id);
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
            $stmt2 = $conn->prepare("DELETE FROM vouchers WHERE voucher_id = ?");
            $stmt2->bind_param("i", $voucher_id);
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
// 8) FETCH DATA FOR FRONTEND TABLES
// ============================================================================

// Fetch ledgers list
$ledgers = $conn->query("
    SELECT ledger_id, ledger_name
    FROM ledgers
    ORDER BY ledger_name ASC
");

// Fetch vouchers with entries combined
$vouchers = $conn->query("
    SELECT v.voucher_id, v.voucher_type, v.date, v.narration,
           GROUP_CONCAT(CONCAT(l.ledger_name, ' ', ve.type, ' ', ve.amount_lkr)
           SEPARATOR '<br>') AS entries
    FROM vouchers v
    JOIN voucher_entries ve ON v.voucher_id = ve.voucher_id
    JOIN ledgers l ON ve.ledger_id = l.ledger_id
    GROUP BY v.voucher_id
    ORDER BY v.voucher_id DESC
");

// Fetch ledger balances using Opening + Dr - Cr
$balances = $conn->query("
    SELECT 
        l.ledger_id,
        l.ledger_name,
        l.opening_balance,
        l.balance_type,

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
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    GROUP BY l.ledger_id
");

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
