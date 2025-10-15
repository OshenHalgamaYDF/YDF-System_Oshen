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
        (
            SELECT p.scientific_name 
            FROM products p 
            WHERE LOWER(TRIM(REPLACE(p.product_name, 'ies', 'y'))) = LOWER(TRIM(REPLACE(nd.fish_type, 'ies', 'y')))
               OR LOWER(TRIM(REPLACE(p.product_name, 's', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 's', '')))
               OR LOWER(TRIM(REPLACE(p.product_name, 'Kingfish', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 'King Fish', '')))
               OR CONCAT(nd.fish_type) LIKE CONCAT('%', p.product_name, '%')
               OR CONCAT(p.product_name) LIKE CONCAT('%', nd.fish_type, '%')
            LIMIT 1
        ) AS scientific_name,
        (
            SELECT p.product_code
            FROM products p
            WHERE LOWER(TRIM(REPLACE(p.product_name, 'ies', 'y'))) = LOWER(TRIM(REPLACE(nd.fish_type, 'ies', 'y')))
               OR LOWER(TRIM(REPLACE(p.product_name, 's', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 's', '')))
               OR LOWER(TRIM(REPLACE(p.product_name, 'Kingfish', ''))) = LOWER(TRIM(REPLACE(nd.fish_type, 'King Fish', '')))
               OR CONCAT(nd.fish_type) LIKE CONCAT('%', p.product_name, '%')
               OR CONCAT(p.product_name) LIKE CONCAT('%', nd.fish_type, '%')
            LIMIT 1
        ) AS product_code,
        GROUP_CONCAT(DISTINCT nd.grades SEPARATOR ', ') AS sizes,
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
    private $headerDisplayed = false;
    private $isLastPage = false;
    
    function Header()
    { 
        // Only show header on first page
        if (!$this->headerDisplayed && $this->PageNo() == 1) {
            // Company Header - Centered
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 5, 'YOUR DAILY FOODS (PVT) LTD.', 0, 1, 'C');
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 4, 'Company Reg. No. ' . ($GLOBALS['header']['company_reg'] ?? 'PV 00246176'), 0, 1, 'C');
            $this->Cell(0, 4, 'VAT Reg. No. ' . ($GLOBALS['header']['vat_reg'] ?? '103246229'), 0, 1, 'C');
            $this->Ln(2);
            
            // Invoice Number - Left aligned
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 5, 'INVOICE NO.: ' . ($GLOBALS['header']['inv_no'] ?? 'YDF/09/2025/180'), 0, 1, 'L');
            
            // Customer Info
            $this->SetFont('Arial', '', 8);
            $customerName = $GLOBALS['header']['customer'] ?? 'First Global Seafood Ltd';
            $this->Cell(0, 4, 'CUSTOMER: ' . $customerName, 0, 1, 'L');
            
            // Date
            $this->Cell(0, 4, 'DATE : ' . ($GLOBALS['header']['date'] ?? $GLOBALS['dateFilter']), 0, 1, 'L');
            $this->Ln(3);
            
            $this->headerDisplayed = true;
            
            // Add table header after main header
            $this->TableHeader();
        }
    }

    function TableHeader()
    {
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(220, 220, 220);
        
        // Compact column widths to match the PDF example
        $this->Cell(20, 6, 'Product Code', 1, 0, 'C', true);
        $this->Cell(28, 6, 'Fish Name', 1, 0, 'C', true);
        $this->Cell(28, 6, 'Scientific Name', 1, 0, 'C', true);
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
        $this->Cell(28, 6, $this->trimText($data['fish_name'], 25), 1, 0, 'L');
        $this->Cell(28, 6, $this->trimText($data['scientific_name'], 25), 1, 0, 'L');
        $this->Cell(18, 6, $data['sizes'], 1, 0, 'C');
        $this->Cell(20, 6, $data['weight'], 1, 0, 'R');
        $this->Cell(20, 6, '£' . $data['unit_price'], 1, 0, 'R');
        $this->Cell(22, 6, '£' . $data['value'], 1, 1, 'R');
    }
    
    function trimText($text, $maxLength)
    {
        if (strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength - 3) . '...';
        }
        return $text;
    }
    
    // Function to mark last page
    function SetLastPage()
    {
        $this->isLastPage = true;
    }
    
    function Footer()
    {
        // Only show bank details on the last page
        if ($this->isLastPage) {
            // Position at 1.5 cm from bottom
            $this->SetY(-25);
            
            // Permanent bank details
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 4, 'Hatton National Bank PLC', 0, 1, 'L');
            $this->Cell(0, 4, 'Ja-Ela Branch', 0, 1, 'L');
            $this->Cell(0, 4, 'Account No: 087010032993', 0, 1, 'L');
            $this->Cell(0, 4, 'Swift Code : HBLILKLX', 0, 1, 'L');
            $this->Cell(0, 4, 'Bank Code : 7083', 0, 1, 'L');
            $this->Ln(3);
            
            // Signature
            $this->Cell(0, 4, 'Authorize Signatory', 0, 1, 'L');
        }
    }
}

// ==== Create PDF (Portrait A4) ====
$pdf = new PDF('P', 'mm', 'A4');
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
$pdf->Cell(114, 6, 'TOTAL', 1, 0, 'R');
$pdf->Cell(20, 6, number_format($totalWeight, 2), 1, 0, 'R');
$pdf->Cell(20, 6, '', 1, 0, 'R');
$pdf->Cell(22, 6, '£' . number_format($totalValue, 2), 1, 1, 'R');

// Mark as last page before output
$pdf->SetLastPage();

// ==== Output ====
$filename = 'Invoice - ' . ($header['date'] ?? $dateFilter) . '.pdf';
$pdf->Output('I', $filename);
?>