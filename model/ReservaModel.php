<?php
/**
 * Modelo de Reservas - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class ReservaModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function crearReserva($datos) {
        try {
            $this->db->beginTransaction();
            
            // Verificar que el servicio existe y está disponible
            $stmt = $this->db->prepare("SELECT * FROM servicios WHERE id = ? AND disponible = 1");
            $stmt->execute([$datos['servicio_id']]);
            $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$servicio) {
                throw new Exception('Servicio no disponible');
            }
            
            // Verificar disponibilidad en esa fecha/hora
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as reservas_simultaneas 
                FROM reservas 
                WHERE servicio_id = ? 
                AND fecha_reserva = ? 
                AND hora_reserva = ?
                AND estado NOT IN ('cancelada')
            ");
            $stmt->execute([$datos['servicio_id'], $datos['fecha_reserva'], $datos['hora_reserva']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['reservas_simultaneas'] >= 3) { // Límite de 3 reservas por horario
                throw new Exception('Este horario ya no está disponible');
            }
            
            // Generar código de reserva único
            $codigo_reserva = 'RES-' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insertar reserva
            $stmt = $this->db->prepare("
                INSERT INTO reservas (
                    codigo_reserva, servicio_id, nombre_cliente, email_cliente, 
                    telefono_cliente, nombre_mascota, tipo_mascota, fecha_reserva, 
                    hora_reserva, notas, subtotal, total, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            
            $precio = $servicio['precio'];
            
            $stmt->execute([
                $codigo_reserva,
                $datos['servicio_id'],
                $datos['nombre'],
                $datos['email'],
                $datos['telefono'],
                $datos['nombre_mascota'],
                $datos['tipo_mascota'],
                $datos['fecha_reserva'],
                $datos['hora_reserva'],
                $datos['notas'],
                $precio,
                $precio
            ]);
            
            $this->db->commit();
            
            error_log("RESERVA CREADA - Código: " . $codigo_reserva);
            
            return [
                'codigo_reserva' => $codigo_reserva,
                'servicio_nombre' => $servicio['nombre'],
                'precio' => $precio
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("ERROR AL CREAR RESERVA: " . $e->getMessage());
            throw new Exception('Error al crear reserva: ' . $e->getMessage());
        }
    }
    
    public function listarReservas() {
        try {
            $stmt = $this->db->query("
                SELECT r.*, s.nombre as servicio_nombre
                FROM reservas r
                INNER JOIN servicios s ON r.servicio_id = s.id
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC
                LIMIT 100
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en listarReservas: " . $e->getMessage());
            throw new Exception("Error al obtener las reservas");
        }
    }
    
    public function verificarDisponibilidad($servicio_id, $fecha, $hora) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as reservas_simultaneas 
                FROM reservas 
                WHERE servicio_id = ? 
                AND fecha_reserva = ? 
                AND hora_reserva = ?
                AND estado NOT IN ('cancelada')
            ");
            $stmt->execute([$servicio_id, $fecha, $hora]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $disponible = $result['reservas_simultaneas'] < 3;
            
            return [
                'disponible' => $disponible,
                'reservas_actuales' => (int)$result['reservas_simultaneas'],
                'limite' => 3
            ];
            
        } catch (PDOException $e) {
            error_log("Error en verificarDisponibilidad: " . $e->getMessage());
            throw new Exception("Error al verificar disponibilidad");
        }
    }
    
    /**
     * Método adicional para obtener reservas por código
     */
    public function obtenerReservaPorCodigo($codigo_reserva) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, s.nombre as servicio_nombre
                FROM reservas r
                INNER JOIN servicios s ON r.servicio_id = s.id
                WHERE r.codigo_reserva = ?
            ");
            $stmt->execute([$codigo_reserva]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerReservaPorCodigo: " . $e->getMessage());
            throw new Exception("Error al obtener reserva");
        }
    }
    
    /**
     * Método adicional para actualizar estado de reserva
     */
    public function actualizarEstadoReserva($codigo_reserva, $estado) {
        try {
            $stmt = $this->db->prepare("
                UPDATE reservas 
                SET estado = ? 
                WHERE codigo_reserva = ?
            ");
            $stmt->execute([$estado, $codigo_reserva]);
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error en actualizarEstadoReserva: " . $e->getMessage());
            throw new Exception("Error al actualizar estado de reserva");
        }
    }
    
    /**
     * Método adicional para obtener reservas por fecha
     */
    public function obtenerReservasPorFecha($fecha) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, s.nombre as servicio_nombre
                FROM reservas r
                INNER JOIN servicios s ON r.servicio_id = s.id
                WHERE r.fecha_reserva = ?
                ORDER BY r.hora_reserva ASC
            ");
            $stmt->execute([$fecha]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerReservasPorFecha: " . $e->getMessage());
            throw new Exception("Error al obtener reservas por fecha");
        }
    }
    
    /**
     * Método adicional para cancelar reserva
     */
    public function cancelarReserva($codigo_reserva, $motivo = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE reservas 
                SET estado = 'cancelada', notas = CONCAT(COALESCE(notas, ''), ' | Cancelación: ', ?) 
                WHERE codigo_reserva = ?
            ");
            $stmt->execute([$motivo, $codigo_reserva]);
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error en cancelarReserva: " . $e->getMessage());
            throw new Exception("Error al cancelar reserva");
        }
    }
}