<?php
/**
 * Controlador de Citas - PetZone
 * Maneja las solicitudes de citas del formulario de reserva
 */

require_once __DIR__ . '/../model/CitaModel.php';

class CitaController {
    private $model;
    
    public function __construct() {
        $this->model = new CitaModel();
        session_start();
    }
    
    public function index() {
        $this->list();
    }
    
    public function list() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $filtro = $_GET['filtro'] ?? 'todas';
            $busqueda = $_GET['busqueda'] ?? '';
            $limite = (int)($_GET['limite'] ?? 50);
            $pagina = (int)($_GET['pagina'] ?? 1);
            
            $resultado = $this->model->listarCitas($filtro, $busqueda, $limite, $pagina);
            
            $this->jsonResponse([
                'success' => true, 
                'citas' => $resultado['citas'],
                'total' => $resultado['total'],
                'pagina' => $pagina,
                'totalPaginas' => $resultado['totalPaginas']
            ]);
            
        } catch (Exception $e) {
            error_log("CitaController - list Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
                return;
            }
            
            $cita = $this->model->obtenerCita($id);
            
            if ($cita) {
                $this->jsonResponse(['success' => true, 'cita' => $cita]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Cita no encontrada'], 404);
            }
            
        } catch (Exception $e) {
            error_log("CitaController - get Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function create() {
        try {
            // Público - crear cita desde formulario
            $data = $_POST;
            if (empty($data)) {
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
            }
            
            $nombre = $this->sanitize($data['nombre'] ?? '');
            $correo = $this->sanitize($data['correo'] ?? '');
            $telefono = $this->sanitize($data['telefono'] ?? '');
            $servicio = $this->sanitize($data['servicio'] ?? '');
            $mensaje = $this->sanitize($data['mensaje'] ?? '');
            
            // Validaciones
            if (empty($nombre) || empty($correo) || empty($telefono) || empty($servicio)) {
                $this->jsonResponse(['success' => false, 'message' => 'Todos los campos son requeridos excepto el mensaje'], 400);
                return;
            }
            
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(['success' => false, 'message' => 'Correo electrónico inválido'], 400);
                return;
            }
            
            $resultado = $this->model->crearCita([
                'nombre' => $nombre,
                'correo' => $correo,
                'telefono' => $telefono,
                'servicio' => $servicio,
                'mensaje' => $mensaje,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Cita registrada exitosamente. Te contactaremos pronto.',
                'codigo_cita' => $resultado['codigo_cita'],
                'id' => $resultado['id']
            ]);
            
        } catch (Exception $e) {
            error_log("CitaController - create Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al procesar la solicitud'], 500);
        }
    }
    
    public function update() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                $data = $_POST;
            }
            
            $id = (int)($data['id'] ?? 0);
            if ($id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            $nombre = $this->sanitize($data['nombre'] ?? '');
            $correo = $this->sanitize($data['correo'] ?? '');
            $telefono = $this->sanitize($data['telefono'] ?? '');
            $servicio = $this->sanitize($data['servicio'] ?? '');
            $mensaje = $this->sanitize($data['mensaje'] ?? '');
            $estado = $this->sanitize($data['estado'] ?? 'pendiente');
            
            $result = $this->model->actualizarCita($id, [
                'nombre' => $nombre,
                'correo' => $correo,
                'telefono' => $telefono,
                'servicio' => $servicio,
                'mensaje' => $mensaje,
                'estado' => $estado
            ], $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Cita actualizada exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar cita'], 500);
            }
            
        } catch (Exception $e) {
            error_log("CitaController - update Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function delete() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            
            if ($id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            $result = $this->model->eliminarCita($id, $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Cita eliminada exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar cita'], 500);
            }
            
        } catch (Exception $e) {
            error_log("CitaController - delete Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update_estado() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            $estado = $this->sanitize($data['estado'] ?? '');
            
            $estadosValidos = ['pendiente', 'confirmada', 'completada', 'cancelada'];
            
            if ($id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            if (!in_array($estado, $estadosValidos)) {
                $this->jsonResponse(['success' => false, 'message' => 'Estado inválido'], 400);
                return;
            }
            
            $result = $this->model->actualizarEstadoCita($id, $estado, $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Estado actualizado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar estado'], 500);
            }
            
        } catch (Exception $e) {
            error_log("CitaController - update_estado Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function stats() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $estadisticas = $this->model->obtenerEstadisticas();
            
            $this->jsonResponse([
                'success' => true,
                'stats' => $estadisticas['stats'],
                'servicios_top' => $estadisticas['servicios_top'],
                'citas_recientes' => $estadisticas['citas_recientes']
            ]);
            
        } catch (Exception $e) {
            error_log("CitaController - stats Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
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