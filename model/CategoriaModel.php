<?php
/**
 * Modelo de Categorías - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class CategoriaModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function listarCategorias() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    id, 
                    nombre, 
                    slug, 
                    descripcion, 
                    icono, 
                    orden, 
                    activo
                FROM categorias 
                WHERE activo = 1 
                ORDER BY orden ASC, nombre ASC
            ");
            
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log para debug
            error_log("CategoriaModel - Total categorías: " . count($categorias));
            
            if (empty($categorias)) {
                error_log("CategoriaModel - ADVERTENCIA: No hay categorías activas");
            }
            
            return $categorias;
            
        } catch (PDOException $e) {
            error_log("Error en listarCategorias: " . $e->getMessage());
            throw new Exception("Error al obtener las categorías");
        }
    }
    
}