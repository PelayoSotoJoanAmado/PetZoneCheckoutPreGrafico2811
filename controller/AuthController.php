<?php
/**
 * Controlador de Autenticación - PetZone
 */

require_once __DIR__ . '/../model/AuthModel.php';

class AuthController {
    private $model;
    
    public function __construct() {
        $this->model = new AuthModel();
        
        // Iniciar sesión solo si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Configurar headers
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Manejar preflight OPTIONS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    public function index() {
        $this->jsonResponse(['success' => false, 'message' => 'Acción no especificada'], 400);
    }
    
    public function login() {
        try {
            $requestData = json_decode(file_get_contents('php://input'), true) ?? [];
            $username = $this->sanitize($requestData['username'] ?? '');
            $password = $requestData['password'] ?? '';
            
            error_log("AuthController - Login attempt: " . $username);
            
            if (empty($username) || empty($password)) {
                $this->jsonResponse(['success' => false, 'message' => 'Usuario y contraseña requeridos'], 400);
                return;
            }
            
            $result = $this->model->iniciarSesion($username, $password);
            
            if ($result['success']) {
                // Guardar en sesión
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['username'] = $result['user']['username'];
                $_SESSION['rol'] = $result['user']['rol'];
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'user' => $result['user']
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => $result['message']], 401);
            }
            
        } catch (Exception $e) {
            error_log("AuthController - login Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error en el servidor'], 500);
        }
    }
    
    public function logout() {
        try {
            if (isset($_SESSION['user_id'])) {
                $this->model->registrarActividad($_SESSION['user_id'], 'Cierre de sesión', 'Autenticación', 'Logout');
            }
            
            session_destroy();
            $this->jsonResponse(['success' => true, 'message' => 'Sesión cerrada']);
            
        } catch (Exception $e) {
            error_log("AuthController - logout Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function check() {
        try {
            if (isset($_SESSION['user_id'])) {
                $user = $this->model->obtenerUsuarioPorId($_SESSION['user_id']);
                
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
            error_log("AuthController - check Error: " . $e->getMessage());
            $this->jsonResponse(['authenticated' => false]);
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