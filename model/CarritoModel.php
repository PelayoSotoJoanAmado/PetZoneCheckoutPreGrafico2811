<?php
/**
 * Modelo de Carrito - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class CarritoModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function agregarAlCarrito($sessionId, $productoId, $cantidad) {
        try {
            // Verificar existencia y stock del producto
            $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
            $stmt->execute([$productoId]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            if ($producto['stock'] < $cantidad) {
                throw new Exception('Stock insuficiente');
            }
            
            // Verificar si ya existe en el carrito
            $stmt = $this->db->prepare("SELECT * FROM carrito WHERE session_id = ? AND producto_id = ?");
            $stmt->execute([$sessionId, $productoId]);
            $itemExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($itemExistente) {
                // Actualizar cantidad
                $nuevaCantidad = $itemExistente['cantidad'] + $cantidad;
                
                if ($producto['stock'] < $nuevaCantidad) {
                    throw new Exception('Stock insuficiente');
                }
                
                $stmt = $this->db->prepare("
                    UPDATE carrito 
                    SET cantidad = ?, precio_unitario = ? 
                    WHERE session_id = ? AND producto_id = ?
                ");
                $stmt->execute([$nuevaCantidad, $producto['precio'], $sessionId, $productoId]);
            } else {
                // Insertar nuevo item
                $stmt = $this->db->prepare("
                    INSERT INTO carrito (session_id, producto_id, cantidad, precio_unitario) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$sessionId, $productoId, $cantidad, $producto['precio']]);
            }
            
            return $this->obtenerTotalesCarrito($sessionId);
            
        } catch (PDOException $e) {
            error_log("Error en agregarAlCarrito: " . $e->getMessage());
            throw new Exception("Error al agregar producto al carrito");
        }
    }
    
    public function actualizarCarrito($sessionId, $productoId, $cantidad) {
        try {
            if ($cantidad == 0) {
                // Eliminar del carrito
                $stmt = $this->db->prepare("DELETE FROM carrito WHERE session_id = ? AND producto_id = ?");
                $stmt->execute([$sessionId, $productoId]);
            } else {
                // Verificar stock
                $stmt = $this->db->prepare("SELECT stock FROM productos WHERE id = ?");
                $stmt->execute([$productoId]);
                $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$producto || $producto['stock'] < $cantidad) {
                    throw new Exception('Stock insuficiente');
                }
                
                // Actualizar cantidad
                $stmt = $this->db->prepare("
                    UPDATE carrito 
                    SET cantidad = ? 
                    WHERE session_id = ? AND producto_id = ?
                ");
                $stmt->execute([$cantidad, $sessionId, $productoId]);
            }
            
            return $this->obtenerTotalesCarrito($sessionId);
            
        } catch (PDOException $e) {
            error_log("Error en actualizarCarrito: " . $e->getMessage());
            throw new Exception("Error al actualizar carrito");
        }
    }
    
    public function eliminarDelCarrito($sessionId, $productoId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM carrito WHERE session_id = ? AND producto_id = ?");
            $stmt->execute([$sessionId, $productoId]);
            
            return $this->obtenerTotalesCarrito($sessionId);
            
        } catch (PDOException $e) {
            error_log("Error en eliminarDelCarrito: " . $e->getMessage());
            throw new Exception("Error al eliminar producto del carrito");
        }
    }
    
    public function obtenerCarrito($sessionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, p.nombre, p.imagen, p.descripcion, p.stock
                FROM carrito c
                INNER JOIN productos p ON c.producto_id = p.id
                WHERE c.session_id = ?
                ORDER BY c.fecha_agregado DESC
            ");
            $stmt->execute([$sessionId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular subtotal por item
            foreach ($items as &$item) {
                $item['subtotal'] = $item['cantidad'] * $item['precio_unitario'];
            }
            
            $totales = $this->obtenerTotalesCarrito($sessionId);
            
            return [
                'items' => $items,
                'totales' => $totales
            ];
            
        } catch (PDOException $e) {
            error_log("Error en obtenerCarrito: " . $e->getMessage());
            throw new Exception("Error al obtener carrito");
        }
    }
    
    public function vaciarCarrito($sessionId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM carrito WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            
        } catch (PDOException $e) {
            error_log("Error en vaciarCarrito: " . $e->getMessage());
            throw new Exception("Error al vaciar carrito");
        }
    }
    
    public function procesarCheckout($sessionId, $datosCliente) {
        try {
            $this->db->beginTransaction();

            // 1. Obtener items del carrito
            $stmt = $this->db->prepare("
                SELECT c.*, p.nombre, p.stock 
                FROM carrito c 
                INNER JOIN productos p ON c.producto_id = p.id 
                WHERE c.session_id = ?
            ");
            $stmt->execute([$sessionId]);
            $carritoItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($carritoItems)) {
                throw new Exception('Carrito vacío');
            }

            // 2. Verificar stock
            foreach ($carritoItems as $item) {
                if ($item['stock'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para: " . $item['nombre']);
                }
            }

            // 3. Calcular subtotal
            $subtotal = 0;
            foreach ($carritoItems as $item) {
                $subtotal += $item['cantidad'] * $item['precio_unitario'];
            }

            // 4. Crear pedido
            $codigo_pedido = 'PZ-' . date('Ymd') . '-' . rand(1000, 9999);
            
            $stmt = $this->db->prepare("
                INSERT INTO pedidos 
                (codigo_pedido, nombre_cliente, email_cliente, telefono_cliente, direccion_envio, 
                 subtotal, total, metodo_pago, notas, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            $stmt->execute([
                $codigo_pedido,
                $datosCliente['nombre'],
                $datosCliente['email'],
                $datosCliente['telefono'],
                $datosCliente['direccion'],
                $subtotal,
                $subtotal, // total = subtotal (sin descuentos/impuestos)
                $datosCliente['metodo_pago'],
                $datosCliente['notas']
            ]);
            
            $pedido_id = $this->db->lastInsertId();

            // 5. Crear items del detalle_pedidos
            $stmt = $this->db->prepare("
                INSERT INTO detalle_pedidos 
                (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($carritoItems as $item) {
                $subtotal_item = $item['cantidad'] * $item['precio_unitario'];
                
                $stmt->execute([
                    $pedido_id,
                    $item['producto_id'],
                    $item['nombre'],
                    $item['cantidad'],
                    $item['precio_unitario'],
                    $subtotal_item
                ]);

                // 6. Actualizar stock
                $updateStmt = $this->db->prepare("
                    UPDATE productos 
                    SET stock = stock - ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$item['cantidad'], $item['producto_id']]);
            }

            // 7. Vaciar carrito
            $stmt = $this->db->prepare("DELETE FROM carrito WHERE session_id = ?");
            $stmt->execute([$sessionId]);

            $this->db->commit();
            return $codigo_pedido;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en procesarCheckout: " . $e->getMessage());
            throw new Exception("Error al procesar pedido: " . $e->getMessage());
        }
    }
    
    private function obtenerTotalesCarrito($sessionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as count,
                    SUM(cantidad) as total_items,
                    SUM(cantidad * precio_unitario) as subtotal
                FROM carrito
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
            $totales = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'count' => (int)$totales['count'],
                'total_items' => (int)$totales['total_items'],
                'subtotal' => (float)$totales['subtotal'],
                'total' => (float)$totales['subtotal'] // Puedes agregar cálculos de envío, impuestos, etc.
            ];
            
        } catch (PDOException $e) {
            error_log("Error en obtenerTotalesCarrito: " . $e->getMessage());
            throw new Exception("Error al obtener totales del carrito");
        }
    }
    
    /**
     * Método adicional para obtener el conteo rápido del carrito
     */
    public function obtenerConteoCarrito($sessionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count, SUM(cantidad) as total_items
                FROM carrito 
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerConteoCarrito: " . $e->getMessage());
            return ['count' => 0, 'total_items' => 0];
        }
    }
}