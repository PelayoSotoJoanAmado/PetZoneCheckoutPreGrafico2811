<?php
/**
 * Controlador de Servicios - PetZone
 */

require_once __DIR__ . '/../model/ServiciosModel.php';

class ServiciosController {
    private $model;
    
    public function __construct() {
        $this->model = new ServiciosModel();
    }
    
    public function index() {
        $this->list();
    }
    
    public function list() {
        try {
            $servicios = $this->model->listarServicios();
            $this->jsonResponse(['success' => true, 'servicios' => $servicios]);
        } catch (Exception $e) {
            error_log("ServiciosController - list Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        try {
            $id = $_GET['id'] ?? null;
            $slug = $_GET['slug'] ?? null;
            
            if (!$id && !$slug) {
                $this->jsonResponse(['success' => false, 'message' => 'ID o slug requerido'], 400);
                return;
            }
            
            $servicio = $this->model->obtenerServicio($id, $slug);
            
            if ($servicio) {
                $this->jsonResponse(['success' => true, 'servicio' => $servicio]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Servicio no encontrado'], 404);
            }
        } catch (Exception $e) {
            error_log("ServiciosController - get Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function disponibles() {
        try {
            $servicios = $this->model->obtenerServiciosDisponibles();
            $this->jsonResponse(['success' => true, 'servicios' => $servicios]);
        } catch (Exception $e) {
            error_log("ServiciosController - disponibles Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}