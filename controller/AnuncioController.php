<?php
/**
 * Controlador de Anuncios - PetZone
 *  VERSIÓN CORREGIDA - Problema del checkbox resuelto
 */

require_once __DIR__ . '/../model/AnuncioModel.php';

class AnuncioController {
    private $model;
    
    public function __construct() {
        $this->model = new AnuncioModel();
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
            
            $anuncios = $this->model->listarAnuncios();
            $this->jsonResponse(['success' => true, 'anuncios' => $anuncios]);
            
        } catch (Exception $e) {
            error_log("AnuncioController - list Error: " . $e->getMessage());
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
            
            $anuncio = $this->model->obtenerAnuncio($id);
            
            if ($anuncio) {
                $this->jsonResponse(['success' => true, 'anuncio' => $anuncio]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Anuncio no encontrado'], 404);
            }
            
        } catch (Exception $e) {
            error_log("AnuncioController - get Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function create() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            // Log para debug
            error_log("AnuncioController - create Data: " . print_r($data, true));
            
            $mensaje = $this->sanitize($data['mensaje'] ?? '');
            $tipo = $this->sanitize($data['tipo'] ?? 'aviso_general');
            $color_fondo = $this->sanitize($data['color_fondo'] ?? '#23906F');
            $color_texto = $this->sanitize($data['color_texto'] ?? '#FFFFFF');
            $icono = $this->sanitize($data['icono'] ?? '');
            $velocidad = (int)($data['velocidad'] ?? 30);
            $prioridad = (int)($data['prioridad'] ?? 0);
            
            //  CORRECCIÓN CRÍTICA: Convertir explícitamente a entero
            $activo = isset($data['activo']) ? (int)$data['activo'] : 1;
            
            error_log("CREATE - activo recibido: " . var_export($data['activo'], true));
            error_log("CREATE - activo convertido: " . $activo);
            
            if (empty($mensaje)) {
                $this->jsonResponse(['success' => false, 'message' => 'Mensaje requerido'], 400);
                return;
            }
            
            $id = $this->model->crearAnuncio([
                'mensaje' => $mensaje,
                'tipo' => $tipo,
                'color_fondo' => $color_fondo,
                'color_texto' => $color_texto,
                'icono' => $icono,
                'velocidad' => $velocidad,
                'prioridad' => $prioridad,
                'activo' => $activo
            ], $_SESSION['user_id']);
            
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Anuncio creado exitosamente',
                'id' => $id
            ]);
            
        } catch (Exception $e) {
            error_log("AnuncioController - create Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            // Log para debug
            error_log("AnuncioController - update Data: " . print_r($data, true));
            
            $id = (int)($data['id'] ?? 0);
            
            if ($id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            $mensaje = $this->sanitize($data['mensaje'] ?? '');
            $tipo = $this->sanitize($data['tipo'] ?? 'aviso_general');
            $color_fondo = $this->sanitize($data['color_fondo'] ?? '#23906F');
            $color_texto = $this->sanitize($data['color_texto'] ?? '#FFFFFF');
            $icono = $this->sanitize($data['icono'] ?? '');
            $velocidad = (int)($data['velocidad'] ?? 30);
            $prioridad = (int)($data['prioridad'] ?? 0);
            
            //  CORRECCIÓN CRÍTICA: Manejar correctamente el valor booleano/entero
            $activo = isset($data['activo']) ? (int)$data['activo'] : 0;
            
            // Log detallado para debug
            error_log("UPDATE - ID: " . $id);
            error_log("UPDATE - activo recibido (raw): " . var_export($data['activo'], true));
            error_log("UPDATE - activo tipo: " . gettype($data['activo']));
            error_log("UPDATE - activo convertido: " . $activo);
            
            if (empty($mensaje)) {
                $this->jsonResponse(['success' => false, 'message' => 'Mensaje requerido'], 400);
                return;
            }
            
            $result = $this->model->actualizarAnuncio($id, [
                'mensaje' => $mensaje,
                'tipo' => $tipo,
                'color_fondo' => $color_fondo,
                'color_texto' => $color_texto,
                'icono' => $icono,
                'velocidad' => $velocidad,
                'prioridad' => $prioridad,
                'activo' => $activo
            ], $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Anuncio actualizado exitosamente',
                    'debug' => [
                        'id' => $id,
                        'activo_enviado' => $activo
                    ]
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar anuncio'], 500);
            }
            
        } catch (Exception $e) {
            error_log("AnuncioController - update Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function delete() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = (int)($data['id'] ?? 0);
            
            if ($id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            $result = $this->model->eliminarAnuncio($id, $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Anuncio eliminado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar anuncio'], 500);
            }
            
        } catch (Exception $e) {
            error_log("AnuncioController - delete Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function activos() {
        try {
            // Este endpoint es público, no requiere autenticación
            $anuncios = $this->model->obtenerAnunciosActivos();
            $this->jsonResponse(['success' => true, 'anuncios' => $anuncios]);
            
        } catch (Exception $e) {
            error_log("AnuncioController - activos Error: " . $e->getMessage());
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