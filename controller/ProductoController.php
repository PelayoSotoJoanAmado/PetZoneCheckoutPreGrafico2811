<?php
/**
 * Controlador de Productos - PetZone
 */

require_once __DIR__ . '/../model/ProductoModel.php';

class ProductoController {
    private $model;
    
    public function __construct() {
        $this->model = new ProductoModel();
        session_start();
    }
    
    public function index() {
        $this->list();
    }
    
    public function list() {
        try {
            $categoria = $_GET['categoria'] ?? '';
            $busqueda = $_GET['busqueda'] ?? '';
            
            $productos = $this->model->listarProductos($categoria, $busqueda);
            
            $this->jsonResponse(['success' => true, 'productos' => $productos]);
            
        } catch (Exception $e) {
            error_log("ProductoController - list Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
                return;
            }
            
            $producto = $this->model->obtenerProducto($id);
            
            if ($producto) {
                $this->jsonResponse(['success' => true, 'producto' => $producto]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
            
        } catch (Exception $e) {
            error_log("ProductoController - get Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function create() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $nombre = $this->sanitize($_POST['nombre'] ?? '');
            $descripcion = $this->sanitize($_POST['descripcion'] ?? '');
            $categoria_id = (int)($_POST['categoria_id'] ?? 0);
            $precio = (float)($_POST['precio'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $codigo_sku = $this->sanitize($_POST['codigo_sku'] ?? '');
            $destacado = isset($_POST['destacado']) ? 1 : 0;
            
            if (empty($nombre) || $categoria_id == 0 || $precio <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            // Procesar imagen
            $imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen = $this->uploadImagen($_FILES['imagen'], 'productos');
            }
            
            $id = $this->model->crearProducto([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'categoria_id' => $categoria_id,
                'precio' => $precio,
                'stock' => $stock,
                'imagen' => $imagen,
                'codigo_sku' => $codigo_sku,
                'destacado' => $destacado
            ], $_SESSION['user_id']);
            
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Producto creado exitosamente', 
                'id' => $id
            ]);
            
        } catch (Exception $e) {
            error_log("ProductoController - create Error: " . $e->getMessage());
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
            
            $nombre = $this->sanitize($_POST['nombre'] ?? '');
            $descripcion = $this->sanitize($_POST['descripcion'] ?? '');
            $categoria_id = (int)($_POST['categoria_id'] ?? 0);
            $precio = (float)($_POST['precio'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $codigo_sku = $this->sanitize($_POST['codigo_sku'] ?? '');
            $destacado = isset($_POST['destacado']) ? 1 : 0;
            
            // Procesar nueva imagen si se subió
            $imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen = $this->uploadImagen($_FILES['imagen'], 'productos');
            }
            
            $result = $this->model->actualizarProducto($id, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'categoria_id' => $categoria_id,
                'precio' => $precio,
                'stock' => $stock,
                'imagen' => $imagen,
                'codigo_sku' => $codigo_sku,
                'destacado' => $destacado
            ], $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Producto actualizado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar producto'], 500);
            }
            
        } catch (Exception $e) {
            error_log("ProductoController - update Error: " . $e->getMessage());
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
            
            $result = $this->model->eliminarProducto($id, $_SESSION['user_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Producto eliminado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar producto'], 500);
            }
            
        } catch (Exception $e) {
            error_log("ProductoController - delete Error: " . $e->getMessage());
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
        $rutaDestino = __DIR__ . "../../public/img/{$carpeta}/";
        
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