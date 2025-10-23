<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "ydf-system";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get exchange rates (USD→GBP and USD→LKR)
$exchangeRateUsdtoGbp = 0;
$exchangeRateUsdtoLkr = 0;

$rateResult = $conn->query("SELECT UsdToGbp, UsdToLkr FROM exchangeratefgs ORDER BY id DESC LIMIT 1");
if ($rateResult && $rateRow = $rateResult->fetch_assoc()) {
    $exchangeRateUsdtoGbp = $rateRow['UsdToGbp'];
    $exchangeRateUsdtoLkr = $rateRow['UsdToLkr'];
}

// Get unique years from the database
$yearQuery = "SELECT DISTINCT YEAR(date) as year FROM fgscosting ORDER BY year DESC";
$yearResult = $conn->query($yearQuery);
$years = [];
while($row = $yearResult->fetch_assoc()) {
    $years[] = $row['year'];
}

// Get all data for the table
$dataQuery = "SELECT date, type, size, specification, buyingprice, volume,  
                     buyingcostandlogic, processingcharge, packagingcost, freightcost, estgrosstonet, expectedyield,
                     p.scientific_name, p.product_code, p.category, p.product_name
              FROM fgscosting 
              JOIN products p ON product_id=p.id
              ORDER BY date DESC, product_code ASC";
$dataResult = $conn->query($dataQuery);
$allData = [];
while($row = $dataResult->fetch_assoc()) {
    $allData[] = $row;
}

// Organize data by month and date for the table structure
$monthlyData = [];
$monthlyAverages = [];
$yearlyProductData = []; // For summary
$graphData = []; // For graph

foreach($allData as $row) {
    $month = date('Y-m', strtotime($row['date']));
    $date = date('Y-m-d', strtotime($row['date']));
    $year = date('Y', strtotime($row['date']));
    
    // --- Correct CNF Formula (in GBP) ---
    $yield = $row['expectedyield'] > 0 ? $row['expectedyield'] : 100;
    $processedCost = ($row['buyingprice'] / ($yield / 100)); // LKR
    $buyingTotal = $processedCost + $row['buyingcostandlogic']; // LKR
    $usdBase = $exchangeRateUsdtoLkr > 0 ? $buyingTotal / $exchangeRateUsdtoLkr : 0; // USD

    $freightForGross = $row['freightcost'] * (1 + ($row['estgrosstonet'] / 100)); // USD
    $cnfUsd = $usdBase + $row['processingcharge'] + $row['packagingcost'] + $freightForGross; // USD
    $cnfGbp = $exchangeRateUsdtoGbp > 0 ? $cnfUsd / $exchangeRateUsdtoGbp : 0; // GBP
    
    $row['cnf'] = $cnfGbp;
    $row['year'] = $year; // Add year to row for filtering

    // Store for monthly tables
    if (!isset($monthlyData[$month])) {
        $monthlyData[$month] = [];
    }
    if (!isset($monthlyData[$month][$date])) {
        $monthlyData[$month][$date] = [];
    }
    $monthlyData[$month][$date][] = $row;
    
    // Store for monthly averages
    if (!isset($monthlyAverages[$month])) {
        $monthlyAverages[$month] = [];
    }
    $productKey = $row['product_code'] . '|' . $date;
    if (!isset($monthlyAverages[$month][$productKey])) {
        $monthlyAverages[$month][$productKey] = [
            'total_cnf' => 0,
            'count' => 0,
            'product_data' => $row,
            'product_code' => $row['product_code'],
            'date' => $date,
            'year' => $year
        ];
    }
    $monthlyAverages[$month][$productKey]['total_cnf'] += $cnfGbp;
    $monthlyAverages[$month][$productKey]['count']++;
    
    // Store for yearly summary
    $productSummaryKey = $row['product_code'] . '|' . $row['product_name'] . '|' . $row['size'] . '|' . $row['specification'];
    if (!isset($yearlyProductData[$year])) {
        $yearlyProductData[$year] = [];
    }
    if (!isset($yearlyProductData[$year][$productSummaryKey])) {
        $yearlyProductData[$year][$productSummaryKey] = [
            'total_cnf' => 0,
            'count' => 0,
            'monthly_data' => [],
            'product_data' => $row
        ];
    }
    $yearlyProductData[$year][$productSummaryKey]['total_cnf'] += $cnfGbp;
    $yearlyProductData[$year][$productSummaryKey]['count']++;
    
    // Store monthly data for each product
    if (!isset($yearlyProductData[$year][$productSummaryKey]['monthly_data'][$month])) {
        $yearlyProductData[$year][$productSummaryKey]['monthly_data'][$month] = [
            'total_cnf' => 0,
            'count' => 0
        ];
    }
    $yearlyProductData[$year][$productSummaryKey]['monthly_data'][$month]['total_cnf'] += $cnfGbp;
    $yearlyProductData[$year][$productSummaryKey]['monthly_data'][$month]['count']++;
    
    // Store for graph data
    if (!isset($graphData[$year])) {
        $graphData[$year] = [];
    }
    $graphProductKey = $row['product_code'] . ' - ' . $row['product_name'];
    if (!isset($graphData[$year][$graphProductKey])) {
        $graphData[$year][$graphProductKey] = [];
    }
    if (!isset($graphData[$year][$graphProductKey][$month])) {
        $graphData[$year][$graphProductKey][$month] = [
            'total_cnf' => 0,
            'count' => 0
        ];
    }
    $graphData[$year][$graphProductKey][$month]['total_cnf'] += $cnfGbp;
    $graphData[$year][$graphProductKey][$month]['count']++;
}

// Calculate averages per product per month
$productAverages = [];
foreach($monthlyAverages as $month => $dateProducts) {
    foreach($dateProducts as $productKey => $data) {
        $productCode = $data['product_code'];
        $year = $data['year'];
        if (!isset($productAverages[$month])) {
            $productAverages[$month] = [];
        }
        if (!isset($productAverages[$month][$productCode])) {
            $productAverages[$month][$productCode] = [
                'total_cnf' => 0,
                'count' => 0,
                'product_data' => $data['product_data'],
                'year' => $year
            ];
        }
        $productAverages[$month][$productCode]['total_cnf'] += $data['total_cnf'];
        $productAverages[$month][$productCode]['count'] += $data['count'];
    }
}

// Calculate final averages for monthly tables
foreach($productAverages as $month => $products) {
    foreach($products as $productCode => $data) {
        $productAverages[$month][$productCode]['average_cnf'] = 
            $data['total_cnf'] / $data['count'];
    }
}

// Calculate yearly averages for summary tab
$yearlyAverages = [];
foreach($yearlyProductData as $year => $products) {
    foreach($products as $productKey => $data) {
        if (!isset($yearlyAverages[$year])) {
            $yearlyAverages[$year] = [];
        }
        $yearlyAverages[$year][$productKey] = [
            'yearly_average' => $data['total_cnf'] / $data['count'],
            'monthly_averages' => []
        ];
        
        // Calculate monthly averages for each product
        foreach($data['monthly_data'] as $month => $monthData) {
            $yearlyAverages[$year][$productKey]['monthly_averages'][$month] = 
                $monthData['total_cnf'] / $monthData['count'];
        }
    }
}

// Prepare graph data - calculate monthly averages for each product
$graphMonthlyAverages = [];
foreach($graphData as $year => $products) {
    foreach($products as $productName => $months) {
        foreach($months as $month => $data) {
            if (!isset($graphMonthlyAverages[$year])) {
                $graphMonthlyAverages[$year] = [];
            }
            if (!isset($graphMonthlyAverages[$year][$productName])) {
                $graphMonthlyAverages[$year][$productName] = [];
            }
            $graphMonthlyAverages[$year][$productName][$month] = 
                $data['total_cnf'] / $data['count'];
        }
    }
}

// Get unique products for graph dropdown
$uniqueProducts = [];
foreach($allData as $row) {
    $productKey = $row['product_code'] . ' - ' . $row['product_name'];
    if (!in_array($productKey, $uniqueProducts)) {
        $uniqueProducts[] = $productKey;
    }
}
sort($uniqueProducts);

// Prepare graph data for JavaScript - limit to 5 products for clarity
$graphProductsForJS = array_slice($uniqueProducts, 0, 5);

// Get months for tabs - from the actual data we have
$months = [];
foreach(array_keys($monthlyData) as $monthKey) {
    $monthName = date('F Y', strtotime($monthKey . '-01'));
    $months[] = [
        'month' => $monthKey,
        'month_name' => $monthName
    ];
}

// Sort months in descending order (newest first)
usort($months, function($a, $b) {
    return strcmp($b['month'], $a['month']);
});

$currentYear = date('Y');
?>