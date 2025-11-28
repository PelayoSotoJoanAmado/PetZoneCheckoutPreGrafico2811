<?php
/**
 * Controlador de Reservas - PetZone
 */

require_once __DIR__ . '/../model/ReservaModel.php';

class ReservaController {
    private $model;
    
    public function __construct() {
        $this->model = new ReservaModel();
    }
    
    public function index() {
        $this->list();
    }
    
    public function crear() {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            // Validar datos requeridos
            $servicio_id = (int)($data['servicio_id'] ?? 0);
            $nombre = $this->sanitize($data['nombre'] ?? '');
            $email = $this->sanitize($data['email'] ?? '');
            $telefono = $this->sanitize($data['telefono'] ?? '');
            $nombre_mascota = $this->sanitize($data['nombre_mascota'] ?? '');
            $tipo_mascota = $this->sanitize($data['tipo_mascota'] ?? 'perro');
            $fecha_reserva = $this->sanitize($data['fecha_reserva'] ?? '');
            $hora_reserva = $this->sanitize($data['hora_reserva'] ?? '');
            $notas = $this->sanitize($data['notas'] ?? '');
            
            // Validaciones
            if ($servicio_id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Servicio no seleccionado'], 400);
                return;
            }
            
            if (empty($nombre) || empty($email) || empty($telefono) || empty($nombre_mascota)) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            if (empty($fecha_reserva) || empty($hora_reserva)) {
                $this->jsonResponse(['success' => false, 'message' => 'Fecha y hora requeridas'], 400);
                return;
            }
            
            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(['success' => false, 'message' => 'Email inválido'], 400);
                return;
            }
            
            // Validar que la fecha no sea en el pasado
            if (strtotime($fecha_reserva) < strtotime(date('Y-m-d'))) {
                $this->jsonResponse(['success' => false, 'message' => 'No se pueden hacer reservas en el pasado'], 400);
                return;
            }
            
            $result = $this->model->crearReserva([
                'servicio_id' => $servicio_id,
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'nombre_mascota' => $nombre_mascota,
                'tipo_mascota' => $tipo_mascota,
                'fecha_reserva' => $fecha_reserva,
                'hora_reserva' => $hora_reserva,
                'notas' => $notas
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Reserva creada exitosamente',
                'codigo_reserva' => $result['codigo_reserva'],
                'servicio' => $result['servicio_nombre'],
                'fecha' => $fecha_reserva,
                'hora' => $hora_reserva,
                'total' => $result['precio']
            ]);
            
        } catch (Exception $e) {
            error_log("ReservaController - crear Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function list() {
        try {
            $reservas = $this->model->listarReservas();
            $this->jsonResponse(['success' => true, 'reservas' => $reservas]);
        } catch (Exception $e) {
            error_log("ReservaController - list Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function verificarDisponibilidad() {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $servicio_id = (int)($data['servicio_id'] ?? 0);
            $fecha = $this->sanitize($data['fecha'] ?? '');
            $hora = $this->sanitize($data['hora'] ?? '');
            
            if ($servicio_id == 0 || empty($fecha) || empty($hora)) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            $disponibilidad = $this->model->verificarDisponibilidad($servicio_id, $fecha, $hora);
            
            $this->jsonResponse([
                'success' => true,
                'disponible' => $disponibilidad['disponible'],
                'reservas_actuales' => $disponibilidad['reservas_actuales'],
                'limite' => $disponibilidad['limite']
            ]);
            
        } catch (Exception $e) {
            error_log("ReservaController - verificarDisponibilidad Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
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