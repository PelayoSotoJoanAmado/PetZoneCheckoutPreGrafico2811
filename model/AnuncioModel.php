<?php
/**
 * Modelo de Anuncios - PetZone
 *  VERSIÓN CORREGIDA - Problema del checkbox resuelto
 */

require_once __DIR__ . '/../config/conexion.php';

class AnuncioModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function listarAnuncios() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM anuncios ORDER BY prioridad DESC, id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en listarAnuncios: " . $e->getMessage());
            throw new Exception("Error al obtener los anuncios");
        }
    }
    
    public function obtenerAnuncio($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM anuncios WHERE id = ?");
            $stmt->execute([$id]);
            $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($anuncio) {
                //  ASEGURAR QUE 'activo' sea un entero
                $anuncio['activo'] = (int)$anuncio['activo'];
            }
            
            return $anuncio;
            
        } catch (PDOException $e) {
            error_log("Error en obtenerAnuncio: " . $e->getMessage());
            throw new Exception("Error al obtener el anuncio");
        }
    }
    
    public function crearAnuncio($datos, $usuarioId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO anuncios 
                (mensaje, tipo, color_fondo, color_texto, icono, velocidad, prioridad, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $datos['mensaje'],
                $datos['tipo'],
                $datos['color_fondo'],
                $datos['color_texto'],
                $datos['icono'],
                $datos['velocidad'],
                $datos['prioridad'],
                $datos['activo']
            ]);
            
            if ($result) {
                $nuevoId = $this->db->lastInsertId();
                $this->registrarActividad($usuarioId, 'Anuncio creado', 'Anuncios', "ID: {$nuevoId} - " . substr($datos['mensaje'], 0, 50));
                return $nuevoId;
            } else {
                error_log("Error en crearAnuncio - execute: " . print_r($stmt->errorInfo(), true));
                throw new Exception('Error al crear anuncio en la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en crearAnuncio: " . $e->getMessage());
            throw new Exception("Error al crear el anuncio");
        }
    }
    
    public function actualizarAnuncio($id, $datos, $usuarioId) {
        try {
            //  VERIFICAR ESTADO ACTUAL ANTES DE ACTUALIZAR
            $stmtCheck = $this->db->prepare("SELECT activo FROM anuncios WHERE id = ?");
            $stmtCheck->execute([$id]);
            $estadoActual = $stmtCheck->fetch();
            error_log("UPDATE - Estado actual en BD: " . var_export($estadoActual['activo'], true));
            
            $stmt = $this->db->prepare("
                UPDATE anuncios 
                SET mensaje = ?, 
                    tipo = ?, 
                    color_fondo = ?, 
                    color_texto = ?, 
                    icono = ?, 
                    velocidad = ?, 
                    prioridad = ?, 
                    activo = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $datos['mensaje'],
                $datos['tipo'],
                $datos['color_fondo'],
                $datos['color_texto'],
                $datos['icono'],
                $datos['velocidad'],
                $datos['prioridad'],
                $datos['activo'],
                $id
            ]);
            
            //  VERIFICAR QUE SE ACTUALIZÓ CORRECTAMENTE
            if ($result) {
                $stmtVerify = $this->db->prepare("SELECT activo FROM anuncios WHERE id = ?");
                $stmtVerify->execute([$id]);
                $nuevoEstado = $stmtVerify->fetch();
                error_log("UPDATE - Nuevo estado en BD: " . var_export($nuevoEstado['activo'], true));
                
                $this->registrarActividad($usuarioId, 'Anuncio actualizado', 'Anuncios', "ID: {$id} - Activo: {$datos['activo']}");
                return true;
            } else {
                error_log("UPDATE - Error en execute: " . print_r($stmt->errorInfo(), true));
                throw new Exception('Error al actualizar anuncio en la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en actualizarAnuncio: " . $e->getMessage());
            throw new Exception("Error al actualizar el anuncio");
        }
    }
    
    public function eliminarAnuncio($id, $usuarioId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM anuncios WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->registrarActividad($usuarioId, 'Anuncio eliminado', 'Anuncios', "ID: {$id}");
                return true;
            } else {
                throw new Exception('Error al eliminar anuncio de la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en eliminarAnuncio: " . $e->getMessage());
            throw new Exception("Error al eliminar el anuncio");
        }
    }
    
    public function obtenerAnunciosActivos() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM anuncios 
                WHERE activo = 1 
                AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
                AND (fecha_fin IS NULL OR fecha_fin >= NOW())
                ORDER BY prioridad DESC 
                LIMIT 3
            ");
            $stmt->execute();
            $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Incrementar visualizaciones
            foreach ($anuncios as $anuncio) {
                $updateStmt = $this->db->prepare("UPDATE anuncios SET visualizaciones = visualizaciones + 1 WHERE id = ?");
                $updateStmt->execute([$anuncio['id']]);
            }
            
            return $anuncios;
            
        } catch (PDOException $e) {
            error_log("Error en obtenerAnunciosActivos: " . $e->getMessage());
            throw new Exception("Error al obtener anuncios activos");
        }
    }
    
    /**
     * Método adicional para obtener anuncios por tipo
     */
    public function obtenerAnunciosPorTipo($tipo, $activo = true) {
        try {
            $sql = "SELECT * FROM anuncios WHERE tipo = ?";
            $params = [$tipo];
            
            if ($activo) {
                $sql .= " AND activo = 1";
            }
            
            $sql .= " ORDER BY prioridad DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerAnunciosPorTipo: " . $e->getMessage());
            throw new Exception("Error al obtener anuncios por tipo");
        }
    }
    
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
}