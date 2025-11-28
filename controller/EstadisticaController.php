<?php
/**
 * Controlador de Estadísticas - PetZone
 */

require_once __DIR__ . '/../model/EstadisticaModel.php';

class EstadisticaController {
    private $model;
    
    public function __construct() {
        $this->model = new EstadisticaModel();
        session_start();
    }
    
    public function index() {
        $this->get();
    }
    
    public function get() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $stats = $this->model->obtenerEstadisticasDashboard();
            $this->jsonResponse(['success' => true, 'stats' => $stats]);
            
        } catch (Exception $e) {
            error_log("EstadisticaController - get Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }


    //
    // function jsonResponse($data, $statusCode = 200) {
    //     http_response_code($statusCode);
    //     echo json_encode($data);
    //     exit;
    // }
}

