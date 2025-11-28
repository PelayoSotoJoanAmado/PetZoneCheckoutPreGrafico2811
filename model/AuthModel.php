<?php
/**
 * Modelo de Autenticación - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class AuthModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function iniciarSesion($username, $password) {
        try {
            error_log("AuthModel - Buscando usuario: " . $username);
            
            $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                error_log("AuthModel - Usuario no encontrado: " . $username);
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
            
            error_log("AuthModel - Usuario encontrado, verificando password...");
            
            if (password_verify($password, $user['password'])) {
                error_log("AuthModel - Password correcto");
                
                // Actualizar último acceso
                $this->actualizarUltimoAcceso($user['id']);
                
                // Registrar actividad
                $this->registrarActividad($user['id'], 'Inicio de sesión', 'Autenticación', 'Login exitoso');
                
                // Limpiar datos sensibles
                unset($user['password']);
                
                return [
                    'success' => true,
                    'user' => $user
                ];
            } else {
                error_log("AuthModel - Password incorrecto");
                return ['success' => false, 'message' => 'Contraseña incorrecta'];
            }
            
        } catch (PDOException $e) {
            error_log("AuthModel - Error en iniciarSesion: " . $e->getMessage());
            throw new Exception("Error al iniciar sesión");
        }
    }
    
    public function obtenerUsuarioPorId($userId) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, nombre_completo, email, rol FROM usuarios WHERE id = ? AND activo = 1");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("AuthModel - Error en obtenerUsuarioPorId: " . $e->getMessage());
            return false;
        }
    }
    
    public function verificarCredenciales($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("AuthModel - Error en verificarCredenciales: " . $e->getMessage());
            throw new Exception("Error al verificar credenciales");
        }
    }
    
    public function actualizarUltimoAcceso($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
            
        } catch (PDOException $e) {
            error_log("AuthModel - Error en actualizarUltimoAcceso: " . $e->getMessage());
            // No lanzamos excepción porque no es crítico para el login
        }
    }

    public function registrarActividad($userId, $accion, $modulo, $detalle = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO actividad_admin (usuario_id, accion, modulo, detalle, ip_address) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $accion,
                $modulo,
                $detalle,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log("AuthModel - Error al registrar actividad: " . $e->getMessage());
            // No lanzamos excepción porque no es crítico
        }
    }
    
}