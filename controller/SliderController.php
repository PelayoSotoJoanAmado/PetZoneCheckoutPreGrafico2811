<?php
/**
 * Controlador de Sliders - PetZone
 */

require_once __DIR__ . '/../model/SliderModel.php';

class SliderController {
    private $model;
    
    public function __construct() {
        $this->model = new SliderModel();
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
            
            $sliders = $this->model->listarSliders();
            $this->jsonResponse(['success' => true, 'sliders' => $sliders]);
            
        } catch (Exception $e) {
            error_log("SliderController - list Error: " . $e->getMessage());
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
            
            $slider = $this->model->obtenerSlider($id);
            
            if ($slider) {
                $this->jsonResponse(['success' => true, 'slider' => $slider]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Slider no encontrado'], 404);
            }
            
        } catch (Exception $e) {
            error_log("SliderController - get Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function create() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $titulo = $this->sanitize($_POST['titulo'] ?? '');
            $descripcion = $this->sanitize($_POST['descripcion'] ?? '');
            $enlace = $this->sanitize($_POST['enlace'] ?? '');
            $posicion = $this->sanitize($_POST['posicion'] ?? 'principal');
            $orden = (int)($_POST['orden'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            if (empty($titulo)) {
                $this->jsonResponse(['success' => false, 'message' => 'Título requerido'], 400);
                return;
            }
            
            // Procesar imagen
            $imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPF_ERR_OK) {
                $imagen = $this->uploadImagen($_FILES['imagen'], 'sliders');
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Imagen requerida'], 400);
                return;
            }
            
            $result = $this->model->crearSlider([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'imagen' => $imagen,
                'enlace' => $enlace,
                'posicion' => $posicion,
                'orden' => $orden,
                'activo' => $activo
            ], $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Slider creado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al crear slider'], 500);
            }
            
        } catch (Exception $e) {
            error_log("SliderController - create Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            $titulo = $this->sanitize($_POST['titulo'] ?? '');
            $descripcion = $this->sanitize($_POST['descripcion'] ?? '');
            $enlace = $this->sanitize($_POST['enlace'] ?? '');
            $posicion = $this->sanitize($_POST['posicion'] ?? 'principal');
            $orden = (int)($_POST['orden'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            // Procesar nueva imagen si se subió
            $imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK && $_FILES['imagen']['size'] > 0) {
                $imagen = $this->uploadImagen($_FILES['imagen'], 'sliders');
            }
            
            $result = $this->model->actualizarSlider($id, [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'imagen' => $imagen,
                'enlace' => $enlace,
                'posicion' => $posicion,
                'orden' => $orden,
                'activo' => $activo
            ], $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Slider actualizado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar slider'], 500);
            }
            
        } catch (Exception $e) {
            error_log("SliderController - update Error: " . $e->getMessage());
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
            
            $result = $this->model->eliminarSlider($id, $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Slider eliminado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar slider'], 500);
            }
            
        } catch (Exception $e) {
            error_log("SliderController - delete Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function activos() {
        try {
            // Este endpoint es público, no requiere autenticación
            $sliders = $this->model->obtenerSlidersActivos();
            $this->jsonResponse(['success' => true, 'sliders' => $sliders]);
            
        } catch (Exception $e) {
            error_log("SliderController - activos Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function uploadImagen($file, $carpeta) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('El archivo es demasiado grande (máx 5MB)');
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
        $rutaDestino = __DIR__ . "/../public/img/{$carpeta}/";
        
        if (!file_exists($rutaDestino)) {
            mkdir($rutaDestino, 0777, true);
        }
        
        $rutaCompleta = $rutaDestino . $nombreArchivo;
        
        if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            return "img/{$carpeta}/{$nombreArchivo}";
        } else {
            throw new Exception('Error al subir imagen');
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