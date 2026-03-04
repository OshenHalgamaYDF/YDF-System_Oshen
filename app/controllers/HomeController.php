<?php
require_once APP_PATH . '/core/Controller.php';

class HomeController extends Controller {
    public function index() {
        requireAuth();
        
        // Include the database connection
        $conn = getDBConnection();
        
        // Fetch statistics
        $stats = [];
        $recentOrders = [];
        
        try {
            // Total Customers
            $sql = "SELECT COUNT(*) AS total FROM customers";
            $result = $conn->query($sql);
            $stats['customers'] = $result->fetch_assoc()['total'];
            
            // Total Orders
            $sql = "SELECT COUNT(*) AS total FROM orders";
            $result = $conn->query($sql);
            $stats['orders'] = $result->fetch_assoc()['total'];
            
            // Total Products
            $sql = "SELECT COUNT(*) AS total FROM products";
            $result = $conn->query($sql);
            $stats['products'] = $result->fetch_assoc()['total'];
            
            // Total Suppliers
            $sql = "SELECT COUNT(*) AS total FROM suppliers";
            $result = $conn->query($sql);
            $stats['suppliers'] = $result->fetch_assoc()['total'];
            
            // Recent Orders
            $sql = "SELECT o.id, c.name AS customer, o.arrival_date 
                    FROM orders o 
                    JOIN customers c ON o.customer_id = c.id 
                    ORDER BY o.arrival_date DESC 
                    LIMIT 5";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $recentOrders = $result->fetch_all(MYSQLI_ASSOC);
            }

            // --- Exchange rates for dashboard ---
            $exchangeRates = [];
            $sql = "SELECT c.code, er.rate_to_lkr, er.source 
                    FROM exchange_rates er 
                    JOIN currencies c ON er.currency_id = c.currency_id 
                    WHERE er.rate_date = CURDATE()";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $code = $row['code'];
                $src  = $row['source'];
                $rate = $row['rate_to_lkr'];
                if (!isset($exchangeRates[$code])) {
                    $exchangeRates[$code] = [];
                }
                $exchangeRates[$code][$src] = $rate;
            }
        } catch (Exception $e) {
            // Handle error
            $error = "Database error: " . $e->getMessage();
        }
        
        $this->view('home', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'exchangeRates' => $exchangeRates ?? [],
            'error' => $error ?? null,
            'pageTitle' => 'Dashboard - YDF'
        ]);
    }
}