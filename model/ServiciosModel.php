<?php
/**
 * Modelo de Servicios - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class ServiciosModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function listarServicios() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM servicios 
                WHERE disponible = 1 
                ORDER BY orden ASC, nombre ASC
            ");
            
            $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar características JSON
            foreach ($servicios as &$servicio) {
                if ($servicio['caracteristicas']) {
                    $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
                }
            }
            
            return $servicios;
            
        } catch (PDOException $e) {
            error_log("Error en listarServicios: " . $e->getMessage());
            throw new Exception("Error al obtener los servicios");
        }
    }
    
    public function obtenerServicio($id = null, $slug = null) {
        try {
            if ($id) {
                $stmt = $this->db->prepare("SELECT * FROM servicios WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM servicios WHERE slug = ?");
                $stmt->execute([$slug]);
            }
            
            $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($servicio) {
                // Decodificar características JSON
                if ($servicio['caracteristicas']) {
                    $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
                }
            }
            
            return $servicio;
            
        } catch (PDOException $e) {
            error_log("Error en obtenerServicio: " . $e->getMessage());
            throw new Exception("Error al obtener el servicio");
        }
    }
    
    public function obtenerServiciosDisponibles() {
        try {
            // Verificar si la vista existe, si no usar consulta alternativa
            $stmt = $this->db->query("SHOW TABLES LIKE 'vista_servicios_disponibles'");
            $vistaExiste = $stmt->fetch();
            
            if ($vistaExiste) {
                $stmt = $this->db->query("SELECT * FROM vista_servicios_disponibles");
            } else {
                // Consulta alternativa si la vista no existe
                $stmt = $this->db->query("
                    SELECT * FROM servicios 
                    WHERE disponible = 1 
                    ORDER BY orden ASC, nombre ASC
                ");
            }
            
            $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar características JSON
            foreach ($servicios as &$servicio) {
                if ($servicio['caracteristicas']) {
                    $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
                }
            }
            
            return $servicios;
            
        } catch (PDOException $e) {
            error_log("Error en obtenerServiciosDisponibles: " . $e->getMessage());
            throw new Exception("Error al obtener servicios disponibles");
        }
    }
    
    /**
     * Método adicional para obtener servicios con filtros (por si se necesita expandir)
     */
    public function listarServiciosConFiltros($categoria = null, $disponible = true) {
        try {
            $sql = "SELECT * FROM servicios WHERE 1=1";
            $params = [];
            
            if ($disponible) {
                $sql .= " AND disponible = 1";
            }
            
            if ($categoria) {
                $sql .= " AND categoria_id = ?";
                $params[] = $categoria;
            }
            
            $sql .= " ORDER BY orden ASC, nombre ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar características JSON
            foreach ($servicios as &$servicio) {
                if ($servicio['caracteristicas']) {
                    $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
                }
            }
            
            return $servicios;
            
        } catch (PDOException $e) {
            error_log("Error en listarServiciosConFiltros: " . $e->getMessage());
            throw new Exception("Error al obtener servicios con filtros");
        }
    }
}