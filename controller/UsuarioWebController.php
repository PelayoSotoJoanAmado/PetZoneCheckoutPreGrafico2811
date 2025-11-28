<?php
/**
 * Controlador de Usuarios Web (Clientes) - PetZone
 */

require_once __DIR__ . '/../model/UsuarioWebModel.php';

class UsuarioWebController {
    private $model;
    
    public function __construct() {
        $this->model = new UsuarioWebModel();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    public function index() {
        $this->jsonResponse(['success' => false, 'message' => 'Acción no especificada'], 400);
    }
    
    /**
     * Registrar nuevo usuario
     */
    public function registrar() {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $nombre = $this->sanitize($data['nombre'] ?? '');
            $email = $this->sanitize($data['email'] ?? '');
            $telefono = $this->sanitize($data['telefono'] ?? '');
            $password = $data['password'] ?? '';
            
            // Validaciones
            if (empty($nombre) || empty($email) || empty($telefono) || empty($password)) {
                $this->jsonResponse(['success' => false, 'message' => 'Todos los campos son obligatorios'], 400);
                return;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(['success' => false, 'message' => 'Email inválido'], 400);
                return;
            }
            
            if (strlen($password) < 6) {
                $this->jsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
                return;
            }
            
            if (!preg_match('/^[9]\d{8}$/', $telefono)) {
                $this->jsonResponse(['success' => false, 'message' => 'Teléfono inválido (9 dígitos, empieza con 9)'], 400);
                return;
            }
            
            $result = $this->model->registrar([
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'password' => $password
            ]);
            
            if ($result['success']) {
                // Auto-login después del registro
                $_SESSION['usuario_web_id'] = $result['user_id'];
                $_SESSION['usuario_web_email'] = $email;
                $_SESSION['usuario_web_nombre'] = $nombre;
            }
            
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - registrar Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error del servidor'], 500);
        }
    }
    
    /**
     * Iniciar sesión
     */
    public function login() {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $email = $this->sanitize($data['email'] ?? '');
            $password = $data['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $this->jsonResponse(['success' => false, 'message' => 'Email y contraseña requeridos'], 400);
                return;
            }
            
            $result = $this->model->login($email, $password);
            
            if ($result['success']) {
                $_SESSION['usuario_web_id'] = $result['user']['id'];
                $_SESSION['usuario_web_email'] = $result['user']['email'];
                $_SESSION['usuario_web_nombre'] = $result['user']['nombre'];
            }
            
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - login Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error del servidor'], 500);
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        try {
            unset($_SESSION['usuario_web_id']);
            unset($_SESSION['usuario_web_email']);
            unset($_SESSION['usuario_web_nombre']);
            
            $this->jsonResponse(['success' => true, 'message' => 'Sesión cerrada']);
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - logout Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Verificar sesión activa
     */
    public function check() {
        try {
            if (isset($_SESSION['usuario_web_id'])) {
                $user = $this->model->obtenerUsuario($_SESSION['usuario_web_id']);
                
                if ($user) {
                    $this->jsonResponse([
                        'authenticated' => true,
                        'user' => $user
                    ]);
                    return;
                }
            }
            
            $this->jsonResponse(['authenticated' => false]);
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - check Error: " . $e->getMessage());
            $this->jsonResponse(['authenticated' => false]);
        }
    }
    
    /**
     * Obtener perfil del usuario
     */
    public function perfil() {
        try {
            if (!isset($_SESSION['usuario_web_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $user = $this->model->obtenerUsuario($_SESSION['usuario_web_id']);
            $direcciones = $this->model->obtenerDirecciones($_SESSION['usuario_web_id']);
            $pedidos = $this->model->obtenerHistorialPedidos($_SESSION['usuario_web_id']);
            
            $this->jsonResponse([
                'success' => true,
                'user' => $user,
                'direcciones' => $direcciones,
                'pedidos' => $pedidos
            ]);
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - perfil Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Actualizar perfil
     */
    public function actualizarPerfil() {
        try {
            if (!isset($_SESSION['usuario_web_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $nombre = $this->sanitize($data['nombre'] ?? '');
            $telefono = $this->sanitize($data['telefono'] ?? '');
            
            if (empty($nombre) || empty($telefono)) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            $result = $this->model->actualizarPerfil($_SESSION['usuario_web_id'], [
                'nombre' => $nombre,
                'telefono' => $telefono
            ]);
            
            if ($result) {
                $_SESSION['usuario_web_nombre'] = $nombre;
                $this->jsonResponse(['success' => true, 'message' => 'Perfil actualizado']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar'], 500);
            }
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - actualizarPerfil Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword() {
        try {
            if (!isset($_SESSION['usuario_web_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $passwordActual = $data['password_actual'] ?? '';
            $passwordNueva = $data['password_nueva'] ?? '';
            
            if (empty($passwordActual) || empty($passwordNueva)) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            if (strlen($passwordNueva) < 6) {
                $this->jsonResponse(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'], 400);
                return;
            }
            
            $result = $this->model->cambiarPassword(
                $_SESSION['usuario_web_id'],
                $passwordActual,
                $passwordNueva
            );
            
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - cambiarPassword Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Guardar dirección
     */
    public function guardarDireccion() {
        try {
            if (!isset($_SESSION['usuario_web_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $result = $this->model->guardarDireccion($_SESSION['usuario_web_id'], [
                'nombre_direccion' => $this->sanitize($data['nombre_direccion'] ?? ''),
                'departamento' => $this->sanitize($data['departamento'] ?? ''),
                'distrito' => $this->sanitize($data['distrito'] ?? ''),
                'direccion_completa' => $this->sanitize($data['direccion_completa'] ?? ''),
                'es_predeterminada' => $data['es_predeterminada'] ?? false
            ]);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Dirección guardada']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al guardar'], 500);
            }
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - guardarDireccion Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Eliminar dirección
     */
    public function eliminarDireccion() {
        try {
            if (!isset($_SESSION['usuario_web_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $direccionId = (int)($data['id'] ?? 0);
            
            if ($direccionId === 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            $result = $this->model->eliminarDireccion($direccionId, $_SESSION['usuario_web_id']);
            
            if ($result) {
                $this->jsonResponse(['success' => true, 'message' => 'Dirección eliminada']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar'], 500);
            }
            
        } catch (Exception $e) {
            error_log("UsuarioWebController - eliminarDireccion Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}