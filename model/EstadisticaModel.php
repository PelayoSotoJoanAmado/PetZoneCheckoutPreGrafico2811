<?php
/**
 * Modelo de Estadísticas - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class EstadisticaModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function obtenerEstadisticasDashboard() {
        try {
            // Verificar si la vista existe
            $stmt = $this->db->query("SHOW TABLES LIKE 'estadisticas_dashboard'");
            $vistaExiste = $stmt->fetch();
            
            if ($vistaExiste) {
                $stmt = $this->db->query("SELECT * FROM estadisticas_dashboard");
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // Si la vista no existe, calcular las estadísticas manualmente
                return $this->calcularEstadisticasDashboard();
            }
            
        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticasDashboard: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas del dashboard");
        }
    }
    
    
    private function calcularEstadisticasDashboard() {
        try {
            $estadisticas = [];
            
            // Productos
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
            $estadisticas['total_productos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM productos WHERE stock < 10 AND activo = 1");
            $estadisticas['productos_stock_bajo'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Sliders
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM sliders WHERE activo = 1");
            $estadisticas['total_sliders_activos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Anuncios
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM anuncios WHERE activo = 1");
            $estadisticas['total_anuncios_activos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Pedidos
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha_creacion) = CURDATE()");
            $estadisticas['pedidos_hoy'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'");
            $estadisticas['pedidos_pendientes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Ventas
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(total), 0) as total 
                FROM pedidos 
                WHERE estado != 'cancelado'
            ");
            $estadisticas['ventas_totales'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(total), 0) as total 
                FROM pedidos 
                WHERE MONTH(fecha_creacion) = MONTH(CURDATE()) 
                AND YEAR(fecha_creacion) = YEAR(CURDATE())
                AND estado != 'cancelado'
            ");
            $estadisticas['ventas_mes_actual'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $estadisticas;
            
        } catch (PDOException $e) {
            error_log("Error en calcularEstadisticasDashboard: " . $e->getMessage());
            throw new Exception("Error al calcular estadísticas del dashboard");
        }
    }
//NEW
public function obtenerProductosPorCategoria() {
    try {
        $stmt = $this->db->query("
            SELECT c.nombre as categoria, COUNT(p.id) as total
            FROM productos p
            INNER JOIN categorias c ON p.categoria_id = c.id
            WHERE p.activo = 1
            GROUP BY c.nombre
            ORDER BY total DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerProductosPorCategoria: " . $e->getMessage());
        throw new Exception("Error al obtener productos por categoría");
    }
}

/**
 * Obtener reservas por servicio
 */
public function obtenerReservasPorServicio($dias = 30) {
    try {
        $stmt = $this->db->prepare("
            SELECT s.nombre as servicio, COUNT(r.id) as total
            FROM reservas r
            INNER JOIN servicios s ON r.servicio_id = s.id
            WHERE r.fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY s.nombre
            ORDER BY total DESC
        ");
        $stmt->execute([$dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerReservasPorServicio: " . $e->getMessage());
        throw new Exception("Error al obtener reservas por servicio");
    }
}

/**
 * Obtener ventas mensuales
 */
public function obtenerVentasMensuales($meses = 6) {
    try {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
                DATE_FORMAT(fecha_creacion, '%M %Y') as mes_nombre,
                COUNT(*) as num_pedidos,
                COALESCE(SUM(total), 0) as total_ventas
            FROM pedidos 
            WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                AND estado != 'cancelado'
            GROUP BY mes, mes_nombre
            ORDER BY mes ASC
        ");
        $stmt->execute([$meses]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerVentasMensuales: " . $e->getMessage());
        throw new Exception("Error al obtener ventas mensuales");
    }
}

/**
 * Obtener productos más vendidos
 */
public function obtenerProductosMasVendidos($limite = 5) {
    try {
        $stmt = $this->db->prepare("
            SELECT 
                p.nombre as producto,
                SUM(dp.cantidad) as total_vendido
            FROM detalle_pedido dp
            INNER JOIN productos p ON dp.producto_id = p.id
            INNER JOIN pedidos ped ON dp.pedido_id = ped.id
            WHERE ped.estado != 'cancelado'
            GROUP BY p.id, p.nombre
            ORDER BY total_vendido DESC
            LIMIT ?
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en obtenerProductosMasVendidos: " . $e->getMessage());
        throw new Exception("Error al obtener productos más vendidos");
    }
}
}

