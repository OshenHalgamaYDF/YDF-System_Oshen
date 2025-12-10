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
        SELECT voucher_id, voucher_type, date, narration
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
            <?php
            // Pick amount from Dr entry or fallback to first entry
            $amt = 0;
            foreach ($entries as $e) {
                if ($e['type'] === 'Dr') { $amt = $e['amount']; break; }
            }
            if ($amt == 0 && isset($entries[0])) $amt = $entries[0]['amount'];
            ?>
            <input type="number" name="amount" class="form-control"
                   step="0.01" required
                   value="<?= number_format((float)$amt, 2, '.', '') ?>">
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
// 3) ADD LEDGER
// ============================================================================
if (isset($_POST['add_ledger'])) {

    $ledger_name      = $_POST['ledger_name'] ?? '';
    $opening_balance  = floatval($_POST['opening_balance'] ?? 0);
    $balance_type     = $_POST['balance_type'] ?? 'Dr';
    $group_id         = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;

    if (empty($group_id)) {
        echo "<script>alert('⚠️ Please select an Account Group.');</script>";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO ledgers (ledger_name, opening_balance, balance_type, group_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sdsi", $ledger_name, $opening_balance, $balance_type, $group_id);
        $stmt->execute();
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

    // Basic validation
    if ($dr_ledger <= 0 || $cr_ledger <= 0 || $amount <= 0) {
        header("Location: ?error=invalid_input");
        exit;
    }

    // Insert voucher header
    $stmt = $conn->prepare("
        INSERT INTO vouchers (voucher_type, date, narration, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("sss", $voucher_type, $date, $narration);
    $stmt->execute();
    $voucher_id = $conn->insert_id;

    // Insert Debit entry
    $stmt2 = $conn->prepare("
        INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount)
        VALUES (?, ?, 'Dr', ?)
    ");
    $stmt2->bind_param("iid", $voucher_id, $dr_ledger, $amount);
    $stmt2->execute();

    // Insert Credit entry
    $stmt3 = $conn->prepare("
        INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount)
        VALUES (?, ?, 'Cr', ?)
    ");
    $stmt3->bind_param("iid", $voucher_id, $cr_ledger, $amount);
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
    $entry_map    = $_POST['entry_id_map'] ?? [];

    // Basic validation
    if ($id <= 0) {
        header("Location: ?error=bad_id");
        exit;
    }

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
            SET voucher_type = ?, date = ?, narration = ?
            WHERE voucher_id = ?
        ");
        $ustmt->bind_param("sssi", $voucher_type, $date, $narration, $id);
        $ustmt->execute();
        $ustmt->close();


        //------------------ Update Debit Entry ------------------
        if (isset($entry_map['Dr']) && intval($entry_map['Dr']) > 0) {

            $eid = intval($entry_map['Dr']);
            $estmt = $conn->prepare("
                UPDATE voucher_entries
                SET ledger_id = ?, amount = ?
                WHERE entry_id = ?
            ");
            $estmt->bind_param("idi", $dr_ledger, $amount, $eid);
            $estmt->execute();
            $estmt->close();

        } else {
            // Insert if missing
            $ist = $conn->prepare("
                INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount)
                VALUES (?, ?, 'Dr', ?)
            ");
            $ist->bind_param("iid", $id, $dr_ledger, $amount);
            $ist->execute();
            $ist->close();
        }


        //------------------ Update Credit Entry ------------------
        if (isset($entry_map['Cr']) && intval($entry_map['Cr']) > 0) {

            $eid = intval($entry_map['Cr']);
            $estmt = $conn->prepare("
                UPDATE voucher_entries
                SET ledger_id = ?, amount = ?
                WHERE entry_id = ?
            ");
            $estmt->bind_param("idi", $cr_ledger, $amount, $eid);
            $estmt->execute();
            $estmt->close();

        } else {
            // Insert if missing
            $ist = $conn->prepare("
                INSERT INTO voucher_entries (voucher_id, ledger_id, type, amount)
                VALUES (?, ?, 'Cr', ?)
            ");
            $ist->bind_param("iid", $id, $cr_ledger, $amount);
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
           GROUP_CONCAT(CONCAT(l.ledger_name, ' ', ve.type, ' ', ve.amount)
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

        COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0) AS total_dr,
        COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0) AS total_cr,

        (
          (CASE WHEN UPPER(l.balance_type)='DR'
                THEN l.opening_balance
                ELSE -l.opening_balance END)
          + COALESCE(SUM(CASE WHEN ve.type='Dr' THEN ve.amount ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN ve.type='Cr' THEN ve.amount ELSE 0 END), 0)
        ) AS net_balance

    FROM ledgers l
    LEFT JOIN voucher_entries ve ON l.ledger_id = ve.ledger_id
    GROUP BY l.ledger_id
");

?>
