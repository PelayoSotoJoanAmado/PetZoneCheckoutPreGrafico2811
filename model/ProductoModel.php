<?php
/**
 * Modelo de Productos - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class ProductoModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function listarProductos($categoria = '', $busqueda = '') {
        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug 
                FROM productos p 
                INNER JOIN categorias c ON p.categoria_id = c.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($categoria)) {
            $sql .= " AND p.categoria_id = ?";
            $params[] = $categoria;
        }
        
        if (!empty($busqueda)) {
            $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
            $params[] = "%{$busqueda}%";
            $params[] = "%{$busqueda}%";
        }
        
        $sql .= " ORDER BY p.destacado DESC, p.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerProducto($id) {
        $stmt = $this->db->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p 
                                   INNER JOIN categorias c ON p.categoria_id = c.id 
                                   WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function crearProducto($datos, $usuarioId) {
        $stmt = $this->db->prepare("
            INSERT INTO productos (nombre, descripcion, categoria_id, precio, stock, imagen, codigo_sku, destacado, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $result = $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'],
            $datos['categoria_id'],
            $datos['precio'],
            $datos['stock'],
            $datos['imagen'],
            $datos['codigo_sku'],
            $datos['destacado']
        ]);
        
        if ($result) {
            $id = $this->db->lastInsertId();
            $this->registrarActividad($usuarioId, 'Producto creado', 'Productos', "Producto: {$datos['nombre']}");
            return $id;
        } else {
            throw new Exception('Error al crear producto en la base de datos');
        }
    }
    
    public function actualizarProducto($id, $datos, $usuarioId) {
        // Obtener imagen actual
        $productoActual = $this->obtenerProducto($id);
        $imagen = $productoActual['imagen'];
        
        // Usar nueva imagen si se proporcionó
        if ($datos['imagen'] !== null) {
            // Eliminar imagen anterior si existe
            if ($imagen && file_exists(__DIR__ . "/../{$imagen}")) {
                @unlink(__DIR__ . "/../{$imagen}");
            }
            $imagen = $datos['imagen'];
        }
        
        $stmt = $this->db->prepare("
            UPDATE productos 
            SET nombre = ?, descripcion = ?, categoria_id = ?, precio = ?, 
                stock = ?, imagen = ?, codigo_sku = ?, destacado = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'],
            $datos['categoria_id'],
            $datos['precio'],
            $datos['stock'],
            $imagen,
            $datos['codigo_sku'],
            $datos['destacado'],
            $id
        ]);
        
        if ($result) {
            $this->registrarActividad($usuarioId, 'Producto actualizado', 'Productos', "ID: {$id}");
            return true;
        } else {
            throw new Exception('Error al actualizar producto en la base de datos');
        }
    }
    
    public function eliminarProducto($id, $usuarioId) {
        // Obtener datos del producto
        $producto = $this->obtenerProducto($id);
        
        if (!$producto) {
            throw new Exception('Producto no encontrado');
        }
        
        // Eliminar imagen
        if ($producto['imagen'] && file_exists(__DIR__ . "../../public/{$producto['imagen']}")) {
            @unlink(__DIR__ . "/../public/{$producto['imagen']}");
        }
        
        // Eliminar producto
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $this->registrarActividad($usuarioId, 'Producto eliminado', 'Productos', "Producto: {$producto['nombre']}");
            return true;
        } else {
            throw new Exception('Error al eliminar producto de la base de datos');
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