<?php
require('C:\xampp\htdocs\ydf-system-oshen\app\views\fpdf\fpdf.php');

// ==== DB Connection ====
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";
$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ==== Get date from GET ====
$dateFilter = $_GET['date'] ?? null;
if (!$dateFilter) {
    die("No date selected!");
}

// ==== Fetch invoice header ====
$header_sql = "SELECT * FROM invoices_details WHERE date = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($header_sql);
$stmt->bind_param("s", $dateFilter);
$stmt->execute();
$header_result = $stmt->get_result();
$header = $header_result->fetch_assoc() ?: [];

// ==== Fetch invoice items ====
$sql = "SELECT 
            nd.fish_type,
            SUM(nd.net_weight) AS total_weight,
            (
                SELECT p.scientific_name 
                FROM products p 
                WHERE LOWER(TRIM(REPLACE(p.product_name, 'ies', 'y'))) = LOWER(TRIM(REPLACE(nd.fish_type, 'ies', 'y')))
                   OR LOWER(TRIM(REPLACE(p.product_name, 's', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 's', '')))
                   OR LOWER(TRIM(REPLACE(p.product_name, 'Kingfish', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 'King Fish', '')))
                   OR nd.fish_type LIKE CONCAT('%', p.product_name, '%')
                   OR p.product_name LIKE CONCAT('%', nd.fish_type, '%')
                LIMIT 1
            ) AS scientific_name,
            GROUP_CONCAT(DISTINCT nd.product_type SEPARATOR ', ') AS product_type,
            nd.unit_price
        FROM invoices_distribution_sheet AS nd
        WHERE nd.production_date = ?
        GROUP BY nd.fish_type, nd.unit_price
        ORDER BY nd.fish_type ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dateFilter);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$totalBoxes = 0;
$totalValue = 0;
while ($row = $result->fetch_assoc()) {
    $value = (float)$row['total_weight'] * (float)$row['unit_price'];
    $totalValue += $value;
    $items[] = [
        'description' => $row['fish_type'] . " (" . $row['product_type'] . ")",
        'scientific_name' => $row['scientific_name'],
        'weight' => number_format($row['total_weight'], 2),
        'unit_price' => number_format($row['unit_price'], 2),
        'value' => number_format($value, 2)
    ];
    $totalBoxes++;
}

// ==== PDF Class ====
class PDF extends FPDF
{
    function Header()
    {
        // Company Header
        if ($this->PageNo() == 1) {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 6, 'YOUR DAILY FOODS (PVT) LTD', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, "No. 22, St. Anthony's Mawatha, Kanuwana, Ekala, Ja-Ela, Sri Lanka", 0, 1, 'C');
            $this->Ln(4);
        }
    }

    // Automatic page break check
    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            $this->TableHeader(); // Repeat header when new page
        }
    }

    // Draw table header
    function TableHeader()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(10, 8, '#', 1, 0, 'C', true);
        $this->Cell(55, 8, 'Description', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Scientific Name', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Weight (Kg)', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Unit Price', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Value (USD)', 1, 1, 'C', true);
    }

    // Draw a single row (auto page break support)
    function Row($data)
    {
        $this->CheckPageBreak(8);
        $this->SetFont('Arial', '', 9);
        $this->Cell(10, 8, $data[0], 1, 0, 'C');
        $this->Cell(55, 8, $data[1], 1, 0);
        $this->Cell(50, 8, $data[2], 1, 0);
        $this->Cell(25, 8, $data[3], 1, 0, 'R');
        $this->Cell(25, 8, $data[4], 1, 0, 'R');
        $this->Cell(25, 8, $data[5], 1, 1, 'R');
    }
}

// ==== Create PDF ====
$pdf = new PDF();
$pdf->AddPage();

// Invoice title
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
$pdf->Ln(3);

// --- Define starting Y position for both columns ---
$yStart = $pdf->GetY();

// === LEFT COLUMN: Consignee Details ===
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(10, $yStart); // Left margin 10mm
$pdf->SetFont('Arial', 'U', 10); // U = underline
$pdf->Cell(0, 6, 'CONSIGNEE', 0, 1);
$pdf->SetFont('Arial', '', 10);  // reset to normal after

$pdf->SetX(10);
$address = $header['consignee_address'] ?? 'N/A';
$address = preg_replace('/,\s*/', ",\n", trim($address)); // break after commas
$pdf->SetFont('Arial', 'B', 10);
$pdf->MultiCell(80, 6, $address); // width 80mm for left column

$pdf->SetX(10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(6, 'Tel: ');

$pdf->SetFont('Arial', '', 10);
$pdf->Write(6, $header['consignee_tele'] ?? 'N/A');
$pdf->Ln(6); // move to next line


// === RIGHT COLUMN: Invoice Info ===
$pdf->SetXY(110, $yStart); // Move to right column start (x=110mm)
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(6, 'DATE: ');
$pdf->SetFont('Arial', '', 10);
$pdf->Write(6, $header['date'] ?? $dateFilter);
$pdf->Ln(6);

$pdf->SetX(110);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(6, 'INV NO: ');
$pdf->SetFont('Arial', '', 10);
$pdf->Write(6, $header['inv_no'] ?? 'N/A');
$pdf->Ln(6);

$pdf->SetX(110);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(6, 'AWB NO: ');
$pdf->SetFont('Arial', '', 10);
$pdf->Write(6, $header['awb_no'] ?? 'N/A');
$pdf->Ln(6);

$pdf->SetX(110);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(6, 'FLIGHT DETAILS: ');
$pdf->SetFont('Arial', '', 10);
$pdf->Write(6, $header['flight_details'] ?? 'N/A');
$pdf->Ln(6);

$pdf->SetX(110);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(6, 'DESTINATION: ');
$pdf->SetFont('Arial', '', 10);
$pdf->Write(6, $header['destination'] ?? 'N/A');
$pdf->Ln(6);

$pdf->SetX(110);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(6, 'FDA REG NO: ');
$pdf->SetFont('Arial', '', 10);
$pdf->Write(6, $header['fda_reg_no'] ?? 'N/A');
$pdf->Ln(6);

$pdf->SetX(110);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(6, 'TOTAL BOXES: ');
$pdf->SetFont('Arial', '', 10);
$pdf->Write(6, $totalBoxes);
$pdf->Ln(6);


// --- Adjust line spacing before next section ---
$pdf->Ln(10);


// ==== Table ====
$pdf->TableHeader();
foreach ($items as $i => $item) {
    $pdf->Row([
        $i + 1,
        $item['description'],
        $item['scientific_name'],
        $item['weight'],
        $item['unit_price'],
        $item['value']
    ]);
}

// ==== Total ====
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(165, 8, 'TOTAL (USD)', 1, 0, 'R');
$pdf->Cell(25, 8, number_format($totalValue, 2), 1, 1, 'R');

// ==== Fetch Shipping Discount ====
$discount_sql = "SELECT value, reason FROM shipping_discount_details WHERE date = ? LIMIT 1";
$stmt = $conn->prepare($discount_sql);
$stmt->bind_param("s", $dateFilter);
$stmt->execute();
$discount_result = $stmt->get_result();
$discount_data = $discount_result->fetch_assoc();

$discount_value = $discount_data['value'] ?? 0;
$discount_reason = $discount_data['reason'] ?? 'Shipping Discount';

// ==== Discount Row ====
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(165, 8, strtoupper($discount_reason) . ' (USD)', 1, 0, 'R');
$pdf->Cell(25, 8, '-' . number_format($discount_value, 2), 1, 1, 'R');

// ==== Final Amount ====
$finalTotal = $totalValue - $discount_value;

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(165, 8, 'FINAL INVOICE VALUE (USD)', 1, 0, 'R', true);
$pdf->Cell(25, 8, number_format($finalTotal, 2), 1, 1, 'R');

$pdf->Ln(5);


// ==== Declaration ====
$pdf->SetFont('Arial', '', 9);
$pdf->MultiCell(0, 5,
"The exporter LKREX102891600DC0841 of the products covered by this document declares that, " .
"except where otherwise clearly indicated, these products are of Sri Lankan origin under GSP rules. " .
"Origin criterion met: 'P'.");

// ==== Bank details section ====

// Check if there's enough space (approx. 60mm needed for bank details box)
$pageHeight = $pdf->GetPageHeight();
$bottomMargin = 20;
if ($pdf->GetY() + 60 > $pageHeight - $bottomMargin) {
    $pdf->AddPage();
}

$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'BANK DETAILS', 0, 1, 'C');
$pdf->Ln(5);

// Draw box
$x = 15;
$y = $pdf->GetY();
$w = 180;
$h = 50;
$pdf->SetLineWidth(0.5);
$pdf->Rect($x, $y, $w, $h);

// Move inside box with proper padding
$pdf->SetXY($x + 10, $y + 8);

// Bank details with better formatting
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, 'Account No:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, '087910206029', 0, 1, 'L');

$pdf->SetX($x + 10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, 'Swift Code:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'HBLILKLX', 0, 1, 'L');

$pdf->SetX($x + 10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, 'Bank Code:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, '7083', 0, 1, 'L');

$pdf->SetX($x + 10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, 'Bank:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Hatton National Bank PLC - Ja-Ela Branch', 0, 1, 'L');

$pdf->Ln(3); // Small space before company info

$pdf->SetX($x + 10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'YOUR DAILY FOODS (PVT) LTD.', 0, 1, 'L');

$pdf->SetX($x + 10);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, 'Company Reg. No. PV 00246176 | VAT Reg. No. 103246229', 0, 1, 'L');

// Output file
$filename = 'Invoice ' . ($header['inv_no'] ?? 'YDF') . '.pdf';
$pdf->Output('I', $filename);
?>