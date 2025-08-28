<?php
require_once APP_PATH . '/core/Controller.php';

class CustomersController extends Controller {
    public function manage() {
        requireAdmin();
        
        $conn = getDBConnection();
        $message = '';
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $message = "Invalid form submission";
            } else {
                if (isset($_POST['create_customer'])) {
                    $message = $this->createCustomer($conn, $_POST);
                } elseif (isset($_POST['update_customer'])) {
                    $message = $this->updateCustomer($conn, $_POST);
                }
            }
        }
        
        // Handle delete requests
        if (isset($_GET['delete_id'])) {
            $message = $this->deleteCustomer($conn, $_GET['delete_id']);
        }
        
        // Get all customers
        $customers = $conn->query("SELECT * FROM customers ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('customers/manage', [
            'customers' => $customers,
            'message' => $message,
            'pageTitle' => 'Manage Customers',
            'csrf_token' => generateCsrfToken()
        ]);
    }
    
    private function createCustomer($conn, $data) {
        $customerName = trim($data['customer_name']);
        
        $insertQuery = "INSERT INTO customers (name) 
                        VALUES (?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("s", $customerName);
        
        if ($stmt->execute()) {
            return "Customer added successfully!";
        } else {
            return "Error adding customer: " . $conn->error;
        }
    }
    
    private function updateCustomer($conn, $data) {
        $customerId = intval($data['id']);
        $customerName = trim($data['customer_name']);
        
        $updateQuery = "UPDATE customers SET 
                        name = ?
                        WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $customerName, $customerId);
        
        if ($stmt->execute()) {
            return "Customer updated successfully!";
        } else {
            return "Error updating customer: " . $conn->error;
        }
    }
    
    private function deleteCustomer($conn, $customerId) {
        $customerId = intval($customerId);
        
        $deleteQuery = "DELETE FROM customers WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $customerId);
        
        if ($stmt->execute()) {
            return "Customer deleted successfully!";
        } else {
            return "Error deleting customer: " . $conn->error;
        }
    }
}