<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get employee id or primary id from query string
$emp_key = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($emp_key)) {
    http_response_code(400);
    echo "<h2>Invalid request: missing employee id.</h2>";
    exit;
}

// First fetch the employee record by either the public `emp_id` or the numeric primary `id`.
$sql = "SELECT e.id, e.emp_id, e.emp_name, e.emp_status, e.emp_NIC AS emp_nic, e.emp_residential_address AS emp_address,
        e.emp_civil_status, e.emp_gender, e.emp_dob, e.emp_contact_details AS emp_contact_number, e.emp_epf_no AS emp_epf_number,
        e.emp_department, e.emp_designation, e.emp_joined_date AS emp_start_date
        FROM tbl_hr_employees_data e
        WHERE e.emp_id = ? OR e.id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("DB prepare error: " . $conn->error);
}
// bind twice; MySQL will coerce types appropriately
$stmt->bind_param('ss', $emp_key, $emp_key);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

if (!$employee) {
    http_response_code(404);
    echo "<h2>Employee not found for id: " . htmlspecialchars($emp_key) . "</h2>";
    exit;
}

// Use the primary id for related lookups (emergency/bank/documents)
$primary_id = $employee['id'];

// Fetch emergency data (if any)
$stmt = $conn->prepare("SELECT emergency_name, emergency_relationship, emergency_address, emergency_contact FROM tbl_hr_employees_emergency_data WHERE emp_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $primary_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $employee['emergency_name'] = $row['emergency_name'];
        $employee['emergency_relationship'] = $row['emergency_relationship'];
        $employee['emergency_address'] = $row['emergency_address'];
        $employee['emergency_contact'] = $row['emergency_contact'];
    }
    $stmt->close();
}

// Fetch bank data (if any)
$stmt = $conn->prepare("SELECT emp_acc_name, emp_acc_no, emp_bank_name, emp_branch, emp_details_change FROM tbl_hr_employees_bank_data WHERE emp_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $primary_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $employee['emp_acc_name'] = $row['emp_acc_name'];
        $employee['emp_acc_no'] = $row['emp_acc_no'];
        $employee['emp_bank_name'] = $row['emp_bank_name'];
        $employee['emp_branch'] = $row['emp_branch'];
        $employee['emp_details_change'] = $row['emp_details_change'];
    }
    $stmt->close();
}

// Fetch documents for this employee using the primary id
$documents = [];
$sql_docs = "SELECT d.id, d.document_type_id, dt.document_types, d.original_name, d.stored_name, d.file_path, d.timestamp
             FROM tbl_hr_employees_data_documents d
             LEFT JOIN tbl_hr_employees_data_document_type dt ON d.document_type_id = dt.id
             WHERE d.employee_data_id = ?
             ORDER BY d.timestamp DESC";

$stmt = $conn->prepare($sql_docs);
if ($stmt) {
    $stmt->bind_param('s', $primary_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $documents[] = $row;
    }
    $stmt->close();
}

// Resolve a stored file path into a web-accessible URL (tries multiple locations)
function resolveFileUrl($filePath, $storedName = null) {
    // If full URL already, return as-is
    if (empty($filePath) && !empty($storedName)) {
        $filePath = 'uploads/documents/' . ltrim($storedName, '/');
    }
    if (empty($filePath)) return null;
    if (preg_match('#^https?://#i', $filePath)) return $filePath;

    // Normalize
    $filePath = ltrim($filePath, '/');
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

    // Candidate filesystem locations to check (most likely first)
    $candidates = [
        $docRoot . '/' . $filePath, // example: /var/www/project/uploads/documents/file.pdf
        __DIR__ . '/' . $filePath,  // relative to this view file
    ];
    if (!empty($storedName)) {
        $candidates[] = __DIR__ . '/uploads/documents/' . $storedName; // old upload path used in view
        $candidates[] = dirname(__DIR__, 4) . '/uploads/documents/' . $storedName; // project-root/uploads/documents/
    }

    foreach ($candidates as $cand) {
        if (empty($cand)) continue;
        $real = @realpath($cand);
        if ($real && file_exists($real)) {
            // If file is under document root, build a URL path
            if (stripos($real, $docRoot) === 0) {
                $urlPath = '/' . ltrim(str_replace('\\', '/', substr($real, strlen($docRoot))), '/');
                // encode filename only
                $parts = explode('/', $urlPath);
                $parts[count($parts)-1] = rawurlencode($parts[count($parts)-1]);
                return implode('/', $parts);
            }
            // If file is under project root, build path relative to project root
            $projectRoot = dirname(__DIR__, 4);
            if (stripos($real, $projectRoot) === 0) {
                $urlPath = '/' . ltrim(str_replace('\\', '/', substr($real, strlen($projectRoot))), '/');
                $parts = explode('/', $urlPath);
                $parts[count($parts)-1] = rawurlencode($parts[count($parts)-1]);
                return implode('/', $parts);
            }
            // Fallback: return encoded basename under the original directory
            $dir = dirname($filePath);
            $file = rawurlencode(basename($filePath));
            return ($dir && $dir !== '.') ? '/' . trim($dir,'/') . '/' . $file : '/' . $file;
        }
    }

    // Final fallback: return the original relative path with encoded filename
    $dir = dirname($filePath);
    $file = rawurlencode(basename($filePath));
    return ($dir && $dir !== '.') ? '/' . trim($dir,'/') . '/' . $file : '/' . $file;
}

// Render a preview (pdf/image) or link for the given stored file info
function renderFilePreview($filePath, $originalName, $storedName = null) {
    $url = resolveFileUrl($filePath, $storedName);
    $pathForExt = parse_url($url, PHP_URL_PATH) ?: $filePath;
    $ext = strtolower(pathinfo($pathForExt, PATHINFO_EXTENSION));
    $safeUrl = htmlspecialchars($url);
    $safeName = htmlspecialchars($originalName ?: $storedName ?: basename($filePath));

    if (in_array($ext, ['pdf'])) {
        return "<div style=\"margin-bottom:1rem;\"><div><strong>{$safeName}</strong></div><iframe src=\"{$safeUrl}\" width=\"100%\" height=\"500px\"></iframe></div>";
    }
    if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
        return "<div style=\"margin-bottom:1rem;\"><div><strong>{$safeName}</strong></div><img src=\"{$safeUrl}\" style=\"max-width:100%;height:auto;\" alt=\"{$safeName}\"></div>";
    }
    return "<div style=\"margin-bottom:0.5rem;\">🔗 <a href=\"{$safeUrl}\" target=\"_blank\">{$safeName}</a> <small class=\"text-muted\">(opens in new tab)</small></div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee: <?php echo htmlspecialchars($employee['emp_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="employeedata.php" class="btn btn-secondary mb-3">← Back to Employees</a>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="mb-0"><?php echo htmlspecialchars($employee['emp_name']); ?> <small class="text-muted">(ID: <?php echo htmlspecialchars($employee['emp_id']); ?>)</small></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>NIC:</strong> <?php echo htmlspecialchars($employee['emp_nic']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($employee['emp_address']); ?></p>
                    <p><strong>Civil Status:</strong> <?php echo htmlspecialchars($employee['emp_civil_status']); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($employee['emp_gender']); ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($employee['emp_dob']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($employee['emp_contact_number']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>EPF Number:</strong> <?php echo htmlspecialchars($employee['emp_epf_number']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['emp_department']); ?></p>
                    <p><strong>Designation:</strong> <?php echo htmlspecialchars($employee['emp_designation']); ?></p>
                    <p><strong>Start Date:</strong> <?php echo htmlspecialchars($employee['emp_start_date']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($employee['emp_status']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Emergency Contact</div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['emergency_name']); ?></p>
                    <p><strong>Relationship:</strong> <?php echo htmlspecialchars($employee['emergency_relationship']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($employee['emergency_contact']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($employee['emergency_address']); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Bank Details</div>
                <div class="card-body">
                    <p><strong>Account Name:</strong> <?php echo htmlspecialchars($employee['emp_acc_name']); ?></p>
                    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($employee['emp_acc_no']); ?></p>
                    <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($employee['emp_bank_name']); ?></p>
                    <p><strong>Branch:</strong> <?php echo htmlspecialchars($employee['emp_branch']); ?></p>
                    <p><strong>Account Change Date:</strong> <?php echo htmlspecialchars($employee['emp_details_change']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Documents</div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <p class="text-muted">No documents uploaded for this employee.</p>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <?php echo renderFilePreview($doc['file_path'], $doc['original_name'], $doc['stored_name']); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>