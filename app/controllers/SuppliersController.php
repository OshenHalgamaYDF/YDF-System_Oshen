<?php
require_once APP_PATH . '/core/Controller.php';

class SuppliersController extends Controller {
    public function manage() {
        requireAdmin();
        
        $conn = getDBConnection();
        $message = '';
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $message = "Invalid form submission";
            } else {
                if (isset($_POST['create_supplier'])) {
                    $message = $this->createSupplier($conn, $_POST);
                } elseif (isset($_POST['update_supplier'])) {
                    $message = $this->updateSupplier($conn, $_POST);
                }
            }
        }
        
        // Handle delete requests
        if (isset($_GET['delete_id'])) {
            $message = $this->deleteSupplier($conn, $_GET['delete_id']);
        }
        
        // Get all suppliers
        $suppliers = $conn->query("SELECT * FROM suppliers ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('suppliers/manage', [
            'suppliers' => $suppliers,
            'message' => $message,
            'pageTitle' => 'Manage Suppliers',
            'csrf_token' => generateCsrfToken()
        ]);
    }
    
    private function createSupplier($conn, $data) {
        $supplierName = trim($data['supplier_name']);
        $supplierPhone = trim($data['supplier_phone'] ?? '');
        
        $insertQuery = "INSERT INTO suppliers (name, phone) 
                        VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ss", $supplierName, $supplierPhone);
        
        if ($stmt->execute()) {
            return "Supplier added successfully!";
        } else {
            return "Error adding supplier: " . $conn->error;
        }
    }
    
    private function updateSupplier($conn, $data) {
        $supplierId = intval($data['id']);
        $supplierName = trim($data['supplier_name']);
        $supplierPhone = trim($data['supplier_phone'] ?? '');
        
        $updateQuery = "UPDATE suppliers SET 
                        name = ?, 
                        phone = ?
                        WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $supplierName, $supplierPhone, $supplierId);
        
        if ($stmt->execute()) {
            return "Supplier updated successfully!";
        } else {
            return "Error updating supplier: " . $conn->error;
        }
    }
    
    private function deleteSupplier($conn, $supplierId) {
        $supplierId = intval($supplierId);
        
        $deleteQuery = "DELETE FROM suppliers WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $supplierId);
        
        if ($stmt->execute()) {
            return "Supplier deleted successfully!";
        } else {
            return "Error deleting supplier: " . $conn->error;
        }
    }
}