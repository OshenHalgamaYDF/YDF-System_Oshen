<?php
class Controller {
    protected function view($view, $data = []) {
        // Extract data to variables
        extract($data);
        
        // Include the view file
        $viewPath = APP_PATH . "/views/{$view}.php";
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new Exception("View {$view} not found");
        }
    }
    
    protected function redirect($url) {
        header("Location: " . BASE_URL . "/{$url}");
        exit();
    }
    
    protected function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}