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
    
    /**
     * Obtener productos por categoría para gráfico
     */
    public function productosPorCategoria() {
        try {
            $datos = $this->model->obtenerProductosPorCategoria();
            
            // Formatear datos para Highcharts
            $chartData = array_map(function($item) {
                return [
                    'name' => $item['categoria'],
                    'y' => (int)$item['total']
                ];
            }, $datos);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $chartData
            ]);
            
        } catch (Exception $e) {
            error_log("EstadisticaController - productosPorCategoria Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener reservas por servicio para gráfico
     */
    public function reservasPorServicio() {
        try {
            $dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 30;
            $datos = $this->model->obtenerReservasPorServicio($dias);
            
            // Formatear datos para Highcharts
            $categories = array_column($datos, 'servicio');
            $values = array_map(function($item) {
                return (int)$item['total'];
            }, $datos);
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'values' => $values
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("EstadisticaController - reservasPorServicio Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener ventas mensuales para gráfico
     */
    public function ventasMensuales() {
        try {
            $meses = isset($_GET['meses']) ? (int)$_GET['meses'] : 6;
            $datos = $this->model->obtenerVentasMensuales($meses);
            
            // Formatear datos para Highcharts
            $categories = array_column($datos, 'mes_nombre');
            $ventas = array_map(function($item) {
                return (float)$item['total_ventas'];
            }, $datos);
            $pedidos = array_map(function($item) {
                return (int)$item['num_pedidos'];
            }, $datos);
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'ventas' => $ventas,
                    'pedidos' => $pedidos
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("EstadisticaController - ventasMensuales Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtener productos más vendidos para gráfico
     */
    public function productosMasVendidos() {
        try {
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 5;
            $datos = $this->model->obtenerProductosMasVendidos($limite);
            
            // Formatear datos para Highcharts
            $categories = array_column($datos, 'producto');
            $values = array_map(function($item) {
                return (int)$item['total_vendido'];
            }, $datos);
            
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'values' => $values
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("EstadisticaController - productosMasVendidos Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}