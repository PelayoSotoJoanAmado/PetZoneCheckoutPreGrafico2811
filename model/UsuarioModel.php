<?php
require_once __DIR__ . '/../config/conexion.php';

class UsuarioModel extends BaseModel {
    protected $table = 'usuarios';
    
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ? AND activo = 1");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function updateLastAccess($userId) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET ultimo_acceso = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function registrarActividad($userId, $accion, $modulo, $detalle = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO actividad_admin (usuario_id, accion, modulo, detalle, ip_address) 
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $userId, 
                $accion, 
                $modulo, 
                $detalle, 
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar actividad: " . $e->getMessage());
            return false;
        }
    }
}