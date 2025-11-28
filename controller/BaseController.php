<?php
abstract class BaseController {
    protected $model;
    protected $requireAuth = true;
    
    public function __construct() {
        if ($this->requireAuth) {
            $this->checkAuth();
        }
    }
    
    protected function checkAuth() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
        }
    }
    
    protected function getRequestData() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        return $data ?? [];
    }
    
    protected function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    protected function registrarActividad($accion, $modulo, $detalle = null) {
        if (!isset($_SESSION['user_id'])) return;
        
        try {
            $db = $this->model->db;
            $stmt = $db->prepare("
                INSERT INTO actividad_admin (usuario_id, accion, modulo, detalle, ip_address) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $accion,
                $modulo,
                $detalle,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar actividad: " . $e->getMessage());
        }
    }
    
    protected function handleImageUpload($file, $folder) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('Archivo demasiado grande (máx 5MB)');
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
        $rutaDestino = __DIR__ . "/../IMG/{$folder}/";
        
        if (!file_exists($rutaDestino)) {
            mkdir($rutaDestino, 0777, true);
        }
        
        $rutaCompleta = $rutaDestino . $nombreArchivo;
        
        if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            return "IMG/{$folder}/{$nombreArchivo}";
        }
        
        throw new Exception('Error al subir imagen');
    }
    
    protected function deleteImage($imagePath) {
        if ($imagePath) {
            $fullPath = __DIR__ . "/../{$imagePath}";
            if (file_exists($fullPath)) {
                return unlink($fullPath);
            }
        }
        return false;
    }
}