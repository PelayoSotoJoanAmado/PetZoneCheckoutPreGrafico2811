<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../model/UsuarioModel.php';

class AuthController2 extends BaseController {
    protected $requireAuth = false;
    
    public function __construct() {
        $this->model = new UsuarioModel();
    }
    
    public function login() {
        try {
            $data = $this->getRequestData();
            
            $username = sanitize($data['username'] ?? '');
            $password = $data['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
            }
            
            $user = $this->model->findByUsername($username);
            
            if (!$user) {
                jsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 401);
            }
            
            if (!password_verify($password, $user['password'])) {
                jsonResponse(['success' => false, 'message' => 'Contraseña incorrecta'], 401);
            }
            
            // Actualizar último acceso
            $this->model->updateLastAccess($user['id']);
            
            // Iniciar sesión
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];
            
            // Registrar actividad
            $this->model->registrarActividad($user['id'], 'Inicio de sesión', 'Autenticación', 'Login exitoso');
            
            unset($user['password']);
            
            jsonResponse([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function logout() {
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            $this->model->registrarActividad($_SESSION['user_id'], 'Cierre de sesión', 'Autenticación', 'Logout');
        }
        
        session_destroy();
        jsonResponse(['success' => true, 'message' => 'Sesión cerrada']);
    }
    
    public function check() {
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            try {
                $user = $this->model->getById($_SESSION['user_id']);
                
                if ($user && $user['activo'] == 1) {
                    unset($user['password']);
                    jsonResponse(['authenticated' => true, 'user' => $user]);
                }
            } catch (Exception $e) {
                error_log("Check session error: " . $e->getMessage());
            }
        }
        
        jsonResponse(['authenticated' => false]);
    }
}