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
$header_sql = "SELECT * FROM invoices_details_UK WHERE date = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($header_sql);
$stmt->bind_param("s", $dateFilter);
$stmt->execute();
$header_result = $stmt->get_result();
$header = $header_result->fetch_assoc() ?: [];

// ==== Fetch invoice items ====
$sql = "SELECT 
        nd.fish_type,
        nd.product_type,
        SUM(nd.net_weight) AS total_weight,
        (SELECT p.scientific_name 
            FROM products p 
            WHERE LOWER(TRIM(REPLACE(p.product_name, 'ies', 'y'))) = LOWER(TRIM(REPLACE(nd.fish_type, 'ies', 'y')))
               OR LOWER(TRIM(REPLACE(p.product_name, 's', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 's', '')))
               OR LOWER(TRIM(REPLACE(p.product_name, 'Kingfish', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 'King Fish', '')))
               OR CONCAT(nd.fish_type) LIKE CONCAT('%', p.product_name, '%')
               OR CONCAT(p.product_name) LIKE CONCAT('%', nd.fish_type, '%')
            LIMIT 1) 
            AS scientific_name,
        (SELECT p.product_code
            FROM products p
            WHERE LOWER(TRIM(REPLACE(p.product_name, 'ies', 'y'))) = LOWER(TRIM(REPLACE(nd.fish_type, 'ies', 'y')))
               OR LOWER(TRIM(REPLACE(p.product_name, 's', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 's', '')))
               OR LOWER(TRIM(REPLACE(p.product_name, 'Kingfish', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 'King Fish', '')))
               OR CONCAT(nd.fish_type) LIKE CONCAT('%', p.product_name, '%')
               OR CONCAT(p.product_name) LIKE CONCAT('%', nd.fish_type, '%')
            LIMIT 1)
        AS product_code,
        -- GROUP_CONCAT(DISTINCT nd.grades SEPARATOR ', ') AS sizes,
         (
            SELECT nd.grades
            FROM invoices_distribution_sheet nd2
            WHERE nd2.fish_type = nd.fish_type
              AND nd2.product_type = nd.product_type
              AND nd2.production_date = nd.production_date
            GROUP BY nd2.grades
            ORDER BY COUNT(*) DESC
            LIMIT 1
        ) AS sizes,
        nd.unit_price,
        nd.production_date
    FROM invoices_distribution_sheet AS nd
    WHERE nd.production_date = ?
    GROUP BY nd.fish_type, nd.product_type, nd.unit_price, nd.production_date
    ORDER BY nd.fish_type ASC, nd.product_type ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dateFilter);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$totalValue = 0;
$totalWeight = 0;
while ($row = $result->fetch_assoc()) {
    $value = (float)$row['total_weight'] * (float)$row['unit_price'];
    $totalValue += $value;
    $totalWeight += $row['total_weight'];

    $items[] = [
        'product_code' => $row['product_code'] ?? '',
        'fish_name' => trim($row['fish_type'] . ' ' . ($row['product_type'] ?? '')),
        'scientific_name' => $row['scientific_name'] ?? '',
        'sizes' => $row['sizes'] ?? '',
        'weight' => number_format($row['total_weight'], 2),
        'unit_price' => number_format($row['unit_price'], 2),
        'value' => number_format($value, 2)
    ];
}

// ==== PDF Class ====
class PDF extends FPDF
{
    private $headerData;
    private $headerDisplayed = false;
    private $dateFilter;

    public function __construct($headerData, $dateFilter)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->headerData = $headerData;
        $this->dateFilter = $dateFilter;
    }

    function Header()
    {
        if (!$this->headerDisplayed) {
            $h = $this->headerData;
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 5, 'YOUR DAILY FOODS (PVT) LTD.', 0, 1, 'C');
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 4, 'Company Reg. No. ' . ($h['company_reg'] ?? 'N/A'), 0, 1, 'C');
            $this->Cell(0, 4, 'VAT Reg. No. ' . ($h['vat_reg'] ?? 'N/A'), 0, 1, 'C');
            $this->Ln(2);

            // Invoice details
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 5, 'INVOICE NO.: ' . ($h['inv_no'] ?? 'N/A'), 0, 1, 'L');
            $this->SetFont('Arial', '', 8);
            $this->MultiCell(0, 4, 'CUSTOMER: ' . ($h['customer'] ?? 'N/A'), 0, 'L');
            $this->Cell(0, 4, 'DATE: ' . ($h['date'] ?? $this->dateFilter), 0, 1, 'L');
            $this->Ln(3);

            $this->headerDisplayed = true;
            $this->TableHeader();
        }
    }

    function TableHeader()
    {
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(220, 220, 220);
        
        // Compact column widths to match the PDF example
        $this->Cell(20, 6, 'Product Code', 1, 0, 'C', true);
        $this->Cell(32, 6, 'Fish Name', 1, 0, 'C', true);
        $this->Cell(35, 6, 'Scientific Name', 1, 0, 'C', true);
        $this->Cell(18, 6, 'Sizes', 1, 0, 'C', true);
        $this->Cell(20, 6, 'Volume Per KG', 1, 0, 'C', true);
        $this->Cell(20, 6, 'Price Per KG', 1, 0, 'C', true);
        $this->Cell(22, 6, 'Total (PWN)', 1, 1, 'C', true);
    }

    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
            // Only add table header on new pages, not the main header
            $this->TableHeader();
        }
    }

    function Row($data)
    {
        $this->CheckPageBreak(6);
        $this->SetFont('Arial', '', 7);
        
        $this->Cell(20, 6, $data['product_code'], 1, 0, 'L');
        $this->Cell(32, 6, $this->trimText($data['fish_name'], 25), 1, 0, 'L');
        $this->Cell(35, 6, $this->trimText($data['scientific_name'], 25), 1, 0, 'L');
        $this->Cell(18, 6, $data['sizes'], 1, 0, 'C');
        $this->Cell(20, 6, $data['weight'], 1, 0, 'R');
        $this->Cell(20, 6,  chr(163) . $data['unit_price'], 1, 0, 'R');
        $this->Cell(22, 6,  chr(163) . $data['value'], 1, 1, 'R');
    }
    
    function trimText($text, $maxLength)
    {
        if (strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength - 3) . '...';
        }
        return $text;
    }
}

// ==== Create PDF (Portrait A4) ====
$pdf = new PDF($header,$dateFilter);
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10); // Smaller margins
$pdf->SetAutoPageBreak(true, 15); // Smaller bottom margin

// ==== Table ====
// Header is automatically called in the Header() function
foreach ($items as $item) {
    $pdf->Row($item);
}

// ==== Totals ====
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(105, 6, 'TOTAL', 1, 0, 'R');
$pdf->Cell(20, 6, number_format($totalWeight, 2), 1, 0, 'R');
$pdf->Cell(20, 6, '', 1, 0, 'R');
$pdf->Cell(22, 6,  chr(163) . number_format($totalValue, 2), 1, 1, 'R');


// ==== Fetch Shipping Discount ====
$discount_sql = "SELECT value, reason FROM shipping_discount_details_UK WHERE date = ? LIMIT 1";
$stmt = $conn->prepare($discount_sql);
$stmt->bind_param("s", $dateFilter);
$stmt->execute();
$discount_result = $stmt->get_result();
$discount_data = $discount_result->fetch_assoc();

$discount_value = $discount_data['value'] ?? 0;
$discount_reason = $discount_data['reason'] ?? 'Shipping Discount';

// ==== Discount Row ====
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(145, 6, strtoupper($discount_reason) . '('. chr(163) .')', 1, 0, 'R');
$pdf->Cell(22, 6, '-' . number_format($discount_value, 2), 1, 1, 'R');

$finalTotal = $totalValue - $discount_value;

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(145, 6, 'FINAL INVOICE VALUE'.'('. chr(163) .')', 1, 0, 'R', true);
$pdf->Cell(22, 6, chr(163) . number_format($finalTotal, 2), 1, 1, 'R');
$pdf->Ln(1);

// ===== BANK DETAILS & SIGNATURE (Aligned, Same Page) =====

// Starting Y position
$startY = $pdf->GetY();

// Column settings
$leftWidth  = 80;  // Bank Details column width
$rightWidth = 60;  // Signature column width
$gap        = 35;  // Space between columns

// ----- LEFT COLUMN: Bank Details -----
$pdf->SetXY(10, $startY);
$pdf->SetFont('Arial', 'BU', 10);
$pdf->Cell($leftWidth, 6, 'Bank Details', 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell($leftWidth, 5, 'Hatton National Bank PLC', 0, 1, 'L');
$pdf->Cell($leftWidth, 5, 'Ja-Ela Branch', 0, 1, 'L');
$pdf->Ln(2); // small spacing

// Account No
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 5, 'Account No:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(55, 5, '087010032993', 0, 1, 'L');

// Swift Code
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 5, 'Swift Code:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(55, 5, 'HBLILKLX', 0, 1, 'L');

// Bank Code
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 5, 'Bank Code:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(55, 5, '7083', 0, 1, 'L');

// Get end of bank details Y position
$endY = $pdf->GetY();

// ----- RIGHT COLUMN: Signature (one line below bank details header) -----
$signatureStartY = $startY + 12; // Move signature one line down from the "Bank Details" header
$pdf->SetXY(10 + $leftWidth + $gap, $signatureStartY);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($rightWidth, 8, '................................', 0, 1, 'C');
$pdf->SetX(10 + $leftWidth + $gap); // Maintain same X position
$pdf->Cell($rightWidth, 6, 'Authorized Signatory', 0, 1, 'C');

// Reset Y to bottom of both columns (whichever is lower)
$finalY = max($endY, $signatureStartY + 20);
$pdf->SetY($finalY);

// ==== Output ====
$filename = 'Invoice - ' . ($header['date'] ?? $dateFilter) . '.pdf';
$pdf->Output('I', $filename);
?>