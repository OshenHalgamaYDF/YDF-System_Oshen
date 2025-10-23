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
               c.volume, c.specification, c.`500grounded_MCO`
        FROM ydfcosting c
        JOIN products p ON c.product_id = p.id
        ORDER BY c.id ASC";
$result = $conn->query($sql);
$costingData = $result->fetch_all(MYSQLI_ASSOC);

// --- PDF Class ---
class PDF extends FPDF {
    function Header() {
        // Main header
        // === Centered Logo ===
        $logoPath = 'C:\xampp\htdocs\ydf-system-oshen\app\views\accounting\YDFcostingalt\1720758043421.jpg';
        $logoWidth = 40; // width in mm (adjust as needed)
        $pageWidth = $this->GetPageWidth();
        $x = ($pageWidth - $logoWidth) / 2; // Center horizontally

        // Place image centered near top
        $this->Image($logoPath, $x, 3, $logoWidth);
        $this->Ln(30); // Space below image

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'Vacuum Product Price List - Price to USA (MCO, MIA and ATL Airport) -300Kg+', 0, 1, 'C');
        $this->Ln(4);

        // Section title
        $this->SetFont('Arial', 'BU', 11);
        $this->Cell(0, 6, 'Product Varieties', 0, 1, 'C');
        $this->Ln(4);
    }

    function ImprovedTable($header, $data) {
        // --- Column widths ---
        $w = [28, 30, 36, 28, 50, 22];
        $lineHeight = 5;

        // --- Table Header ---
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(240, 240, 240);
        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.2);
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        // --- Table Data ---
        $this->SetFont('Arial', '', 8);
        $fill = false;

        foreach ($data as $row) {
            // Calculate row height (based on max text lines per cell)
            $lines = [
                $this->NbLines($w[0], $row['product_code']),
                $this->NbLines($w[1], $row['product_name']),
                $this->NbLines($w[2], $row['scientific_name']),
                $this->NbLines($w[3], $row['volume']),
                $this->NbLines($w[4], $row['specification']),
                $this->NbLines($w[5], '$' . number_format($row['500grounded_MCO'], 2))
            ];
            $maxLines = max($lines);
            $h = $lineHeight * $maxLines;

            // --- Page break check ---
            if ($this->GetY() + $h > $this->PageBreakTrigger) {
                $this->AddPage();
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(240, 240, 240);
                for ($i = 0; $i < count($header); $i++) {
                    $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
                }
                $this->Ln();
                $this->SetFont('Arial', '', 8);
            }

            // --- Alternating row color ---
            if ($fill) $this->SetFillColor(250, 250, 250);
            else $this->SetFillColor(255, 255, 255);
            $fill = !$fill;

            // --- Save starting X & Y ---
            $xStart = $this->GetX();
            $yStart = $this->GetY();

            // --- Column data ---
            $cells = [
                $row['product_code'],
                $row['product_name'],
                $row['scientific_name'],
                $row['volume'],
                $row['specification'],
                '$' . number_format($row['500grounded_MCO'], 2)
            ];

            // --- Draw cells with aligned borders ---
            for ($i = 0; $i < count($cells); $i++) {
                $x = $this->GetX();
                $y = $this->GetY();

                // Draw cell border manually for perfect height
                $this->Rect($x, $y, $w[$i], $h);

                // Print text
                $this->MultiCell($w[$i], $lineHeight, $cells[$i], 0, 'L', $fill);

                // Move cursor to right edge of cell
                $this->SetXY($x + $w[$i], $y);
            }

            // Move to next line
            $this->SetXY($xStart, $yStart + $h);
        }
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
    // function Footer() {
    // }
}

// --- Generate PDF ---
$pdf = new PDF();
$pdf->AddPage();

$header = ['Product Code', 'Product Name', 'Scientific Name', 'Size Range(Kg)', 'Specification', '500g Price($)'];
$pdf->ImprovedTable($header, $costingData);

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, 'No. 170, Orex City Shopping Complex, Ekala, Ja ela, Sri Lanka', 0, 1, 'C');
$pdf->Cell(0, 6, 'Web: www.ydf.lk | Mail: info@ydf.lk | Tel: +94 (0)76 081 8181 | Mob: +94 (0)77 296 2277', 0, 1, 'C');

$pdf->Output('I', 'Price_List_to_MCO_' . date('d.m.Y') . '_300Kg+.pdf');
$conn->close();
?>