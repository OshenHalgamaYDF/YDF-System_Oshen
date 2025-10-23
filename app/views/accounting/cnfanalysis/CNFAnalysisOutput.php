<?php
    include('C:\xampp\htdocs\ydf-system-oshen\app\controllers\accounting\cnfanalysis\CNFAnalysisController.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CNF Analysis UK</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- jQuery (must come before DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <style>
        .nav-tabs .nav-link.active {
            font-weight: bold;
            background-color: #e9ecef;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .cnf-header {
            background-color: #d1ecf1 !important;
            font-weight: bold;
        }
        .date-column {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .average-column {
            background-color: #fff3cd !important;
            font-weight: bold;
        }
        .yearly-average-column {
            background-color: #d4edda !important;
            font-weight: bold;
        }
        .product-row:hover {
            background-color: #f8f9fa;
        }
        .month-table {
            margin-bottom: 30px;
        }
        .product-details-column {
            min-width: 120px;
        }
        .cnf-cell {
            min-width: 80px;
        }
        .dataTables_wrapper {
            position: relative;
        }
        .summary-table-container {
            max-height: 80vh;
            overflow-y: auto;
        }
        .chart-container {
            position: relative;
            height: 60vh;
            width: 100%;
        }
        .graph-controls {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary-year-header {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-5">
    <h2 class="text-center text-primary fw-bold mb-4">Shipment CNF Analysis</h2>
    <h4 class="text-left text-primary">Please Select year to view CNF analysis</h4>

    <!-- Year Filter and Monthly Tabs -->
    <div class="container-fluid mb-4">
        <div class="mb-3">
            <label for="yearFilter" class="form-label fw-bold me-2">Filter by Year:</label>
            <select id="yearFilter" class="form-select d-inline-block w-auto">
                <option value="all">All Years</option>
                <?php foreach($years as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year == $currentYear ? 'selected' : ''; ?>>
                    <?php echo $year; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="clearYearFilter" class="btn btn-outline-secondary ms-2">Clear</button>
        </div>
        
        <!-- Monthly Tabs - Show latest month first -->
        <ul class="nav nav-tabs" id="monthTabs">
            <?php 
            if(!empty($months)) {
                $firstMonth = true;
                foreach($months as $month): 
                    $monthName = isset($month['month_name']) ? $month['month_name'] : 'Unknown Month';
                    $monthValue = isset($month['month']) ? $month['month'] : '';
                    if (empty($monthValue)) continue;
            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $firstMonth ? 'active' : ''; ?>" data-month="<?php echo $monthValue; ?>">
                    <?php echo $monthName; ?>
                </a>
            </li>
            <?php 
                    $firstMonth = false;
                endforeach; 
            } else {
                echo '<li class="nav-item"><span class="nav-link text-muted">No monthly data available</span></li>';
            }
            ?>
            <li class="nav-item">
                <a class="nav-link text-danger" data-month="summary">Summary</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-warning" data-month="graph">Graph</a>
            </li>
        </ul>
    </div>
    
    <!-- CNF Analysis Tables -->
    <div class="container-fluid mb-5" id="cnfTablesContainer">
        <?php 
        $firstTable = true;
        foreach($monthlyData as $month => $dates): 
            $monthName = date('F Y', strtotime($month . '-01'));
            $monthYear = explode('-', $month)[0];
            $monthDates = array_keys($dates);
            sort($monthDates);
        ?>
        <div class="month-table <?php echo $firstTable ? '' : 'd-none'; ?>" data-month="<?php echo $month; ?>" data-year="<?php echo $monthYear; ?>">
            <h4 class="text-primary mb-3"><?php echo $monthName; ?> - CNF Analysis</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped month-cnf-table" style="width:100%">
                    <thead>
                        <tr>
                            <!-- Product Detail Columns -->
                            <th class="text-center date-column product-details-column">Product Code</th>
                            <th class="text-center date-column product-details-column">Product Name</th>
                            <th class="text-center date-column product-details-column">Scientific Name</th>
                            <th class="text-center date-column product-details-column">Size</th>
                            <th class="text-center date-column product-details-column">Specification</th>
                            
                            <!-- Daily CNF Columns -->
                            <?php foreach($monthDates as $date): 
                                $dayName = date('D', strtotime($date));
                                $dayNumber = date('j', strtotime($date));
                            ?>
                            <th class="text-center cnf-header cnf-cell" title="<?php echo $date; ?>">
                                <?php echo $dayName ?><br><?php echo $dayNumber; ?>
                            </th>
                            <?php endforeach; ?>
                            
                            <!-- Average Column -->
                            <th class="text-center average-column cnf-cell">Monthly Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get all unique products for this month
                        $products = [];
                        foreach($dates as $dateData) {
                            foreach($dateData as $productData) {
                                $productKey = $productData['product_code'] . '|' . $productData['product_name'];
                                if (!isset($products[$productKey])) {
                                    $products[$productKey] = $productData;
                                }
                            }
                        }
                        
                        foreach($products as $productKey => $product): 
                            list($productCode, $productName) = explode('|', $productKey);
                            $averageCnf = isset($productAverages[$month][$productCode]) ? 
                                number_format($productAverages[$month][$productCode]['average_cnf'], 2) : '-';
                        ?>
                        <tr class="product-row">
                            <!-- Product Detail Cells -->
                            <td class="text-center">
                                <strong class="text-primary"><?php echo $productCode; ?></strong>
                            </td>
                            <td class="text-center">
                                <?php echo $productName; ?>
                            </td>
                            <td class="text-center">
                                <span class="small"><?php echo $product['scientific_name']; ?></span>
                            </td>
                            <td class="text-center">
                                <?php echo $product['size']; ?>
                            </td>
                            <td class="text-center">
                                <?php echo $product['specification']; ?>
                            </td>
                            
                            <!-- Daily CNF Cells -->
                            <?php foreach($monthDates as $date): ?>
                            <td class="text-center cnf-cell">
                                <?php
                                $cnfValue = '';
                                if (isset($dates[$date])) {
                                    // Find all matching products for this date and code
                                    $matchingProducts = array_filter($dates[$date], function($p) use ($productCode) {
                                        return $p['product_code'] == $productCode;
                                    });
                                    
                                    if (!empty($matchingProducts)) {
                                        // If multiple entries for same product on same date, take average
                                        $total = 0;
                                        $count = 0;
                                        foreach($matchingProducts as $matchedProduct) {
                                            $total += $matchedProduct['cnf'];
                                            $count++;
                                        }
                                        $cnfValue = number_format($total / $count, 2);
                                    }
                                }
                                echo $cnfValue ?: '-';
                                ?>
                            </td>
                            <?php endforeach; ?>
                            
                            <!-- Average Cell -->
                            <td class="text-center average-column cnf-cell fw-bold">
                                <?php echo $averageCnf; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php 
        $firstTable = false;
        endforeach; 
        ?>
        
        <!-- Single Summary Table Container (rendered client-side based on yearFilter) -->
        <div class="month-table d-none" data-month="summary" id="summaryTableContainer">
            <div class="summary-year-header">
                <h4 class="text-primary mb-0">Yearly CNF Summary</h4>
            </div>
            <div class="summary-table-container">
                <div class="table-responsive" id="summaryInnerContainer">
                    <!-- Summary table HTML will be injected here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Graph Tab -->
        <div class="month-table d-none" data-month="graph">
            <h4 class="text-primary mb-3">CNF Fluctuation Analysis</h4>
            
            <div class="graph-controls">
                <div class="row">
                    <div class="col-md-4">
                        <label for="graphYearFilter" class="form-label fw-bold">Select Year:</label>
                        <select id="graphYearFilter" class="form-select">
                            <?php foreach($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == $currentYear ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="productFilter" class="form-label fw-bold">Select Product:</label>
                        <select id="productFilter" class="form-select">
                            <option value="all">All Products</option>
                            <?php foreach($uniqueProducts as $product): ?>
                            <option value="<?php echo $product; ?>"><?php echo $product; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="updateGraph" class="btn btn-primary w-100">Update Graph</button>
                    </div>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="cnfChart"></canvas>
            </div>
        </div>
    </div>
    
</div>
<script>
    // Pass PHP data to JavaScript
    const graphMonthlyAverages = <?php echo json_encode($graphMonthlyAverages); ?>;
    const graphProducts = <?php echo json_encode($graphProductsForJS); ?>;
    const allMonths = <?php echo json_encode(array_keys($monthlyData)); ?>; // e.g. ["2025-01","2025-02"]
    const monthsMeta = <?php echo json_encode($months); ?>; // [{month: '2025-01', month_name: 'Jan 2025'}, ...]
    const yearsList = <?php echo json_encode($years); ?>;
    const yearlyAverages = <?php echo json_encode($yearlyAverages); ?>;

    $(document).ready(function() {
        let cnfChart = null;
        let summaryTableDT = null;

        // Helper: show a month/table (smoothly)
        function showMonth(month) {
            // Hide all tables
            $('.month-table').addClass('d-none');
            // Show selected month table
            $(`.month-table[data-month="${month}"]`).removeClass('d-none');
            // Update active tab
            $('#monthTabs a').removeClass('active');
            $(`#monthTabs a[data-month="${month}"]`).addClass('active');

            // If graph tab is selected, initialize the chart
            if (month === 'graph') {
                initializeChart();
            }

            // Adjust DataTables if visible
            setTimeout(function() {
                if ($.fn.dataTable && $.fn.dataTable.tables) {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                }
            }, 200);
        }

        // Render summary table for a specific year
        function renderSummaryTable(year) {
            const container = $('#summaryInnerContainer');
            container.empty();

            // Build month list for the selected year
            const yearMonths = monthsMeta
                .filter(m => m.month.startsWith(year + '-'))
                .sort((a,b) => a.month.localeCompare(b.month)); // ascending

            // Build table header
            let html = '<table id="summaryTable" class="table table-bordered table-striped" style="width:100%">';
            html += '<thead><tr>';
            html += '<th class="text-center date-column product-details-column">Product Code</th>';
            html += '<th class="text-center date-column product-details-column">Product Name</th>';
            html += '<th class="text-center date-column product-details-column">Size</th>';
            html += '<th class="text-center date-column product-details-column">Specification</th>';

            // monthly columns
            yearMonths.forEach(m => {
                const shortName = new Date(m.month + '-01').toLocaleString('en-GB', { month: 'short', year: 'numeric' });
                html += `<th class="text-center cnf-header cnf-cell" title="${m.month}">${shortName}</th>`;
            });

            html += '<th class="text-center yearly-average-column cnf-cell">Yearly Average</th>';
            html += '</tr></thead>';

            html += '<tbody>';

            // If no data for year, show message
            if (!yearlyAverages || !yearlyAverages[year] || Object.keys(yearlyAverages[year]).length === 0) {
                html += `<tr><td colspan="${4 + yearMonths.length + 1}" class="text-center text-muted">No data available for ${year}</td></tr>`;
            } else {
                Object.keys(yearlyAverages[year]).forEach(productKey => {
                    const product = yearlyAverages[year][productKey];
                    // productKey format: product_code|product_name|size|specification
                    const parts = productKey.split('|');
                    const pCode = parts[0] || '';
                    const pName = parts[1] || '';
                    const pSize = parts[2] || '';
                    const pSpec = parts[3] || '';

                    html += '<tr class="product-row">';
                    html += `<td class="text-center"><strong class="text-primary">${pCode}</strong></td>`;
                    html += `<td class="text-center">${pName}</td>`;
                    html += `<td class="text-center">${pSize}</td>`;
                    html += `<td class="text-center">${pSpec}</td>`;

                    // monthly values
                    yearMonths.forEach(m => {
                        const monthKey = m.month;
                        let monthlyAvg = '-';
                        if (product.monthly_averages && product.monthly_averages[monthKey] !== undefined) {
                            monthlyAvg = Number(product.monthly_averages[monthKey]).toFixed(2);
                        }
                        html += `<td class="text-center cnf-cell">${monthlyAvg}</td>`;
                    });

                    // yearly average
                    const yearlyAvg = product.yearly_average ? Number(product.yearly_average).toFixed(2) : '-';
                    html += `<td class="text-center yearly-average-column cnf-cell fw-bold">${yearlyAvg}</td>`;

                    html += '</tr>';
                });
            }

            html += '</tbody></table>';

            container.html(html);

            // Initialize DataTable for the dynamically created summary table
            if ($.fn.dataTable) {
                if (summaryTableDT) {
                    try { summaryTableDT.destroy(); } catch(e) {}
                }
                summaryTableDT = $('#summaryTable').DataTable({
                    paging: false,
                    searching: true,
                    ordering: true,
                    info: false,
                    autoWidth: false,
                    scrollX: true,
                    dom: '<"row"<"col-sm-12"f>>rtip'
                });
            }
        }

        // Initialize or update the chart
        function initializeChart() {
            const selectedYear = $('#graphYearFilter').val();
            const selectedProduct = $('#productFilter').val();
            
            // Destroy existing chart if it exists
            if (cnfChart) {
                cnfChart.destroy();
            }
            
            // Create new chart
            const ctx = document.getElementById('cnfChart').getContext('2d');
            
            // Get data for the chart based on selections
            const chartData = getChartData(selectedYear, selectedProduct);
            
            cnfChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `CNF Fluctuation Analysis - ${selectedYear}`,
                            font: { size: 16 }
                        },
                        legend: { display: true, position: 'top' },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: £${context.parsed.y ? context.parsed.y.toFixed(2) : '0.00'}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Months' } },
                        y: { title: { display: true, text: 'CNF Value (GBP)' }, beginAtZero: false }
                    },
                    interaction: { mode: 'nearest', axis: 'x', intersect: false }
                }
            });
        }
        
        // Get chart data based on year and product selection
        function getChartData(year, product) {
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const datasets = [];
            const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'];

            // Build a list of months for the entire dataset, but only show months present in allMonths
            const monthsForYear = monthsMeta.filter(m => m.month.startsWith(year + '-')).map(m => m.month);

            if (product === 'all') {
                graphProducts.forEach((productName, index) => {
                    const data = monthsForYear.map(month => {
                        if (graphMonthlyAverages[year] && graphMonthlyAverages[year][productName] && graphMonthlyAverages[year][productName][month]) {
                            return graphMonthlyAverages[year][productName][month];
                        }
                        return null;
                    });
                    datasets.push({
                        label: productName,
                        data: data,
                        borderColor: colors[index % colors.length],
                        backgroundColor: colors[index % colors.length] + '20',
                        tension: 0.4,
                        fill: false,
                        spanGaps: true
                    });
                });
            } else {
                const data = monthsForYear.map(month => {
                    if (graphMonthlyAverages[year] && graphMonthlyAverages[year][product] && graphMonthlyAverages[year][product][month]) {
                        return graphMonthlyAverages[year][product][month];
                    }
                    return null;
                });
                datasets.push({
                    label: product,
                    data: data,
                    borderColor: '#36A2EB',
                    backgroundColor: '#36A2EB20',
                    tension: 0.4,
                    fill: false,
                    spanGaps: true
                });
            }

            const monthLabels = monthsMeta
                .filter(m => m.month.startsWith(year + '-'))
                .map(m => {
                    const parts = m.month.split('-');
                    const monthNum = parseInt(parts[1]);
                    return monthNames[monthNum - 1] + ' ' + parts[0];
                });

            return { labels: monthLabels, datasets: datasets };
        }
        
        // Update graph when button is clicked
        $('#updateGraph').on('click', function() {
            initializeChart();
        });
        
        // Update graph when year filter changes (if on graph tab)
        $('#graphYearFilter').on('change', function() {
            if ($('.month-table[data-month="graph"]').is(':visible')) {
                initializeChart();
            }
        });

        // Month tab click event
        $('#monthTabs').on('click', 'a', function(e) {
            e.preventDefault();
            const selectedMonth = $(this).data('month');
            showMonth(selectedMonth);
        });

        // Year filter functionality - now filters tabs and summary content strictly by year
        $('#yearFilter').on('change', function() {
            const selectedYear = $(this).val();

            if (selectedYear === 'all') {
                // Show all months and tabs
                $('#monthTabs li').show();
                $('.month-table').removeClass('d-none');
                // Render summary for the first available year in the list
                const firstYear = yearsList.length ? yearsList[0] : new Date().getFullYear();
                renderSummaryTable(firstYear);
            } else {
                // Filter month tabs by selected year
                $('#monthTabs li').each(function() {
                    const tabMonth = $(this).find('a').data('month');
                    if (tabMonth === 'summary' || tabMonth === 'graph') {
                        $(this).show();
                    } else if (typeof tabMonth === 'string') {
                        const tabYear = tabMonth.split('-')[0];
                        if (tabYear === selectedYear) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    }
                });

                // Show tables for selected year only
                $('.month-table').addClass('d-none');
                $('.month-table').each(function() {
                    const tableMonth = $(this).data('month');
                    const tableYear = $(this).data('year');

                    if (tableMonth === 'summary') {
                        $(this).removeClass('d-none');
                    } else if (tableMonth === 'graph') {
                        $(this).removeClass('d-none');
                    } else {
                        if (String(tableYear) === String(selectedYear)) {
                            $(this).removeClass('d-none');
                        }
                    }
                });

                // Render summary for the selected year
                renderSummaryTable(selectedYear);
            }

            // Activate first visible tab
            const firstVisibleTab = $('#monthTabs a:visible').first();
            if (firstVisibleTab.length) {
                const visibleMonth = firstVisibleTab.data('month');
                showMonth(visibleMonth);
            }
        });

        // Clear year filter
        $('#clearYearFilter').on('click', function() {
            $('#yearFilter').val('all').trigger('change');
        });

        // Initialize DataTables for month tables
        $('.month-cnf-table').DataTable({
            paging: false,
            searching: true,
            ordering: true,
            info: false,
            autoWidth: false,
            scrollX: true,
            dom: '<"row"<"col-sm-12"f>>rtip'
        });
        
        // Initialize summary table with currently selected year
        const initYear = $('#yearFilter').val() === 'all' ? (yearsList.length ? yearsList[0] : new Date().getFullYear()) : $('#yearFilter').val();
        renderSummaryTable(initYear);

        // Initialize with first month or default to summary if no months
        let initialMonth = 'summary';
        const firstVisibleTab = $('#monthTabs a:visible').first();
        if (firstVisibleTab.length) {
            initialMonth = firstVisibleTab.data('month');
        }
        showMonth(initialMonth);
    });
</script>
</body>
</html>