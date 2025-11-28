<?php
/**
 * Controlador de Categorías - PetZone
 */

require_once __DIR__ . '/../model/CategoriaModel.php';

class CategoriaController {
    private $model;
    
    public function __construct() {
        $this->model = new CategoriaModel();
    }
    
    public function index() {
        $this->list();
    }
    
    public function list() {
        try {
            $categorias = $this->model->listarCategorias();
            
            $this->jsonResponse([
                'success' => true, 
                'categorias' => $categorias,
                'total' => count($categorias)
            ]);
            
        } catch (Exception $e) {
            error_log("CategoriaController - list Error: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error al cargar categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}