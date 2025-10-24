<?php
require('C:\xampp\htdocs\ydf-system-oshen\app\views\fpdf\fpdf.php');

// --- DB Connection ---
$servername = "localhost";
$username = "root";
$password = "";
$database = "ydf-system";
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- Fetch Data ---
$sql = "SELECT c.id, p.product_code, p.product_name, p.scientific_name, 
               c.volume, c.specification, c.`500groundedprice`, c.sizerange
        FROM ydfcosting c
        JOIN products p ON c.product_id = p.id
        ORDER BY c.id ASC";
$result = $conn->query($sql);
$costingData = $result->fetch_all(MYSQLI_ASSOC);

// --- PDF Class ---
class PDF extends FPDF {


    function ImprovedTable($header, $data) {
        // --- Column widths ---
        $w = [28, 30, 36, 28, 50, 22];
        $lineHeight = 5;

        $totalWidth = array_sum($w); // total table width

        // --- Add Logo Row ---
        $logoPath = 'C:\xampp\htdocs\ydf-system-oshen\app\views\accounting\YDFcostingalt\logocopy1.jpg';
        $logoWidth = 25;   // Adjust image width
        $logoHeight = 20;  // Adjust image height

        // Save starting position
        $xStart = $this->GetX();
        $yStart = $this->GetY();

        // Draw a cell with border for the logo row
        $this->Cell($totalWidth, $logoHeight + 4, '', 1, 1, 'C'); // empty bordered cell

        // Calculate X position to center the image
        $pageWidth = $this->GetPageWidth();
        $xImage = $xStart + ($totalWidth - $logoWidth) / 2;
        $yImage = $yStart + 2; // a bit of top padding inside the cell

        // Place image centered inside the bordered cell
        $this->Image($logoPath, $xImage, $yImage, $logoWidth, $logoHeight);

        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(158, 153, 153);

        $totalWidth = array_sum($w); // total table width   
        $this->Cell($totalWidth, 8, 'Vacuum Product Price List - Price to USA (MCO, MIA and ATL Airport) - 300Kg+', 1, 1, 'C',true);

        // --- Section Title ---
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(240, 132, 29);

        $this->Cell($totalWidth, 6, 'Product Varieties', 1, 1, 'C', true);

        // --- Table Header ---
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(3, 90, 168); // Light gray for header
        $this->SetDrawColor(41, 40, 40); // Darker gray for borders
        $this->SetLineWidth(0.3);
        
        // Draw header cells with borders
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        // --- Table Data ---
        $this->SetFont('Arial', '', 8);
        $this->SetDrawColor(41, 40, 40); // Consistent border color
        $this->SetLineWidth(0.2);

        foreach ($data as $row) {
            // Calculate row height (based on max text lines per cell)
            $lines = [
                $this->NbLines($w[0], $row['product_code']),
                $this->NbLines($w[1], $row['product_name']),
                $this->NbLines($w[2], $row['scientific_name']),
                $this->NbLines($w[3], $row['sizerange']),
                $this->NbLines($w[4], $row['specification']),
                $this->NbLines($w[5], '$' . number_format($row['500groundedprice'], 2))
            ];
            $maxLines = max($lines);
            $h = $lineHeight * $maxLines;

            // --- Page break check ---
            if ($this->GetY() + $h > $this->PageBreakTrigger) {
                $this->AddPage();
                // Redraw header on new page
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(220, 220, 220);
                for ($i = 0; $i < count($header); $i++) {
                    $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
                }
                $this->Ln();
                $this->SetFont('Arial', '', 8);
            }

            // --- Alternating row color ---
            static $rowCount = 0;
            if ($rowCount % 2 == 0) {
                $this->SetFillColor(255, 255, 255); // White
            } else {
                $this->SetFillColor(245, 245, 245); // Very light gray
            }
            $rowCount++;

            // --- Save starting X & Y ---
            $xStart = $this->GetX();
            $yStart = $this->GetY();

            // --- Column data ---
            $cells = [
                $row['product_code'],
                $row['product_name'],
                $row['scientific_name'],
                $row['sizerange'],
                $row['specification'],
                '$' . number_format($row['500groundedprice'], 2)
            ];

            // --- Draw complete row with borders ---
            for ($i = 0; $i < count($cells); $i++) {
                $x = $this->GetX();
                $y = $this->GetY();

                // Draw cell with border and fill
                $this->Cell($w[$i], $h, '', 1, 0, '', true); // Empty cell with border and fill
                
                // Print text on top of the cell
                $this->SetXY($x, $y);
                $this->MultiCell($w[$i], $lineHeight, $cells[$i], 0, 'C');
                
                // Move cursor to right edge of cell
                $this->SetXY($x + $w[$i], $y);
            }

            // Move to next line
            $this->SetXY($xStart, $yStart + $h);
        }
        // --- Final Row (like a footer row in table) ---
        $this->SetFont('Arial', 'I', 6);
        $this->SetFillColor(204, 134, 65); // Same color as header (or change)

        $totalWidth = array_sum($w); // total table width
        $this->Cell($totalWidth, 6, 'No. 170, Orex City Shopping Complex, Ekala, Ja Ela, Sri Lanka', 1, 1, 'C', true);
        $this->Cell($totalWidth, 6, 'Web: www.ydf.lk | Mail: info@ydf.lk | Tel: +94 (0)76 081 8181 | Mob: +94 (0)77 296 2277', 1, 1, 'C',true);
    }

    function NbLines($w, $txt) {
        if (empty($txt)) return 1;
        
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c == ' ') $sep = $i;
            $l += isset($cw[$c]) ? $cw[$c] : 0;
            if ($l > $wmax) {
                if ($sep == -1) { 
                    if ($i == $j) $i++; 
                } else {
                    $i = $sep + 1;
                }
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

// --- Generate PDF ---
$pdf = new PDF();
$pdf->AddPage();

$header = ['Product Code', 'Product Name', 'Scientific Name', 'Size Range', 'Specification', '500g Price($)'];
$pdf->ImprovedTable($header, $costingData);

$pdf->Output('I', 'Price_List_to_MCO_' . date('d.m.Y') . '_300Kg+.pdf');
$conn->close();
?>