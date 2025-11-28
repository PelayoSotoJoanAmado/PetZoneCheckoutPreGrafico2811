<?php
/**
 * Modelo de Citas - PetZone
 * Maneja la lógica de base de datos para las citas
 */

require_once __DIR__ . '/../config/conexion.php';

class CitaModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function listarCitas($filtro = 'todas', $busqueda = '', $limite = 50, $pagina = 1) {
        try {
            $offset = ($pagina - 1) * $limite;
            
            $query = "SELECT * FROM vista_citas WHERE 1=1";
            $params = [];
            
            // Filtro por estado
            if ($filtro !== 'todas') {
                $query .= " AND estado = ?";
                $params[] = $filtro;
            }
            
            // Búsqueda
            if (!empty($busqueda)) {
                $query .= " AND (nombre LIKE ? OR correo LIKE ? OR telefono LIKE ? OR codigo_cita LIKE ?)";
                $busquedaParam = "%{$busqueda}%";
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
            }
            
            // Contar total
            $countQuery = "SELECT COUNT(*) as total FROM citas WHERE 1=1";
            $countParams = [];
            
            if ($filtro !== 'todas') {
                $countQuery .= " AND estado = ?";
                $countParams[] = $filtro;
            }
            
            if (!empty($busqueda)) {
                $countQuery .= " AND (nombre LIKE ? OR correo LIKE ? OR telefono LIKE ? OR codigo_cita LIKE ?)";
                $countParams[] = $busquedaParam;
                $countParams[] = $busquedaParam;
                $countParams[] = $busquedaParam;
                $countParams[] = $busquedaParam;
            }
            
            $stmtCount = $this->db->prepare($countQuery);
            $stmtCount->execute($countParams);
            $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener citas
            $query .= " ORDER BY fecha_solicitud DESC LIMIT ? OFFSET ?";
            $params[] = $limite;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'citas' => $citas,
                'total' => $total,
                'totalPaginas' => ceil($total / $limite)
            ];
            
        } catch (PDOException $e) {
            error_log("Error en listarCitas: " . $e->getMessage());
            throw new Exception("Error al obtener las citas");
        }
    }
    
    public function obtenerCita($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM citas WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerCita: " . $e->getMessage());
            throw new Exception("Error al obtener la cita");
        }
    }
    
    public function crearCita($datos) {
        try {
            $codigoCita = $this->generarCodigoCita();
            
            $stmt = $this->db->prepare("
                INSERT INTO citas (codigo_cita, nombre, correo, telefono, servicio, mensaje, estado, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)
            ");
            
            $result = $stmt->execute([
                $codigoCita,
                $datos['nombre'],
                $datos['correo'],
                $datos['telefono'],
                $datos['servicio'],
                $datos['mensaje'],
                $datos['ip_address']
            ]);
            
            if ($result) {
                $citaId = $this->db->lastInsertId();
                
                // Opcional: Enviar email de confirmación
                // $this->enviarEmailConfirmacion($datos['correo'], $datos['nombre'], $codigoCita);
                
                return [
                    'id' => $citaId,
                    'codigo_cita' => $codigoCita
                ];
            } else {
                throw new Exception('Error al registrar la cita en la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en crearCita: " . $e->getMessage());
            throw new Exception("Error al crear la cita");
        }
    }
    
    public function actualizarCita($id, $datos, $usuarioId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE citas 
                SET nombre = ?, correo = ?, telefono = ?, servicio = ?, mensaje = ?, estado = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $datos['nombre'],
                $datos['correo'],
                $datos['telefono'],
                $datos['servicio'],
                $datos['mensaje'],
                $datos['estado'],
                $id
            ]);
            
            if ($result) {
                $this->registrarActividad($usuarioId, 'Cita actualizada', 'Citas', "Cita ID: {$id} - Cliente: {$datos['nombre']}");
                return true;
            } else {
                throw new Exception('Error al actualizar cita en la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en actualizarCita: " . $e->getMessage());
            throw new Exception("Error al actualizar la cita");
        }
    }
    
    public function eliminarCita($id, $usuarioId) {
        try {
            // Obtener datos de la cita antes de eliminar
            $stmt = $this->db->prepare("SELECT codigo_cita, nombre FROM citas WHERE id = ?");
            $stmt->execute([$id]);
            $cita = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cita) {
                throw new Exception('Cita no encontrada');
            }
            
            $stmt = $this->db->prepare("DELETE FROM citas WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->registrarActividad($usuarioId, 'Cita eliminada', 'Citas', "Código: {$cita['codigo_cita']} - Cliente: {$cita['nombre']}");
                return true;
            } else {
                throw new Exception('Error al eliminar cita de la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en eliminarCita: " . $e->getMessage());
            throw new Exception("Error al eliminar la cita");
        }
    }
    
    public function actualizarEstadoCita($id, $estado, $usuarioId) {
        try {
            $stmt = $this->db->prepare("UPDATE citas SET estado = ? WHERE id = ?");
            $result = $stmt->execute([$estado, $id]);
            
            if ($result) {
                $this->registrarActividad($usuarioId, 'Estado de cita actualizado', 'Citas', "Cita ID: {$id} - Nuevo estado: {$estado}");
                return true;
            } else {
                throw new Exception('Error al actualizar estado en la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en actualizarEstadoCita: " . $e->getMessage());
            throw new Exception("Error al actualizar el estado de la cita");
        }
    }
    
    public function obtenerEstadisticas() {
        try {
            // Estadísticas generales
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                    SUM(CASE WHEN DATE(fecha_solicitud) = CURDATE() THEN 1 ELSE 0 END) as hoy,
                    SUM(CASE WHEN WEEK(fecha_solicitud) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as esta_semana,
                    SUM(CASE WHEN MONTH(fecha_solicitud) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as este_mes
                FROM citas
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Servicios más solicitados
            $stmt = $this->db->query("
                SELECT servicio, COUNT(*) as total 
                FROM citas 
                GROUP BY servicio 
                ORDER BY total DESC 
                LIMIT 5
            ");
            $serviciosTop = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Citas recientes
            $stmt = $this->db->query("
                SELECT * FROM vista_citas 
                WHERE estado = 'pendiente' 
                ORDER BY fecha_solicitud DESC 
                LIMIT 10
            ");
            $citasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'stats' => $stats,
                'servicios_top' => $serviciosTop,
                'citas_recientes' => $citasRecientes
            ];
            
        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Generar código único para la cita
     */
    private function generarCodigoCita() {
        return 'CITA-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    }
    
    /**
     * Registrar actividad del admin
     */
    private function registrarActividad($userId, $accion, $modulo, $detalle = null) {
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
            error_log("Error al registrar actividad: " . $e->getMessage());
        }
    }
    
    /**
     * Función opcional para enviar email de confirmación
     */
    private function enviarEmailConfirmacion($correo, $nombre, $codigoCita) {
        // Implementar según tu sistema de emails
        // Ejemplo con PHPMailer o mail() de PHP
        $asunto = "Confirmación de Cita - PetZone";
        $mensaje = "
            Hola {$nombre},
            
            Tu solicitud de cita ha sido recibida exitosamente.
            Código de cita: {$codigoCita}
            
            Nos pondremos en contacto contigo pronto para confirmar la fecha y hora.
            
            Saludos,
            Equipo PetZone
        ";
        
        // mail($correo, $asunto, $mensaje);
    }
    
    /**
     * Método adicional para obtener citas por estado
     */
    public function obtenerCitasPorEstado($estado, $limite = null) {
        try {
            $sql = "SELECT * FROM vista_citas WHERE estado = ? ORDER BY fecha_solicitud DESC";
            
            if ($limite) {
                $sql .= " LIMIT ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$estado, $limite]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$estado]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerCitasPorEstado: " . $e->getMessage());
            throw new Exception("Error al obtener citas por estado");
        }
    }
}