<?php
/**
 * Controlador de Carrito - PetZone
 */

require_once __DIR__ . '/../model/CarritoModel.php';

class CarritoController {
    private $model;
    
    public function __construct() {
        $this->model = new CarritoModel();
    }
    
    public function index() {
        $this->get();
    }
    
    public function add() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $sessionId = $this->getCartSessionId();
            
            $productoId = (int)($data['producto_id'] ?? 0);
            $cantidad = (int)($data['cantidad'] ?? 1);
            
            if ($productoId <= 0 || $cantidad <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos'], 400);
                return;
            }
            
            $result = $this->model->agregarAlCarrito($sessionId, $productoId, $cantidad);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'cart' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - add Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $sessionId = $this->getCartSessionId();
            
            $productoId = (int)($data['producto_id'] ?? 0);
            $cantidad = (int)($data['cantidad'] ?? 1);
            
            if ($productoId <= 0 || $cantidad < 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos'], 400);
                return;
            }
            
            $result = $this->model->actualizarCarrito($sessionId, $productoId, $cantidad);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Carrito actualizado',
                'cart' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - update Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function remove() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $sessionId = $this->getCartSessionId();
            
            $productoId = (int)($data['producto_id'] ?? 0);
            
            if ($productoId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            $result = $this->model->eliminarDelCarrito($sessionId, $productoId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Producto eliminado',
                'cart' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - remove Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        try {
            $sessionId = $this->getCartSessionId();
            $carrito = $this->model->obtenerCarrito($sessionId);
            
            $this->jsonResponse([
                'success' => true,
                'items' => $carrito['items'],
                'totales' => $carrito['totales']
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - get Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function clear() {
        try {
            $sessionId = $this->getCartSessionId();
            $this->model->vaciarCarrito($sessionId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Carrito vaciado',
                'cart' => ['count' => 0, 'subtotal' => 0, 'total' => 0]
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - clear Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function checkout() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $sessionId = $this->getCartSessionId();
            
            $nombre = $this->sanitize($data['nombre'] ?? '');
            $email = $this->sanitize($data['email'] ?? '');
            $telefono = $this->sanitize($data['telefono'] ?? '');
            $direccion = $this->sanitize($data['direccion'] ?? '');
            $metodo_pago = $this->sanitize($data['metodo_pago'] ?? '');
            $notas = $this->sanitize($data['notas'] ?? '');
            
            if (empty($nombre) || empty($email) || empty($telefono) || empty($direccion) || empty($metodo_pago)) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            $result = $this->model->procesarCheckout($sessionId, [
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'metodo_pago' => $metodo_pago,
                'notas' => $notas
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'codigo_pedido' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - checkout Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

        function procesarCheckout($sessionId) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $nombre = sanitize($data['nombre'] ?? '');
        $email = sanitize($data['email'] ?? '');
        $telefono = sanitize($data['telefono'] ?? '');
        $direccion = sanitize($data['direccion'] ?? '');
        $metodo_pago = sanitize($data['metodo_pago'] ?? '');
        $notas = sanitize($data['notas'] ?? '');
        
        if (empty($nombre) || empty($email) || empty($telefono) || empty($direccion) || empty($metodo_pago)) {
            jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
        }
        
        $db = getDB();
        
        try {
            $db->beginTransaction();

            // 1. Obtener items del carrito
            $stmt = $db->prepare("
                SELECT c.*, p.nombre, p.stock 
                FROM carrito c 
                INNER JOIN productos p ON c.producto_id = p.id 
                WHERE c.session_id = ?
            ");
            $stmt->execute([$sessionId]);
            $carritoItems = $stmt->fetchAll();

            if (empty($carritoItems)) {
                jsonResponse(['success' => false, 'message' => 'Carrito vacío'], 400);
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

            // 4. Crear pedido (SIN session_id)
            $codigo_pedido = 'PZ-' . date('Ymd') . '-' . rand(1000, 9999);
            
            $stmt = $db->prepare("
                INSERT INTO pedidos 
                (codigo_pedido, nombre_cliente, email_cliente, telefono_cliente, direccion_envio, 
                subtotal, total, metodo_pago, notas, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            $stmt->execute([
                $codigo_pedido,
                $nombre,
                $email,
                $telefono,
                $direccion,
                $subtotal,
                $subtotal, // total = subtotal (sin descuentos/impuestos)
                $metodo_pago,
                $notas
            ]);
            
            $pedido_id = $db->lastInsertId();

            // 5. Crear items del detalle_pedidos
            $stmt = $db->prepare("
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
                $updateStmt = $db->prepare("
                    UPDATE productos 
                    SET stock = stock - ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$item['cantidad'], $item['producto_id']]);
            }

            // 7. Vaciar carrito
            $stmt = $db->prepare("DELETE FROM carrito WHERE session_id = ?");
            $stmt->execute([$sessionId]);

            $db->commit();

            jsonResponse([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'codigo_pedido' => $codigo_pedido
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Error al procesar pedido: ' . $e->getMessage()], 500);
        }
    }
    
    private function getCartSessionId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = uniqid('cart_', true);
        }
        
        return $_SESSION['cart_session_id'];
    }
    
    private function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}