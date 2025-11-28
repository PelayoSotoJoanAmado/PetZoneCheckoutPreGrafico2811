<?php
/**
 * Modelo de Sliders - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class SliderModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function listarSliders() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sliders ORDER BY orden ASC, id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en listarSliders: " . $e->getMessage());
            throw new Exception("Error al obtener los sliders");
        }
    }
    
    public function obtenerSlider($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sliders WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerSlider: " . $e->getMessage());
            throw new Exception("Error al obtener el slider");
        }
    }
    
    public function crearSlider($datos, $usuarioId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sliders (titulo, descripcion, imagen, enlace, posicion, orden, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $datos['titulo'],
                $datos['descripcion'],
                $datos['imagen'],
                $datos['enlace'],
                $datos['posicion'],
                $datos['orden'],
                $datos['activo']
            ]);
            
            if ($result) {
                $this->registrarActividad($usuarioId, 'Slider creado', 'Sliders', "Slider: {$datos['titulo']}");
                return true;
            } else {
                throw new Exception('Error al crear slider en la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en crearSlider: " . $e->getMessage());
            throw new Exception("Error al crear el slider");
        }
    }
    
    public function actualizarSlider($id, $datos, $usuarioId) {
        try {
            // Obtener slider actual
            $sliderActual = $this->obtenerSlider($id);
            $imagen = $sliderActual['imagen'];
            
            // Usar nueva imagen si se proporcionó
            if ($datos['imagen'] !== null) {
                // Eliminar imagen anterior si existe
                if ($imagen && file_exists(__DIR__ . "/../public/{$imagen}")) {
                    @unlink(__DIR__ . "/../public/{$imagen}");
                }
                $imagen = $datos['imagen'];
            }
            
            $stmt = $this->db->prepare("
                UPDATE sliders 
                SET titulo = ?, descripcion = ?, imagen = ?, enlace = ?, 
                    posicion = ?, orden = ?, activo = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $datos['titulo'],
                $datos['descripcion'],
                $imagen,
                $datos['enlace'],
                $datos['posicion'],
                $datos['orden'],
                $datos['activo'],
                $id
            ]);
            
            if ($result) {
                $this->registrarActividad($usuarioId, 'Slider actualizado', 'Sliders', "Slider ID: {$id}");
                return true;
            } else {
                throw new Exception('Error al actualizar slider en la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en actualizarSlider: " . $e->getMessage());
            throw new Exception("Error al actualizar el slider");
        }
    }
    
    public function eliminarSlider($id, $usuarioId) {
        try {
            // Obtener datos del slider
            $slider = $this->obtenerSlider($id);
            
            if (!$slider) {
                throw new Exception('Slider no encontrado');
            }
            
            // Eliminar slider
            $stmt = $this->db->prepare("DELETE FROM sliders WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                // Eliminar imagen del slider
                if ($slider['imagen'] && file_exists(__DIR__ . "/../public/{$slider['imagen']}")) {
                    @unlink(__DIR__ . "/../public/{$slider['imagen']}");
                }
                
                $this->registrarActividad($usuarioId, 'Slider eliminado', 'Sliders', "Slider: {$slider['titulo']}");
                return true;
            } else {
                throw new Exception('Error al eliminar slider de la base de datos');
            }
            
        } catch (PDOException $e) {
            error_log("Error en eliminarSlider: " . $e->getMessage());
            throw new Exception("Error al eliminar el slider");
        }
    }
    
    public function obtenerSlidersActivos() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sliders WHERE activo = 1 ORDER BY orden ASC LIMIT 5");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerSlidersActivos: " . $e->getMessage());
            throw new Exception("Error al obtener sliders activos");
        }
    }
    
    /**
     * Método adicional para obtener sliders por posición
     */
    public function obtenerSlidersPorPosicion($posicion, $activo = true) {
        try {
            $sql = "SELECT * FROM sliders WHERE posicion = ?";
            $params = [$posicion];
            
            if ($activo) {
                $sql .= " AND activo = 1";
            }
            
            $sql .= " ORDER BY orden ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerSlidersPorPosicion: " . $e->getMessage());
            throw new Exception("Error al obtener sliders por posición");
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