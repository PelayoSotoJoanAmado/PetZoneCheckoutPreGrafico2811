<?php
/**
 * Modelo de Usuarios Web (Clientes) - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class UsuarioWebModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    /**
     * Registrar nuevo usuario
     */
    public function registrar($datos) {
        try {
            // Verificar si el email ya existe
            $stmt = $this->db->prepare("SELECT id FROM usuarios_web WHERE email = ?");
            $stmt->execute([$datos['email']]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Este correo ya está registrado'];
            }
            
            // Hashear contraseña
            $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
            
            // Insertar usuario
            $stmt = $this->db->prepare("
                INSERT INTO usuarios_web (nombre, email, telefono, password) 
                VALUES (?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $datos['nombre'],
                $datos['email'],
                $datos['telefono'],
                $passwordHash
            ]);
            
            if ($result) {
                $userId = $this->db->lastInsertId();
                
                return [
                    'success' => true,
                    'message' => 'Registro exitoso',
                    'user_id' => $userId
                ];
            }
            
            return ['success' => false, 'message' => 'Error al registrar usuario'];
            
        } catch (PDOException $e) {
            error_log("Error en registrar: " . $e->getMessage());
            throw new Exception("Error al registrar usuario");
        }
    }
    
    /**
     * Iniciar sesión
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, email, telefono, password 
                FROM usuarios_web 
                WHERE email = ? AND activo = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
            
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Contraseña incorrecta'];
            }
            
            // Actualizar último acceso
            $this->actualizarUltimoAcceso($user['id']);
            
            // No devolver el password
            unset($user['password']);
            
            return [
                'success' => true,
                'message' => 'Login exitoso',
                'user' => $user
            ];
            
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            throw new Exception("Error al iniciar sesión");
        }
    }
    
    /**
     * Obtener datos del usuario
     */
    public function obtenerUsuario($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, email, telefono, fecha_registro 
                FROM usuarios_web 
                WHERE id = ? AND activo = 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerUsuario: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualizar perfil
     */
    public function actualizarPerfil($userId, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE usuarios_web 
                SET nombre = ?, telefono = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $datos['nombre'],
                $datos['telefono'],
                $userId
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en actualizarPerfil: " . $e->getMessage());
            throw new Exception("Error al actualizar perfil");
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($userId, $passwordActual, $passwordNueva) {
        try {
            // Verificar contraseña actual
            $stmt = $this->db->prepare("SELECT password FROM usuarios_web WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($passwordActual, $user['password'])) {
                return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
            }
            
            // Actualizar contraseña
            $passwordHash = password_hash($passwordNueva, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE usuarios_web SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$passwordHash, $userId])) {
                return ['success' => true, 'message' => 'Contraseña actualizada'];
            }
            
            return ['success' => false, 'message' => 'Error al actualizar contraseña'];
            
        } catch (PDOException $e) {
            error_log("Error en cambiarPassword: " . $e->getMessage());
            throw new Exception("Error al cambiar contraseña");
        }
    }
    
    /**
     * Guardar dirección
     */
    public function guardarDireccion($userId, $datos) {
        try {
            // Si es predeterminada, quitar predeterminada de las demás
            if ($datos['es_predeterminada']) {
                $stmt = $this->db->prepare("
                    UPDATE direcciones_usuario 
                    SET es_predeterminada = 0 
                    WHERE usuario_id = ?
                ");
                $stmt->execute([$userId]);
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO direcciones_usuario 
                (usuario_id, nombre_direccion, departamento, distrito, direccion_completa, es_predeterminada) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $datos['nombre_direccion'],
                $datos['departamento'],
                $datos['distrito'],
                $datos['direccion_completa'],
                $datos['es_predeterminada'] ? 1 : 0
            ]);
            
        } catch (PDOException $e) {
            error_log("Error en guardarDireccion: " . $e->getMessage());
            throw new Exception("Error al guardar dirección");
        }
    }
    
    /**
     * Obtener direcciones del usuario
     */
    public function obtenerDirecciones($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM direcciones_usuario 
                WHERE usuario_id = ? 
                ORDER BY es_predeterminada DESC, fecha_creacion DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerDirecciones: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Eliminar dirección
     */
    public function eliminarDireccion($direccionId, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM direcciones_usuario 
                WHERE id = ? AND usuario_id = ?
            ");
            return $stmt->execute([$direccionId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Error en eliminarDireccion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar último acceso
     */
    private function actualizarUltimoAcceso($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE usuarios_web SET ultimo_acceso = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error en actualizarUltimoAcceso: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener historial de pedidos
     */
    public function obtenerHistorialPedidos($userId, $limite = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.id,
                    p.codigo_pedido,
                    p.total,
                    p.estado,
                    p.fecha_pedido,
                    COUNT(dp.id) as total_items
                FROM pedidos p
                LEFT JOIN detalle_pedidos dp ON p.id = dp.pedido_id
                WHERE p.usuario_id = ?
                GROUP BY p.id
                ORDER BY p.fecha_pedido DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerHistorialPedidos: " . $e->getMessage());
            return [];
        }
    }
}