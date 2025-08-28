<?php
require_once APP_PATH . '/core/Controller.php';

class ProductsController extends Controller {
    public function manage() {
        requireAdmin();
        
        $conn = getDBConnection();
        $message = '';
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $message = "Invalid form submission";
            } else {
                if (isset($_POST['create_product'])) {
                    $message = $this->createProduct($conn, $_POST);
                } elseif (isset($_POST['update_product'])) {
                    $message = $this->updateProduct($conn, $_POST);
                }
            }
        }
        
        // Handle delete requests
        if (isset($_GET['delete_id'])) {
            $message = $this->deleteProduct($conn, $_GET['delete_id']);
        }
        
        // Get all products
        $products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('products/manage', [
            'products' => $products,
            'message' => $message,
            'pageTitle' => 'Manage Products',
            'csrf_token' => generateCsrfToken()
        ]);
    }
    
    private function createProduct($conn, $data) {
        $productCode = trim($data['product_code']);
        $productName = trim($data['product_name']);
        $scientificName = trim($data['scientific_name'] ?? '');
        $category = trim($data['category'] ?? '');
        $recoveryPercentage = floatval($data['recovery_percentage']);
        $cfName = trim($data['cf_name'] ?? '');
        
        $insertQuery = "INSERT INTO products (product_code, product_name, scientific_name, category, recovery_percentage, cf_name) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssssds", $productCode, $productName, $scientificName, $category, $recoveryPercentage, $cfName);
        
        if ($stmt->execute()) {
            return "Product added successfully!";
        } else {
            return "Error adding product: " . $conn->error;
        }
    }
    
    private function updateProduct($conn, $data) {
        $productId = intval($data['id']);
        $productCode = trim($data['product_code']);
        $productName = trim($data['product_name']);
        $scientificName = trim($data['scientific_name'] ?? '');
        $category = trim($data['category'] ?? '');
        $recoveryPercentage = floatval($data['recovery_percentage']);
        $cfName = trim($data['cf_name'] ?? '');
        
        $updateQuery = "UPDATE products SET 
                        product_code = ?, 
                        product_name = ?, 
                        scientific_name = ?,
                        category = ?, 
                        recovery_percentage = ?,
                        cf_name = ?
                        WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssdsi", $productCode, $productName, $scientificName, $category, $recoveryPercentage, $cfName, $productId);
        
        if ($stmt->execute()) {
            return "Product updated successfully!";
        } else {
            return "Error updating product: " . $conn->error;
        }
    }
    
    private function deleteProduct($conn, $productId) {
        $productId = intval($productId);
        
        $deleteQuery = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $productId);
        
        if ($stmt->execute()) {
            return "Product deleted successfully!";
        } else {
            return "Error deleting product: " . $conn->error;
        }
    }
}